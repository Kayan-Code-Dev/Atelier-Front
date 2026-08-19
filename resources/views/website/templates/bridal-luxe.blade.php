<!DOCTYPE html>
<html lang="{{ ($general['language'] ?? 'ar') === 'en' ? 'en' : 'ar' }}" dir="{{ ($general['language'] ?? 'ar') === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $isAr = ($general['language'] ?? 'ar') !== 'en';
        $t = function (string $ar, string $en) use ($isAr): string {
            return $isAr ? $ar : $en;
        };
        $brandName = trim((string) ($general['site_name'] ?? '')) ?: ($isAr ? 'الأتيليه' : 'The Atelier');
        $heroTitle = trim((string) ($texts['hero_title'] ?? '')) ?: $t('صُممت للحظات التي لا تُنسى.', 'Designed for your most unforgettable moments.');
        $heroSubtitle = trim((string) ($texts['hero_subtitle'] ?? '')) ?: $t('اكتشفي قطعًا استثنائية صُنعت واختيرت للحظات التي تستحق أن تُحفظ.', 'Discover exquisite pieces crafted and curated for the moments that deserve to be remembered.');
        $ctaLabel = trim((string) ($texts['cta_label'] ?? '')) ?: $t('احجزي موعدك', 'Book an Appointment');
        $primary = $branding['primary_color'] ?? '#1A1410';
        $secondary = $branding['secondary_color'] ?? '#3D3229';
        $accent = $branding['accent_color'] ?? '#B8956C';
        $bg = $branding['background_color'] ?? '#F6F1E9';
        $textColor = $branding['text_color'] ?? '#2A221C';
        $surface = $theme['surface'] ?? '#F6F1E9';
        $card = $theme['card'] ?? '#FFFCF8';

        $phone = trim((string) ($branding['phone'] ?? $channels['phone'] ?? ''));
        $email = trim((string) ($branding['email'] ?? $channels['email'] ?? ''));
        $whatsappRaw = trim((string) ($branding['whatsapp'] ?? $channels['whatsapp'] ?? ''));
        $whatsappDigits = preg_replace('/\D+/', '', $whatsappRaw) ?: '';
        $instagram = trim((string) ($branding['instagram'] ?? $channels['instagram'] ?? ''));
        $facebook = trim((string) ($branding['facebook'] ?? $channels['facebook'] ?? ''));
        $tiktok = trim((string) ($branding['tiktok'] ?? $channels['tiktok'] ?? ''));
        $pinterest = trim((string) ($branding['pinterest'] ?? $channels['pinterest'] ?? ''));
        $socials = collect([
            ['key' => 'instagram', 'label' => 'Instagram', 'url' => $instagram],
            ['key' => 'facebook', 'label' => 'Facebook', 'url' => $facebook],
            ['key' => 'tiktok', 'label' => 'TikTok', 'url' => $tiktok],
            ['key' => 'pinterest', 'label' => 'Pinterest', 'url' => $pinterest],
        ])->filter(fn ($s) => $s['url'] !== '')->values();

        $mediaUrl = function ($raw): ?string {
            if (! is_string($raw) || trim($raw) === '') {
                return null;
            }
            $raw = trim($raw);
            if (str_starts_with($raw, 'http') || str_starts_with($raw, '/')) {
                return $raw;
            }
            return \Illuminate\Support\Facades\Storage::disk('public')->url($raw);
        };
        $dressImage = function ($dress) use ($mediaUrl): ?string {
            $images = $dress?->images;
            if (! $images || $images->isEmpty()) {
                return null;
            }
            $img = $images->firstWhere('is_primary', true) ?? $images->first();
            return $mediaUrl($img->path ?? $img->url ?? $img->file_path ?? null);
        };
        $dressImages = function ($dress) use ($mediaUrl): array {
            $out = [];
            foreach ($dress?->images ?? [] as $img) {
                $url = $mediaUrl($img->path ?? $img->url ?? $img->file_path ?? null);
                if ($url) {
                    $out[] = $url;
                }
            }
            return $out;
        };

        $product = $product ?? null;
        $isHome = ($path ?? '/') === '/';
        $enabled = $sections->pluck('type')->all();
        $show = fn (string $type) => in_array($type, $enabled, true) || ($isHome && empty($enabled));
        $bookingOn = (bool) ($booking['enable_online_booking'] ?? $booking['enable_booking'] ?? true);
        $headerMenus = $menus['header'] ?? collect();
        $footerMenus = $menus['footer'] ?? $headerMenus;
        $hasProducts = isset($products) && $products->isNotEmpty();
        $hasServices = isset($services) && $services->isNotEmpty();
        $galleryImages = collect();
        foreach ($albums ?? [] as $album) {
            foreach ($album->images ?? [] as $image) {
                if ($image->media) {
                    $galleryImages->push([
                        'url' => $mediaUrl($image->media->path),
                        'alt' => $image->alt_text ?: ($album->title ?? $brandName),
                    ]);
                }
            }
        }
        $galleryImages = $galleryImages->filter(fn ($i) => ! empty($i['url']))->values();
        $hasGallery = $galleryImages->isNotEmpty();

        $heroImage = $mediaUrl($texts['hero_image'] ?? $sharing['og_image'] ?? null);
        if (! $heroImage && $hasProducts) {
            $heroImage = $dressImage($products->first()?->dress);
        }
        if (! $heroImage && $hasGallery) {
            $heroImage = $galleryImages->first()['url'] ?? null;
        }

        $aboutText = trim((string) ($texts['about'] ?? $general['about'] ?? ''));
        $canonical = $seo['canonical_url'] ?? null;
        if (! $canonical && ! empty($public_url)) {
            $canonical = rtrim((string) $public_url, '/').($path === '/' ? '' : $path);
        }
        $pageTitle = $seo['site_title'] ?: ($texts['seo_title'] ?: $brandName);
        $pageDesc = $seo['meta_description'] ?: ($texts['seo_description'] ?? '');
        if ($product) {
            $pageTitle = ($product->site_title ?: ($product->dress->name ?? $pageTitle)).' · '.$brandName;
            $pageDesc = trim((string) ($product->dress->description ?? '')) ?: $pageDesc;
        }
        $displayFont = trim((string) ($theme['font_display'] ?? ''));
        if ($displayFont === '' || str_contains($displayFont, '<')) {
            $displayFont = '"Cormorant Garamond", "Amiri", serif';
        }
        $displayFontRtl = '"Amiri", "Cormorant Garamond", serif';
        $ogTitle = ($sharing['og_title'] ?? null) ?: $pageTitle;
        $ogDescription = ($sharing['og_description'] ?? null) ?: $pageDesc;
        $ogImage = $mediaUrl($sharing['og_image'] ?? null) ?: ($product ? $dressImage($product->dress) : $heroImage) ?: $logo_url;
        $robots = $seo['robots'] ?? 'index,follow';
        $metaPixel = trim((string) ($pixels['meta_pixel'] ?? ''));
        $gaId = trim((string) ($pixels['ga_id'] ?? ''));
        $gtmId = trim((string) ($pixels['gtm_id'] ?? ''));
        $navFallback = [
            ['href' => '/dresses', 'label' => $t('المجموعة', 'Collection')],
            ['href' => '/about', 'label' => $t('عن الأتيليه', 'About')],
            ['href' => '/services', 'label' => $t('الخدمات', 'Services')],
            ['href' => '/gallery', 'label' => $t('المعرض', 'Gallery')],
            ['href' => '/contact', 'label' => $t('تواصل', 'Contact')],
        ];
        $headerNav = collect($headerMenus)->filter(function ($item) {
            $url = strtolower(rtrim((string) ($item->url ?? ''), '/') ?: '/');
            return ! in_array($url, ['/', '/home', '/booking'], true);
        })->values();
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}">
    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    @if(!empty($favicon_url))
        <link rel="icon" href="{{ $favicon_url }}">
    @endif
    @if(!empty($canonical))
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:url" content="{{ $canonical }}">
    @endif
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:type" content="{{ $product ? 'product' : 'website' }}">
    <meta property="og:locale" content="{{ $isAr ? 'ar_AR' : 'en_US' }}">
    <meta property="og:site_name" content="{{ $brandName }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    @if(!empty($robots))
        <meta name="robots" content="{{ $robots }}">
    @endif
    @if($gtmId !== '')
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($gtmId));</script>
    @endif
    @if($metaPixel !== '')
        <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',@json($metaPixel));fbq('track','PageView');</script>
    @endif
    @if($gaId !== '')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@json($gaId));</script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Outfit:wght@300;400;500;600&family=Cairo:wght@300;400;500;600&family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: {{ $primary }};
            --color-secondary: {{ $secondary }};
            --color-accent: {{ $accent }};
            --color-bg: {{ $bg }};
            --color-surface: {{ $surface }};
            --color-card: {{ $card }};
            --color-text: {{ $textColor }};
            --color-muted: color-mix(in srgb, var(--color-text) 62%, transparent);
            --color-border: color-mix(in srgb, var(--color-accent) 28%, transparent);
            --color-button: var(--color-primary);
            --color-button-hover: var(--color-secondary);
            --color-on-button: #F7F1E8;
            --font-display: {!! $displayFont !!};
            --font-body: "Outfit", "Cairo", sans-serif;
            --space: clamp(1.25rem, 4vw, 2.5rem);
            --wrap: min(1180px, calc(100% - 2 * var(--space)));
        }
        [dir="rtl"] {
            --font-display: {!! $displayFontRtl !!};
            --font-body: "Cairo", "Outfit", sans-serif;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: var(--font-body);
            color: var(--color-text);
            background: var(--color-bg);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        button, input, textarea, select { font: inherit; }
        .wrap { width: var(--wrap); margin-inline: auto; }
        .sr-only {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0,0,0,0); border: 0;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .reveal { animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .d1 { animation-delay: .1s; } .d2 { animation-delay: .2s; } .d3 { animation-delay: .32s; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .reveal, .hero-photo img, .look-photo img { animation: none !important; transition: none !important; }
        }

        .preview-banner {
            background: var(--color-primary); color: var(--color-accent);
            text-align: center; padding: .45rem; font-size: .78rem; letter-spacing: .08em;
        }

        /* Header */
        .site-header {
            position: sticky; top: 0; z-index: 50;
            background: color-mix(in srgb, var(--color-bg) 88%, transparent);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid color-mix(in srgb, var(--color-border) 70%, transparent);
        }
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; min-height: 72px;
        }
        .brand { display: flex; align-items: center; gap: .75rem; min-width: 0; }
        .brand img { width: 40px; height: 40px; object-fit: cover; }
        .brand-name {
            font-family: var(--font-display); font-size: 1.55rem; font-weight: 400;
            font-style: italic; letter-spacing: .04em; line-height: 1.1;
        }
        [dir="rtl"] .brand-name { font-style: normal; }
        .menu {
            display: flex; align-items: center; gap: 1.35rem;
            font-size: .78rem; letter-spacing: .14em; text-transform: uppercase; font-weight: 500;
        }
        [dir="rtl"] .menu { letter-spacing: .03em; text-transform: none; font-size: .86rem; }
        .menu a { opacity: .78; }
        .menu a:hover, .menu a:focus-visible { opacity: 1; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 44px; padding: .7rem 1.35rem;
            border: 1px solid transparent;
            background: var(--color-button); color: var(--color-on-button);
            font-size: .78rem; letter-spacing: .14em; text-transform: uppercase; font-weight: 500;
            cursor: pointer; transition: background .25s ease, color .25s ease, border-color .25s ease;
        }
        .btn:hover, .btn:focus-visible { background: var(--color-button-hover); }
        .btn-ghost {
            background: transparent; color: var(--color-on-button);
            border-color: color-mix(in srgb, #fff 45%, transparent);
        }
        .btn-ghost:hover { border-color: #fff; background: transparent; }
        .btn-line {
            background: transparent; color: var(--color-text);
            border-color: var(--color-border);
        }
        .btn-line:hover { border-color: var(--color-primary); background: transparent; color: var(--color-primary); }
        .nav-toggle { display: none; }
        .burger {
            display: none; width: 44px; height: 44px; border: 0; background: none;
            align-items: center; justify-content: center; cursor: pointer;
        }
        .burger span, .burger span::before, .burger span::after {
            display: block; width: 20px; height: 1px; background: var(--color-text); position: relative;
        }
        .burger span::before, .burger span::after {
            content: ""; position: absolute; inset-inline-start: 0;
        }
        .burger span::before { top: -6px; } .burger span::after { top: 6px; }

        /* Hero */
        .hero {
            position: relative; min-height: min(100dvh, 920px); height: 92dvh;
            display: grid; align-items: end; color: #fff; overflow: hidden;
            background: var(--color-primary);
        }
        .hero-photo {
            position: absolute; inset: 0;
        }
        .hero-photo img, .hero-photo .hero-fallback {
            width: 100%; height: 100%; object-fit: cover;
        }
        .hero-fallback {
            position: relative;
            background:
                radial-gradient(ellipse at 72% 38%, color-mix(in srgb, var(--color-accent) 22%, transparent), transparent 52%),
                linear-gradient(165deg, color-mix(in srgb, var(--color-secondary) 78%, #000) 0%, var(--color-primary) 58%, #0c0907 100%);
        }
        .hero-fallback::before {
            content: "";
            position: absolute; inset: 0;
            opacity: .16;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }
        .hero-mono {
            position: absolute; inset-inline-end: 6%; top: 14%;
            font-family: var(--font-display); font-weight: 400; font-style: italic;
            font-size: clamp(10rem, 26vw, 20rem); line-height: .75;
            color: color-mix(in srgb, var(--color-accent) 24%, transparent);
            pointer-events: none; user-select: none;
        }
        [dir="rtl"] .hero-mono { font-style: normal; }
        .hero-frame {
            position: absolute; inset: clamp(.85rem, 2vw, 1.5rem);
            border: 1px solid color-mix(in srgb, var(--color-accent) 38%, transparent);
            z-index: 2; pointer-events: none;
        }
        .hero-veil {
            position: absolute; inset: 0;
            background:
                linear-gradient(90deg, color-mix(in srgb, var(--color-primary) 72%, transparent) 0%, color-mix(in srgb, var(--color-primary) 18%, transparent) 58%, color-mix(in srgb, var(--color-primary) 42%, transparent) 100%),
                linear-gradient(180deg, transparent 20%, color-mix(in srgb, var(--color-primary) 55%, transparent) 100%);
        }
        [dir="ltr"] .hero-veil {
            background:
                linear-gradient(270deg, color-mix(in srgb, var(--color-primary) 72%, transparent) 0%, color-mix(in srgb, var(--color-primary) 18%, transparent) 58%, color-mix(in srgb, var(--color-primary) 42%, transparent) 100%),
                linear-gradient(180deg, transparent 20%, color-mix(in srgb, var(--color-primary) 55%, transparent) 100%);
        }
        .hero-inner {
            position: relative; z-index: 1;
            padding: 0 0 clamp(3rem, 8vh, 5.5rem);
            max-width: 38rem;
        }
        .eyebrow {
            display: inline-block; margin: 0 0 1rem;
            font-size: .68rem; letter-spacing: .32em; text-transform: uppercase; font-weight: 500;
            color: var(--color-accent);
        }
        .hero h1 {
            font-family: var(--font-display);
            font-size: clamp(2.6rem, 7vw, 5rem);
            font-weight: 400; line-height: 1.08; margin: 0 0 1.1rem;
            text-wrap: balance;
        }
        .hero p {
            margin: 0 0 1.8rem; max-width: 28rem;
            color: rgba(255,255,255,.82); font-weight: 300; font-size: 1.02rem;
        }
        .hero-actions { display: flex; flex-wrap: wrap; gap: .75rem; }

        /* Sections */
        .section { padding: clamp(4.5rem, 10vw, 8rem) 0; }
        .section-head { max-width: 36rem; margin: 0 auto 3.2rem; text-align: center; }
        .section-head h2 {
            font-family: var(--font-display); font-weight: 400;
            font-size: clamp(2rem, 4vw, 3.2rem); margin: .35rem 0 .7rem; line-height: 1.15;
        }
        .section-head p { margin: 0; color: var(--color-muted); }

        /* About */
        .about {
            display: grid; grid-template-columns: 1.05fr .95fr;
            gap: clamp(1.75rem, 5vw, 4.5rem); align-items: center;
        }
        .about-visual { position: relative; min-height: 520px; overflow: hidden; }
        .about-visual img, .about-visual .ph {
            width: 100%; height: 100%; object-fit: cover; min-height: 520px;
        }
        .about-visual .ph {
            background:
                radial-gradient(ellipse at 40% 30%, color-mix(in srgb, var(--color-accent) 18%, transparent), transparent 55%),
                linear-gradient(165deg, var(--color-secondary), var(--color-primary));
            display: grid; place-items: center;
            font-family: var(--font-display); font-size: clamp(5rem, 12vw, 8rem);
            color: color-mix(in srgb, var(--color-accent) 45%, transparent);
        }
        .about-copy .eyebrow { color: var(--color-accent); }
        .about-copy h2 {
            font-family: var(--font-display); font-weight: 400;
            font-size: clamp(2.1rem, 4vw, 3.3rem); margin: 0 0 1.1rem; line-height: 1.12;
        }
        .about-copy p { color: var(--color-muted); margin: 0 0 1.2rem; max-width: 36rem; }

        /* Collection editorial */
        .looks {
            display: grid; grid-template-columns: 1.15fr .85fr .95fr;
            grid-auto-rows: minmax(220px, auto);
            gap: .85rem;
        }
        .look { position: relative; overflow: hidden; background: var(--color-card); }
        .look:nth-child(1) { grid-row: span 2; }
        .look:nth-child(4) { grid-column: span 2; }
        .look-photo { display: block; aspect-ratio: 3/4; overflow: hidden; background: var(--color-secondary); }
        .look:nth-child(1) .look-photo { aspect-ratio: 3/4.4; height: 100%; }
        .look:nth-child(4) .look-photo { aspect-ratio: 16/9; }
        .look-photo img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 1.1s cubic-bezier(.22,1,.36,1);
        }
        .look:hover .look-photo img { transform: scale(1.04); }
        .look-meta { padding: 1rem 0 .2rem; }
        .look-meta .cat {
            display: block; font-size: .68rem; letter-spacing: .18em; text-transform: uppercase;
            color: var(--color-muted); margin-bottom: .25rem;
        }
        .look-meta h3 {
            font-family: var(--font-display); font-size: 1.55rem; font-weight: 400; margin: 0;
        }
        .look-meta .price { margin: .35rem 0 0; font-size: .88rem; color: var(--color-muted); }
        .empty-note { text-align: center; color: var(--color-muted); font-family: var(--font-display); font-size: 1.3rem; }

        /* Product detail */
        .pdp {
            display: grid; grid-template-columns: 1.15fr .85fr;
            gap: clamp(1.5rem, 5vw, 4rem); align-items: start;
            padding-top: 2rem;
        }
        .pdp-gallery { display: grid; gap: .65rem; }
        .pdp-gallery img { width: 100%; height: auto; object-fit: cover; }
        .pdp-gallery img:first-child { aspect-ratio: 3/4; }
        .pdp h1 {
            font-family: var(--font-display); font-weight: 400;
            font-size: clamp(2.2rem, 4vw, 3.4rem); margin: .4rem 0 1rem; line-height: 1.1;
        }
        .pdp .meta { color: var(--color-muted); margin: 0 0 .85rem; }
        .pdp .prices { display: grid; gap: .25rem; margin: 1.2rem 0; font-size: 1.05rem; }
        .pdp-actions { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.6rem; }

        /* Services */
        .services {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 0;
            border-top: 1px solid var(--color-border);
        }
        .service {
            padding: 2.2rem 1.6rem;
            border-bottom: 1px solid var(--color-border);
            border-inline-end: 1px solid var(--color-border);
        }
        .service:nth-child(2n) { border-inline-end: 0; }
        .service span {
            font-family: var(--font-display); color: var(--color-accent); font-size: 1.4rem;
        }
        .service h3 {
            font-family: var(--font-display); font-weight: 400; font-size: 1.7rem; margin: .4rem 0 .5rem;
        }
        .service p { margin: 0; color: var(--color-muted); }

        /* Gallery */
        .masonry { columns: 3; column-gap: .7rem; }
        .masonry figure { break-inside: avoid; margin: 0 0 .7rem; overflow: hidden; }
        .masonry img {
            width: 100%; display: block; object-fit: cover;
            transition: transform 1s ease;
        }
        .masonry figure:hover img { transform: scale(1.03); }
        .masonry figure:nth-child(3n) img { aspect-ratio: 4/5; }
        .masonry figure:nth-child(4n) img { aspect-ratio: 1/1; }
        .masonry figure:nth-child(5n) img { aspect-ratio: 3/4; }

        /* Appointment band */
        .band {
            padding: clamp(5rem, 12vw, 8rem) var(--space);
            text-align: center; color: #fff;
            background: var(--color-primary);
        }
        .band h2 {
            font-family: var(--font-display); font-weight: 400;
            font-size: clamp(2.1rem, 5vw, 3.6rem); margin: 0 0 .8rem; line-height: 1.15;
        }
        .band p { margin: 0 auto 1.6rem; max-width: 28rem; color: rgba(255,255,255,.78); }

        /* Forms / contact */
        .form { display: grid; gap: .8rem; max-width: 520px; margin-inline: auto; }
        input, textarea, select {
            width: 100%; padding: .95rem 1rem;
            border: 0; border-bottom: 1px solid var(--color-border);
            background: transparent; color: var(--color-text); border-radius: 0;
        }
        input:focus, textarea:focus, select:focus {
            outline: none; border-bottom-color: var(--color-primary);
        }
        .flash {
            max-width: 520px; margin: 0 auto 1rem; text-align: center;
            color: var(--color-primary); font-size: .92rem;
        }
        .contact {
            display: grid; grid-template-columns: .85fr 1.15fr;
            gap: clamp(1.5rem, 5vw, 4rem);
        }
        .contact-aside h3 {
            font-family: var(--font-display); font-weight: 400; font-size: 2rem; margin: 0 0 1.2rem;
        }
        .contact-aside p { margin: 0 0 1rem; color: var(--color-muted); }
        .contact-aside strong {
            display: block; font-size: .68rem; letter-spacing: .18em; text-transform: uppercase;
            color: var(--color-accent); margin-bottom: .2rem; font-weight: 500;
        }

        /* Social */
        .socials { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.4rem; }
        .socials a {
            letter-spacing: .16em; text-transform: uppercase; font-size: .75rem;
            border-bottom: 1px solid var(--color-border); padding-bottom: .2rem;
        }

        /* Footer */
        .site-footer {
            padding: 3.2rem 0 1.4rem;
            border-top: 1px solid var(--color-border);
            font-size: .9rem;
        }
        .foot {
            display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 2rem;
            margin-bottom: 2.2rem;
        }
        .foot-brand {
            font-family: var(--font-display); font-size: 1.8rem; margin-bottom: .5rem;
        }
        .foot p { color: var(--color-muted); margin: 0; }
        .foot strong {
            display: block; font-size: .68rem; letter-spacing: .18em; text-transform: uppercase;
            margin-bottom: .8rem; font-weight: 500;
        }
        .foot-links { display: grid; gap: .4rem; }
        .foot-base {
            display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
            padding-top: 1.1rem; border-top: 1px solid var(--color-border);
            color: var(--color-muted); font-size: .78rem;
        }

        .wa-float {
            position: fixed; inset-inline-end: 1.1rem; bottom: 1.1rem; z-index: 40;
            width: 48px; height: 48px; border-radius: 50%;
            display: grid; place-items: center;
            background: #128C7E; color: #fff;
            box-shadow: 0 10px 24px rgba(18,140,126,.28);
        }
        .wa-float:focus-visible { outline: 2px solid var(--color-accent); outline-offset: 3px; }

        @media (max-width: 1024px) {
            .looks { grid-template-columns: 1fr 1fr; }
            .look:nth-child(1), .look:nth-child(4) { grid-column: auto; grid-row: auto; }
            .masonry { columns: 2; }
        }
        @media (max-width: 860px) {
            .burger { display: inline-flex; }
            .menu, .nav .btn { display: none; }
            .nav-toggle:checked ~ .menu {
                display: flex; flex-direction: column; align-items: flex-start;
                position: absolute; inset-inline: 0; top: 72px;
                background: var(--color-bg); padding: 1.2rem var(--space) 1.6rem;
                border-bottom: 1px solid var(--color-border); gap: 1rem;
            }
            .nav-toggle:checked ~ .btn { display: inline-flex; margin: 0 var(--space) 1.2rem auto; }
            .hero { height: auto; min-height: 86dvh; align-items: end; }
            .hero-inner { padding-block: 7.5rem 2.6rem; }
            .about, .pdp, .contact, .foot, .services { grid-template-columns: 1fr; }
            .about-visual, .about-visual img, .about-visual .ph { min-height: 320px; }
            .service { border-inline-end: 0; }
        }
        @media (max-width: 560px) {
            .looks, .masonry { grid-template-columns: 1fr; columns: 1; }
            .hero-actions, .pdp-actions { flex-direction: column; }
            .hero-actions .btn, .pdp-actions .btn { width: 100%; }
            .hero h1 { font-size: clamp(2.3rem, 11vw, 3.1rem); }
        }
    </style>
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $brandName,
            'image' => $logo_url ?: $ogImage,
            'url' => $canonical ?: ($public_url ?? null),
            'telephone' => $phone ?: null,
            'email' => $email ?: null,
            'address' => !empty($general['address']) ? ['@type' => 'PostalAddress', 'streetAddress' => $general['address']] : null,
        ]), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body>
@if($gtmId !== '')
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
@if(!empty($preview))
    <div class="preview-banner">{{ $t('وضع المعاينة', 'Preview mode') }}</div>
@endif

<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="/">
            @if(!empty($logo_url))
                <img src="{{ $logo_url }}" alt="{{ $brandName }}">
            @endif
            <span class="brand-name">{{ $brandName }}</span>
        </a>
        <input class="nav-toggle" type="checkbox" id="nav-toggle">
        <label class="burger" for="nav-toggle" aria-label="{{ $t('القائمة', 'Menu') }}"><span></span></label>
        <nav class="menu" aria-label="{{ $t('التنقل الرئيسي', 'Primary') }}">
            @forelse($headerNav as $item)
                <a href="{{ $item->url }}">{{ $item->title }}</a>
            @empty
                @foreach($navFallback as $item)
                    <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                @endforeach
            @endforelse
        </nav>
        @if($bookingOn)
            <a class="btn" href="/booking">{{ $ctaLabel }}</a>
        @endif
    </div>
</header>

<main>
@if($product)
    @php
        $dress = $product->dress;
        $gallery = $dressImages($dress);
        $name = $product->site_title ?: ($dress->name ?? $t('قطعة', 'Piece'));
        $category = $dress?->category?->name ?: $dress?->subcategory?->name;
        $desc = trim((string) ($dress->description ?? ''));
        $size = trim((string) ($dress->size ?? ''));
        $color = trim((string) ($dress->color ?? ''));
        $sale = $dress && (float) $dress->sale_price > 0 ? (float) $dress->sale_price : null;
        $rent = $dress && (float) $dress->rental_price > 0 ? (float) $dress->rental_price : null;
        $status = (string) ($dress->status ?? '');
        $waText = rawurlencode($t('أود الاستفسار عن', 'I would like to ask about').' '.$name);
    @endphp
    <section class="section" aria-labelledby="pdp-title">
        <div class="wrap pdp">
            <div class="pdp-gallery">
                @forelse($gallery as $img)
                    <img src="{{ $img }}" alt="{{ $name }}" {{ $loop->first ? '' : 'loading=lazy' }}>
                @empty
                    <div class="about-visual"><div class="ph" role="img" aria-label="{{ $name }}"></div></div>
                @endforelse
            </div>
            <div>
                @if($category)<p class="eyebrow">{{ $category }}</p>@endif
                <h1 id="pdp-title">{{ $name }}</h1>
                @if($desc)<p>{{ $desc }}</p>@endif
                <div class="prices">
                    @if($sale)<div>{{ $t('سعر الشراء', 'Purchase') }} · {{ number_format($sale, 0) }}</div>@endif
                    @if($rent)<div>{{ $t('سعر الإيجار', 'Rental') }} · {{ number_format($rent, 0) }}</div>@endif
                    @if($size)<div class="meta">{{ $t('المقاس', 'Size') }} · {{ $size }}</div>@endif
                    @if($color)<div class="meta">{{ $t('اللون', 'Color') }} · {{ $color }}</div>@endif
                    @if($status)<div class="meta">{{ $t('التوفر', 'Availability') }} · {{ $status }}</div>@endif
                </div>
                <div class="pdp-actions">
                    @if($bookingOn)
                        <a class="btn" href="/booking?product={{ urlencode($name) }}">{{ $product->cta_label ?: $t('احجزي / احجزي القطعة', 'Book / Reserve') }}</a>
                    @endif
                    @if($whatsappDigits !== '')
                        <a class="btn btn-line" href="https://wa.me/{{ $whatsappDigits }}?text={{ $waText }}" target="_blank" rel="noopener">{{ $t('اسألي عبر واتساب', 'Ask on WhatsApp') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@else
    @if($isHome && $show('Hero'))
    <section class="hero" aria-label="{{ $t('الرئيسية', 'Hero') }}">
        <div class="hero-photo">
            @if($heroImage)
                <img src="{{ $heroImage }}" alt="{{ $brandName }}" fetchpriority="high">
            @else
                <div class="hero-fallback" aria-hidden="true">
                    <span class="hero-mono">{{ mb_substr($brandName, 0, 1) }}</span>
                </div>
            @endif
            <div class="hero-veil" aria-hidden="true"></div>
            <div class="hero-frame" aria-hidden="true"></div>
        </div>
        <div class="wrap hero-inner">
            <p class="eyebrow reveal">{{ $t('زفاف · سهرات · كوتور', 'BRIDAL • EVENING • COUTURE') }}</p>
            <h1 class="reveal d1">{{ $heroTitle }}</h1>
            <p class="reveal d2">{{ $heroSubtitle }}</p>
            <div class="hero-actions reveal d3">
                <a class="btn" href="/dresses">{{ $t('استكشفي المجموعة', 'Explore Collection') }}</a>
                @if($bookingOn)
                    <a class="btn btn-ghost" href="/booking">{{ $ctaLabel }}</a>
                @endif
            </div>
        </div>
    </section>
    @endif

    @if(($isHome && $show('About')) || $path === '/about')
    <section class="section" id="about">
        <div class="wrap about">
            <div class="about-visual reveal">
                @if($heroImage)
                    <img src="{{ $heroImage }}" alt="{{ $brandName }}" loading="lazy">
                @elseif($hasProducts && $dressImage($products->skip(1)->first()?->dress))
                    <img src="{{ $dressImage($products->skip(1)->first()->dress) }}" alt="{{ $brandName }}" loading="lazy">
                @else
                    <div class="ph" aria-hidden="true">{{ mb_substr($brandName, 0, 1) }}</div>
                @endif
            </div>
            <div class="about-copy reveal d2">
                <p class="eyebrow">{{ $t('الأتيليه', 'The Atelier') }}</p>
                <h2>{{ $t('قصة تُروى بالقماش والضوء', 'A house of cloth, light and memory') }}</h2>
                <p>{{ $aboutText !== '' ? $aboutText : $heroSubtitle }}</p>
                <a class="btn btn-line" href="/about">{{ $t('اكتشفي قصتنا', 'Discover Our Story') }}</a>
            </div>
        </div>
    </section>
    @endif

    @if(($isHome && $hasProducts && ($show('Featured Products') || $show('Product Grid'))) || $path === '/dresses')
    <section class="section" id="collection">
        <div class="wrap">
            <div class="section-head reveal">
                <p class="eyebrow">{{ $t('المجموعة', 'Collection') }}</p>
                <h2>{{ $t('قطع مختارة بعناية', 'Pieces chosen with intention') }}</h2>
            </div>
            @if($hasProducts)
                <div class="looks">
                    @foreach($products as $i => $item)
                        @php
                            $d = $item->dress;
                            $title = $item->site_title ?: ($d->name ?? $t('قطعة', 'Piece'));
                            $cat = $d?->category?->name;
                            $img = $dressImage($d);
                            $sale = $d && (float) $d->sale_price > 0 ? (float) $d->sale_price : null;
                            $rent = $d && (float) $d->rental_price > 0 ? (float) $d->rental_price : null;
                        @endphp
                        <article class="look reveal">
                            <a class="look-photo" href="/dresses/{{ $item->dress_id ?: $item->id }}">
                                @if($img)
                                    <img src="{{ $img }}" alt="{{ $title }}" loading="{{ $i < 2 ? 'eager' : 'lazy' }}">
                                @endif
                            </a>
                            <div class="look-meta">
                                @if($cat)<span class="cat">{{ $cat }}</span>@endif
                                <h3><a href="/dresses/{{ $item->dress_id ?: $item->id }}">{{ $title }}</a></h3>
                                @if($sale || $rent)
                                    <p class="price">
                                        @if($sale){{ $t('شراء', 'Buy') }} {{ number_format($sale, 0) }}@endif
                                        @if($sale && $rent) · @endif
                                        @if($rent){{ $t('إيجار', 'Rent') }} {{ number_format($rent, 0) }}@endif
                                    </p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="empty-note">{{ $t('المجموعة تُجهَّز حاليًا.', 'The collection is being prepared.') }}</p>
            @endif
        </div>
    </section>
    @endif

    @if((($isHome && $show('Services')) || $path === '/services') && $hasServices)
    <section class="section" id="services">
        <div class="wrap">
            <div class="section-head reveal">
                <p class="eyebrow">{{ $t('الخدمات', 'Services') }}</p>
                <h2>{{ $t('ما نقدّمه في الأتيليه', 'What the house offers') }}</h2>
            </div>
            <div class="services">
                @foreach($services as $i => $service)
                    <article class="service reveal">
                        <span>{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <h3>{{ $service->name }}</h3>
                        @if($service->description)<p>{{ $service->description }}</p>@endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if((($isHome && $show('Gallery')) || $path === '/gallery') && $hasGallery)
    <section class="section" id="gallery">
        <div class="wrap">
            <div class="section-head reveal">
                <p class="eyebrow">{{ $t('المعرض', 'Gallery') }}</p>
                <h2>{{ $t('لحظات من الأتيليه', 'Moments from the house') }}</h2>
            </div>
            <div class="masonry">
                @foreach($galleryImages as $image)
                    <figure>
                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy">
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if($isHome && $show('CTA') && $bookingOn)
    <section class="band" aria-label="{{ $t('الحجز', 'Appointment') }}">
        <h2>{{ $t('إطلالتك المثالية تبدأ بحوار.', 'Your perfect look starts with a conversation.') }}</h2>
        <p>{{ $t('احجزي موعدك الخاص ودعينا نساعدك في إيجاد القطعة التي تشعرين أنها صُنعت لكِ.', 'Book your private appointment and let our team help you find the piece that feels made for you.') }}</p>
        <a class="btn" href="/booking" style="background:#fff;color:var(--color-primary)">{{ $ctaLabel }}</a>
    </section>
    @endif

    @if($isHome && $socials->isNotEmpty() && $show('Social Media'))
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $t('تابعينا', 'Follow') }}</p>
                <h2>{{ $t('تابعي الأتيليه', 'Follow the Atelier') }}</h2>
            </div>
            <div class="socials">
                @foreach($socials as $s)
                    <a href="{{ $s['url'] }}" target="_blank" rel="noopener">{{ $s['label'] }}</a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if($bookingOn && (($isHome && $show('Booking')) || $path === '/booking'))
    <section class="section" id="booking">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $t('الحجز', 'Booking') }}</p>
                <h2>{{ $t('احجزي موعدًا خاصًا', 'Reserve a private hour') }}</h2>
            </div>
            @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
            <form class="form" method="post" action="/booking">
                @csrf
                <input type="hidden" name="kind" value="appointment">
                <input type="hidden" name="product" value="{{ request('product') }}">
                <label class="sr-only" for="bk-name">{{ $t('الاسم', 'Name') }}</label>
                <input id="bk-name" name="name" required placeholder="{{ $t('الاسم الكامل', 'Full name') }}" value="{{ old('name') }}">
                <label class="sr-only" for="bk-phone">{{ $t('الجوال', 'Phone') }}</label>
                <input id="bk-phone" name="phone" placeholder="{{ $t('رقم الجوال', 'Phone number') }}" value="{{ old('phone') }}">
                <label class="sr-only" for="bk-email">{{ $t('البريد', 'Email') }}</label>
                <input id="bk-email" name="email" type="email" placeholder="{{ $t('البريد الإلكتروني', 'Email') }}" value="{{ old('email') }}">
                <label class="sr-only" for="bk-service">{{ $t('الخدمة', 'Service') }}</label>
                <input id="bk-service" name="service" placeholder="{{ $t('نوع الموعد', 'Appointment type') }}" value="{{ old('service') }}">
                <label class="sr-only" for="bk-date">{{ $t('التاريخ', 'Date') }}</label>
                <input id="bk-date" name="preferred_date" placeholder="{{ $t('التاريخ المفضّل', 'Preferred date') }}" value="{{ old('preferred_date') }}">
                <textarea name="notes" rows="4" placeholder="{{ $t('ملاحظات', 'Notes') }}">{{ old('notes') }}</textarea>
                <button class="btn" type="submit">{{ $t('تأكيد الحجز', 'Confirm booking') }}</button>
            </form>
        </div>
    </section>
    @endif

    @if(($isHome && $show('Contact')) || $path === '/contact')
    <section class="section" id="contact">
        <div class="wrap">
            <div class="section-head">
                <p class="eyebrow">{{ $t('تواصل', 'Contact') }}</p>
                <h2>{{ $t('نحن هنا لأجلك', 'We are here for you') }}</h2>
            </div>
            @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
            <div class="contact">
                <aside class="contact-aside">
                    <h3>{{ $brandName }}</h3>
                    @if($phone)<p><strong>{{ $t('هاتف', 'Phone') }}</strong>{{ $phone }}</p>@endif
                    @if($whatsappRaw)<p><strong>WhatsApp</strong>{{ $whatsappRaw }}</p>@endif
                    @if($email)<p><strong>{{ $t('بريد', 'Email') }}</strong>{{ $email }}</p>@endif
                    @if(!empty($general['address']))<p><strong>{{ $t('العنوان', 'Address') }}</strong>{{ $general['address'] }}</p>@endif
                    @if(!empty($general['working_hours']))<p><strong>{{ $t('ساعات العمل', 'Hours') }}</strong>{{ $general['working_hours'] }}</p>@endif
                </aside>
                <form class="form" method="post" action="/contact" style="max-width:none;margin:0">
                    @csrf
                    <input name="name" required placeholder="{{ $t('الاسم', 'Name') }}" value="{{ old('name') }}">
                    <input name="phone" placeholder="{{ $t('الجوال', 'Phone') }}" value="{{ old('phone') }}">
                    <input name="email" type="email" placeholder="{{ $t('البريد', 'Email') }}" value="{{ old('email') }}">
                    <input name="subject" placeholder="{{ $t('الموضوع', 'Subject') }}" value="{{ old('subject') }}">
                    <textarea name="message" rows="5" required placeholder="{{ $t('رسالتك', 'Your message') }}">{{ old('message') }}</textarea>
                    <button class="btn" type="submit">{{ $t('إرسال', 'Send') }}</button>
                </form>
            </div>
        </div>
    </section>
    @endif
@endif
</main>

<footer class="site-footer">
    <div class="wrap foot">
        <div>
            <div class="foot-brand">{{ $brandName }}</div>
            <p>{{ $heroSubtitle }}</p>
        </div>
        <div>
            <strong>{{ $t('تصفحي', 'Explore') }}</strong>
            <div class="foot-links">
                @forelse($footerMenus as $item)
                    <a href="{{ $item->url }}">{{ $item->title }}</a>
                @empty
                    @foreach($navFallback as $item)
                        <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                    @endforeach
                @endforelse
            </div>
        </div>
        <div>
            <strong>{{ $t('تواصل', 'Contact') }}</strong>
            @if($phone)<p>{{ $phone }}</p>@endif
            @if($email)<p>{{ $email }}</p>@endif
            @if(!empty($general['address']))<p>{{ $general['address'] }}</p>@endif
            @if(!empty($general['working_hours']))<p>{{ $general['working_hours'] }}</p>@endif
            @if($socials->isNotEmpty())
                <p style="margin-top:1rem">
                    @foreach($socials as $s)
                        <a href="{{ $s['url'] }}" target="_blank" rel="noopener">{{ $s['label'] }}</a>@if(!$loop->last) · @endif
                    @endforeach
                </p>
            @endif
        </div>
    </div>
    <div class="wrap foot-base">
        <span>© {{ date('Y') }} {{ $brandName }}</span>
        <span>{{ $t('أتيليه أزياء', 'Fashion Atelier') }}</span>
    </div>
</footer>

@if($whatsappDigits !== '')
    <a class="wa-float" href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener" aria-label="WhatsApp">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.5 3.5A11 11 0 0 0 2.1 17.1L1 23l6-1.1A11 11 0 0 0 20.5 3.5Zm-8.5 17a9 9 0 0 1-4.6-1.3l-.3-.2-3.5.7.7-3.4-.2-.3A9 9 0 1 1 12 20.5Zm5-6.7c-.3-.1-1.6-.8-1.8-.9s-.4-.1-.6.1-.7.9-.8 1-.3.2-.6.1a7.4 7.4 0 0 1-2.2-1.4 8.2 8.2 0 0 1-1.5-1.9c-.2-.3 0-.4.1-.6l.4-.5.1-.3c0-.1 0-.3-.1-.4s-.6-1.4-.8-1.9-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3a2.1 2.1 0 0 0-.7 1.6 3.7 3.7 0 0 0 .8 2c.1.1 1.4 2.2 3.5 3 2 .9 2 .6 2.4.6s1.2-.5 1.4-1 .2-.9.1-1-.2-.2-.5-.3Z"/></svg>
    </a>
@endif
</body>
</html>
