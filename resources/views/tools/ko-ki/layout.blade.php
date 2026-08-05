<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — WAn Ko-Ki</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --koki-ink: #172033;
            --koki-muted: #667085;
            --koki-blue: #3157d5;
            --koki-cyan: #27b3c2;
            --koki-yellow: #ffd65c;
            --koki-surface: #ffffff;
            --koki-bg: #f2f7ff;
        }

        body {
            min-height: 100vh;
            color: var(--koki-ink);
            background:
                radial-gradient(circle at top left, rgba(39, 179, 194, 0.16), transparent 34rem),
                linear-gradient(180deg, #f8fbff 0%, var(--koki-bg) 100%);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .koki-navbar {
            background: rgba(23, 32, 51, 0.96);
            box-shadow: 0 10px 35px rgba(23, 32, 51, 0.14);
        }

        .koki-brand-mark {
            display: inline-grid;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border-radius: 0.85rem;
            color: var(--koki-ink);
            background: var(--koki-yellow);
            font-size: 1.3rem;
        }

        .koki-page {
            padding: 7rem 0 4rem;
        }

        .koki-card {
            border: 1px solid rgba(49, 87, 213, 0.12);
            border-radius: 1.5rem;
            background: var(--koki-surface);
            box-shadow: 0 24px 60px rgba(49, 87, 213, 0.1);
        }

        .koki-eyebrow {
            color: var(--koki-blue);
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .koki-title {
            font-weight: 800;
            letter-spacing: -0.035em;
        }

        .koki-muted {
            color: var(--koki-muted);
        }

        .koki-btn-primary {
            border-color: var(--koki-blue);
            background: var(--koki-blue);
            box-shadow: 0 10px 24px rgba(49, 87, 213, 0.2);
        }

        .koki-btn-primary:hover,
        .koki-btn-primary:focus {
            border-color: #2445b5;
            background: #2445b5;
        }

        .koki-step {
            height: 100%;
            padding: 1.1rem;
            border: 1px solid #dbe5f5;
            border-radius: 1rem;
            background: #f9fbff;
        }

        .koki-step-number {
            display: inline-grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border-radius: 50%;
            color: #fff;
            background: var(--koki-blue);
            font-weight: 800;
        }

        .koki-dialogue {
            padding: 1.25rem;
            border: 2px solid rgba(49, 87, 213, 0.16);
            border-radius: 1.25rem;
            background: #f9fbff;
            font-size: clamp(1.1rem, 2vw, 1.35rem);
            font-weight: 700;
        }

        .koki-answer {
            position: relative;
        }

        .koki-answer input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .koki-answer label {
            display: flex;
            min-height: 4.5rem;
            align-items: center;
            gap: 0.9rem;
            padding: 1rem 1.15rem;
            border: 2px solid #d7e1f1;
            border-radius: 1rem;
            background: #fff;
            cursor: pointer;
            font-size: 1.05rem;
            font-weight: 700;
            transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
        }

        .koki-answer label::before {
            width: 1.35rem;
            height: 1.35rem;
            flex: 0 0 auto;
            border: 2px solid #aebbd0;
            border-radius: 50%;
            content: '';
        }

        .koki-answer input:focus-visible + label {
            outline: 4px solid rgba(49, 87, 213, 0.2);
            outline-offset: 2px;
        }

        .koki-answer input:checked + label {
            border-color: var(--koki-blue);
            background: rgba(49, 87, 213, 0.08);
            transform: translateY(-1px);
        }

        .koki-answer input:checked + label::before {
            border: 0.38rem solid var(--koki-blue);
            background: #fff;
        }

        .koki-progress {
            height: 0.7rem;
            border-radius: 999px;
            background: #dfe8f5;
        }

        .koki-progress .progress-bar {
            background: linear-gradient(90deg, var(--koki-blue), var(--koki-cyan));
        }

        .koki-grid {
            --grid-columns: 6;
            display: grid;
            width: min(100%, 34rem);
            aspect-ratio: 1;
            grid-template-columns: repeat(var(--grid-columns), 1fr);
            overflow: hidden;
            border: 4px solid var(--koki-ink);
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 18px 35px rgba(23, 32, 51, 0.14);
        }

        .koki-grid-cell {
            position: relative;
            display: grid;
            min-width: 0;
            place-items: center;
            border-right: 1px solid #cbd8ea;
            border-bottom: 1px solid #cbd8ea;
            background: #f8fbff;
        }

        .koki-grid-cell:nth-child(odd) {
            background: #edf5ff;
        }

        .koki-grid-cell.is-obstacle {
            background: #d8dee9;
            font-size: clamp(1rem, 4vw, 2rem);
        }

        .koki-target {
            font-size: clamp(1.2rem, 5vw, 2.7rem);
            filter: drop-shadow(0 4px 3px rgba(23, 32, 51, 0.18));
        }

        .koki-robot {
            position: absolute;
            inset: 8%;
            display: grid;
            place-items: center;
            border: 3px solid #203fa7;
            border-radius: 24%;
            background: var(--koki-yellow);
            box-shadow: 0 5px 0 rgba(32, 63, 167, 0.25);
            font-size: clamp(1.15rem, 5vw, 2.4rem);
            transition: inset 0.15s ease;
            z-index: 2;
        }

        .koki-direction {
            position: absolute;
            top: -0.65rem;
            right: -0.55rem;
            display: grid;
            width: 1.55rem;
            height: 1.55rem;
            place-items: center;
            border: 2px solid #fff;
            border-radius: 50%;
            color: #fff;
            background: var(--koki-blue);
            font-size: 1rem;
            font-weight: 900;
        }

        .koki-command-button {
            min-height: 4rem;
            border-width: 2px;
            border-radius: 1rem;
            font-weight: 800;
        }

        .koki-program-list {
            display: grid;
            max-height: 19rem;
            gap: 0.65rem;
            margin: 0;
            padding: 0;
            overflow-y: auto;
            list-style: none;
        }

        .koki-program-command {
            display: grid;
            grid-template-columns: 2rem 1fr auto;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border: 2px solid #dbe5f5;
            border-radius: 0.85rem;
            background: #fff;
            transition: all 0.15s ease;
        }

        .koki-program-command > span {
            display: grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border-radius: 50%;
            color: #fff;
            background: var(--koki-blue);
            font-weight: 800;
        }

        .koki-program-command code {
            color: var(--koki-muted);
            font-size: 0.72rem;
        }

        .koki-program-command.is-active {
            border-color: #f2af00;
            background: #fff7d6;
            box-shadow: 0 0 0 4px rgba(255, 214, 92, 0.32);
            transform: translateX(0.2rem);
        }

        [hidden] {
            display: none !important;
        }

        @media (max-width: 575.98px) {
            .koki-program-command {
                grid-template-columns: 2rem 1fr;
            }

            .koki-program-command code {
                grid-column: 2;
            }
        }

        .form-control,
        .form-select {
            border-color: #cfd9ea;
            border-radius: 0.9rem;
            padding: 0.8rem 1rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--koki-blue);
            box-shadow: 0 0 0 0.25rem rgba(49, 87, 213, 0.12);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark koki-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('tools.ko-ki.welcome') }}">
                <span class="koki-brand-mark" aria-hidden="true"><i class="bi bi-robot"></i></span>
                <span>WAn Ko-Ki</span>
            </a>
            <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="{{ route('home') }}">
                <i class="bi bi-house-door me-1" aria-hidden="true"></i> Beranda
            </a>
        </div>
    </nav>

    <main class="koki-page">
        <div class="container">
            @if (session('status'))
                <div class="alert alert-info border-0 rounded-4" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
