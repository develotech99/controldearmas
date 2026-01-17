<?php

namespace App\Services;

use App\Models\FelToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FelService

{

    
    protected bool $verifySsl;

    public function __construct()
    {
        $this->verifySsl = (bool) config('fel.verify_ssl', false);
    }

    /**
     * Obtiene (o refresca) el token del proveedor FEL y lo persiste.
     */
    public function getToken(): string
    {
        $now = Carbon::now();

        // Reutiliza token válido si existe
        $tokenRow = FelToken::where('is_active', true)
            ->where('expires_at', '>', $now->copy()->addSeconds(30))
            ->orderByDesc('id')
            ->first();

        if ($tokenRow) {
            return $tokenRow->token;
        }

        // Invalida tokens activos anteriores (por si acaso)
        FelToken::where('is_active', true)->update(['is_active' => false]);

        $url      = (string) config('fel.urls.get_token');
        $username = (string) config('fel.username');
        $password = (string) config('fel.password');

        if (!$url || !$username || !$password) {
            throw new \RuntimeException('FEL: faltan credenciales o URL de token en config/fel.php');
        }

        $resp = $this->baseRequest()
            ->asForm()
            ->acceptJson()
            ->post($url, [
                'username'   => $username,
                'password'   => $password,
                'grant_type' => 'password',
            ]);

        if ($resp->failed()) {
            throw new \RuntimeException('Error al obtener token FEL: ' . $resp->body());
        }

        $json        = $resp->json();
        $accessToken = $json['access_token'] ?? null;
        $tokenType   = $json['token_type']   ?? 'Bearer';
        $expiresIn   = (int)($json['expires_in'] ?? 3600);

        if (!$accessToken) {
            throw new \RuntimeException('Respuesta FEL sin access_token');
        }

        // Normaliza el tipo (Laravel por default usa 'Bearer' con mayúscula inicial)
        $tokenType = ucfirst(strtolower($tokenType));

        $issuedAt  = Carbon::now();
        $expiresAt = $issuedAt->copy()->addSeconds(max(60, $expiresIn - 120));

        FelToken::create([
            'token'      => $accessToken,
            'token_type' => $tokenType,
            'expires_in' => $expiresIn,
            'issued_at'  => $issuedAt,
            'expires_at' => $expiresAt,
            'is_active'  => true,
        ]);

        return $accessToken;
    }

    /**
     * Consulta de NIT (SAT/Proveedor FEL).
     * Hace fallback JSON -> FORM (415) y reintento al renovar token (401/403).
     */
    public function consultarNit(string $nit): array
    {
        $url = (string) config('fel.urls.consultar_nit');

        if (!$url) {
            throw new \RuntimeException('FEL: falta URL consultar_nit en config/fel.php');
        }

        $token     = $this->getToken();
        $tokenType = $this->currentTokenType();

        $payload = [
            'nit' => strtoupper(str_replace('-', '', $nit)),
            // Si tu proveedor exige además el NIT del emisor, descomenta:
            // 'nitEmisor' => preg_replace('/-/', '', (string) config('fel.emisor.nit')),
        ];

        // 1) Intento en JSON
        $resp = $this->baseRequest()
            ->withToken($token, $tokenType)
            ->acceptJson()
            ->post($url, $payload);

        $lastFormat = 'json';

        // 415 -> servidor exige x-www-form-urlencoded
        if ($resp->status() === 415) {
            $resp = $this->baseRequest()
                ->withToken($token, $tokenType)
                ->acceptJson()
                ->asForm()
                ->post($url, $payload);

            $lastFormat = 'form';
        }

        // 401/403 -> token inválido/expirado: refresca e intenta de nuevo en el mismo formato
        if ($resp->status() === 401 || $resp->status() === 403) {
            FelToken::where('is_active', true)->update(['is_active' => false]);
            $token     = $this->getToken();
            $tokenType = $this->currentTokenType();

            $req = $this->baseRequest()->withToken($token, $tokenType)->acceptJson();
            if ($lastFormat === 'form') {
                $req = $req->asForm();
            }

            $resp = $req->post($url, $payload);
        }

        if ($resp->failed()) {
            throw new \RuntimeException('Fallo consulta NIT: HTTP ' . $resp->status() . ' ' . $resp->body());
        }

        $json = $resp->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Crea la base de request con opciones comunes.
     */
    protected function baseRequest(): PendingRequest
    {
        return Http::withOptions(['verify' => $this->verifySsl])
            ->timeout(20);
    }

    /**
     * Lee el tipo de token actual desde la última fila activa; fallback a 'Bearer'.
     */
    protected function currentTokenType(): string
    {
        $row = FelToken::where('is_active', true)->orderByDesc('id')->first();
        return $row ? ucfirst(strtolower($row->token_type ?: 'Bearer')) : 'Bearer';
    }


    /**
     * Certifica un DTE.
     * @param string $xmlDteBase64 XML del DTE en Base64.
     * @param string $referencia   Debe ser ÚNICA (p.ej. FACT-<tu-ref>).
     * @return array Respuesta JSON del certificador.
     */
 public function certificarDte(string $xmlDteBase64, string $referencia): array
{
    // 1) URL desde config
    $url = (string) config('fel.urls.certificar_dte');
    if (!$url) {
        throw new \RuntimeException('FEL: falta URL certificar_dte en config/fel.php');
    }

    // 2) Obtener token
    $token     = $this->getToken();
    $tokenType = $this->currentTokenType();

    $payload = [
        'xmlDte'     => $xmlDteBase64,
        'Referencia' => $referencia,
    ];



    // 3) Enviar petición
    $resp = $this->baseRequest()
        ->withToken($token, $tokenType)
        ->acceptJson()
        ->post($url, $payload);

    // 4) Si expira token → renovar
    if ($resp->status() === 401 || $resp->status() === 403) {
        FelToken::where('is_active', true)->update(['is_active' => false]);

        $token     = $this->getToken();
        $tokenType = $this->currentTokenType();

        $resp = $this->baseRequest()
            ->withToken($token, $tokenType)
            ->acceptJson()
            ->post($url, $payload);
    }



    // 5) Errores HTTP
    if ($resp->failed()) {
        throw new \RuntimeException(
            'Fallo certificar DTE: HTTP ' . $resp->status() . ' ' . $resp->body()
        );
    }

    return $resp->json() ?? [];
}


    /**
     * Anula un DTE enviando el XML de anulación (Base64).
     */
    public function anularDte(string $xmlAnulacionBase64): array
    {
        $url = (string) config('fel.urls.anular_dte');
        if (!$url) {
            throw new \RuntimeException('FEL: falta URL anular_dte en config/fel.php');
        }

        $token     = $this->getToken();
        $tokenType = $this->currentTokenType();

        $payload = ['xmlDte' => $xmlAnulacionBase64];

        $resp = $this->baseRequest()
            ->withToken($token, $tokenType)
            ->acceptJson()
            ->post($url, $payload);

        if ($resp->status() === 401 || $resp->status() === 403) {
            FelToken::where('is_active', true)->update(['is_active' => false]);
            $token     = $this->getToken();
            $tokenType = $this->currentTokenType();

            $resp = $this->baseRequest()
                ->withToken($token, $tokenType)
                ->acceptJson()
                ->post($url, $payload);
        }

        if ($resp->failed()) {
            throw new \RuntimeException('Fallo anular DTE: HTTP ' . $resp->status() . ' ' . $resp->body());
        }

        return $resp->json() ?? [];
    }

    /**
     * Consulta un DTE por UUID.
     */
    public function consultarDte(string $uuid): array
    {
        $url = (string) config('fel.urls.consultar_dte');
        if (!$url) {
            throw new \RuntimeException('FEL: falta URL consultar_dte en config/fel.php');
        }

        $token     = $this->getToken();
        $tokenType = $this->currentTokenType();

        $payload = ['UUID' => $uuid];

        $resp = $this->baseRequest()
            ->withToken($token, $tokenType)
            ->acceptJson()
            ->post($url, $payload);

        if ($resp->status() === 401 || $resp->status() === 403) {
            FelToken::where('is_active', true)->update(['is_active' => false]);
            $token     = $this->getToken();
            $tokenType = $this->currentTokenType();

            $resp = $this->baseRequest()
                ->withToken($token, $tokenType)
                ->acceptJson()
                ->post($url, $payload);
        }

        if ($resp->failed()) {
            throw new \RuntimeException('Fallo consultar DTE: HTTP ' . $resp->status() . ' ' . $resp->body());
        }

        return $resp->json() ?? [];
    }

    /**
     * Consulta CUI (opcional: por si lo usas en otras pantallas).
     */
    public function consultarCui(string $cui): array
    {
        $url = (string) config('fel.urls.consultar_cui');
        if (!$url) {
            throw new \RuntimeException('FEL: falta URL consultar_cui en config/fel.php');
        }

        $token     = $this->getToken();
        $tokenType = $this->currentTokenType();

        $payload = ['Cui' => $cui];

        $resp = $this->baseRequest()
            ->withToken($token, $tokenType)
            ->acceptJson()
            ->post($url, $payload);

        if ($resp->status() === 401 || $resp->status() === 403) {
            FelToken::where('is_active', true)->update(['is_active' => false]);
            $token     = $this->getToken();
            $tokenType = $this->currentTokenType();

            $resp = $this->baseRequest()
                ->withToken($token, $tokenType)
                ->acceptJson()
                ->post($url, $payload);
        }

        if ($resp->failed()) {
            throw new \RuntimeException('Fallo consultar CUI: HTTP ' . $resp->status() . ' ' . $resp->body());
        }

        return $resp->json() ?? [];
    }
}
