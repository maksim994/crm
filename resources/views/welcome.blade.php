<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background:
                radial-gradient(circle at top left, rgba(58, 87, 252, 0.12), transparent 42%),
                radial-gradient(circle at bottom right, rgba(58, 87, 252, 0.08), transparent 38%),
                #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: min(100%, 520px);
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .logo {
            height: 32px;
            width: auto;
            margin: 0 auto 24px;
            display: block;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .actions {
            display: grid;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 20px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .btn-primary {
            background: #3a57fc;
            color: #fff;
            box-shadow: 0 10px 24px rgba(58, 87, 252, 0.28);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background: #2f49e8;
        }

        .btn-secondary {
            background: #fff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background: #f8fafc;
        }

        .meta {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .meta a {
            color: #64748b;
            text-decoration: none;
        }

        .meta a:hover {
            color: #3a57fc;
        }
    </style>
</head>
<body>
    <main class="card">
        <img src="{{ asset('images/logo/logo.svg') }}" alt="{{ config('app.name') }}" class="logo">

        <h1>{{ config('app.name') }}</h1>
        <p class="subtitle">
            Платформа учёта лидов и сквозной аналитики для digital-агентства.
        </p>

        <div class="actions">
            <a class="btn btn-primary" href="{{ url('/admin/') }}">Войти в админку</a>
            <a class="btn btn-secondary" href="{{ url('/cabinet/') }}">Личный кабинет</a>
        </div>

        <p class="meta">
            <a href="{{ url('/health') }}">Health check</a>
        </p>
    </main>
</body>
</html>
