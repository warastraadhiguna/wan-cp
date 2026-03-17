<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warastra Adhiguna - Digitally Customize Your Needs</title>
    <meta name="description" content="Profil perusahaan Warastra Adhiguna (WAn), penyedia solusi teknologi informasi, pengembangan software, dan implementasi jaringan.">
    @php($faviconVersion = filemtime(public_path('favicon.ico')))
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico').'?v='.$faviconVersion }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png').'?v='.filemtime(public_path('favicon-32x32.png')) }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico').'?v='.$faviconVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png').'?v='.filemtime(public_path('apple-touch-icon.png')) }}">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- AOS Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        /* Pengaturan Scroll Mulus & Jarak Berhenti */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 85px; /* Jarak aman agar judul section tidak tertutup navbar */
        }

        /* CSS Variabel Kustom - Light Theme */
        :root {
            --wa-bg-color: #f8fafc; /* Slate 50 */
            --wa-card-bg: #ffffff; /* White */
            --wa-border: #e2e8f0; /* Slate 200 */
            --wa-text-muted: #64748b; /* Slate 500 */
            --wa-text-dark: #0f172a; /* Slate 900 */
            --wa-blue: #3b82f6;
            --wa-emerald: #10b981; /* Sedikit lebih pekat agar terbaca di background terang */
        }

        body {
            background-color: var(--wa-bg-color);
            color: var(--wa-text-dark);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            overflow-x: hidden;
        }

        /* Teks Gradasi */
        .text-gradient {
            background: linear-gradient(to right, #2563eb, #0891b2, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Teks Gradasi untuk Latar Gelap (Hero) */
        .text-gradient-light {
            background: linear-gradient(to right, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* --- Efek Teks Berkilau (Shimmer/Shine) HANYA untuk "Your Needs" --- */
        .text-shimmer-gradient {
            display: inline-block;
            background: linear-gradient(
                to right,
                #60a5fa 20%, 
                #ffffff 40%,    /* Kilauan putih di tengah warna gradient */
                #ffffff 60%,
                #34d399 80%
            );
            background-size: 200% auto;
            color: transparent;
            -webkit-background-clip: text;
            background-clip: text;
            animation: shine 4s linear infinite alternate;
        }

        @keyframes shine {
            0% { background-position: 0% center; }
            100% { background-position: 200% center; }
        }
        /* --- Akhir Efek Berkilau --- */

        /* Utility Teks Gelap */
        .text-wa-dark {
            color: var(--wa-text-dark) !important;
        }

        /* Navbar Kustom - Dinamis (Transparan ke Putih) */
        .navbar {
            transition: all 0.3s ease-in-out;
            padding: 1.5rem 0;
            background-color: transparent !important;
            border-bottom: 1px solid transparent;
        }
        .navbar .logo-text {
            color: #ffffff;
            transition: color 0.3s ease;
        }
        .navbar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .navbar .nav-link:hover,
        .navbar .nav-link:focus,
        .navbar .nav-link.active {
            color: #ffffff !important;
        }
        /* Penanda aktif saat navbar transparan */
        .navbar .nav-link.active {
            font-weight: 700;
            text-shadow: 0 0 8px rgba(255,255,255,0.5);
        }
        .navbar .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
        }
        /* Ikon Toggler Putih (Transparan) */
        .navbar .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Language Dropdown saat Transparan */
        .navbar .lang-dropdown .nav-link {
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 0.4rem 1rem !important;
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff !important;
        }
        .navbar .lang-dropdown .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* --- STATE SCROLLED (Latar Putih) --- */
        .navbar.scrolled {
            padding: 1rem 0;
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .navbar.scrolled .logo-text {
            color: var(--wa-text-dark);
        }
        .navbar.scrolled .nav-link {
            color: var(--wa-text-muted) !important;
        }
        .navbar.scrolled .nav-link:hover,
        .navbar.scrolled .nav-link:focus,
        .navbar.scrolled .nav-link.active {
            color: var(--wa-blue) !important;
        }
        /* Penanda aktif saat navbar putih (scrolled) */
        .navbar.scrolled .nav-link.active {
            font-weight: 700;
        }
        .navbar.scrolled .navbar-toggler {
            border-color: rgba(0, 0, 0, 0.1);
        }
        /* Ikon Toggler Gelap (Scrolled) */
        .navbar.scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2815, 23, 42, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Language Dropdown saat Scrolled */
        .navbar.scrolled .lang-dropdown .nav-link {
            border-color: var(--wa-border);
            background-color: white;
            color: var(--wa-text-dark) !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .navbar.scrolled .lang-dropdown .nav-link:hover {
            background-color: #f1f5f9;
        }

        /* Logo Icon */
        .logo-mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
        }
        .logo-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .navbar.scrolled .logo-mark {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(16, 185, 129, 0.12));
            border-color: rgba(59, 130, 246, 0.14);
        }

        /* Latar Belakang Abstrak Hero */
        .hero-section {
            position: relative;
            height: 100vh;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .content-relative { z-index: 10; position: relative; }

        /* Pengaturan Gambar Slider */
        .hero-carousel-img {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        /* Bayangan Teks */
        .text-shadow-custom {
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
        }

        /* Tombol Kustom */
        .btn-wa-primary {
            background-color: var(--wa-text-dark);
            color: white;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 14px 0 rgba(15, 23, 42, 0.39);
        }
        .btn-wa-primary:hover {
            background-color: #334155; /* Slate 700 */
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.23);
        }
        .btn-wa-outline {
            background-color: transparent;
            color: var(--wa-text-dark);
            border: 1px solid var(--wa-border);
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-wa-outline:hover {
            border-color: var(--wa-text-dark);
            color: var(--wa-text-dark);
            background-color: rgba(0,0,0,0.03);
        }

        /* Kartu Layanan */
        .wa-card {
            background-color: var(--wa-card-bg);
            border: 1px solid var(--wa-border);
            border-radius: 1rem;
            padding: 2rem;
            height: 100%;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        .wa-card:hover {
            transform: translateY(-8px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .wa-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(to right, var(--wa-blue), var(--wa-emerald));
            transform: scaleX(0); transform-origin: left; transition: transform 0.4s ease;
        }
        .wa-card:hover::before { transform: scaleX(1); }

        /* Kartu Portofolio */
        .portfolio-card {
            border-radius: 1rem;
            overflow: hidden;
            background-color: var(--wa-card-bg);
            border: 1px solid var(--wa-border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .portfolio-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .portfolio-img-container {
            height: 250px;
            overflow: hidden;
            position: relative;
        }
        .portfolio-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }
        .portfolio-card:hover .portfolio-img-container img {
            transform: scale(1.08);
        }
        .portfolio-badge {
            position: absolute;
            top: 15px; left: 15px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--wa-text-dark);
            z-index: 2;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .portfolio-link-trigger {
            border: 0;
            background: transparent;
            color: var(--wa-blue);
            font-weight: 700;
            transition: transform 0.2s ease, color 0.2s ease;
        }
        .portfolio-link-trigger:hover {
            color: #1d4ed8;
            transform: translateX(2px);
        }

        /* Modal Semua Proyek */
        .projects-modal .modal-dialog {
            max-width: min(1240px, calc(100vw - 1.5rem));
        }
        .projects-modal .modal-content {
            border: none;
            border-radius: 2rem;
            overflow: hidden;
            background: linear-gradient(180deg, #0f172a 0%, #132238 42%, #f8fafc 42%, #f8fafc 100%);
            box-shadow: 0 35px 90px rgba(15, 23, 42, 0.32);
        }
        .projects-modal .modal-body {
            padding: 0;
        }
        .projects-modal-hero {
            position: relative;
            padding: 2rem;
            color: #ffffff;
            overflow: hidden;
        }
        .projects-modal-hero::before,
        .projects-modal-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(10px);
            opacity: 0.5;
        }
        .projects-modal-hero::before {
            width: 260px;
            height: 260px;
            top: -90px;
            right: -60px;
            background: rgba(59, 130, 246, 0.28);
        }
        .projects-modal-hero::after {
            width: 200px;
            height: 200px;
            bottom: -80px;
            left: -40px;
            background: rgba(16, 185, 129, 0.2);
        }
        .projects-modal-close {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 2;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            backdrop-filter: blur(10px);
        }
        .projects-modal-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(10px);
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .projects-modal-lead {
            max-width: 680px;
            color: rgba(255, 255, 255, 0.76);
        }
        .projects-modal-stat {
            height: 100%;
            padding: 1.4rem 1.5rem;
            border-radius: 1.4rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(12px);
        }
        .projects-modal-stat-value {
            font-size: clamp(2rem, 3vw, 2.8rem);
            font-weight: 800;
            line-height: 1;
        }
        .projects-modal-stat-label {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.95rem;
        }
        .projects-featured-card {
            position: relative;
            z-index: 1;
            margin-top: 2rem;
            border-radius: 1.75rem;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(14px);
        }
        .projects-featured-media {
            min-height: 100%;
            background: rgba(255, 255, 255, 0.06);
        }
        .projects-featured-media img {
            width: 100%;
            height: 100%;
            min-height: 260px;
            object-fit: cover;
        }
        .projects-featured-copy {
            padding: 2rem;
        }
        .projects-featured-label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .projects-modal-grid {
            padding: 0 2rem 2rem;
        }
        .projects-modal-card {
            height: 100%;
            border: 1px solid var(--wa-border);
            border-radius: 1.5rem;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 18px 35px rgba(15, 23, 42, 0.08);
            transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
        }
        .projects-modal-card:hover {
            transform: translateY(-6px);
            border-color: rgba(59, 130, 246, 0.22);
            box-shadow: 0 26px 45px rgba(15, 23, 42, 0.14);
        }
        .projects-modal-card-image {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: #e2e8f0;
        }
        .projects-modal-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .projects-modal-card:hover .projects-modal-card-image img {
            transform: scale(1.06);
        }
        .projects-modal-card-index {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.78);
            color: #ffffff;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }
        .projects-modal-card-body {
            padding: 1.4rem;
        }
        .projects-modal-card-text {
            min-height: 3rem;
        }
        .projects-modal-card-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.08);
            color: var(--wa-blue);
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
        }
        .projects-modal-card-action-muted {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1rem;
            border-radius: 999px;
            background: #f8fafc;
            color: var(--wa-text-muted);
            font-size: 0.9rem;
            font-weight: 700;
        }
        @media (max-width: 767.98px) {
            .projects-modal .modal-dialog {
                max-width: calc(100vw - 1rem);
                margin: 0.5rem auto;
            }
            .projects-modal-hero,
            .projects-modal-grid {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .projects-featured-copy {
                padding: 1.5rem;
            }
        }

        /* Ikon Lingkaran */
        .icon-circle {
            width: 50px; height: 50px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            background-color: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        /* Form Kustom */
        .form-control {
            background-color: #ffffff;
            border: 1px solid var(--wa-border);
            color: var(--wa-text-dark);
            padding: 0.8rem 1.2rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .form-control:focus {
            background-color: #ffffff;
            color: var(--wa-text-dark);
            border-color: var(--wa-blue);
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
        }
        
        .text-wa-muted { color: var(--wa-text-muted); }
        .bg-wa-card { background-color: var(--wa-card-bg); }
        .section-padding { padding-top: 100px; padding-bottom: 100px; }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#navbarNav" data-bs-root-margin="0px 0px -40%" tabindex="0">

    <!-- Navigation -->
    <nav id="mainNavbar" class="navbar navbar-expand-lg fixed-top" data-aos="fade-down" data-aos-duration="1000">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#home">
                <span class="logo-mark">
                    <img src="{{ asset('logo.png') }}" alt="Logo Warastra Adhiguna">
                </span>
                <span class="fw-bold fs-5 tracking-wide logo-text">Warastra <span class="text-primary">Adhiguna</span></span>
            </a>
            <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="#home" data-i18n="navHome">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about" data-i18n="navAbout">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services" data-i18n="navServices">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#portfolio" data-i18n="navPortfolio">Portfolio</a></li>
                    <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                        <a href="#contact" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-i18n="navContact">Contact Us</a>
                    </li>
                    <!-- Language Selector -->
                    <li class="nav-item dropdown ms-lg-3 mt-3 mt-lg-0 lang-dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-globe"></i> <span id="currentLangDisplay" class="fw-bold">EN</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="changeLanguage('en'); return false;">English</a></li>
                            <li><a class="dropdown-item" href="#" onclick="changeLanguage('id'); return false;">Indonesia</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        
        <!-- Background Slider (Carousel) -->
        <div id="heroSlider" class="carousel slide carousel-fade position-absolute top-0 start-0 w-100 h-100" data-bs-ride="carousel" data-bs-pause="false" data-bs-interval="4000" style="z-index: 0;">
            <div class="carousel-inner w-100 h-100">
                <div class="carousel-item active w-100 h-100">
                    <img src="{{ $content['hero_slide_1_url'] }}" class="hero-carousel-img" alt="Hero slide 1">
                </div>
                <div class="carousel-item w-100 h-100">
                    <img src="{{ $content['hero_slide_2_url'] }}" class="hero-carousel-img" alt="Hero slide 2">
                </div>
                <div class="carousel-item w-100 h-100">
                    <img src="{{ $content['hero_slide_3_url'] }}" class="hero-carousel-img" alt="Hero slide 3">
                </div>
            </div>
            <!-- Lapisan gelap tipis agar teks putih sangat terbaca (Opacity 50%) -->
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: rgba(15, 23, 42, 0.5); z-index: 1;"></div>
        </div>

        <div class="container position-relative text-center" style="z-index: 2;">
            <!-- Teks Langsung di Atas Gambar (Tanpa Kotak) -->
            <div class="mx-auto mt-5" style="max-width: 850px;">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px);" data-aos="zoom-in" data-aos-delay="200">
                    <span class="spinner-grow spinner-grow-sm text-info" role="status"></span>
                    <span class="text-white small fw-bold" data-i18n="heroBadge">{{ $content['hero_badge_en'] }}</span>
                </div>
                
                <h1 class="display-3 fw-bolder mb-4 lh-sm text-shadow-custom" data-aos="fade-up" data-aos-delay="400">
                    <!-- Digitally Customize dibuat PUTIH MURNI (tanpa class shimmer) -->
                    <span class="text-white" data-i18n="heroTitle">{{ $content['hero_title_en'] }}</span> <br class="d-none d-md-block">
                    <span class="text-shimmer-gradient" data-i18n="heroTitleHighlight">{{ $content['hero_title_highlight_en'] }}</span>
                </h1>
                
                <!-- Deskripsi diperkecil dan ditipiskan (Hapus class lead & fw-medium) -->
                <p class="text-white mx-auto mb-5 text-shadow-custom opacity-75" style="max-width: 700px; line-height: 1.6; font-size: 1.05rem;" data-i18n="heroDesc" data-aos="fade-up" data-aos-delay="600">
                    {{ $content['hero_description_en'] }}
                </p>
                
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3" data-aos="fade-up" data-aos-delay="800">
                    <a href="#services" class="btn btn-primary rounded-pill px-4 py-3 fw-bold border-0 d-flex align-items-center justify-content-center shadow" style="background: linear-gradient(to right, #3b82f6, #10b981);">
                        <span data-i18n="heroBtnServices">{{ $content['hero_primary_button_en'] }}</span> <i class="bi bi-chevron-right ms-2"></i>
                    </a>
                    <a href="#portfolio" class="btn btn-outline-light rounded-pill px-4 py-3 fw-bold d-flex align-items-center justify-content-center" data-i18n="heroBtnPortfolio">
                        {{ $content['hero_secondary_button_en'] }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section-padding bg-white border-top border-bottom border-secondary border-opacity-10">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-6 pe-lg-5">
                    <div data-aos="fade-right">
                        <p class="text-primary text-uppercase fw-bold letter-spacing small mb-2" data-i18n="aboutTag">{{ $content['about_tag_en'] }}</p>
                        <h2 class="display-6 fw-bold text-wa-dark mb-4" data-i18n="aboutTitle">{{ $content['about_title_en'] }}</h2>
                        <p class="text-wa-muted mb-5 lh-lg" data-i18n="aboutDesc">
                            {{ $content['about_description_en'] }}
                        </p>
                    </div>
                    
                    <div class="d-flex mb-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="icon-circle me-3 flex-shrink-0">
                            <i class="bi bi-bullseye text-primary fs-5"></i>
                        </div>
                        <div>
                            <h5 class="text-wa-dark fw-bold mb-1" data-i18n="aboutPhilTitle">{{ $content['about_philosophy_title_en'] }}</h5>
                            <p class="text-wa-muted small lh-lg mb-0" data-i18n="aboutPhilDesc">{!! $content['about_philosophy_description_en'] !!}</p>
                        </div>
                    </div>
                    
                    <div class="d-flex" data-aos="fade-up" data-aos-delay="400">
                        <div class="icon-circle me-3 flex-shrink-0" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2);">
                            <i class="bi bi-people text-success fs-5"></i>
                        </div>
                        <div>
                            <h5 class="text-wa-dark fw-bold mb-1" data-i18n="aboutWaTitle">{{ $content['about_meaning_title_en'] }}</h5>
                            <p class="text-wa-muted small lh-lg mb-0" data-i18n="aboutWaDesc">{!! $content['about_meaning_description_en'] !!}</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                    <div class="position-relative">
                        <div class="position-absolute w-100 h-100 rounded-4" style="background: linear-gradient(to top right, var(--wa-blue), var(--wa-emerald)); transform: rotate(3deg); opacity: 0.1; filter: blur(20px);"></div>
                        <img src="{{ $content['about_image_url'] }}" alt="Tentang Warastra Adhiguna" class="img-fluid rounded-4 position-relative border border-white shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section-padding">
        <div class="container">
            <div class="text-center mb-5 mx-auto" style="max-width: 700px;" data-aos="fade-down">
                <p class="text-primary text-uppercase fw-bold small mb-2" data-i18n="servicesTag">{{ $content['services_tag_en'] }}</p>
                <h2 class="display-6 fw-bold text-wa-dark mb-3" data-i18n="servicesTitle">{{ $content['services_title_en'] }}</h2>
                <p class="text-wa-muted" data-i18n="servicesDesc">{{ $content['services_description_en'] }}</p>
            </div>

            <div class="row g-4">
                @foreach ($services as $service)
                    <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="{{ $loop->iteration * 100 }}">
                        <div class="wa-card">
                            <i class="{{ $service['icon_class'] }} fs-1 mb-3 d-block" style="color: {{ $service['icon_color'] }};"></i>
                            <h5 class="text-wa-dark fw-bold mb-3" data-i18n="srv{{ $loop->iteration }}Title">{{ $service['title_en'] }}</h5>
                            <p class="text-wa-muted small mb-0" data-i18n="srv{{ $loop->iteration }}Desc">{{ $service['description_en'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="section-padding bg-white border-top border-bottom border-secondary border-opacity-10">
        <div class="container-fluid px-4 px-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
                <div data-aos="fade-right">
                    <p class="text-primary text-uppercase fw-bold small mb-2" data-i18n="portTag">{{ $content['portfolio_tag_en'] }}</p>
                    <h2 class="display-6 fw-bold text-wa-dark mb-0" data-i18n="portTitle">{{ $content['portfolio_title_en'] }}</h2>
                </div>
                <button type="button" class="portfolio-link-trigger fw-bold small mt-3 mt-md-0 d-inline-flex align-items-center gap-2" data-aos="fade-left" data-bs-toggle="modal" data-bs-target="#allProjectsModal">
                    <span data-i18n="portLink">{{ $content['portfolio_link_label_en'] }}</span> <i class="bi bi-box-arrow-up-right"></i>
                </button>
            </div>

            <div class="row g-4">
                @foreach ($featuredPortfolioItems as $portfolioItem)
                    <div class="col-md-6 col-xl-4" data-aos="zoom-in-up" data-aos-delay="{{ $loop->iteration * 100 }}">
                        <div class="portfolio-card h-100">
                            <div class="portfolio-img-container">
                                <span class="portfolio-badge" data-i18n="port{{ $loop->iteration }}Badge">{{ $portfolioItem['badge_en'] }}</span>
                                <div class="position-absolute w-100 h-100 z-1" style="background: rgba(0, 0, 0, 0.05);"></div>
                                <img src="{{ $portfolioItem['image_src'] }}" alt="{{ $portfolioItem['title_en'] }}">
                            </div>
                            <div class="p-4">
                                <h5 class="text-wa-dark fw-bold mb-1" data-i18n="port{{ $loop->iteration }}Title">{{ $portfolioItem['title_en'] }}</h5>
                                <p class="text-wa-muted small mb-0" data-i18n="port{{ $loop->iteration }}Subtitle">{{ $portfolioItem['subtitle_en'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @php($modalFeaturedProject = $portfolioItems[0] ?? null)

    <div class="modal fade projects-modal" id="allProjectsModal" tabindex="-1" aria-labelledby="allProjectsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="projects-modal-hero">
                        <button type="button" class="projects-modal-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div class="row g-4 align-items-end position-relative">
                            <div class="col-lg-7">
                                <span class="projects-modal-pill mb-4">
                                    <i class="bi bi-stars"></i>
                                    <span data-i18n="portTag">{{ $content['portfolio_tag_en'] }}</span>
                                </span>
                                <h2 id="allProjectsModalLabel" class="display-5 fw-bold mb-3" data-i18n="projectsModalTitle">All Our Projects</h2>
                                <p class="projects-modal-lead mb-0" data-i18n="projectsModalDesc">
                                    Explore Warastra Adhiguna digital work across dashboards, web platforms, and custom business solutions built around client needs.
                                </p>
                            </div>
                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="projects-modal-stat">
                                            <div class="projects-modal-stat-value">{{ $portfolioItemsCount }}</div>
                                            <div class="projects-modal-stat-label" data-i18n="projectsModalCountLabel">Active projects</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="projects-modal-stat">
                                            <div class="projects-modal-stat-value">{{ $portfolioCategoriesCount }}</div>
                                            <div class="projects-modal-stat-label" data-i18n="projectsModalCategoryLabel">Work categories</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($modalFeaturedProject)
                            <div class="projects-featured-card">
                                <div class="row g-0 align-items-stretch">
                                    <div class="col-lg-5 projects-featured-media">
                                        <img src="{{ $modalFeaturedProject['image_src'] }}" alt="{{ $modalFeaturedProject['title_en'] }}">
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="projects-featured-copy h-100 d-flex flex-column justify-content-center">
                                            <span class="projects-featured-label mb-3">
                                                <i class="bi bi-gem"></i>
                                                <span data-i18n="projectsModalFeatured">Featured highlight</span>
                                            </span>
                                            <span class="d-inline-flex align-items-center gap-2 rounded-pill px-3 py-2 mb-3 align-self-start" style="background: rgba(255, 255, 255, 0.12);">
                                                <span class="small fw-bold" data-i18n="port1Badge">{{ $modalFeaturedProject['badge_en'] }}</span>
                                            </span>
                                            <h3 class="fw-bold mb-2" data-i18n="port1Title">{{ $modalFeaturedProject['title_en'] }}</h3>
                                            <p class="mb-0 text-white-50" data-i18n="port1Subtitle">{{ $modalFeaturedProject['subtitle_en'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="projects-modal-grid">
                        <div class="row g-4">
                            @foreach ($portfolioItems as $portfolioItem)
                                <div class="col-md-6 col-xl-4">
                                    <article class="projects-modal-card">
                                        <div class="projects-modal-card-image">
                                            <span class="portfolio-badge" data-i18n="port{{ $loop->iteration }}Badge">{{ $portfolioItem['badge_en'] }}</span>
                                            <img src="{{ $portfolioItem['image_src'] }}" alt="{{ $portfolioItem['title_en'] }}">
                                            <span class="projects-modal-card-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        </div>
                                        <div class="projects-modal-card-body">
                                            <h5 class="text-wa-dark fw-bold mb-2" data-i18n="port{{ $loop->iteration }}Title">{{ $portfolioItem['title_en'] }}</h5>
                                            <p class="text-wa-muted mb-4 projects-modal-card-text" data-i18n="port{{ $loop->iteration }}Subtitle">{{ $portfolioItem['subtitle_en'] }}</p>

                                            @if (filled($portfolioItem['project_url']))
                                                <a href="{{ $portfolioItem['project_url'] }}" target="_blank" rel="noreferrer" class="projects-modal-card-action">
                                                    <span data-i18n="projectsModalVisit">Visit project</span>
                                                    <i class="bi bi-arrow-up-right"></i>
                                                </a>
                                            @else
                                                <span class="projects-modal-card-action-muted">
                                                    <i class="bi bi-grid-3x3-gap"></i>
                                                    <span data-i18n="projectsModalShowcase">Company showcase</span>
                                                </span>
                                            @endif
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Section -->
    <section id="contact" class="section-padding">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="display-6 fw-bold text-wa-dark mb-4" data-i18n="contactTitle">{{ $content['contact_title_en'] }}</h2>
                    <p class="text-wa-muted mb-5" data-i18n="contactDesc">
                        {{ $content['contact_description_en'] }}
                    </p>
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-circle me-3 flex-shrink-0 bg-white" style="box-shadow: 0 2px 10px rgba(59,130,246,0.1);">
                            <i class="bi bi-instagram text-primary"></i>
                        </div>
                        <div>
                            <p class="small text-wa-muted mb-0" data-i18n="contactPhone">{{ $content['contact_phone_label_en'] }}</p>
                            <p class="fw-bold text-wa-dark mb-0">
                                <a href="{{ $content['contact_instagram_url'] }}" class="text-wa-dark text-decoration-none" target="_blank" rel="noreferrer">
                                    {{ $content['contact_instagram_display'] }}
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-circle me-3 flex-shrink-0 bg-white" style="box-shadow: 0 2px 10px rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.2);">
                            <i class="bi bi-envelope text-success"></i>
                        </div>
                        <div>
                            <p class="small text-wa-muted mb-0" data-i18n="contactEmail">{{ $content['contact_email_label_en'] }}</p>
                            <p class="fw-bold text-wa-dark mb-0">{{ $content['contact_email'] }}</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center">
                        <div class="icon-circle me-3 flex-shrink-0 bg-white" style="box-shadow: 0 2px 10px rgba(139,92,246,0.1); border-color: rgba(139,92,246,0.2);">
                            <i class="bi bi-geo-alt" style="color: #8b5cf6;"></i>
                        </div>
                        <div>
                            <p class="small text-wa-muted mb-0" data-i18n="contactLocation">{{ $content['contact_location_label_en'] }}</p>
                            <p class="fw-bold text-wa-dark mb-0" data-i18n="contactLocationVal">{{ $content['contact_location_value_en'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="flip-left" data-aos-delay="200">
                    <div class="bg-white p-4 p-md-5 rounded-4 border position-relative overflow-hidden shadow-sm" style="border-color: var(--wa-border);">
                        <!-- Decorative element -->
                        <div class="position-absolute top-0 end-0" style="width: 200px; height: 200px; background: rgba(59, 130, 246, 0.05); filter: blur(40px); border-radius: 50%;"></div>
                        
                        @php($contactFormToken = \Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp))

                        <form method="POST" action="{{ route('contact-messages.store') }}" class="position-relative z-1" novalidate>
                            @csrf

                            @if (session('contact_message_status') === 'sent')
                                <div class="alert alert-success border-0 rounded-4 mb-4" style="background: rgba(16, 185, 129, 0.12); color: #065f46;">
                                    <span data-i18n="contactFormSuccess">Your message has been sent successfully. We will contact you soon.</span>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger border-0 rounded-4 mb-4" style="background: rgba(239, 68, 68, 0.12); color: #991b1b;">
                                    <span data-i18n="contactFormError">Please review the form below.</span>
                                </div>
                            @endif

                            <div class="position-absolute start-0 top-0 opacity-0 pe-none" aria-hidden="true">
                                <label for="website">Website</label>
                                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
                            </div>

                            <input type="hidden" name="form_token" value="{{ $contactFormToken }}">

                            <div class="mb-3">
                                <label class="form-label text-wa-dark small fw-bold" data-i18n="formName">{{ $content['form_name_label_en'] }}</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    data-i18n-placeholder="formNamePh"
                                    placeholder="{{ $content['form_name_placeholder_en'] }}"
                                    value="{{ old('name') }}"
                                    maxlength="120"
                                    minlength="3"
                                    autocomplete="name"
                                    required
                                >
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-wa-dark small fw-bold" data-i18n="formEmail">{{ $content['form_email_label_en'] }}</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    data-i18n-placeholder="formEmailPh"
                                    placeholder="{{ $content['form_email_placeholder_en'] }}"
                                    value="{{ old('email') }}"
                                    maxlength="120"
                                    autocomplete="email"
                                    inputmode="email"
                                    required
                                >
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-wa-dark small fw-bold" data-i18n="formMsg">{{ $content['form_message_label_en'] }}</label>
                                <textarea
                                    name="message"
                                    class="form-control @error('message') is-invalid @enderror"
                                    rows="4"
                                    data-i18n-placeholder="formMsgPh"
                                    placeholder="{{ $content['form_message_placeholder_en'] }}"
                                    maxlength="3000"
                                    minlength="10"
                                    required
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('form_token')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('website')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-sm" style="background: linear-gradient(to right, var(--wa-blue), var(--wa-emerald)); border: none;" data-i18n="formBtn">
                                {{ $content['form_button_en'] }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 text-center border-top" style="border-color: var(--wa-border) !important; background-color: #f1f5f9;">
        <div class="container">
            <p class="text-wa-muted small mb-0">
                &copy; {{ now()->year }} Warastra Adhiguna. <span data-i18n="footerRights">{{ $content['footer_rights_en'] }}</span> <br>
                <span class="fw-medium text-wa-dark" data-i18n="footerTagline">{{ $content['footer_tagline_en'] }}</span>
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JS: Translation & Animation Logic -->
    <script>
        // Inisialisasi AOS (Animate On Scroll)
        AOS.init({
            duration: 800,     // Durasi animasi 0.8 detik
            once: true,        // Animasi hanya berjalan 1 kali (tidak berulang saat scroll naik)
            offset: 50         // Jarak mulai animasi saat elemen mendekati layar
        });

        // Efek transparan ke putih pada Navbar saat di-scroll
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNavbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Tutup otomatis menu mobile (HP) saat link diklik
        document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle), .navbar .btn').forEach(link => {
            link.addEventListener('click', () => {
                const navbarCollapse = document.getElementById('navbarNav');
                if (navbarCollapse.classList.contains('show')) {
                    document.querySelector('.navbar-toggler').click();
                }
            });
        });

        // --- KAMUS TERJEMAHAN (DICTIONARY) ---
        const translations = @json($translations);

        // Logika untuk mengubah bahasa
        function changeLanguage(lang) {
            // Update teks biasa
            document.querySelectorAll('[data-i18n]').forEach(element => {
                const key = element.getAttribute('data-i18n');
                if (translations[lang] && translations[lang][key]) {
                    element.innerHTML = translations[lang][key]; // Menggunakan innerHTML agar tag <strong> dll berfungsi
                }
            });

            // Update placeholder form
            document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
                const key = element.getAttribute('data-i18n-placeholder');
                if (translations[lang] && translations[lang][key]) {
                    element.setAttribute('placeholder', translations[lang][key]);
                }
            });

            // Update tampilan bahasa saat ini di navbar
            document.getElementById('currentLangDisplay').innerText = lang.toUpperCase();

            // Simpan preferensi bahasa pengguna (opsional)
            localStorage.setItem('wa_preferred_lang', lang);
        }

        // Jalankan bahasa awal sesuai preferensi atau default ke 'en'
        document.addEventListener('DOMContentLoaded', () => {
            const savedLang = localStorage.getItem('wa_preferred_lang') || 'en';
            changeLanguage(savedLang);
        });
    </script>
</body>
</html>

