@echo off
title MikPOS - Agente de etiquetas ZPL
cd /d "%~dp0.."
echo.
echo  Agente de impresion de etiquetas MikPOS
echo  Impresora: SAT TT448-2 USE (ZPL)
echo  URL local: http://127.0.0.1:9311
echo.
echo  Deja esta ventana abierta mientras imprimes desde el navegador.
echo.
php tools\barcode-print-agent.php
pause
