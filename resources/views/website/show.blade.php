<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['site_title'] ?: ($texts['seo_title'] ?: ($general['site_name'] ?? 'DressnMore')) }}</title>
    <meta name="description" content="{{ $seo['meta_description'] ?: ($texts['seo_description'] ?? '') }}">
    @if(!empty($favicon_url))
        <link rel="icon" href="{{ $favicon_url }}">
    @endif
    @php
        $canonical = $seo['canonical_url'] ?? null;
        if (! $canonical && ! empty($public_url)) {
            $canonical = rtrim((string) $public_url, '/').($path === '/' ? '' : $path);
        }
        $ogTitle = ($sharing['og_title'] ?? null) ?: ($seo['site_title'] ?? null) ?: ($general['site_name'] ?? 'DressnMore');
        $ogDescription = ($sharing['og_description'] ?? null) ?: ($seo['meta_description'] ?? null) ?: ($texts['seo_description'] ?? '');
        $robots = $seo['robots'] ?? 'index,follow';
        $metaPixel = trim((string) ($pixels['meta_pixel'] ?? ''));
        $gaId = trim((string) ($pixels['ga_id'] ?? ''));
        $gtmId = trim((string) ($pixels['gtm_id'] ?? ''));
    @endphp
    @if(!empty($canonical))
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:url" content="{{ $canonical }}">
    @endif
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    @if(!empty($robots))
        <meta name="robots" content="{{ $robots }}">
    @endif
    @if($gtmId !== '')
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($gtmId));</script>
    @endif
    @if($metaPixel !== '')
        <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($metaPixel));
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ urlencode($metaPixel) }}&ev=PageView&noscript=1"
            alt=""></noscript>
    @endif
    @if($gaId !== '')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($gaId));
        </script>
    @endif
    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@400;500;600;700;800&family=Cinzel:wght@500;700&family=Cormorant+Garamond:wght@500;600;700&family=Great+Vibes&family=Oswald:wght@500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    @php
        $layoutKey = $theme['layout'] ?? match ((string) ($template['id'] ?? '')) {
            'fashion-editorial' => 'editorial',
            'business-minimal', 'pearl-atelier' => 'minimal',
            'midnight-glam' => 'dark',
            'atelier-studio', 'bridal-classic', 'fashion-boutique', 'rental-glow' => 'split',
            default => 'centered',
        };
        $layoutClass = 'layout-' . preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $layoutKey));
    @endphp
    <style>
        :root {
            --primary: {{ $branding['primary_color'] ?? '#0C1A3E' }};
            --secondary: {{ $branding['secondary_color'] ?? '#1E3A7B' }};
            --accent: {{ $branding['accent_color'] ?? '#C2964A' }};
            --bg: {{ $branding['background_color'] ?? $theme['surface'] }};
            --text: {{ $branding['text_color'] ?? '#0F172A' }};
            --surface: {{ $theme['surface'] }};
            --card: {{ $theme['card'] }};
            --radius: {{ $theme['radius'] }};
            --font-display: {{ $theme['font_display'] }};
            --font-body: {{ $theme['font_body'] }};
            --hero-gradient: {{ $theme['hero_gradient'] }};
            --hero-overlay: {{ $theme['hero_overlay'] }};
            --pattern: {{ $theme['pattern'] }};
            --ink-soft: color-mix(in srgb, var(--text) 68%, transparent);
            --line: color-mix(in srgb, var(--primary) 12%, transparent);
            --shadow-soft: 0 18px 50px color-mix(in srgb, var(--primary) 12%, transparent);
            --shadow-lift: 0 28px 60px color-mix(in srgb, var(--primary) 18%, transparent);
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: var(--font-body);
            color: var(--text);
            background: var(--surface);
            line-height: 1.75;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1180px, calc(100% - 2.5rem)); margin-inline: auto; }
        .wrap-narrow { width: min(720px, calc(100% - 2.5rem)); margin-inline: auto; }

        /* —— Motion —— */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes softFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(2deg); }
        }
        @keyframes grainShift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-8%, -6%); }
        }
        .reveal {
            animation: fadeUp 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .reveal-delay-1 { animation-delay: 0.12s; }
        .reveal-delay-2 { animation-delay: 0.24s; }
        .reveal-delay-3 { animation-delay: 0.36s; }
        @media (prefers-reduced-motion: reduce) {
            .reveal, .hero-ornament, .hero-grain { animation: none !important; }
        }

        /* —— Header —— */
        .site-header {
            position: sticky; top: 0; z-index: 50;
            backdrop-filter: blur(16px) saturate(1.2);
            background: color-mix(in srgb, var(--card) 82%, transparent);
            border-bottom: 1px solid var(--line);
        }
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; padding: 0.95rem 0;
        }
        .brand { display: flex; align-items: center; gap: 0.8rem; font-weight: 800; min-width: 0; }
        .brand img {
            width: 44px; height: 44px; object-fit: cover;
            border-radius: calc(var(--radius) * 0.55);
            box-shadow: 0 4px 14px color-mix(in srgb, var(--primary) 20%, transparent);
        }
        .brand-name {
            font-family: var(--font-display);
            font-size: 1.28rem;
            letter-spacing: 0.01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .menu {
            display: flex; flex-wrap: wrap; gap: 0.25rem 1.35rem;
            font-size: 0.92rem; font-weight: 600;
        }
        .menu a {
            position: relative;
            opacity: 0.82;
            transition: opacity 0.25s ease, color 0.25s ease;
        }
        .menu a:hover { opacity: 1; color: var(--accent); }
        .menu a::after {
            content: "";
            position: absolute; inset-inline: 0; bottom: -4px;
            height: 1px; background: var(--accent);
            transform: scaleX(0); transform-origin: right;
            transition: transform 0.3s ease;
        }
        .menu a:hover::after { transform: scaleX(1); }

        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 0.4rem;
            padding: 0.82rem 1.45rem;
            border-radius: 999px; border: 0; cursor: pointer;
            font-weight: 700; font-family: inherit; font-size: 0.95rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            box-shadow: 0 10px 28px color-mix(in srgb, var(--primary) 28%, transparent);
            transition: transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px color-mix(in srgb, var(--primary) 36%, transparent);
            filter: brightness(1.05);
        }
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid color-mix(in srgb, var(--primary) 32%, transparent);
            box-shadow: none;
        }
        .btn-outline:hover { background: color-mix(in srgb, var(--primary) 6%, transparent); }
        .btn-ghost {
            background: rgba(255,255,255,0.12);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.35);
            box-shadow: none;
            backdrop-filter: blur(8px);
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.2); }

        /* —— Hero base —— */
        .hero {
            position: relative;
            overflow: hidden;
            min-height: clamp(460px, 78vh, 700px);
            display: grid;
            background: var(--hero-gradient);
            color: #fff;
        }
        .hero-grain {
            pointer-events: none;
            position: absolute; inset: -20%;
            opacity: 0.18;
            background-image:
                radial-gradient(circle at 1px 1px, rgba(255,255,255,0.45) 1px, transparent 0);
            background-size: 18px 18px;
            animation: grainShift 18s linear infinite;
            mix-blend-mode: soft-light;
        }
        .hero-overlay {
            position: absolute; inset: 0;
            background: var(--pattern), linear-gradient(180deg, var(--hero-overlay), color-mix(in srgb, var(--hero-overlay) 70%, transparent));
        }
        .hero-ornament {
            pointer-events: none;
            position: absolute;
            width: min(42vw, 380px);
            height: min(42vw, 380px);
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.18);
            box-shadow: inset 0 0 60px rgba(255,255,255,0.06);
            animation: softFloat 7s ease-in-out infinite;
        }
        .hero-ornament--tl { top: 8%; inset-inline-start: -6%; }
        .hero-ornament--br { bottom: -10%; inset-inline-end: -4%; animation-delay: -2.5s; width: min(28vw, 240px); height: min(28vw, 240px); }
        .hero-inner { position: relative; z-index: 2; }
        .hero-kicker {
            display: inline-block;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            opacity: 0.78;
            margin-bottom: 1rem;
        }
        .hero h1 {
            font-family: var(--font-display);
            font-size: clamp(2.35rem, 6.2vw, 4.4rem);
            line-height: 1.12;
            margin: 0 0 1rem;
            font-weight: 700;
            text-wrap: balance;
        }
        .hero .hero-sub {
            font-size: 1.08rem;
            opacity: 0.9;
            margin: 0 0 1.75rem;
            max-width: 34rem;
            line-height: 1.8;
        }
        .hero-actions { display: flex; gap: 0.85rem; flex-wrap: wrap; }

        /* centered: full-bleed cinematic */
        .layout-centered .hero {
            place-items: center;
            text-align: center;
        }
        .layout-centered .hero-inner {
            padding: 4rem 1.25rem;
            max-width: 860px;
            margin-inline: auto;
        }
        .layout-centered .hero .hero-sub { margin-inline: auto; }
        .layout-centered .hero-actions { justify-content: center; }

        /* split: text + mosaic panel */
        .layout-split .hero {
            grid-template-columns: 1.05fr 0.95fr;
            min-height: clamp(520px, 82vh, 740px);
            align-items: stretch;
        }
        .layout-split .hero-copy {
            display: flex; flex-direction: column; justify-content: center;
            padding: clamp(2.5rem, 6vw, 5rem) clamp(1.5rem, 4vw, 3.5rem);
            position: relative; z-index: 2;
        }
        .layout-split .hero-visual {
            position: relative; z-index: 2;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            grid-template-rows: 1.2fr 0.8fr;
            gap: 0.85rem;
            padding: clamp(1.5rem, 4vw, 3rem);
            align-self: center;
        }
        .mosaic-tile {
            border-radius: calc(var(--radius) + 4px);
            background:
                linear-gradient(145deg, color-mix(in srgb, var(--accent) 55%, transparent), transparent 60%),
                linear-gradient(320deg, rgba(255,255,255,0.18), transparent 50%),
                color-mix(in srgb, var(--secondary) 70%, #000);
            box-shadow: var(--shadow-soft);
            min-height: 140px;
        }
        .mosaic-tile:nth-child(1) { grid-row: span 2; min-height: 280px; }
        .mosaic-tile:nth-child(2) {
            background:
                linear-gradient(160deg, rgba(255,255,255,0.22), transparent 55%),
                linear-gradient(45deg, var(--accent), color-mix(in srgb, var(--primary) 50%, #000));
        }
        .mosaic-tile:nth-child(3) {
            background:
                radial-gradient(circle at 30% 30%, rgba(255,255,255,0.28), transparent 50%),
                linear-gradient(200deg, var(--primary), var(--secondary));
        }

        /* editorial: asymmetric bold */
        .layout-editorial .hero {
            place-items: stretch;
            background: #0a0a0a;
        }
        .layout-editorial .hero-overlay {
            background:
                linear-gradient(105deg, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.55) 48%, rgba(0,0,0,0.25) 100%),
                var(--pattern);
        }
        .layout-editorial .hero-inner {
            display: grid;
            grid-template-columns: 12px 1fr;
            gap: clamp(1.25rem, 3vw, 2.5rem);
            align-items: end;
            padding: clamp(3.5rem, 10vh, 7rem) clamp(1.25rem, 5vw, 4rem);
            width: min(1180px, 100%);
            margin-inline: auto;
        }
        .layout-editorial .editorial-rail {
            width: 4px;
            height: min(70%, 320px);
            background: linear-gradient(180deg, var(--accent), transparent);
            align-self: end;
            margin-bottom: 0.5rem;
        }
        .layout-editorial .hero h1 {
            font-size: clamp(3rem, 9vw, 6.2rem);
            letter-spacing: -0.02em;
            max-width: 12ch;
            line-height: 0.98;
        }
        .layout-editorial .hero .hero-sub {
            max-width: 28rem;
            font-size: 1.05rem;
            border-top: 1px solid rgba(255,255,255,0.22);
            padding-top: 1.1rem;
            margin-top: 0.35rem;
        }

        /* minimal: whitespace + thin rules */
        .layout-minimal .hero {
            min-height: clamp(380px, 62vh, 560px);
            background: var(--surface);
            color: var(--text);
            place-items: center;
            border-bottom: 1px solid var(--line);
        }
        .layout-minimal .hero-overlay,
        .layout-minimal .hero-grain { display: none; }
        .layout-minimal .hero-inner {
            text-align: start;
            width: min(780px, calc(100% - 2.5rem));
            padding: 4.5rem 0 3.5rem;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }
        .layout-minimal .hero-kicker {
            color: var(--accent);
            letter-spacing: 0.22em;
        }
        .layout-minimal .hero h1 {
            font-size: clamp(2.1rem, 4.5vw, 3.4rem);
            font-weight: 600;
        }
        .layout-minimal .hero .hero-sub { opacity: 0.72; color: var(--text); }
        .layout-minimal .btn-ghost {
            color: var(--primary);
            border-color: color-mix(in srgb, var(--primary) 28%, transparent);
            background: transparent;
        }

        /* dark: midnight glam */
        .layout-dark,
        .layout-dark body,
        body.layout-dark {
            color: #f3efe6;
            background: #080812;
        }
        .layout-dark {
            --text: #f3efe6;
            --surface: {{ $theme['surface'] ?? '#0b0b14' }};
            --card: {{ $theme['card'] ?? '#151526' }};
            --line: rgba(212, 175, 55, 0.16);
        }
        .layout-dark .site-header {
            background: color-mix(in srgb, #0b0b14 88%, transparent);
            border-bottom-color: rgba(212, 175, 55, 0.18);
        }
        .layout-dark .hero {
            background:
                radial-gradient(ellipse at 70% 20%, rgba(212,175,55,0.22), transparent 45%),
                linear-gradient(165deg, #050510 0%, #12101f 40%, #1a1440 70%, #3a2f12 100%);
        }
        .layout-dark .hero-inner {
            text-align: center;
            padding: 4.5rem 1.25rem;
            max-width: 880px;
            margin-inline: auto;
            place-self: center;
        }
        .layout-dark .hero .hero-sub { margin-inline: auto; }
        .layout-dark .hero-actions { justify-content: center; }
        .layout-dark .hero h1 {
            background: linear-gradient(120deg, #fff8e7 10%, #d4af37 55%, #fff1c2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .layout-dark .btn {
            background: linear-gradient(135deg, #d4af37, #8a6a1f);
            color: #120f08;
            box-shadow: 0 12px 32px rgba(212,175,55,0.28);
        }
        .layout-dark .section.alt { background: color-mix(in srgb, #151526 80%, #000); }
        .layout-dark .card {
            background: var(--card);
            border-color: rgba(212,175,55,0.14);
            box-shadow: 0 16px 40px rgba(0,0,0,0.35);
        }
        .layout-dark input,
        .layout-dark textarea,
        .layout-dark select {
            background: #10101c;
            color: #f3efe6;
            border-color: rgba(212,175,55,0.22);
        }
        .layout-dark .btn-outline {
            color: #f3efe6;
            border-color: rgba(212,175,55,0.35);
        }

        /* —— Sections —— */
        .section { padding: clamp(3.5rem, 7vw, 5.5rem) 0; }
        .section.alt {
            background: color-mix(in srgb, var(--primary) 4.5%, var(--surface));
        }
        .section-head {
            text-align: center;
            margin-bottom: clamp(2rem, 4vw, 2.75rem);
            max-width: 640px;
            margin-inline: auto;
        }
        .section-head .eyebrow {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 0.65rem;
        }
        .section-head h2 {
            font-family: var(--font-display);
            font-size: clamp(1.7rem, 3.2vw, 2.55rem);
            margin: 0 0 0.55rem;
            line-height: 1.2;
        }
        .section-head p { margin: 0; color: var(--ink-soft); font-size: 1.02rem; }

        .feature-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .feature-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 1.45rem 1.35rem;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-soft);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lift);
        }
        .feature-index {
            font-family: var(--font-display);
            font-size: 0.85rem;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 0.65rem;
        }
        .feature-card h3 { margin: 0 0 0.45rem; font-size: 1.08rem; }
        .feature-card p { margin: 0; font-size: 0.94rem; color: var(--ink-soft); }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.15rem;
        }
        .card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 1.3rem;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-soft);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card h3 { margin: 0 0 0.4rem; font-size: 1.08rem; }
        .card p { margin: 0; font-size: 0.93rem; color: var(--ink-soft); }

        .product-card {
            overflow: hidden;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lift);
        }
        .product-media {
            height: 210px;
            background:
                linear-gradient(160deg, color-mix(in srgb, var(--primary) 75%, #000) 0%, transparent 55%),
                linear-gradient(45deg, var(--secondary), var(--accent));
            position: relative;
            overflow: hidden;
        }
        .product-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-media::after {
            content: "";
            position: absolute; inset: auto 0 0 0; height: 45%;
            background: linear-gradient(transparent, color-mix(in srgb, var(--card) 20%, transparent));
        }
        .product-body { padding: 1.15rem 1.25rem 1.35rem; }
        .product-cta {
            display: inline-block;
            margin-top: 0.65rem;
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--accent);
        }

        .service-card {
            position: relative;
            padding-top: 1.6rem;
        }
        .service-badge {
            width: 42px; height: 42px;
            border-radius: 50%;
            display: grid; place-items: center;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--primary);
            background: color-mix(in srgb, var(--accent) 22%, var(--card));
            border: 1px solid color-mix(in srgb, var(--accent) 40%, transparent);
            margin-bottom: 0.9rem;
        }
        .layout-dark .service-badge { color: #d4af37; }

        .gallery-masonry {
            columns: 3 220px;
            column-gap: 1rem;
        }
        .gallery-item {
            break-inside: avoid;
            margin-bottom: 1rem;
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--card);
            box-shadow: var(--shadow-soft);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lift);
        }
        .gallery-img {
            width: 100%;
            height: auto;
            min-height: 160px;
            max-height: 320px;
            object-fit: cover;
            display: block;
            background: #ddd;
        }
        .gallery-item:nth-child(3n) .gallery-img { min-height: 220px; }
        .gallery-item:nth-child(4n) .gallery-img { min-height: 180px; }
        .gallery-ph {
            height: 200px;
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--primary) 70%, #000), var(--accent));
        }

        .testimonials {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.15rem;
        }
        .quote-card {
            padding: 1.6rem 1.45rem;
            position: relative;
        }
        .quote-mark {
            font-family: var(--font-display);
            font-size: 2.8rem;
            line-height: 1;
            color: var(--accent);
            opacity: 0.55;
            margin-bottom: 0.35rem;
        }
        .quote-card blockquote {
            margin: 0 0 1.1rem;
            font-size: 1.02rem;
            color: var(--ink-soft);
            font-style: italic;
        }
        .quote-meta {
            font-weight: 700;
            font-size: 0.92rem;
        }
        .quote-role {
            display: block;
            font-weight: 500;
            font-size: 0.82rem;
            color: var(--ink-soft);
            margin-top: 0.15rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            text-align: center;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            overflow: hidden;
            background: var(--card);
            box-shadow: var(--shadow-soft);
        }
        .stat {
            padding: 1.75rem 1rem;
            position: relative;
        }
        .stat:not(:last-child)::after {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 22%;
            bottom: 22%;
            width: 1px;
            background: var(--line);
        }
        .stat strong {
            display: block;
            font-size: clamp(1.6rem, 3vw, 2.1rem);
            color: var(--accent);
            font-family: var(--font-display);
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }
        .stat span { font-size: 0.9rem; color: var(--ink-soft); }

        .cta-band {
            margin: 0.5rem 0;
            padding: clamp(2.4rem, 5vw, 3.4rem);
            border-radius: calc(var(--radius) + 4px);
            background:
                radial-gradient(circle at 15% 20%, color-mix(in srgb, var(--accent) 35%, transparent), transparent 40%),
                linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            text-align: center;
            box-shadow: var(--shadow-lift);
        }
        .cta-band h2 {
            font-family: var(--font-display);
            margin: 0 0 0.6rem;
            font-size: clamp(1.6rem, 3vw, 2.3rem);
        }
        .cta-band p { margin: 0 0 1.4rem; opacity: 0.9; }
        .cta-band .btn {
            background: #fff;
            color: var(--primary);
            box-shadow: 0 10px 28px rgba(0,0,0,0.18);
        }

        form.stack { display: grid; gap: 0.85rem; }
        .form-shell {
            max-width: 580px;
            margin-inline: auto;
            padding: 1.6rem;
        }
        input, textarea, select {
            width: 100%;
            padding: 0.88rem 1rem;
            border-radius: calc(var(--radius) * 0.65);
            border: 1px solid color-mix(in srgb, var(--primary) 16%, #c9d0dc);
            font: inherit;
            background: #fff;
            color: #111;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: color-mix(in srgb, var(--accent) 65%, var(--primary));
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 22%, transparent);
        }
        .flash {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 0.9fr 1.1fr;
            gap: 1.25rem;
            align-items: start;
        }
        .contact-meta p {
            margin: 0 0 0.7rem;
            font-size: 0.95rem;
            color: var(--ink-soft);
        }
        .contact-meta strong {
            display: block;
            color: var(--text);
            font-size: 0.78rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 0.15rem;
        }

        .site-footer {
            margin-top: 1rem;
            padding: 3rem 0 1.75rem;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--primary) 88%, #000), color-mix(in srgb, var(--primary) 96%, #000));
            color: rgba(255,255,255,0.92);
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .footer-brand {
            font-family: var(--font-display);
            font-size: 1.45rem;
            margin-bottom: 0.55rem;
        }
        .footer-col strong {
            display: block;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.75;
        }
        .footer-links {
            display: grid;
            gap: 0.45rem;
            font-size: 0.95rem;
        }
        .footer-links a { opacity: 0.82; transition: opacity 0.2s ease; }
        .footer-links a:hover { opacity: 1; }
        .social-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-top: 0.35rem;
        }
        .social-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.22);
            font-size: 0.86rem;
            font-weight: 600;
            opacity: 0.9;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .social-chip:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.4);
        }
        .footer-base {
            border-top: 1px solid rgba(255,255,255,0.12);
            padding-top: 1.15rem;
            font-size: 0.86rem;
            opacity: 0.7;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .muted { opacity: 0.72; font-size: 0.9rem; }
        .preview-banner {
            background: #111;
            color: #fbbf24;
            text-align: center;
            padding: 0.5rem;
            font-size: 0.85rem;
            font-weight: 700;
        }

        @media (max-width: 960px) {
            .layout-split .hero { grid-template-columns: 1fr; min-height: auto; }
            .layout-split .hero-visual {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 140px 120px;
                padding-top: 0;
            }
            .layout-split .mosaic-tile:nth-child(1) { grid-row: span 1; min-height: 140px; }
            .feature-strip { grid-template-columns: 1fr; }
            .stats { grid-template-columns: 1fr 1fr; }
            .stat:nth-child(2)::after { display: none; }
            .stat:nth-child(odd)::after { display: block; }
            .contact-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; }
            .gallery-masonry { columns: 2 160px; }
        }
        @media (max-width: 800px) {
            .menu { display: none; }
            .nav .btn { padding: 0.72rem 1.1rem; font-size: 0.88rem; }
            .layout-editorial .hero h1 { max-width: none; }
        }
        @media (max-width: 560px) {
            .wrap { width: min(100% - 1.5rem, 1180px); }
            .stats { grid-template-columns: 1fr; }
            .stat:not(:last-child)::after {
                inset-inline: 18% 18%;
                inset-block: auto 0;
                top: auto; width: auto; height: 1px;
            }
            .gallery-masonry { columns: 1; }
            .hero-actions { flex-direction: column; align-items: stretch; }
            .hero-actions .btn { width: 100%; }
        }
    </style>
</head>
<body class="{{ $layoutClass }}{{ ($template['id'] ?? '') === 'midnight-glam' || $layoutKey === 'dark' ? ' dark-theme' : '' }}">
@if(!empty($preview))
    <div class="preview-banner">وضع المعاينة — الموقع غير منشور للعامة بالضرورة</div>
@endif

<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="/">
            @if(!empty($logo_url))
                <img src="{{ $logo_url }}" alt="logo">
            @endif
            <span class="brand-name">{{ $general['site_name'] ?? 'DressnMore' }}</span>
        </a>
        <nav class="menu" aria-label="القائمة الرئيسية">
            @php $headerMenus = $menus['header'] ?? collect(); @endphp
            @forelse($headerMenus as $item)
                <a href="{{ $item->url }}">{{ $item->title }}</a>
            @empty
                @foreach($pages as $p)
                    <a href="{{ $p->slug === '/' ? '/' : $p->slug }}">{{ $p->title }}</a>
                @endforeach
            @endforelse
        </nav>
        <a class="btn" href="/booking">{{ $texts['cta_label'] ?: 'احجزي الآن' }}</a>
    </div>
</header>

<main>
@php
    $enabled = $sections->pluck('type')->all();
    $show = fn(string $type) => in_array($type, $enabled, true) || ($path !== '/' && empty($enabled));
    $brandName = $general['site_name'] ?? 'DressnMore';
    $heroTitle = $texts['hero_title'] ?: ('أناقة '.$brandName);
    $heroSubtitle = $texts['hero_subtitle'] ?: 'فساتين، حجوزات، وتجربة فاخرة مصممة لتُبهري عميلاتك من أول زيارة.';
    $ctaLabel = $texts['cta_label'] ?: 'احجزي موعدك';
@endphp

@if($show('Hero') || $path === '/')
<section class="hero" aria-label="القسم الرئيسي">
    <div class="hero-overlay" aria-hidden="true"></div>
    <div class="hero-grain" aria-hidden="true"></div>
    <div class="hero-ornament hero-ornament--tl" aria-hidden="true"></div>
    <div class="hero-ornament hero-ornament--br" aria-hidden="true"></div>

    @if(in_array($layoutKey, ['split'], true))
        <div class="hero-copy reveal">
            <span class="hero-kicker">{{ $brandName }}</span>
            <h1>{{ $heroTitle }}</h1>
            <p class="hero-sub">{{ $heroSubtitle }}</p>
            <div class="hero-actions">
                <a class="btn" href="/booking">{{ $ctaLabel }}</a>
                <a class="btn btn-ghost" href="/dresses">استعرضي المجموعة</a>
            </div>
        </div>
        <div class="hero-visual reveal reveal-delay-1" aria-hidden="true">
            <div class="mosaic-tile"></div>
            <div class="mosaic-tile"></div>
            <div class="mosaic-tile"></div>
        </div>
    @elseif($layoutKey === 'editorial')
        <div class="hero-inner reveal">
            <div class="editorial-rail" aria-hidden="true"></div>
            <div>
                <span class="hero-kicker">{{ $brandName }}</span>
                <h1>{{ $heroTitle }}</h1>
                <p class="hero-sub">{{ $heroSubtitle }}</p>
                <div class="hero-actions">
                    <a class="btn" href="/booking">{{ $ctaLabel }}</a>
                    <a class="btn btn-ghost" href="/dresses">استعرضي المجموعة</a>
                </div>
            </div>
        </div>
    @else
        <div class="hero-inner reveal">
            <span class="hero-kicker">{{ $brandName }}</span>
            <h1>{{ $heroTitle }}</h1>
            <p class="hero-sub">{{ $heroSubtitle }}</p>
            <div class="hero-actions">
                <a class="btn" href="/booking">{{ $ctaLabel }}</a>
                <a class="btn {{ $layoutKey === 'minimal' ? 'btn-outline' : 'btn-ghost' }}" href="/dresses">استعرضي المجموعة</a>
            </div>
        </div>
    @endif
</section>
@endif

@if($show('About') || $path === '/about')
<section class="section">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">قصتنا</span>
            <h2>من نحن</h2>
            <p>{{ $general['address'] ?: 'أتيليه متخصص في الأزياء الراقية والتفصيل حسب الطلب.' }}</p>
        </div>
        <div class="feature-strip reveal reveal-delay-1">
            <article class="feature-card">
                <div class="feature-index">01</div>
                <h3>تفصيل راقٍ</h3>
                <p>قصة تفصيل دقيقة مع متابعة شخصية لكل عميلة.</p>
            </article>
            <article class="feature-card">
                <div class="feature-index">02</div>
                <h3>مجموعة مميزة</h3>
                <p>فساتين سهرات وزفاف وإيجار بذوق رفيع.</p>
            </article>
            <article class="feature-card">
                <div class="feature-index">03</div>
                <h3>تجربة حجز سلسة</h3>
                <p>احجزي بروفة أو استشارة خلال دقائق من الموقع.</p>
            </article>
        </div>
    </div>
</section>
@endif

@if($show('Featured Products') || $show('Product Grid') || $path === '/dresses')
<section class="section alt">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">المختارات</span>
            <h2>المنتجات / الفساتين</h2>
            <p>مختارات منشورة من مخزون النظام</p>
        </div>
        <div class="grid">
            @forelse($products as $product)
                @php
                    $dressImages = $product->dress?->images;
                    $primaryImage = $dressImages
                        ? ($dressImages->firstWhere('is_primary', true) ?? $dressImages->first())
                        : null;
                    $productImageUrl = $primaryImage
                        ? app(\App\Services\Tenant\DressImageStorageService::class)->url($primaryImage->path)
                        : null;
                @endphp
                <article class="card product-card">
                    <div class="product-media" aria-hidden="true">
                        @if($productImageUrl)
                            <img src="{{ $productImageUrl }}" alt="{{ $product->site_title ?: ($product->dress->name ?? 'فستان') }}">
                        @endif
                    </div>
                    <div class="product-body">
                        <h3>{{ $product->site_title ?: ($product->dress->name ?? 'فستان') }}</h3>
                        <span class="product-cta">{{ $product->cta_label ?: 'اطلبي الآن' }}</span>
                    </div>
                </article>
            @empty
                <div class="card"><h3>قريبًا</h3><p>سيتم عرض الفساتين بعد نشرها من لوحة التحكم.</p></div>
            @endforelse
        </div>
    </div>
</section>
@endif

@if($show('Services') || $path === '/services')
<section class="section">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">الخبرة</span>
            <h2>خدماتنا</h2>
        </div>
        <div class="grid">
            @forelse($services as $service)
                <article class="card service-card">
                    <div class="service-badge">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</div>
                    <h3>{{ $service->name }}</h3>
                    <p>{{ $service->description }}</p>
                </article>
            @empty
                <article class="card service-card">
                    <div class="service-badge">01</div>
                    <h3>إيجار</h3>
                    <p>فساتين سهرات ومناسبات.</p>
                </article>
                <article class="card service-card">
                    <div class="service-badge">02</div>
                    <h3>تفصيل</h3>
                    <p>تصميم حسب المقاس.</p>
                </article>
                <article class="card service-card">
                    <div class="service-badge">03</div>
                    <h3>تعديلات</h3>
                    <p>ضبط وتعديل احترافي.</p>
                </article>
            @endforelse
        </div>
    </div>
</section>
@endif

@if($show('Gallery') || $path === '/gallery')
<section class="section alt">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">الإطلالات</span>
            <h2>المعرض</h2>
        </div>
        <div class="gallery-masonry">
            @php $galleryHasItems = false; @endphp
            @forelse($albums as $album)
                @foreach($album->images as $image)
                    @php $galleryHasItems = true; @endphp
                    <div class="gallery-item">
                        @if($image->media)
                            <img class="gallery-img" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->media->path) }}" alt="{{ $image->alt_text }}">
                        @else
                            <div class="gallery-ph" aria-hidden="true"></div>
                        @endif
                    </div>
                @endforeach
            @empty
                <div class="card"><p class="muted">أضيفي ألبومات من لوحة الموقع لإظهار المعرض هنا.</p></div>
            @endforelse
            @if(!$albums->isEmpty() && ! $galleryHasItems)
                <div class="card"><p class="muted">أضيفي ألبومات من لوحة الموقع لإظهار المعرض هنا.</p></div>
            @endif
        </div>
    </div>
</section>
@endif

@if($show('Testimonials'))
<section class="section">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">آراء العميلات</span>
            <h2>تجارب حقيقية</h2>
            <p>انطباعات تلخّص جودة التفصيل ودفء التجربة داخل الأتيليه.</p>
        </div>
        <div class="testimonials reveal reveal-delay-1">
            <article class="card quote-card">
                <div class="quote-mark" aria-hidden="true">"</div>
                <blockquote>تجربة راقية من أول استشارة حتى التسليم. القصة والتفاصيل كانت على مستوى التوقعات تمامًا.</blockquote>
                <div class="quote-meta">نورة العتيبي<span class="quote-role">عروس · تفصيل خاص</span></div>
            </article>
            <article class="card quote-card">
                <div class="quote-mark" aria-hidden="true">"</div>
                <blockquote>مجموعة الإيجار متنوعة والحجز عبر الموقع وفّر عليّ وقتًا كثيرًا. أنصح كل من تبحث عن أناقة بلا تعقيد.</blockquote>
                <div class="quote-meta">لمى الشمري<span class="quote-role">مناسبة خاصة · إيجار</span></div>
            </article>
            <article class="card quote-card">
                <div class="quote-mark" aria-hidden="true">"</div>
                <blockquote>الاهتمام بالمقاسات والتعديلات جعل الإطلالة مثالية. فريق محترف وذوق رفيع.</blockquote>
                <div class="quote-meta">سارة القحطاني<span class="quote-role">سهرة · تعديلات</span></div>
            </article>
        </div>
    </div>
</section>
@endif

@if($show('Statistics'))
<section class="section">
    <div class="wrap">
        <div class="stats reveal">
            <div class="stat"><strong>+500</strong><span>إطلالة</span></div>
            <div class="stat"><strong>+12</strong><span>سنة خبرة</span></div>
            <div class="stat"><strong>24/7</strong><span>دعم حجوزات</span></div>
            <div class="stat"><strong>100%</strong><span>اهتمام بالتفاصيل</span></div>
        </div>
    </div>
</section>
@endif

@if($show('CTA'))
<section class="section">
    <div class="wrap">
        <div class="cta-band reveal">
            <h2>جاهزة لإطلالتك القادمة؟</h2>
            <p>{{ $texts['hero_subtitle'] ?: 'احجزي موعد بروفة أو استشارة اليوم.' }}</p>
            <a class="btn" href="/booking">{{ $texts['cta_label'] ?: 'ابدئي الآن' }}</a>
        </div>
    </div>
</section>
@endif

@if($show('Booking') || $path === '/booking')
<section class="section alt" id="booking">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">المواعيد</span>
            <h2>حجز موعد</h2>
            <p>أرسلي طلبك وسنتواصل معك للتأكيد</p>
        </div>
        @if(session('success')) <div class="flash">{{ session('success') }}</div> @endif
        <form class="stack card form-shell reveal reveal-delay-1" method="post" action="/booking">
            @csrf
            <input name="name" required placeholder="الاسم الكامل" value="{{ old('name') }}">
            <input name="phone" placeholder="رقم الجوال" value="{{ old('phone') }}">
            <input name="email" type="email" placeholder="البريد" value="{{ old('email') }}">
            <input name="service" placeholder="الخدمة (إيجار / تفصيل / بروفة)" value="{{ old('service') }}">
            <input name="preferred_date" placeholder="التاريخ المفضل" value="{{ old('preferred_date') }}">
            <textarea name="notes" rows="4" placeholder="ملاحظات">{{ old('notes') }}</textarea>
            <button class="btn" type="submit">إرسال طلب الحجز</button>
        </form>
    </div>
</section>
@endif

@if($show('Contact') || $path === '/contact')
<section class="section" id="contact">
    <div class="wrap">
        <div class="section-head reveal">
            <span class="eyebrow">تواصل</span>
            <h2>تواصل معنا</h2>
        </div>
        @if(session('success')) <div class="flash">{{ session('success') }}</div> @endif
        <div class="contact-grid reveal reveal-delay-1">
            <div class="card contact-meta">
                <h3 style="margin-top:0;margin-bottom:1.1rem;font-family:var(--font-display)">بيانات التواصل</h3>
                <p><strong>هاتف</strong>{{ $branding['phone'] ?: ($channels['phone'] ?? '—') }}</p>
                <p><strong>واتساب</strong>{{ $branding['whatsapp'] ?: ($channels['whatsapp'] ?? '—') }}</p>
                <p><strong>بريد</strong>{{ $branding['email'] ?: ($channels['email'] ?? '—') }}</p>
                <p><strong>العنوان</strong>{{ $general['address'] ?: '—' }}</p>
                <p><strong>ساعات العمل</strong>{{ $general['working_hours'] ?: '—' }}</p>
            </div>
            <form class="card stack" method="post" action="/contact">
                @csrf
                <input name="name" required placeholder="الاسم" value="{{ old('name') }}">
                <input name="phone" placeholder="الجوال" value="{{ old('phone') }}">
                <input name="email" type="email" placeholder="البريد" value="{{ old('email') }}">
                <input name="subject" placeholder="الموضوع" value="{{ old('subject') }}">
                <textarea name="message" rows="5" required placeholder="رسالتك">{{ old('message') }}</textarea>
                <button class="btn" type="submit">إرسال</button>
            </form>
        </div>
    </div>
</section>
@endif
</main>

<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <div class="footer-brand">{{ $general['site_name'] ?? 'DressnMore' }}</div>
            <p class="muted" style="color:rgba(255,255,255,0.7);margin:0 0 1rem">قالب: {{ $template['name'] ?? '' }} · مدعوم من DressnMore</p>
            <div class="social-row">
                @if(!empty($branding['instagram']))
                    <a class="social-chip" href="https://instagram.com/{{ ltrim($branding['instagram'], '@') }}" target="_blank" rel="noopener">Instagram · @{{ ltrim($branding['instagram'], '@') }}</a>
                @endif
                @if(!empty($branding['facebook']))
                    @php
                        $fb = (string) $branding['facebook'];
                        $fbUrl = str_starts_with($fb, 'http://') || str_starts_with($fb, 'https://')
                            ? $fb
                            : 'https://facebook.com/'.$fb;
                    @endphp
                    <a class="social-chip" href="{{ $fbUrl }}" target="_blank" rel="noopener">Facebook</a>
                @endif
                @if(!empty($branding['whatsapp']) || !empty($channels['whatsapp'] ?? null))
                    @php $wa = preg_replace('/\D+/', '', (string) ($branding['whatsapp'] ?: ($channels['whatsapp'] ?? ''))); @endphp
                    @if($wa !== '')
                        <a class="social-chip" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">WhatsApp</a>
                    @endif
                @endif
            </div>
        </div>
        <div class="footer-col">
            <strong>روابط</strong>
            <div class="footer-links">
                @foreach(($menus['footer'] ?? $menus['header'] ?? collect()) as $item)
                    <a href="{{ $item->url }}">{{ $item->title }}</a>
                @endforeach
            </div>
        </div>
        <div class="footer-col">
            <strong>تواصل</strong>
            <p class="muted" style="color:rgba(255,255,255,0.7);margin:0 0 0.4rem">{{ $branding['phone'] ?: ($channels['phone'] ?? '') }}</p>
            <p class="muted" style="color:rgba(255,255,255,0.7);margin:0">{{ $branding['email'] ?: ($channels['email'] ?? '') }}</p>
        </div>
    </div>
    <div class="wrap footer-base">
        <span>{{ $general['site_name'] ?? 'DressnMore' }}</span>
        <span>تجربة موقع فاخرة من DressnMore</span>
    </div>
</footer>
</body>
</html>
