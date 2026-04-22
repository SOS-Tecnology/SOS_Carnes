<?php

namespace App\Controllers;

use App\Services\MessageFormatter;

class StickerController
{
    // Configuración de la empresa
    private const EMPRESA_NOMBRE = 'EL GOURMET';
    private const EMPRESA_DIRECCION = 'Calle 16A nº 16C - 32 Bogotá';
    private const EMPRESA_TELEFONOS = '(801) 070 497 - (801) 6729 231 - 320 302 9614';
    
    // Tamaño del sticker en mm
    private const STICKER_WIDTH = 100;  // 3.5 inches
    private const STICKER_HEIGHT = 142; // 5 inches

    /**
     * Genera HTML del sticker para impresión
     * Tamaño: 3.5 x 5 cm (100 x 142 mm)
     * Se imprime directamente desde el navegador
     */
    public function generar($request, $response): mixed
    {
        $queryParams = $request->getQueryParams();
        
        $codart      = trim($queryParams['codart'] ?? '');
        $descripcion = trim($queryParams['desc'] ?? '');
        $pesoStr     = trim($queryParams['peso'] ?? '0');
        $peso        = (float)$pesoStr;
        
        // Validar que el peso sea válido y mayor a 0
        $validacion = MessageFormatter::validateWeight($peso);
        if (!$validacion['valid']) {
            return $this->renderErrorPage($response, $validacion['message']);
        }
        
        $fechaHoy    = date('d/m/Y');
        $horaActual  = date('H:i');
        
        // Datos para el QR: código|descripción|peso
        $qrData = "{$codart}|{$descripcion}|{$pesoStr}";
        
        // URL para generar el QR usando API pública (qr-server.com)
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrData);
        
        // Escapar datos
        $codartEsc   = htmlspecialchars($codart, ENT_QUOTES, 'UTF-8');
        $descEsc     = htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8');
        $pesoEsc     = htmlspecialchars($pesoStr, ENT_QUOTES, 'UTF-8');
        $dirEsc      = htmlspecialchars(self::EMPRESA_DIRECCION, ENT_QUOTES, 'UTF-8');
        $telEsc      = htmlspecialchars(self::EMPRESA_TELEFONOS, ENT_QUOTES, 'UTF-8');
        $empresaEsc  = htmlspecialchars(self::EMPRESA_NOMBRE, ENT_QUOTES, 'UTF-8');
        
        // HTML del sticker con validación de tamaño en comentario
        $html = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sticker - $codartEsc</title>
    <!-- VALIDACIÓN DE TAMAÑO: 100mm x 142mm (3.5" x 5") -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            background: white;
            font-family: Arial, sans-serif;
        }
        
        @page {
            size: 100mm 142mm;
            margin: 0;
        }
        
        .sticker-container {
            width: 100mm;
            height: 142mm;
            padding: 3mm;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-after: always;
            break-after: page;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 3mm;
        }
        
        .empresa-nombre {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            letter-spacing: 0.5px;
        }
        
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            text-align: center;
        }
        
        .codigo {
            font-size: 12px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
            margin: 2mm 0;
            word-break: break-all;
        }
        
        .descripcion {
            font-size: 9px;
            text-align: center;
            margin: 2mm 0;
            font-weight: 600;
            line-height: 1.3;
            max-height: 15mm;
            overflow: hidden;
            word-wrap: break-word;
        }
        
        .peso-destacado {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 3mm 0;
            border: 2px solid #000;
            padding: 3mm 5mm;
            background: #f9f9f9;
            min-width: 40mm;
        }
        
        .qr-container {
            text-align: center;
            margin: 3mm 0;
        }
        
        .qr-container img {
            width: 32mm;
            height: 32mm;
            image-rendering: pixelated;
        }
        
        .fecha-hora {
            font-size: 8px;
            text-align: center;
            margin: 1mm 0;
            color: #333;
        }
        
        .footer {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 1mm;
        }
        
        .footer-empresa {
            font-size: 7px;
            font-weight: bold;
            color: #000;
            margin-bottom: 0.5mm;
        }
        
        .footer-direccion {
            font-size: 6px;
            color: #333;
            margin-bottom: 0.5mm;
            line-height: 1.2;
        }
        
        .footer-telefonos {
            font-size: 6px;
            color: #333;
            line-height: 1.2;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .sticker-container {
                width: 100mm;
                height: 142mm;
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="sticker-container">
        <!-- Encabezado con empresa -->
        <div class="header">
            <div class="empresa-nombre">$empresaEsc</div>
        </div>
        
        <!-- Contenido principal -->
        <div class="content">
            <!-- Código del producto -->
            <div class="codigo">$codartEsc</div>
            
            <!-- Descripción -->
            <div class="descripcion">$descEsc</div>
            
            <!-- Peso destacado -->
            <div class="peso-destacado">
                $pesoEsc kg
            </div>
            
            <!-- QR -->
            <div class="qr-container">
                <img src="$qrUrl" alt="QR Code" onerror="this.style.display='none'">
            </div>
            
            <!-- Fecha y Hora -->
            <div class="fecha-hora">
                $fechaHoy $horaActual
            </div>
        </div>
        
        <!-- Pie de página con información de la empresa -->
        <div class="footer">
            <div class="footer-empresa">$empresaEsc</div>
            <div class="footer-direccion">$dirEsc</div>
            <div class="footer-telefonos">$telEsc</div>
        </div>
    </div>
    
    <script>
        // Auto-imprimir al cargar
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
EOT;
        
        // Devolver HTML con headers apropiados
        $response->getBody()->write($html);
        
        return $response
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withHeader('Content-Disposition', 'inline; filename="sticker_' . $codart . '_' . date('YmdHis') . '.html"');
    }

    /**
     * Renderiza una página de error con formato amable
     */
    private function renderErrorPage($response, string $mensaje): mixed
    {
        $html = <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Validación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 8px;
            padding: 40px;
            max-width: 500px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .error-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .error-message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .btn-volver {
            display: inline-block;
            background: #1a4dad;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-volver:hover {
            background: #163fa0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <div class="error-title">No se puede generar el sticker</div>
        <div class="error-message">$mensaje</div>
        <button class="btn-volver" onclick="window.close()">Cerrar</button>
    </div>
</body>
</html>
EOT;
        
        $response->getBody()->write($html);
        
        return $response
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withStatus(400);
    }
}


