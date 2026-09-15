<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — WAn Ko-Ki</title>
    <link rel="icon" type="image/png" href="{{ asset('wan-ko-ki.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            /* Setara tampilan browser 75%, tetapi hanya berlaku di modul WAn Ko-Ki. */
            zoom: 0.75;
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

        .koki-navbar-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .koki-brand-logo {
            width: 3rem;
            height: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 0.8rem;
            object-fit: cover;
            box-shadow: 0 5px 16px rgba(0, 0, 0, 0.22);
        }

        .koki-hero-logo {
            display: block;
            width: min(100%, 18rem);
            height: auto;
            margin-inline: auto;
            border-radius: 1.5rem;
            box-shadow: 0 20px 45px rgba(23, 32, 51, 0.22);
        }

        .koki-hero-logo-sm {
            width: min(100%, 12rem);
        }

        .koki-page {
            padding: 5.75rem 0 2.5rem;
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

        .koki-curriculum-line {
            position: relative;
            padding-left: 1.5rem;
            border-left: 3px solid rgba(49, 87, 213, 0.18);
        }

        .koki-curriculum-line::before {
            position: absolute;
            top: 0.35rem;
            left: -0.55rem;
            width: 0.95rem;
            height: 0.95rem;
            border: 3px solid #fff;
            border-radius: 50%;
            background: var(--koki-blue);
            box-shadow: 0 0 0 2px rgba(49, 87, 213, 0.2);
            content: '';
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
            --grid-rows: 6;
            display: grid;
            width: min(100%, 34rem);
            aspect-ratio: 1;
            grid-template-columns: repeat(var(--grid-columns), 1fr);
            grid-template-rows: repeat(var(--grid-rows), 1fr);
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
            top: 50%;
            left: 50%;
            display: grid;
            width: 72%;
            height: auto;
            aspect-ratio: 1;
            place-items: center;
            border: 3px solid #203fa7;
            border-radius: 24%;
            background: var(--koki-yellow);
            box-shadow: 0 5px 0 rgba(32, 63, 167, 0.25);
            font-size: clamp(1.15rem, 5vw, 2.4rem);
            transform: translate(-50%, -50%);
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
            max-height: 14rem;
            gap: 0.65rem;
            margin: 0;
            padding: 0 0.35rem 0 0;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-color: var(--koki-blue) #dfe8f5;
            scrollbar-width: thin;
            list-style: none;
            align-content: start;
        }

        .koki-program-list::-webkit-scrollbar {
            width: 0.55rem;
        }

        .koki-program-list::-webkit-scrollbar-track {
            border-radius: 999px;
            background: #dfe8f5;
        }

        .koki-program-list::-webkit-scrollbar-thumb {
            border: 2px solid #dfe8f5;
            border-radius: 999px;
            background: var(--koki-blue);
        }

        .koki-game-card .koki-grid {
            width: min(100%, 30rem, calc(100vh - 13.5rem));
        }

        .koki-game-card .koki-command-button {
            min-height: 3.1rem;
        }

        .koki-game-card .koki-program-list {
            max-height: none;
        }

        .koki-program-box {
            display: flex;
            height: clamp(14rem, 40vh, 22rem);
            flex-direction: column;
        }

        .koki-program-box .koki-program-list {
            min-height: 0;
            flex: 1;
        }

        .koki-program-box > [data-empty-program]:not([hidden]) {
            margin: auto !important;
        }

        .koki-mission-panel {
            min-width: 0;
        }

        .koki-mission-panel .koki-answer label {
            min-height: 3.4rem;
            padding: 0.7rem 0.9rem;
        }

        [data-ko-ki-game].is-mission-mode .koki-activity-header {
            display: none !important;
        }

        .koki-game-card .koki-dialogue {
            padding: 0.85rem 1rem;
            font-size: 1rem;
        }

        [data-missions] {
            scroll-margin-top: 5.5rem;
        }

        .koki-program-command {
            display: grid;
            min-width: 0;
            grid-template-columns: 2rem minmax(0, 1fr);
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
            .koki-fullscreen-label {
                display: none;
            }

            .koki-program-command {
                grid-template-columns: 2rem 1fr;
            }

            .koki-brand-logo {
                width: 2.6rem;
                height: 2.6rem;
            }
        }

        @media (min-width: 992px) and (max-height: 850px) {
            .koki-activity-header {
                margin-bottom: 0.9rem !important;
            }

            .koki-activity-header .koki-eyebrow {
                margin-bottom: 0 !important;
            }

            .koki-activity-header h1 {
                font-size: 1.8rem;
                margin-bottom: 0.15rem !important;
            }

            .koki-game-card {
                padding: 1rem !important;
            }

            .koki-game-card .row {
                --bs-gutter-y: 0.75rem;
            }
        }

        @media (min-width: 992px) {
            .koki-mission-panel {
                display: flex;
                height: min(calc(100vh - 7rem), 42rem);
                flex-direction: column;
                overflow: hidden;
            }

            .koki-mission-panel [data-program-builder] {
                display: flex;
                min-height: 0;
                flex: 1;
                flex-direction: column;
            }

            .koki-mission-panel .koki-program-box {
                height: auto;
                min-height: 12rem;
                flex: 1;
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
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-dark koki-navbar fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('tools.ko-ki.index') }}">
                <img class="koki-brand-logo" src="{{ asset('wan-ko-ki.png') }}" alt="Logo WAn Ko-Ki">
                <span>WAn Ko-Ki</span>
            </a>
            <div class="koki-navbar-actions">
                <button class="btn btn-sm btn-outline-light rounded-pill px-3" type="button" data-koki-fullscreen>
                    <i class="bi bi-arrows-fullscreen me-sm-1" data-koki-fullscreen-icon aria-hidden="true"></i>
                    <span class="koki-fullscreen-label" data-koki-fullscreen-label>Layar Penuh</span>
                </button>
                <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="{{ route('home') }}">
                    <i class="bi bi-house-door me-1" aria-hidden="true"></i> Beranda
                </a>
            </div>
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
    <script>
        (() => {
            const button = document.querySelector('[data-koki-fullscreen]');
            const label = document.querySelector('[data-koki-fullscreen-label]');
            const icon = document.querySelector('[data-koki-fullscreen-icon]');
            if (!button || !document.documentElement.requestFullscreen) {
                button?.setAttribute('hidden', '');

                return;
            }

            const updateButton = () => {
                const isFullscreen = Boolean(document.fullscreenElement);
                label.textContent = isFullscreen ? 'Keluar Layar Penuh' : 'Layar Penuh';
                icon.className = isFullscreen
                    ? 'bi bi-fullscreen-exit me-sm-1'
                    : 'bi bi-arrows-fullscreen me-sm-1';

            };

            const enterFullscreen = async () => {
                if (document.fullscreenElement) return;

                try {
                    await document.documentElement.requestFullscreen({ navigationUI: 'hide' });
                } catch {
                    // Browser akan mengizinkan lagi setelah interaksi pengguna berikutnya.
                }
            };

            button.addEventListener('click', async () => {
                if (document.fullscreenElement) {
                    await document.exitFullscreen();

                    return;
                }

                await enterFullscreen();
            });

            document.addEventListener('fullscreenchange', () => {
                updateButton();
            });

            // Fullscreen tanpa gesture dapat ditolak browser. Permintaan diulang tepat
            // pada interaksi pertama agar siswa tidak perlu menekan tombol fullscreen.
            document.addEventListener('pointerdown', enterFullscreen, { capture: true, once: true });
            document.addEventListener('keydown', enterFullscreen, { capture: true, once: true });
            enterFullscreen();

            updateButton();
        })();
    </script>
    <script>
        (() => {
            const meetingPath = '/tools/wan-ko-ki/pertemuan-4/';
            if (!window.location.pathname.startsWith(meetingPath)) return;

            let navigating = false;

            const loadPage = async (url, pushState = true) => {
                if (navigating) return;
                navigating = true;
                document.documentElement.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(url, {
                        headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Halaman berikutnya belum dapat dimuat.');

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const resolvedUrl = response.url || url;
                    const currentMain = document.querySelector('main.koki-page');
                    const nextMain = page.querySelector('main.koki-page');
                    if (!currentMain || !nextMain) throw new Error('Konten halaman tidak ditemukan.');

                    window.dispatchEvent(new CustomEvent('koki:before-soft-navigation'));
                    currentMain.innerHTML = nextMain.innerHTML;
                    document.title = page.title;
                    if (pushState) window.history.pushState({ kokiMeetingFour: true }, '', resolvedUrl);
                    window.scrollTo({ top: 0, behavior: 'auto' });

                    const moduleScript = [...page.querySelectorAll('script[type="module"][src]')]
                        .find((script) => script.getAttribute('src')?.includes('/js/ko-ki/detective-'));
                    if (moduleScript) {
                        const moduleUrl = new URL(moduleScript.getAttribute('src'), resolvedUrl);
                        moduleUrl.searchParams.set('soft', Date.now().toString());
                        await import(moduleUrl.href);
                    }
                } catch {
                    window.location.assign(url);
                } finally {
                    navigating = false;
                    document.documentElement.removeAttribute('aria-busy');
                }
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[href]');
                if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                if (link.target || link.hasAttribute('download')) return;

                const url = new URL(link.href, window.location.href);
                if (url.origin !== window.location.origin || !url.pathname.startsWith(meetingPath)) return;

                event.preventDefault();
                void loadPage(url.href);
            });

            window.addEventListener('popstate', () => {
                if (window.location.pathname.startsWith(meetingPath)) void loadPage(window.location.href, false);
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
