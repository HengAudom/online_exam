<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Page Title -->
    <title>OnlineXam - Online Examination System</title>

    <!-- Primary Meta Tags -->
    <meta name="title" content="OnlineXam - Online Examination System">
    <meta name="description" content="OnlineXam - ប្រព័ន្ធគ្រប់គ្រងការប្រឡងអនឡាញ រៀបចំការប្រឡង វាយតម្លៃលទ្ធផលសិស្ស និងគ្រប់គ្រងទិន្នន័យប្រឡងដោយសុវត្ថិភាព។">
    <meta name="keywords" content="OnlineXam, onlinexam, Online Examination System, ប្រព័ន្ធប្រឡងអនឡាញ">
    <meta name="author" content="Heng Audom (ហេង ឧត្តម)">
    <meta name="robots" content="index, follow">
    @php
        $currentPath = request()->path();
        $canonicalUrl = ($currentPath === '/' || $currentPath === '') 
            ? 'https://onlinexam.site/' 
            : ($currentPath === 'login' ? 'https://onlinexam.site/login' : url()->current());
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => 'OnlineXam',
            'url' => 'https://onlinexam.site',
            'applicationCategory' => 'EducationalApplication',
            'image' => 'https://onlinexam.site/pwa-512.png',
            'logo' => 'https://onlinexam.site/pwa-512.png',
            'author' => [
                '@type' => 'Person',
                'name' => 'Heng Audom',
                'alternateName' => 'ហេង ឧត្តម',
                'sameAs' => [
                    'https://www.facebook.com/may.dom.bon.1502',
                    'https://t.me/DomAi1'
                ]
            ]
        ];
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <!-- JSON-LD Structured Data for Google Indexing -->
    <script type="application/ld+json">
    {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://onlinexam.site/login">
    <meta property="og:title" content="OnlineXam - Online Examination System">
    <meta property="og:description" content="OnlineXam - ប្រព័ន្ធគ្រប់គ្រងការប្រឡងអនឡាញ រៀបចំការប្រឡង វាយតម្លៃលទ្ធផលសិស្ស និងគ្រប់គ្រងទិន្នន័យប្រឡងដោយសុវត្ថិភាព។">
    <meta property="og:image" content="https://onlinexam.site/pwa-512.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:site_name" content="OnlineXam">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://onlinexam.site/login">
    <meta property="twitter:title" content="OnlineXam - Online Examination System">
    <meta property="twitter:description" content="OnlineXam - ប្រព័ន្ធគ្រប់គ្រងការប្រឡងអនឡាញ រៀបចំការប្រឡង វាយតម្លៃលទ្ធផលសិស្ស និងគ្រប់គ្រងទិន្នន័យប្រឡងដោយសុវត្ថិភាព។">
    <meta property="twitter:image" content="https://onlinexam.site/pwa-512.png">

    <!-- Google Fonts: Manrope + Inter + Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" />

    <!-- Favicon & App Icons (Google Search & Multi-device compliant) -->
    <link rel="icon" type="image/x-icon" href="https://onlinexam.site/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="https://onlinexam.site/favicon.ico">
    <link rel="icon" type="image/png" sizes="48x48" href="https://onlinexam.site/favicon-48x48.png">
    <link rel="icon" type="image/png" sizes="96x96" href="https://onlinexam.site/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="192x192" href="https://onlinexam.site/favicon-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://onlinexam.site/apple-touch-icon.png">
    <link rel="icon" type="image/svg+xml" href="https://onlinexam.site/ico.svg">
    <link rel="manifest" href="/manifest.json">
    <link rel="alternate" type="application/manifest+json" href="/manifest.webmanifest">
    <meta name="theme-color" content="#00288e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="OnlineXam">
    <meta name="application-name" content="OnlineXam">

    <!-- Instant Standalone State & PWA Event Detection -->
    <script>
        window.__pwa_deferred_prompt = null;
        window.__pwa_is_standalone = false;

        try {
            window.__pwa_is_standalone = window.matchMedia('(display-mode: standalone)').matches ||
                                         window.navigator.standalone === true ||
                                         document.referrer.includes('android-app://') ||
                                         window.location.search.includes('source=pwa');
            if (window.__pwa_is_standalone) {
                document.documentElement.classList.add('is-pwa-standalone');
            }
        } catch (e) {}

        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            window.__pwa_deferred_prompt = e;
            window.dispatchEvent(new CustomEvent('pwa-prompt-ready', { detail: e }));
        });

        window.addEventListener('appinstalled', function() {
            window.__pwa_deferred_prompt = null;
            window.__pwa_is_standalone = true;
            window.dispatchEvent(new CustomEvent('pwa-installed'));
        });

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function(reg) {
                    window.__pwa_sw_reg = reg;
                })
                .catch(function(err) {
                    console.warn('[PWA] SW register error:', err);
                });
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <!-- Initial Startup Splash Screen (ONLY shown in standalone installed App, hidden in Web Browsers) -->
    <style>
            #splash-screen {
                display: none;
                position: fixed;
                inset: 0;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: radial-gradient(circle at 50% 40%, #0037c2 0%, #00288e 70%, #001f70 100%);
                color: #ffffff;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                z-index: 99999;
                user-select: none;
                -webkit-user-select: none;
            }
            html.is-pwa-standalone #splash-screen {
                display: flex;
            }
            .splash-logo-box {
                position: relative;
                width: 92px;
                height: 92px;
                border-radius: 26px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.25);
                box-shadow: 0 20px 40px -10px rgba(0, 10, 50, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                animation: splashPulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
            .splash-logo-box img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3));
            }
            .splash-glow {
                position: absolute;
                width: 140px;
                height: 140px;
                background: radial-gradient(circle, rgba(56, 189, 248, 0.4) 0%, rgba(0, 40, 142, 0) 70%);
                border-radius: 50%;
                filter: blur(25px);
                pointer-events: none;
            }
            .splash-title {
                margin-top: 22px;
                font-size: 18px;
                font-weight: 800;
                letter-spacing: -0.02em;
                color: #ffffff;
                text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            }
            .splash-subtitle {
                margin-top: 4px;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #93c5fd;
            }
            .splash-loader {
                margin-top: 28px;
                width: 48px;
                height: 4px;
                background: rgba(255, 255, 255, 0.15);
                border-radius: 999px;
                overflow: hidden;
                position: relative;
            }
            .splash-loader::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                width: 40%;
                background: #38bdf8;
                border-radius: 999px;
                animation: splashSlide 1.4s ease-in-out infinite;
            }
            @keyframes splashPulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.04); }
            }
            #splash-screen.fade-out {
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.35s ease-out;
            }
        </style>
        <div id="splash-screen">
            <div class="splash-glow"></div>
            <div class="splash-logo-box">
                <img src="{{ asset('pwa-192.png') }}?v=4" alt="Logo" />
            </div>
            <h1 class="splash-title">{{ \App\Http\Controllers\AdminController::getSystemSettings()['institutionName'] ?? 'OnlineXam' }}</h1>
            <p class="splash-subtitle">Assessment Portal</p>
            <div class="splash-loader"></div>
        </div>
        <div id="app"></div>
</body>
</html>