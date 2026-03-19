<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Editor | Warastra Adhiguna Tools</title>
    <meta name="description" content="Tools Image Editor Warastra Adhiguna untuk mengubah foto menjadi hitam putih, biner, atau background transparan langsung di browser.">
    @php($faviconVersion = filemtime(public_path('favicon.ico')))
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico').'?v='.$faviconVersion }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png').'?v='.filemtime(public_path('favicon-32x32.png')) }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico').'?v='.$faviconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png').'?v='.filemtime(public_path('apple-touch-icon.png')) }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --wa-bg: #f8fafc;
            --wa-card: rgba(255, 255, 255, 0.94);
            --wa-border: rgba(148, 163, 184, 0.24);
            --wa-text: #0f172a;
            --wa-muted: #64748b;
            --wa-blue: #2563eb;
            --wa-cyan: #0891b2;
            --wa-emerald: #10b981;
            --wa-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 34%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.14), transparent 28%),
                linear-gradient(180deg, #eff6ff 0%, var(--wa-bg) 20%, #f8fafc 100%);
            color: var(--wa-text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .site-navbar {
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .site-navbar .nav-link,
        .site-navbar .navbar-brand {
            color: rgba(255, 255, 255, 0.92) !important;
        }

        .site-navbar .nav-link:hover,
        .site-navbar .nav-link:focus,
        .site-navbar .nav-link.active {
            color: #ffffff !important;
        }

        .site-navbar .dropdown-menu {
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 1rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        }

        .logo-mark {
            width: 42px;
            height: 42px;
            padding: 0.25rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
        }

        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .page-shell {
            padding-top: 7rem;
            padding-bottom: 4rem;
        }

        .hero-card,
        .editor-shell {
            background: var(--wa-card);
            border: 1px solid var(--wa-border);
            border-radius: 1.75rem;
            box-shadow: var(--wa-shadow);
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--wa-blue);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .text-gradient {
            background: linear-gradient(120deg, var(--wa-blue), var(--wa-cyan), var(--wa-emerald));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .upload-panel {
            min-height: 320px;
            border: 2px dashed rgba(37, 99, 235, 0.22);
            border-radius: 1.5rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(239, 246, 255, 0.85));
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .upload-panel:hover {
            border-color: rgba(37, 99, 235, 0.45);
            transform: translateY(-2px);
            box-shadow: 0 20px 45px rgba(37, 99, 235, 0.12);
        }

        .upload-trigger {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(16, 185, 129, 0.14));
            color: var(--wa-blue);
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.1);
        }

        .editor-sidebar {
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.92), rgba(241, 245, 249, 0.96));
            border-right: 1px solid rgba(226, 232, 240, 0.9);
        }

        .editor-preview {
            min-height: 540px;
            background:
                radial-gradient(circle at top, rgba(37, 99, 235, 0.06), transparent 30%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(241, 245, 249, 0.9));
        }

        .preview-frame {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 1.5rem;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
        }

        .preview-grid {
            background-image:
                linear-gradient(45deg, #e2e8f0 25%, transparent 25%),
                linear-gradient(-45deg, #e2e8f0 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #e2e8f0 75%),
                linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
            background-size: 28px 28px;
            background-position: 0 0, 0 14px, 14px -14px, -14px 0;
        }

        .setting-card {
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .mode-button {
            width: 100%;
            text-align: left;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 1rem;
            background: #ffffff;
            color: var(--wa-text);
            padding: 0.9rem 1rem;
            transition: all 0.2s ease;
        }

        .mode-button:hover {
            border-color: rgba(37, 99, 235, 0.35);
            transform: translateY(-1px);
        }

        .mode-button.active {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.18);
        }

        .range-value {
            min-width: 46px;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--wa-blue);
            text-align: center;
            font-weight: 700;
            font-size: 0.78rem;
        }

        .switch-input {
            width: 3rem;
            height: 1.6rem;
        }

        .toolbar-note {
            font-size: 0.9rem;
            color: var(--wa-muted);
        }

        .toolbar-note strong {
            color: var(--wa-text);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 0.75rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.05);
            font-size: 0.85rem;
            color: var(--wa-muted);
        }

        .preview-empty {
            min-height: 360px;
            border: 1px dashed rgba(148, 163, 184, 0.4);
            border-radius: 1.5rem;
            color: var(--wa-muted);
        }

        .section-muted {
            color: var(--wa-muted);
        }

        @media (max-width: 991.98px) {
            .editor-sidebar {
                border-right: 0;
                border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            }

            .editor-preview {
                min-height: 420px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top site-navbar" data-bs-theme="dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                <span class="logo-mark">
                    <img src="{{ asset('logo.png') }}" alt="Logo Warastra Adhiguna">
                </span>
                <span class="fw-bold">Warastra <span class="text-primary">Adhiguna</span></span>
            </a>
            <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#siteNavbar" aria-controls="siteNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="siteNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#portfolio">Portfolio</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Tools
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="{{ route('tools.image-editor') }}">Image Editor</a></li>
                        </ul>
                    </li>
                    <li class="nav-item ms-lg-2 mt-3 mt-lg-0">
                        <a href="{{ route('home') }}#contact" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Contact Us</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="page-shell">
        <div class="container">
            <section class="hero-card p-4 p-lg-5 mb-4">
                <div class="row align-items-center gy-4">
                    <div class="col-lg-8">
                        <span class="hero-chip mb-3">
                            <i class="bi bi-stars"></i>
                            Warastra Adhiguna Tools
                        </span>
                        <h1 class="display-5 fw-bold mb-3">
                            <span class="text-gradient">Image Editor</span>
                            untuk Foto Hitam Putih
                        </h1>
                        <p class="lead section-muted mb-3">
                            Ubah gambar menjadi grayscale, biner, atau background transparan langsung di browser. Cocok untuk tanda tangan, dokumen, scan, dan aset grafis sederhana.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="status-badge"><i class="bi bi-shield-check"></i> Proses lokal di browser</span>
                            <span class="status-badge"><i class="bi bi-clipboard2"></i> Bisa upload atau Ctrl+V paste</span>
                            <span class="status-badge"><i class="bi bi-download"></i> Hasil PNG siap unduh</span>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="setting-card p-4 h-100">
                            <h2 class="h6 text-uppercase text-primary fw-bold mb-3">Catatan Keamanan</h2>
                            <p class="toolbar-note mb-2">
                                <strong>File tidak diunggah ke server.</strong> Semua proses dilakukan di perangkat Anda memakai elemen `canvas`.
                            </p>
                            <p class="toolbar-note mb-0">
                                Gunakan mode <strong>Hapus Background</strong> untuk tanda tangan atau logo monokrom dengan latar yang ingin dibuat transparan.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="editor-shell overflow-hidden">
                <div id="uploadState" class="p-4 p-lg-5">
                    <label for="imageInput" class="upload-panel d-flex flex-column align-items-center justify-content-center text-center p-4 text-decoration-none w-100">
                        <span class="upload-trigger mb-4">
                            <i class="bi bi-cloud-arrow-up fs-1"></i>
                        </span>
                        <span class="h4 fw-bold mb-2">Klik untuk unggah gambar</span>
                        <span class="section-muted mb-3">Atau tekan <strong>Ctrl+V</strong> untuk paste langsung dari clipboard.</span>
                        <span class="btn btn-outline-primary rounded-pill px-4">Pilih File</span>
                        <span class="small section-muted mt-3">Format yang didukung: PNG, JPG, JPEG, WEBP, GIF, BMP</span>
                    </label>
                    <input id="imageInput" type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif,image/bmp" class="d-none">
                </div>

                <div id="editorState" class="d-none">
                    <div class="row g-0">
                        <aside class="col-lg-4 editor-sidebar p-4 p-xl-5">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-4 pb-4 border-bottom">
                                <div class="min-w-0">
                                    <div class="small text-uppercase text-primary fw-bold mb-1">File Aktif</div>
                                    <div id="fileName" class="fw-semibold text-break">-</div>
                                </div>
                                <button id="resetButton" type="button" class="btn btn-outline-danger btn-sm rounded-pill">
                                    <i class="bi bi-trash3 me-1"></i> Ganti
                                </button>
                            </div>

                            <div class="setting-card p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-bold mb-1"><i class="bi bi-arrow-left-right me-2 text-warning"></i>Balikkan Warna</div>
                                        <div class="small section-muted">Aktifkan jika sumber gambar memiliki background gelap dan objek terang.</div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input switch-input" type="checkbox" role="switch" id="invertToggle">
                                    </div>
                                </div>
                            </div>

                            <div class="setting-card p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label for="sharpnessRange" class="fw-bold mb-0">
                                        <i class="bi bi-lightning-charge me-2 text-primary"></i>Ketajaman
                                    </label>
                                    <span id="sharpnessValue" class="range-value">0</span>
                                </div>
                                <input id="sharpnessRange" type="range" class="form-range" min="0" max="10" step="1" value="0">
                            </div>

                            <div class="setting-card p-4 mb-3">
                                <div class="fw-bold mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Mode Pengolahan</div>
                                <div class="d-grid gap-2">
                                    <button type="button" class="mode-button active" data-mode="grayscale">
                                        <span class="fw-bold d-block">Grayscale</span>
                                        <span class="small opacity-75">Hitam putih dengan gradasi abu-abu.</span>
                                    </button>
                                    <button type="button" class="mode-button" data-mode="binary">
                                        <span class="fw-bold d-block">Biner</span>
                                        <span class="small opacity-75">Kontras tinggi untuk teks atau dokumen.</span>
                                    </button>
                                    <button type="button" class="mode-button" data-mode="transparent">
                                        <span class="fw-bold d-block">Hapus Background</span>
                                        <span class="small opacity-75">Buat latar transparan untuk tanda tangan atau logo.</span>
                                    </button>
                                </div>
                            </div>

                            <div id="thresholdPanel" class="setting-card p-4 mb-3 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label for="thresholdRange" class="fw-bold mb-0">
                                        <i class="bi bi-toggles2 me-2 text-primary"></i>Threshold
                                    </label>
                                    <span id="thresholdValue" class="range-value">128</span>
                                </div>
                                <input id="thresholdRange" type="range" class="form-range" min="0" max="255" step="1" value="128">
                            </div>

                            <div class="pt-2 mt-2">
                                <button id="applyButton" type="button" class="btn btn-primary w-100 rounded-pill fw-bold py-3">
                                    <i class="bi bi-magic me-2"></i>Terapkan Efek
                                </button>
                                <button id="downloadButton" type="button" class="btn btn-success w-100 rounded-pill fw-bold py-3 mt-3 d-none">
                                    <i class="bi bi-download me-2"></i>Unduh PNG
                                </button>
                                <button id="refreshButton" type="button" class="btn btn-outline-secondary w-100 rounded-pill fw-semibold py-2 mt-3 d-none">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh Preview
                                </button>
                            </div>
                        </aside>

                        <div class="col-lg-8 editor-preview p-4 p-xl-5">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                <div>
                                    <div class="small text-uppercase text-primary fw-bold">Preview</div>
                                    <h2 class="h4 fw-bold mb-1">Hasil Pengolahan Gambar</h2>
                                    <p class="section-muted mb-0">Atur mode, threshold, atau ketajaman sesuai kebutuhan.</p>
                                </div>
                                <span class="status-badge"><i class="bi bi-cpu"></i> 100% client-side</span>
                            </div>

                            <div id="previewEmpty" class="preview-empty d-flex flex-column align-items-center justify-content-center text-center p-4">
                                <i class="bi bi-image fs-1 mb-3 text-primary"></i>
                                <div class="fw-semibold mb-2">Preview akan muncul di sini</div>
                                <div class="section-muted">Upload gambar dulu, lalu klik <strong>Terapkan Efek</strong>.</div>
                            </div>

                            <div id="previewFrame" class="preview-frame d-none position-relative">
                                <div id="previewBackground" class="p-2 p-md-3">
                                    <img id="previewImage" src="" alt="Preview editor gambar" class="img-fluid w-100 d-block">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <canvas id="processingCanvas" class="d-none"></canvas>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            const state = {
                originalUrl: null,
                originalUrlManaged: false,
                processedDataUrl: null,
                fileName: '',
                isProcessing: false,
                mode: 'grayscale',
                threshold: 128,
                sharpness: 0,
                inverted: false,
                autoRefreshTimer: null,
            };

            const uploadState = document.getElementById('uploadState');
            const editorState = document.getElementById('editorState');
            const imageInput = document.getElementById('imageInput');
            const fileNameEl = document.getElementById('fileName');
            const invertToggle = document.getElementById('invertToggle');
            const sharpnessRange = document.getElementById('sharpnessRange');
            const sharpnessValue = document.getElementById('sharpnessValue');
            const thresholdPanel = document.getElementById('thresholdPanel');
            const thresholdRange = document.getElementById('thresholdRange');
            const thresholdValue = document.getElementById('thresholdValue');
            const applyButton = document.getElementById('applyButton');
            const downloadButton = document.getElementById('downloadButton');
            const refreshButton = document.getElementById('refreshButton');
            const resetButton = document.getElementById('resetButton');
            const previewEmpty = document.getElementById('previewEmpty');
            const previewFrame = document.getElementById('previewFrame');
            const previewImage = document.getElementById('previewImage');
            const previewBackground = document.getElementById('previewBackground');
            const canvas = document.getElementById('processingCanvas');
            const modeButtons = Array.from(document.querySelectorAll('[data-mode]'));

            function sanitizeFileName(name) {
                return (name || 'image.png').replace(/[^A-Za-z0-9._-]+/g, '-');
            }

            function releaseOriginalUrl() {
                if (state.originalUrl && state.originalUrlManaged) {
                    URL.revokeObjectURL(state.originalUrl);
                }

                state.originalUrl = null;
                state.originalUrlManaged = false;
            }

            function resetProcessedState() {
                state.processedDataUrl = null;
            }

            function updateModeButtons() {
                modeButtons.forEach((button) => {
                    button.classList.toggle('active', button.dataset.mode === state.mode);
                });
            }

            function updateThresholdVisibility() {
                thresholdPanel.classList.toggle('d-none', state.mode === 'grayscale');
            }

            function updatePreview() {
                const currentSrc = state.processedDataUrl || state.originalUrl;

                if (!currentSrc) {
                    previewEmpty.classList.remove('d-none');
                    previewFrame.classList.add('d-none');
                    return;
                }

                previewEmpty.classList.add('d-none');
                previewFrame.classList.remove('d-none');
                previewImage.src = currentSrc;
                previewBackground.classList.toggle('preview-grid', state.mode === 'transparent' && !!state.processedDataUrl);
            }

            function updateButtons() {
                const hasOriginal = !!state.originalUrl;
                const hasProcessed = !!state.processedDataUrl;

                applyButton.disabled = !hasOriginal || state.isProcessing;
                applyButton.innerHTML = state.isProcessing
                    ? '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses...'
                    : '<i class="bi bi-magic me-2"></i>Terapkan Efek';

                downloadButton.classList.toggle('d-none', !hasProcessed);
                refreshButton.classList.toggle('d-none', !hasProcessed);
            }

            function updateShell() {
                const hasOriginal = !!state.originalUrl;

                uploadState.classList.toggle('d-none', hasOriginal);
                editorState.classList.toggle('d-none', !hasOriginal);
                fileNameEl.textContent = state.fileName || '-';
                sharpnessValue.textContent = String(state.sharpness);
                thresholdValue.textContent = String(state.threshold);
                sharpnessRange.value = String(state.sharpness);
                thresholdRange.value = String(state.threshold);
                invertToggle.checked = state.inverted;

                updateModeButtons();
                updateThresholdVisibility();
                updatePreview();
                updateButtons();
            }

            function queueAutoRefresh() {
                if (!state.originalUrl || !state.processedDataUrl) {
                    return;
                }

                window.clearTimeout(state.autoRefreshTimer);
                state.autoRefreshTimer = window.setTimeout(() => {
                    processImage();
                }, 180);
            }

            function loadImage(src) {
                return new Promise((resolve, reject) => {
                    const image = new Image();
                    image.onload = () => resolve(image);
                    image.onerror = () => reject(new Error('Gagal memuat gambar.'));
                    image.src = src;
                });
            }

            async function processImage() {
                if (!state.originalUrl || state.isProcessing) {
                    return;
                }

                state.isProcessing = true;
                updateButtons();

                try {
                    const image = await loadImage(state.originalUrl);
                    const context = canvas.getContext('2d', { willReadFrequently: true });

                    canvas.width = image.width;
                    canvas.height = image.height;
                    context.clearRect(0, 0, canvas.width, canvas.height);
                    context.drawImage(image, 0, 0);

                    const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                    const data = imageData.data;
                    const width = canvas.width;
                    const height = canvas.height;

                    if (state.sharpness > 0) {
                        const buffer = new Uint8ClampedArray(data);
                        const mix = state.sharpness * 0.05;

                        for (let y = 1; y < height - 1; y += 1) {
                            for (let x = 1; x < width - 1; x += 1) {
                                const index = (y * width + x) * 4;
                                const up = ((y - 1) * width + x) * 4;
                                const down = ((y + 1) * width + x) * 4;
                                const left = (y * width + (x - 1)) * 4;
                                const right = (y * width + (x + 1)) * 4;

                                for (let channel = 0; channel < 3; channel += 1) {
                                    const current = buffer[index + channel];
                                    const neighbors = buffer[up + channel] + buffer[down + channel] + buffer[left + channel] + buffer[right + channel];
                                    const delta = (4 * current) - neighbors;
                                    data[index + channel] = Math.min(255, Math.max(0, current + (delta * mix)));
                                }
                            }
                        }
                    }

                    for (let i = 0; i < data.length; i += 4) {
                        let red = data[i];
                        let green = data[i + 1];
                        let blue = data[i + 2];

                        if (state.inverted) {
                            red = 255 - red;
                            green = 255 - green;
                            blue = 255 - blue;
                        }

                        const gray = (0.299 * red) + (0.587 * green) + (0.114 * blue);

                        if (state.mode === 'binary') {
                            const binary = gray < state.threshold ? 0 : 255;
                            data[i] = binary;
                            data[i + 1] = binary;
                            data[i + 2] = binary;
                        } else if (state.mode === 'transparent') {
                            if (gray > state.threshold) {
                                data[i + 3] = 0;
                            } else {
                                data[i] = 0;
                                data[i + 1] = 0;
                                data[i + 2] = 0;
                                data[i + 3] = 255;
                            }
                        } else {
                            data[i] = gray;
                            data[i + 1] = gray;
                            data[i + 2] = gray;
                        }
                    }

                    context.putImageData(imageData, 0, 0);
                    state.processedDataUrl = canvas.toDataURL('image/png');
                    updatePreview();
                    updateButtons();
                } catch (error) {
                    window.alert(error.message || 'Gagal memproses gambar.');
                } finally {
                    state.isProcessing = false;
                    updateButtons();
                }
            }

            function handleIncomingImage(file) {
                if (!file || !file.type.startsWith('image/')) {
                    window.alert('File harus berupa gambar.');
                    return;
                }

                releaseOriginalUrl();
                state.originalUrl = URL.createObjectURL(file);
                state.originalUrlManaged = true;
                state.fileName = file.name || 'image.png';
                state.mode = 'grayscale';
                state.threshold = 128;
                state.sharpness = 0;
                state.inverted = false;
                resetProcessedState();
                updateShell();
            }

            imageInput.addEventListener('change', (event) => {
                const [file] = event.target.files || [];

                if (file) {
                    handleIncomingImage(file);
                }

                event.target.value = '';
            });

            window.addEventListener('paste', (event) => {
                const items = event.clipboardData?.items || [];

                for (const item of items) {
                    if (item.type.startsWith('image/')) {
                        const file = item.getAsFile();

                        if (file) {
                            handleIncomingImage(file);
                            state.fileName = 'pasted-' + new Date().toISOString().replace(/[:.]/g, '-') + '.png';
                            updateShell();
                            event.preventDefault();
                        }

                        break;
                    }
                }
            });

            modeButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    state.mode = button.dataset.mode;
                    updateShell();
                    queueAutoRefresh();
                });
            });

            invertToggle.addEventListener('change', () => {
                state.inverted = invertToggle.checked;
                queueAutoRefresh();
            });

            sharpnessRange.addEventListener('input', () => {
                state.sharpness = Number.parseInt(sharpnessRange.value, 10) || 0;
                sharpnessValue.textContent = String(state.sharpness);
                queueAutoRefresh();
            });

            thresholdRange.addEventListener('input', () => {
                state.threshold = Number.parseInt(thresholdRange.value, 10) || 128;
                thresholdValue.textContent = String(state.threshold);
                queueAutoRefresh();
            });

            applyButton.addEventListener('click', () => {
                processImage();
            });

            refreshButton.addEventListener('click', () => {
                processImage();
            });

            downloadButton.addEventListener('click', () => {
                if (!state.processedDataUrl) {
                    return;
                }

                const link = document.createElement('a');
                const safeName = sanitizeFileName(state.fileName);
                const invertPrefix = state.inverted ? 'inverted-' : '';

                link.href = state.processedDataUrl;
                link.download = 'bw-' + state.mode + '-' + invertPrefix + safeName.replace(/\.(png|jpg|jpeg|webp|gif|bmp)$/i, '') + '.png';
                document.body.appendChild(link);
                link.click();
                link.remove();
            });

            resetButton.addEventListener('click', () => {
                window.clearTimeout(state.autoRefreshTimer);
                releaseOriginalUrl();
                state.fileName = '';
                state.mode = 'grayscale';
                state.threshold = 128;
                state.sharpness = 0;
                state.inverted = false;
                resetProcessedState();
                updateShell();
            });

            window.addEventListener('beforeunload', () => {
                releaseOriginalUrl();
            });

            updateShell();
        })();
    </script>
</body>
</html>
