<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expirée | Kori Asset Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #C49A22;
            --primary-dark: #A67C1A;
            --secondary: #5C1F10;
            --bg: #0f0f0f;
            --text-gold: #C49A22;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(92, 31, 16, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(196, 154, 34, 0.1) 0%, transparent 40%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            overflow: hidden;
        }

        /* Effet de particules ou vagues subtiles au fond */
        .waves {
            position: absolute;
            top: 0; bottom: 0; left: 0; right: 0;
            opacity: 0.3;
            z-index: -1;
            pointer-events: none;
        }

        .error-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(196, 154, 34, 0.2);
            padding: 3rem 2rem;
            border-radius: 24px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.8s ease-out forwards;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(196, 154, 34, 0.1);
            border: 1px solid var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            position: relative;
        }

        /* Icône horloge stylisée en CSS */
        .clock-icon {
            width: 32px;
            height: 32px;
            border: 2px solid var(--primary);
            border-radius: 50%;
            position: relative;
        }
        .clock-icon::after {
            content: '';
            position: absolute;
            left: 50%; top: 50%;
            width: 2px; height: 10px;
            background: var(--primary);
            transform-origin: bottom center;
            transform: translate(-50%, -100%) rotate(45deg);
        }
        .clock-icon::before {
            content: '';
            position: absolute;
            left: 50%; top: 2px;
            width: 2px; height: 14px;
            background: var(--primary);
            transform-origin: bottom center;
            transform: translate(-50%, 0);
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin-bottom: 2.5rem;
            font-size: 1.1rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: #1a1a1a;
            box-shadow: 0 4px 15px rgba(196, 154, 34, 0.3);
        }

        .btn-primary:hover {
            background: #e8b830;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(196, 154, 34, 0.4);
        }

        .btn-outline {
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .footer-brand {
            margin-top: 3rem;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        /* Micro-animation de l'icône */
        .icon-box::after {
            content: '';
            position: absolute;
            width: 100%; height: 100%;
            border: 1px solid var(--primary);
            border-radius: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.4); opacity: 0; }
        }

        @media (max-width: 640px) {
            h1 { font-size: 2rem; }
            .error-card { padding: 2.5rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-box">
            <div class="clock-icon"></div>
        </div>
        <h1>419</h1>
        <p>
            Votre session sécurisée a expiré par mesure de prudence boursière.<br>
            Veuillez rafraîchir la page ou vous reconnecter pour une intégrité totale.
        </p>
        <div class="btn-group">
            <a href="javascript:location.reload()" class="btn btn-primary">Actualiser la page</a>
            <a href="{{ url('/') }}" class="btn btn-outline">Retour à l'accueil</a>
        </div>
        <div class="footer-brand">Kori Asset Management S.A.</div>
    </div>
</body>
</html>
