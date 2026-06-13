<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Imprimir etiquetas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 24px 16px 40px;
        }

        .wrap {
            max-width: 720px;
            margin: 0 auto;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 8px;
        }

        .meta {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 16px;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .status.pending { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .status.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .preview-box {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 180px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 16px;
        }

        .preview-box img {
            max-width: 100%;
            height: auto;
            image-rendering: pixelated;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary { background: #ff7261; color: #fff; }
        .btn-primary:hover { background: #e55a4a; }
        .btn-secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; }

        .note {
            font-size: 12px;
            line-height: 1.5;
            color: #64748b;
        }

        .note strong { color: #334155; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Impresión de etiquetas ZPL</h1>
        <p class="meta">{{ $labelCount }} etiqueta(s) · SAT TT448 · {{ config('barcode.label_width_mm') }}×{{ config('barcode.label_height_mm') }} mm</p>

        <div id="status" class="status pending">Enviando etiquetas a la impresora...</div>

        <div class="card">
            <strong>Vista previa (primera etiqueta)</strong>
            <div class="preview-box" style="margin-top:12px;">
                <img id="preview" src="{{ $previewUrl }}" alt="Vista previa etiqueta">
            </div>
        </div>

        <div class="card">
            <div class="actions">
                <button type="button" class="btn btn-primary" onclick="sendToPrinter()">Reintentar impresión</button>
                <a href="{{ route('barcode.print.download') }}" class="btn btn-secondary">Descargar .zpl</a>
            </div>
            <p class="note" style="margin-top:14px;">
                Esta impresora usa <strong>ZPL</strong>, no impresión del navegador.
                Si la app está en la nube, ejecuta el agente local:
                <strong>tools\iniciar-agente-etiquetas.bat</strong>
            </p>
        </div>
    </div>

    <script>
        const zpl = @json($zpl);
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const serverSendUrl = @json(route('barcode.print.send'));
        const localAgentUrl = @json(rtrim(config('barcode.local_agent_url'), '/'));
        const autoPrint = @json($autoPrint);

        function setStatus(type, message) {
            const el = document.getElementById('status');
            el.className = 'status ' + type;
            el.textContent = message;
        }

        async function sendToServer() {
            const res = await fetch(serverSendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ zpl }),
            });

            return res.json();
        }

        async function sendToLocalAgent() {
            const res = await fetch(localAgentUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain' },
                body: zpl,
                mode: 'cors',
            });

            if (!res.ok) {
                throw new Error('Agente local no respondió');
            }

            return res.json();
        }

        async function sendToPrinter() {
            setStatus('pending', 'Enviando etiquetas a la impresora...');

            try {
                const serverResult = await sendToServer();
                if (serverResult.success) {
                    setStatus('success', serverResult.message);
                    return;
                }
            } catch (e) {}

            try {
                const agentResult = await sendToLocalAgent();
                if (agentResult.success) {
                    setStatus('success', 'Etiquetas enviadas mediante agente local.');
                    return;
                }

                setStatus('error', agentResult.message || 'El agente local no pudo imprimir.');
            } catch (e) {
                setStatus(
                    'error',
                    'No se pudo imprimir. Ejecuta tools\\iniciar-agente-etiquetas.bat en esta PC o descarga el archivo .zpl.'
                );
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (autoPrint) {
                sendToPrinter();
            } else {
                setStatus('pending', 'Listo. Pulsa "Reintentar impresión" para enviar a la impresora.');
            }
        });
    </script>
</body>
</html>
