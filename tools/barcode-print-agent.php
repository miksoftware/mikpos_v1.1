<?php

/**
 * Agente local de impresión ZPL para impresoras USB (SAT TT448).
 *
 * Ejecutar: tools\iniciar-agente-etiquetas.bat
 */

if (PHP_SAPI === 'cli') {
    $host = '127.0.0.1';
    $port = 9311;

    echo PHP_EOL;
    echo " Agente de impresión MikPOS" . PHP_EOL;
    echo " URL: http://{$host}:{$port}" . PHP_EOL;
    echo " Impresora: " . (getenv('BARCODE_PRINTER_WINDOWS_NAME') ?: 'SAT TT448-2 USE (ZPL)') . PHP_EOL;
    echo " Deja esta ventana abierta mientras imprimes." . PHP_EOL;
    echo PHP_EOL;

    passthru('php -S ' . $host . ':' . $port . ' "' . __FILE__ . '"');
    exit;
}

$printerName = getenv('BARCODE_PRINTER_WINDOWS_NAME') ?: 'SAT TT448-2 USE (ZPL)';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method === 'GET') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Agente de etiquetas MikPOS activo.\nImpresora: {$printerName}\n";
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

function sendRawFileToWindowsPrinter(string $printerName, string $filePath): array
{
    $scriptPath = tempnam(sys_get_temp_dir(), 'mikpos_ps_');

    if ($scriptPath === false) {
        return [
            'success' => false,
            'message' => 'No se pudo crear script temporal para imprimir.',
        ];
    }

    $ps1Path = $scriptPath . '.ps1';
    @rename($scriptPath, $ps1Path);

    $script = <<<'POWERSHELL'
param(
    [Parameter(Mandatory = $true)]
    [string]$PrinterName,

    [Parameter(Mandatory = $true)]
    [string]$FilePath
)

Add-Type -TypeDefinition @"
using System;
using System.ComponentModel;
using System.IO;
using System.Runtime.InteropServices;

public static class RawPrinterHelper
{
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOCINFO
    {
        [MarshalAs(UnmanagedType.LPWStr)]
        public string pDocName;

        [MarshalAs(UnmanagedType.LPWStr)]
        public string pOutputFile;

        [MarshalAs(UnmanagedType.LPWStr)]
        public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    public static extern bool OpenPrinter(string pPrinterName, out IntPtr phPrinter, IntPtr pDefault);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In] DOCINFO di);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError = true)]
    public static extern bool WritePrinter(IntPtr hPrinter, byte[] pBytes, int dwCount, out int dwWritten);

    public static void SendFile(string printerName, string filePath)
    {
        IntPtr printerHandle;

        if (!OpenPrinter(printerName, out printerHandle, IntPtr.Zero))
        {
            throw new Win32Exception(Marshal.GetLastWin32Error(), "No se pudo abrir la impresora.");
        }

        try
        {
            var docInfo = new DOCINFO
            {
                pDocName = Path.GetFileName(filePath),
                pDataType = "RAW"
            };

            if (!StartDocPrinter(printerHandle, 1, docInfo))
            {
                throw new Win32Exception(Marshal.GetLastWin32Error(), "No se pudo iniciar el documento RAW.");
            }

            try
            {
                if (!StartPagePrinter(printerHandle))
                {
                    throw new Win32Exception(Marshal.GetLastWin32Error(), "No se pudo iniciar la pagina RAW.");
                }

                try
                {
                    var bytes = File.ReadAllBytes(filePath);
                    int written;

                    if (!WritePrinter(printerHandle, bytes, bytes.Length, out written) || written != bytes.Length)
                    {
                        throw new Win32Exception(Marshal.GetLastWin32Error(), "No se pudieron enviar todos los bytes a la impresora.");
                    }
                }
                finally
                {
                    EndPagePrinter(printerHandle);
                }
            }
            finally
            {
                EndDocPrinter(printerHandle);
            }
        }
        finally
        {
            ClosePrinter(printerHandle);
        }
    }
}
"@

[RawPrinterHelper]::SendFile($PrinterName, $FilePath)
Write-Output "RAW_OK"
POWERSHELL;

    file_put_contents($ps1Path, $script);

    $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
        . escapeshellarg($ps1Path)
        . ' -PrinterName '
        . escapeshellarg($printerName)
        . ' -FilePath '
        . escapeshellarg($filePath);

    exec($command, $output, $exitCode);

    @unlink($ps1Path);

    if ($exitCode === 0 && in_array('RAW_OK', $output, true)) {
        return [
            'success' => true,
            'message' => "Enviado a {$printerName}",
        ];
    }

    return [
        'success' => false,
        'message' => 'Fallo de spooler RAW: ' . trim(implode(' ', $output)),
    ];
}

function sendViaWindowsShare(string $printerName, string $filePath, string $rawFailureMessage): array
{
    $target = '\\\\localhost\\' . $printerName;
    $command = 'copy /B "' . $filePath . '" "' . $target . '"';
    exec($command, $output, $exitCode);

    if ($exitCode === 0) {
        return [
            'success' => true,
            'message' => "Enviado a {$printerName}",
        ];
    }

    $shareMessage = trim(implode(' ', $output));

    return [
        'success' => false,
        'message' => 'No se pudo imprimir. '
            . trim($rawFailureMessage)
            . ($shareMessage !== '' ? ' | Fallback share: ' . $shareMessage : ''),
    ];
}

$zpl = file_get_contents('php://input');

if ($zpl === false || trim($zpl) === '') {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ZPL vacío']);
    exit;
}

$tmp = tempnam(sys_get_temp_dir(), 'mikpos_zpl_');

if ($tmp === false) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se pudo crear archivo temporal']);
    exit;
}

file_put_contents($tmp, $zpl);
$result = sendRawFileToWindowsPrinter($printerName, $tmp);

if (! $result['success']) {
    $result = sendViaWindowsShare($printerName, $tmp, $result['message']);
}

@unlink($tmp);

header('Content-Type: application/json');

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => $result['message'],
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => $result['message'],
]);
