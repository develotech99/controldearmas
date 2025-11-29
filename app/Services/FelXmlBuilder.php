<?php

namespace App\Services;

use App\Models\Facturacion;
use Carbon\Carbon;
use DOMDocument;
use DateTime;

class FelXmlBuilder
{
    /**
     * Genera el XML del DTE según el esquema de la SAT
     *
     * @param array $datos Datos de la factura
     * @return string XML generado
     */
    public function generarXmlFactura(array $datos): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Elemento raíz GTDocumento
        $root = $xml->createElementNS('http://www.sat.gob.gt/dte/fel/0.2.0', 'dte:GTDocumento');
        $root->setAttribute('xmlns:dte', 'http://www.sat.gob.gt/dte/fel/0.2.0');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('Version', '0.1');
        $xml->appendChild($root);

        // SAT
        $sat = $xml->createElement('dte:SAT');
        $sat->setAttribute('ClaseDocumento', 'dte');
        $root->appendChild($sat);

        // DTE
        $dte = $xml->createElement('dte:DTE');
        $dte->setAttribute('ID', 'DatosCertificados');
        $sat->appendChild($dte);

        // DatosEmision
        $datosEmision = $xml->createElement('dte:DatosEmision');
        $datosEmision->setAttribute('ID', 'DatosEmision');
        $dte->appendChild($datosEmision);

        // Agregar secciones
         $datos['tipo'] = 'FACT';
        $this->agregarDatosGenerales($xml, $datosEmision, $datos);
        $this->agregarEmisor($xml, $datosEmision);
        $this->agregarReceptor($xml, $datosEmision, $datos);
        $this->agregarFrases($xml, $datosEmision);
        $this->agregarItems($xml, $datosEmision, $datos['items']);
        $this->agregarTotales($xml, $datosEmision, $datos['totales']);

        return $xml->saveXML();
    }


    protected function agregarDatosGenerales($xml, $parent, $datos)
{
    $dg = $xml->createElement('dte:DatosGenerales');

    // 👇 Toma el tipo desde $datos si viene, si no usa FACT por defecto
    $tipo = $datos['tipo'] ?? $datos['tipo_documento'] ?? 'FACT';
    $dg->setAttribute('Tipo', $tipo);

    $dg->setAttribute(
        'FechaHoraEmision',
        (new \DateTime())->format('Y-m-d\TH:i:s')
    );
    $dg->setAttribute('CodigoMoneda', 'GTQ');

    $parent->appendChild($dg);
}

public function generarXmlFacturaCambiaria(array $datos): string
{
    $emisor = config('fel.emisor');

    $xml = new \DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;

    // ================= ROOT =================
    $root = $xml->createElement('dte:GTDocumento');
    $root->setAttribute('xmlns:dte', 'http://www.sat.gob.gt/dte/fel/0.2.0');
    $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $root->setAttribute('xmlns:cfc', 'http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0');
    $root->setAttribute('Version', '0.1');
    $xml->appendChild($root);

    // ================ SAT ==================
    $sat = $xml->createElement('dte:SAT');
    $sat->setAttribute('ClaseDocumento', 'dte');
    $root->appendChild($sat);

    // ================ DTE ==================
    $dte = $xml->createElement('dte:DTE');
    $dte->setAttribute('ID', 'DatosCertificados');
    $sat->appendChild($dte);

    // ================ DatosEmision ==================
    $datosEmision = $xml->createElement('dte:DatosEmision');
    $datosEmision->setAttribute('ID', 'DatosEmision');
    $dte->appendChild($datosEmision);

    // ---- Datos Generales ----
    $dg = $xml->createElement('dte:DatosGenerales');
    $dg->setAttribute('Tipo', 'FCAM');
    $dg->setAttribute('FechaHoraEmision', now()->format('Y-m-d\TH:i:s'));
    $dg->setAttribute('CodigoMoneda', 'GTQ');
    $datosEmision->appendChild($dg);

    // ---- Emisor ----
    $emisorNode = $xml->createElement('dte:Emisor');
    $emisorNode->setAttribute('NITEmisor', $emisor['nit']);
    $emisorNode->setAttribute('NombreEmisor', $emisor['nombre']);
    $emisorNode->setAttribute('CodigoEstablecimiento', '1');
    $emisorNode->setAttribute('NombreComercial', $emisor['nombre_comercial']);
    $emisorNode->setAttribute('AfiliacionIVA', $emisor['afiliacion_iva']);

    $dirEm = $xml->createElement('dte:DireccionEmisor');
    $dirEm->appendChild($xml->createElement('dte:Direccion', $emisor['direccion']));
    $dirEm->appendChild($xml->createElement('dte:CodigoPostal', $emisor['codigo_postal']));
    $dirEm->appendChild($xml->createElement('dte:Municipio', $emisor['municipio']));
    $dirEm->appendChild($xml->createElement('dte:Departamento', $emisor['departamento']));
    $dirEm->appendChild($xml->createElement('dte:Pais', 'GT'));

    $emisorNode->appendChild($dirEm);
    $datosEmision->appendChild($emisorNode);

    // ================= RECEPTOR =================
    $rec = $datos['receptor'];

    // IDReceptor (validado)
    $cui = isset($rec['cui']) ? trim($rec['cui']) : '';
    $nit = isset($rec['nit']) ? trim($rec['nit']) : '';

    if ($cui !== '') {
        $idReceptor = $cui;
    } elseif ($nit !== '') {
        $idReceptor = $nit;
    } else {
        $idReceptor = 'CF';
    }

    // Dirección obligatoria
    $direc = isset($rec['direccion']) ? trim($rec['direccion']) : '';
    if ($direc === '') {
        $direc = 'Ciudad de Guatemala';
    }

    $receptorNode = $xml->createElement('dte:Receptor');
    $receptorNode->setAttribute('IDReceptor', $idReceptor);
    $receptorNode->setAttribute('NombreReceptor', $rec['nombre']);

    $dirRec = $xml->createElement('dte:DireccionReceptor');
    $dirRec->appendChild($xml->createElement('dte:Direccion', $direc));
    $dirRec->appendChild($xml->createElement('dte:CodigoPostal', '01001'));
    $dirRec->appendChild($xml->createElement('dte:Municipio', 'Guatemala'));
    $dirRec->appendChild($xml->createElement('dte:Departamento', 'Guatemala'));
    $dirRec->appendChild($xml->createElement('dte:Pais', 'GT'));

    $receptorNode->appendChild($dirRec);
    $datosEmision->appendChild($receptorNode);

    // ================ FRASES =================
    $frases = $xml->createElement('dte:Frases');
    $f = $xml->createElement('dte:Frase');
    $f->setAttribute('TipoFrase', '1');
    $f->setAttribute('CodigoEscenario', '1');
    $frases->appendChild($f);
    $datosEmision->appendChild($frases);

    // ================= ITEMS ==================
    $itemsNode = $xml->createElement('dte:Items');
    $datosEmision->appendChild($itemsNode);

    $linea = 1;
    foreach ($datos['items'] as $item) {
        $node = $xml->createElement('dte:Item');
        $node->setAttribute('NumeroLinea', $linea++);
        $node->setAttribute('BienOServicio', 'B');

        $cantidad = (float)$item['cantidad'];
        $precio = (float)$item['precio_unitario'];
        $descuento = (float)$item['descuento'];
        $montoGravable = (float)$item['monto_gravable'];
        $iva = (float)$item['iva'];
        $total = (float)$item['total'];

        $node->appendChild($xml->createElement('dte:Cantidad', number_format($cantidad, 2, '.', '')));
        $node->appendChild($xml->createElement('dte:UnidadMedida', 'UNI'));
        $node->appendChild($xml->createElement('dte:Descripcion', $item['descripcion']));
        $node->appendChild($xml->createElement('dte:PrecioUnitario', number_format($precio, 2, '.', '')));
        $node->appendChild($xml->createElement('dte:Precio', number_format($cantidad * $precio, 2, '.', '')));
        $node->appendChild($xml->createElement('dte:Descuento', number_format($descuento, 2, '.', '')));

        $impNode = $xml->createElement('dte:Impuestos');
        $imp = $xml->createElement('dte:Impuesto');
        $imp->appendChild($xml->createElement('dte:NombreCorto', 'IVA'));
        $imp->appendChild($xml->createElement('dte:CodigoUnidadGravable', '1'));
        $imp->appendChild($xml->createElement('dte:MontoGravable', number_format($montoGravable, 2, '.', '')));
        $imp->appendChild($xml->createElement('dte:MontoImpuesto', number_format($iva, 2, '.', '')));
        $impNode->appendChild($imp);
        $node->appendChild($impNode);

        $node->appendChild($xml->createElement('dte:Total', number_format($total, 2, '.', '')));

        $itemsNode->appendChild($node);
    }

    // ================= TOTALES =================
    $tot = $xml->createElement('dte:Totales');
    $ti = $xml->createElement('dte:TotalImpuestos');
    $ivaTot = $xml->createElement('dte:TotalImpuesto');
    $ivaTot->setAttribute('NombreCorto', 'IVA');
    $ivaTot->setAttribute('TotalMontoImpuesto', number_format($datos['totales']['iva'], 2, '.', ''));
    $ti->appendChild($ivaTot);
    $tot->appendChild($ti);

    $tot->appendChild(
        $xml->createElement('dte:GranTotal', number_format($datos['totales']['total'], 2, '.', ''))
    );

    $datosEmision->appendChild($tot);

    // ============ COMPLEMENTO CAMBIARIO ============
    $comps = $xml->createElement('dte:Complementos');
    $comp = $xml->createElement('dte:Complemento');
    $comp->setAttribute('IDComplemento', 'AbonosFacturaCambiaria1');
    $comp->setAttribute('NombreComplemento', 'AbonosFacturaCambiaria');
    $comp->setAttribute('URIComplemento', 'http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0');

    $rootCfc = $xml->createElement('cfc:AbonosFacturaCambiaria');
    $rootCfc->setAttribute('Version', '1');

    foreach ($datos['abonos'] as $ab) {
        $n = $xml->createElement('cfc:Abono');
        $n->appendChild($xml->createElement('cfc:NumeroAbono', (int)$ab['numero']));
        $n->appendChild($xml->createElement('cfc:FechaVencimiento', $ab['fecha']));
        $n->appendChild($xml->createElement('cfc:MontoAbono', number_format($ab['monto'], 2, '.', '')));
        $rootCfc->appendChild($n);
    }

    $comp->appendChild($rootCfc);
    $comps->appendChild($comp);
    $datosEmision->appendChild($comps);

    return $xml->saveXML();
}



protected function agregarDatosGeneralesCambiaria($xml, $parent, $datos)
{
    $dg = $xml->createElement('dte:DatosGenerales');
    $dg->setAttribute('Tipo', 'FCAM');
    $dg->setAttribute('FechaHoraEmision', (new DateTime())->format('Y-m-d\TH:i:s'));
    $dg->setAttribute('CodigoMoneda', 'GTQ');
    $parent->appendChild($dg);
}




    protected function agregarEmisor($xml, $parent)
    {
        $nit = str_replace('-', '', config('fel.emisor.nit'));

        $emisor = $xml->createElement('dte:Emisor');
        $emisor->setAttribute('NITEmisor', $nit);
        $emisor->setAttribute('NombreEmisor', config('fel.emisor.nombre'));
        $emisor->setAttribute('CodigoEstablecimiento', '1');
        $emisor->setAttribute('NombreComercial', config('fel.emisor.nombre_comercial'));
        $emisor->setAttribute('AfiliacionIVA', config('fel.emisor.afiliacion_iva'));

        // DireccionEmisor
        $direccion = $xml->createElement('dte:DireccionEmisor');
        $direccion->appendChild($xml->createElement('dte:Direccion', config('fel.emisor.direccion')));
        $direccion->appendChild($xml->createElement('dte:CodigoPostal', config('fel.emisor.codigo_postal')));
        $direccion->appendChild($xml->createElement('dte:Municipio', config('fel.emisor.municipio')));
        $direccion->appendChild($xml->createElement('dte:Departamento', config('fel.emisor.departamento')));
        $direccion->appendChild($xml->createElement('dte:Pais', config('fel.emisor.pais')));
        $emisor->appendChild($direccion);

        $parent->appendChild($emisor);
    }



private function agregarReceptor(\DOMDocument $xml, \DOMElement $parent, array $datos)
{
    $rec = $datos['receptor'] ?? [];

    $nit       = trim($rec['nit'] ?? '');
    $nombre    = trim($rec['nombre'] ?? '');
    $direccion = trim($rec['direccion'] ?? '');

    // IDReceptor nunca vacío
    $idReceptor = $nit !== '' ? $nit : 'CF';

    if ($nombre === '') {
        $nombre = 'CONSUMIDOR FINAL';
    }

    if ($direccion === '') {
        $direccion = 'Ciudad de Guatemala';
    }

    $receptor = $xml->createElement('dte:Receptor');
    $receptor->setAttribute('IDReceptor', $idReceptor);
    $receptor->setAttribute('NombreReceptor', $nombre);

    $dirRec = $xml->createElement('dte:DireccionReceptor');
    $dirRec->appendChild($xml->createElement('dte:Direccion', $direccion));
    $dirRec->appendChild($xml->createElement('dte:CodigoPostal', '01001'));
    $dirRec->appendChild($xml->createElement('dte:Municipio', 'Guatemala'));
    $dirRec->appendChild($xml->createElement('dte:Departamento', 'Guatemala'));
    $dirRec->appendChild($xml->createElement('dte:Pais', 'GT'));

    $receptor->appendChild($dirRec);
    $parent->appendChild($receptor);
}



    protected function agregarFrases($xml, $parent)
    {
        $frases = $xml->createElement('dte:Frases');

        $frase = $xml->createElement('dte:Frase');
        $frase->setAttribute('TipoFrase', '1'); // 1 = Sujeto a pagos trimestrales IVA
        $frase->setAttribute('CodigoEscenario', '1');
        $frases->appendChild($frase);

        $parent->appendChild($frases);
    }

    protected function agregarItems($xml, $parent, $items)
    {
        $itemsElement = $xml->createElement('dte:Items');

        foreach ($items as $index => $item) {
            $itemElement = $xml->createElement('dte:Item');
            $itemElement->setAttribute('NumeroLinea', $index + 1);
            $itemElement->setAttribute('BienOServicio', 'B'); // B = Bien, S = Servicio

            $cantidad = (float) $item['cantidad'];
            $precioUnitario = (float) $item['precio_unitario'];
            $descuento = (float) ($item['descuento'] ?? 0);

            // Precio total del item
            $precioTotal = $cantidad * $precioUnitario;
            $totalConDescuento = $precioTotal - $descuento;

            // Calcular base gravable (sin IVA) y el IVA
            $montoGravable = $totalConDescuento / 1.12;
            $montoIva = $totalConDescuento - $montoGravable;

            $itemElement->appendChild($xml->createElement('dte:Cantidad', number_format($cantidad, 2, '.', '')));
            $itemElement->appendChild($xml->createElement('dte:UnidadMedida', 'UNI'));
            $itemElement->appendChild($xml->createElement('dte:Descripcion', htmlspecialchars($item['descripcion'], ENT_XML1)));
            $itemElement->appendChild($xml->createElement('dte:PrecioUnitario', number_format($precioUnitario, 2, '.', '')));
            $itemElement->appendChild($xml->createElement('dte:Precio', number_format($precioTotal, 2, '.', '')));
            $itemElement->appendChild($xml->createElement('dte:Descuento', number_format($descuento, 2, '.', '')));

            // Impuestos
            $impuestos = $xml->createElement('dte:Impuestos');
            $impuesto = $xml->createElement('dte:Impuesto');
            $impuesto->appendChild($xml->createElement('dte:NombreCorto', 'IVA'));
            $impuesto->appendChild($xml->createElement('dte:CodigoUnidadGravable', '1'));
            $impuesto->appendChild($xml->createElement('dte:MontoGravable', number_format($montoGravable, 2, '.', '')));
            $impuesto->appendChild($xml->createElement('dte:MontoImpuesto', number_format($montoIva, 2, '.', '')));
            $impuestos->appendChild($impuesto);
            $itemElement->appendChild($impuestos);

            $itemElement->appendChild($xml->createElement('dte:Total', number_format($totalConDescuento, 2, '.', '')));

            $itemsElement->appendChild($itemElement);
        }

        $parent->appendChild($itemsElement);
    }

    protected function agregarTotales($xml, $parent, $totales)
    {
        $totalesElement = $xml->createElement('dte:Totales');

        // TotalImpuestos
        $totalImpuestos = $xml->createElement('dte:TotalImpuestos');
        $totalImpuesto = $xml->createElement('dte:TotalImpuesto');
        $totalImpuesto->setAttribute('NombreCorto', 'IVA');
        $totalImpuesto->setAttribute('TotalMontoImpuesto', number_format($totales['iva'], 2, '.', ''));
        $totalImpuestos->appendChild($totalImpuesto);
        $totalesElement->appendChild($totalImpuestos);

        $totalesElement->appendChild($xml->createElement('dte:GranTotal', number_format($totales['total'], 2, '.', '')));

        $parent->appendChild($totalesElement);
    }

    protected function agregarComplementoCambiaria(DOMDocument $xml, $parent, array $abonos): void
{
    if (empty($abonos)) {
        return;
    }

    $nsCfc = 'http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0';

    // <dte:Complementos>
    $complementos = $xml->createElement('dte:Complementos');

    // <dte:Complemento ...>
    $complemento = $xml->createElement('dte:Complemento');
    $complemento->setAttribute('IDComplemento', 'CompCambiaria1');
    $complemento->setAttribute('NombreComplemento', 'AbonosFacturaCambiaria');
    $complemento->setAttribute('URIComplemento', $nsCfc);

    // <cfc:AbonosFacturaCambiaria Version="1">
    $abonosEl = $xml->createElementNS($nsCfc, 'cfc:AbonosFacturaCambiaria');
    $abonosEl->setAttribute('Version', '1');

    foreach ($abonos as $index => $abono) {
        // Se espera algo como: ['numero' => 1, 'fecha' => '2025-03-15', 'monto' => 1500.00]
        $numero = isset($abono['numero']) ? (int) $abono['numero'] : ($index + 1);
        $fecha  = $abono['fecha'] ?? date('Y-m-d');
        $monto  = isset($abono['monto']) ? (float) $abono['monto'] : 0.0;

        $abonoEl = $xml->createElementNS($nsCfc, 'cfc:Abono');

        $numEl   = $xml->createElementNS($nsCfc, 'cfc:NumeroAbono', (string) $numero);
        $fechaEl = $xml->createElementNS($nsCfc, 'cfc:FechaVencimiento', $fecha);
        $montoEl = $xml->createElementNS(
            $nsCfc,
            'cfc:MontoAbono',
            number_format($monto, 2, '.', '')
        );

        $abonoEl->appendChild($numEl);
        $abonoEl->appendChild($fechaEl);
        $abonoEl->appendChild($montoEl);

        $abonosEl->appendChild($abonoEl);
    }

    // Estructura: DatosEmision > Complementos > Complemento > AbonosFacturaCambiaria
    $complemento->appendChild($abonosEl);
    $complementos->appendChild($complemento);
    $parent->appendChild($complementos);
}

    public function generarXmlAnulacion(Facturacion $factura): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Elemento raíz GTAnulacionDocumento
        $root = $xml->createElementNS('http://www.sat.gob.gt/dte/fel/0.1.0', 'anu:GTAnulacionDocumento');
        $root->setAttribute('xmlns:anu', 'http://www.sat.gob.gt/dte/fel/0.1.0');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('Version', '0.1');
        $xml->appendChild($root);

        $sat = $xml->createElement('anu:SAT');
        $root->appendChild($sat);

        // AnulacionDTE
        $anulacionDte = $xml->createElement('anu:AnulacionDTE');
        $anulacionDte->setAttribute('ID', 'DatosCertificados');
        $sat->appendChild($anulacionDte);

        // DatosGenerales
        $datosGenerales = $xml->createElement('anu:DatosGenerales');
        $datosGenerales->setAttribute('ID', 'DatosAnulacion');
        $datosGenerales->setAttribute('NumeroDocumentoAAnular', $factura->fac_uuid);
        $datosGenerales->setAttribute('NITEmisor', str_replace('-', '', config('fel.emisor.nit')));
        $datosGenerales->setAttribute('IDReceptor', strtoupper(str_replace('-', '', $factura->fac_nit_receptor)));

        $fechaEmision = Carbon::parse($factura->fac_fecha_emision)->format('Y-m-d\TH:i:s.000-06:00');
        $fechaAnulacion = now()->format('Y-m-d\TH:i:s.000-06:00');

        $datosGenerales->setAttribute('FechaEmisionDocumentoAnular', $fechaEmision);
        $datosGenerales->setAttribute('FechaHoraAnulacion', $fechaAnulacion);
        $datosGenerales->setAttribute('MotivoAnulacion', 'Solicitud del emisor');

        $anulacionDte->appendChild($datosGenerales);

        return $xml->saveXML();
    }
}
