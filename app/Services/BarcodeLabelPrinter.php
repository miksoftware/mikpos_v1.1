<?php

namespace App\Services;

class BarcodeLabelPrinter
{
    public function send(string $zpl): array
    {
        $host = config('barcode.printer_host');

        if ($host) {
            return $this->sendViaTcp($host, (int) config('barcode.printer_port', 9100), $zpl);
        }

        $windowsName = config('barcode.printer_windows_name');

        if ($windowsName && PHP_OS_FAMILY === 'Windows') {
            return $this->sendViaWindows($windowsName, $zpl);
        }

        return [
            'success' => false,
            'message' => 'Impresora no configurada en el servidor. Usa el agente local o descarga el archivo ZPL.',
            'code' => 'not_configured',
        ];
    }

    private function sendViaTcp(string $host, int $port, string $zpl): array
    {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if (! $socket) {
            return [
                'success' => false,
                'message' => "No se pudo conectar a {$host}:{$port} ({$errstr})",
                'code' => 'connection_failed',
            ];
        }

        fwrite($socket, $zpl);
        fclose($socket);

        return [
            'success' => true,
            'message' => "Etiquetas enviadas a {$host}:{$port}",
            'code' => 'sent_tcp',
        ];
    }

    private function sendViaWindows(string $printerName, string $zpl): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mikpos_zpl_');

        if ($tmp === false) {
            return [
                'success' => false,
                'message' => 'No se pudo crear archivo temporal.',
                'code' => 'temp_failed',
            ];
        }

        file_put_contents($tmp, $zpl);
        $result = $this->sendRawFileToWindowsPrinter($printerName, $tmp);

        if ($result['success']) {
            @unlink($tmp);
            return $result;
        }

        $fallbackResult = $this->sendViaWindowsShare($printerName, $tmp, $result['message']);
        @unlink($tmp);

        return $fallbackResult;
    }

    private function sendRawFileToWindowsPrinter(string $printerName, string $filePath): array
    {
        $scriptPath = tempnam(sys_get_temp_dir(), 'mikpos_ps_');

        if ($scriptPath === false) {
            return [
                'success' => false,
                'message' => 'No se pudo crear script temporal para imprimir.',
                'code' => 'powershell_script_failed',
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
                'message' => "Etiquetas enviadas a {$printerName}",
                'code' => 'sent_windows_raw',
            ];
        }

        return [
            'success' => false,
            'message' => 'Fallo de spooler RAW: ' . trim(implode(' ', $output)),
            'code' => 'windows_raw_failed',
        ];
    }

    private function sendViaWindowsShare(string $printerName, string $filePath, string $rawFailureMessage): array
    {
        $target = '\\\\localhost\\' . $printerName;
        $command = 'copy /B "' . $filePath . '" "' . $target . '"';
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            return [
                'success' => true,
                'message' => "Etiquetas enviadas a {$printerName}",
                'code' => 'sent_windows_share',
            ];
        }

        $shareMessage = trim(implode(' ', $output));

        return [
            'success' => false,
            'message' => 'No se pudo imprimir en Windows. '
                . trim($rawFailureMessage)
                . ($shareMessage !== '' ? ' | Fallback share: ' . $shareMessage : ''),
            'code' => 'windows_failed',
        ];
    }
}
