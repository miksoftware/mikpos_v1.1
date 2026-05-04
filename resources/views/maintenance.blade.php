<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema en Mantenimiento — MikPOS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c1a 0%, #1a1225 40%, #2d1f3d 70%, #1a1225 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated background orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
            animation: pulse 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(255,114,97,0.18) 0%, transparent 70%);
            top: -15%; right: -10%;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(168,85,247,0.18) 0%, transparent 70%);
            bottom: -15%; left: -10%;
            animation-delay: 3s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 6s;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.05); }
        }

        /* Grid pattern overlay */
        .grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* Card */
        .card {
            position: relative;
            z-index: 10;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 56px 48px;
            max-width: 560px;
            width: 90%;
            text-align: center;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 32px 80px rgba(0,0,0,0.6),
                0 0 120px rgba(168,85,247,0.08);
            animation: fadeUp 0.8s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Icon container */
        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px; height: 80px;
            border-radius: 22px;
            background: linear-gradient(135deg, #ff7261, #a855f7);
            margin-bottom: 28px;
            box-shadow: 0 16px 40px rgba(168,85,247,0.35);
            animation: iconBounce 2.5s ease-in-out infinite;
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        .icon-wrap svg {
            width: 40px; height: 40px;
            color: #fff;
            stroke: #fff;
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,114,97,0.12);
            border: 1px solid rgba(255,114,97,0.25);
            border-radius: 999px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #ff9285;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        .badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #ff7261;
            animation: blink 1.4s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.2; }
        }

        /* Typography */
        h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            font-size: 1.05rem;
            color: rgba(255,255,255,0.55);
            line-height: 1.7;
            margin-bottom: 36px;
        }

        /* Divider */
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
            margin-bottom: 32px;
        }

        /* Support info */
        .support-block {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: rgba(168,85,247,0.06);
            border: 1px solid rgba(168,85,247,0.15);
            border-radius: 14px;
            padding: 18px 20px;
            text-align: left;
        }

        .support-icon {
            flex-shrink: 0;
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(168,85,247,0.3), rgba(99,102,241,0.3));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .support-icon svg {
            width: 18px; height: 18px;
            stroke: #c084fc;
        }

        .support-text {
            flex: 1;
        }

        .support-text p {
            font-size: 0.82rem;
            font-weight: 600;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 4px;
        }

        .support-text span {
            font-size: 0.97rem;
            color: rgba(255,255,255,0.75);
            font-weight: 500;
        }

        /* Footer note */
        .footer-note {
            margin-top: 32px;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.2);
        }

        @media (max-width: 480px) {
            .card { padding: 40px 28px; }
            h1 { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-bg"></div>

    <div class="card">

        <div class="badge">
            <span class="badge-dot"></span>
            En mantenimiento
        </div>

        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.653-4.655m5.714-3.032A3 3 0 0 0 15 6a3 3 0 0 0-3 3c0 .394.075.77.213 1.113M8.868 8.868a3 3 0 1 1 4.243 4.243"/>
            </svg>
        </div>

        <h1>Lo sentimos, estamos<br>trabajando para mejorar</h1>

        <p class="subtitle">
            Nuestro sistema se encuentra temporalmente fuera de servicio
            para realizarle mejoras. Estaremos de vuelta muy pronto.
        </p>

        <div class="divider"></div>

        <div class="support-block">
            <div class="support-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
                </svg>
            </div>
            <div class="support-text">
                <p>¿Necesita ayuda?</p>
                <span>Comuníquese con soporte técnico</span>
            </div>
        </div>

        <p class="footer-note">MikPOS &mdash; Sistema POS Multisucursal</p>

    </div>
</body>
</html>
