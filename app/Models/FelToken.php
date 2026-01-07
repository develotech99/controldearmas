<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

class FelToken extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'fel_tokens';

    protected $fillable = [
        'token', 'token_type', 'expires_in', 'issued_at', 'expires_at', 'is_active'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Mutador para issued_at - convierte formato d/m/Y H:i:s a formato Carbon
     */
    public function setIssuedAtAttribute($value)
    {
        if (is_string($value) && strpos($value, '/') !== false) {
            // Si viene en formato 14/11/2025 11:07:51, convertirlo
            $this->attributes['issued_at'] = Carbon::createFromFormat('d/m/Y H:i:s', $value);
        } else {
            $this->attributes['issued_at'] = $value;
        }
    }

    /**
     * Mutador para expires_at - convierte formato d/m/Y H:i:s a formato Carbon
     */
    public function setExpiresAtAttribute($value)
    {
        if (is_string($value) && strpos($value, '/') !== false) {
            // Si viene en formato 14/11/2025 11:07:51, convertirlo
            $this->attributes['expires_at'] = Carbon::createFromFormat('d/m/Y H:i:s', $value);
        } else {
            $this->attributes['expires_at'] = $value;
        }
    }
}