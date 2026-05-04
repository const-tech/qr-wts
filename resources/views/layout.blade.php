@php
    $branding = config('whatsapp-gateway.branding');
    $platformName = $branding['platform_name'] ?? __('whatsapp-gateway::messages.platform_name');
    $appName = $branding['app_name'] ?? config('app.name');
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur']);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $platformName)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap{{ $isRtl ? '.rtl' : '' }}.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --wa-green:#25D366; --wa-dark:#128C7E; --wa-deep:#075E54; }
        body { font-family: 'Tajawal', system-ui, sans-serif; background:#f4f7f9; min-height:100vh; position:relative; }
        /* Constrain only the decorative bg, never the body itself — body
           overflow:hidden breaks Bootstrap modal scroll lock & padding. */
        .wa-bg { background: radial-gradient(circle at 20% 0%, rgba(37,211,102,.12), transparent 40%),
                          radial-gradient(circle at 80% 100%, rgba(18,140,126,.12), transparent 40%); }
        /* Decorative floating WhatsApp glyphs — pure CSS, no extra assets. */
        .wa-bg-deco { position:fixed; inset:0; pointer-events:none; overflow:hidden; z-index:0; isolation:isolate; }
        .wa-bg-deco i { position:absolute; font-family:'Font Awesome 6 Brands','Font Awesome 6 Free'; opacity:.06; color:var(--wa-deep); animation: wa-float 18s ease-in-out infinite; }
        .wa-bg-deco i:nth-child(1){ top:10%;  left:8%;  font-size:6rem;  animation-delay:-2s; }
        .wa-bg-deco i:nth-child(2){ top:65%;  left:12%; font-size:4rem;  animation-delay:-7s; }
        .wa-bg-deco i:nth-child(3){ top:20%;  right:10%;font-size:5rem;  animation-delay:-4s; }
        .wa-bg-deco i:nth-child(4){ top:75%;  right:18%;font-size:7rem;  animation-delay:-11s; }
        .wa-bg-deco i:nth-child(5){ top:40%;  left:48%; font-size:3rem;  animation-delay:-9s; opacity:.04; }
        @keyframes wa-float { 0%,100%{transform:translateY(0) rotate(0);} 50%{transform:translateY(-22px) rotate(8deg);} }
        main, nav, footer { position:relative; z-index:1; }
        /* Full-page loading overlay shown on form submit */
        #wa-submit-overlay { position:fixed; inset:0; background:rgba(255,255,255,.94); backdrop-filter:blur(4px); z-index:9999; display:none; align-items:center; justify-content:center; }
        #wa-submit-overlay.show { display:flex; }
        #wa-submit-overlay .wa-dots span { width:1rem; height:1rem; }
        .wa-progress { width:240px; height:.4rem; border-radius:999px; background:rgba(37,211,102,.15); overflow:hidden; margin-top:1rem; }
        .wa-progress::after { content:''; display:block; width:30%; height:100%; background:var(--wa-green); border-radius:999px; animation: wa-slide 1.6s infinite; }
        @keyframes wa-slide { 0%{transform:translateX(-100%);} 100%{transform:translateX(440%);} }
        .wa-card { border:none; border-radius: 1.25rem; box-shadow: 0 10px 40px rgba(0,0,0,.06); }
        .wa-hero { background: linear-gradient(135deg,var(--wa-deep) 0%,var(--wa-dark) 50%,var(--wa-green) 100%); color:#fff; }
        .wa-hero .badge-eyebrow { background:#FFD43B; color:#1f1f1f; font-weight:700; padding:.5rem 1rem; border-radius:999px; display:inline-block; }
        .wa-icon-watermark { position:absolute; opacity:.15; font-size:18rem; line-height:1; pointer-events:none; }
        .feature-pill { background: rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25); border-radius:999px; padding:.45rem 1rem; }
        .step-indicator { display:flex; gap:.5rem; align-items:center; }
        .step-indicator .dot { width:.75rem; height:.75rem; border-radius:50%; background:#dee2e6; }
        .step-indicator .dot.active { background:var(--wa-green); transform:scale(1.2); }
        .qr-frame { border:8px solid var(--wa-green); border-radius:1.5rem; padding:.75rem; display:inline-block; background:#fff; box-shadow:0 12px 40px rgba(0,0,0,.08); }
        .qr-frame img { display:block; max-width:280px; width:100%; height:auto; }
        .pulse-dot { width:.6rem; height:.6rem; border-radius:50%; background:var(--wa-green); display:inline-block; box-shadow:0 0 0 0 rgba(37,211,102,.7); animation: wapulse 1.5s infinite; }
        @keyframes wapulse { 0%{box-shadow:0 0 0 0 rgba(37,211,102,.7);} 70%{box-shadow:0 0 0 12px rgba(37,211,102,0);} 100%{box-shadow:0 0 0 0 rgba(37,211,102,0);} }
        /* Lightweight three-dot loader, no background, no borders */
        .wa-dots { display:inline-flex; gap:.45rem; align-items:center; padding:.5rem 0; }
        .wa-dots span { width:.6rem; height:.6rem; border-radius:50%; background:var(--wa-green); animation: wa-bounce 1.2s infinite ease-in-out both; }
        .wa-dots span:nth-child(2){ animation-delay:.15s; }
        .wa-dots span:nth-child(3){ animation-delay:.3s; }
        @keyframes wa-bounce { 0%,80%,100%{ transform:scale(.4); opacity:.4; } 40%{ transform:scale(1); opacity:1; } }
        .check-list li { padding:.35rem 0; }
        .check-list i { color:var(--wa-green); }
        .stat-pill { background:#fff; border-radius:1rem; padding:1rem 1.25rem; box-shadow:0 4px 14px rgba(0,0,0,.05); }
        .nav-actions { display:flex; gap:.5rem; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-top:1.5rem; }
        .brand-logo-stack { display:flex; gap:.5rem; align-items:center; }
        .brand-logo-stack .wa-circle { width:38px; height:38px; border-radius:50%; background:var(--wa-green); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:1.25rem; box-shadow:0 4px 12px rgba(37,211,102,.35); }
        .brand-logo-stack .platform-circle { width:38px; height:38px; border-radius:50%; background:#fff; color:var(--wa-deep); display:inline-flex; align-items:center; justify-content:center; font-size:1.25rem; border:2px solid var(--wa-green); margin-inline-start:-.6rem; }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @stack('head')
</head>
<body class="wa-bg">
<div class="wa-bg-deco" aria-hidden="true">
    <i class="fa-brands fa-whatsapp"></i>
    <i class="fa-brands fa-whatsapp"></i>
    <i class="fa-brands fa-whatsapp"></i>
    <i class="fa-brands fa-whatsapp"></i>
    <i class="fa-solid fa-comment"></i>
</div>

<div id="wa-submit-overlay" role="status" aria-live="polite">
    <div class="text-center">
        <div class="wa-dots" aria-hidden="true"><span></span><span></span><span></span></div>
        <h4 class="fw-bold mt-3 mb-1">{{ __('whatsapp-gateway::messages.submitting_title') }}</h4>
        <p class="text-muted mb-0">{{ __('whatsapp-gateway::messages.submitting_subtitle') }}</p>
        <div class="wa-progress mx-auto"></div>
    </div>
</div>

<nav class="navbar bg-white shadow-sm py-3 mb-4">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2 m-0" href="{{ $branding['home_url'] ?? url('/') }}">
            <span class="brand-logo-stack">
                @if (!empty($branding['platform_logo']))
                    <img src="{{ $branding['platform_logo'] }}" alt="{{ $platformName }}" style="height:38px; border-radius:50%;">
                @else
                    <span class="platform-circle"><i class="fa-solid fa-mobile-screen-button"></i></span>
                @endif
                <span class="wa-circle"><i class="fa-brands fa-whatsapp"></i></span>
            </span>
            <span class="d-flex flex-column lh-1">
                <span class="fs-6">{{ $platformName }}</span>
                @if (! empty($appName) && strcasecmp($appName, 'Laravel') !== 0)
                    <small class="text-muted fw-normal" style="font-size:.75rem;">{{ $appName }}</small>
                @endif
            </span>
        </a>
        <a href="{{ $branding['home_url'] ?? url('/') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-{{ $isRtl ? 'left' : 'right' }} me-1"></i>
            {{ __('whatsapp-gateway::messages.go_to_program') }}
        </a>
    </div>
</nav>

<main class="container pb-5">
    @yield('content')
</main>

<footer class="text-center text-muted py-4 small">
    &copy; {{ date('Y') }} {{ $platformName }}
    @if (!empty($branding['support_phone']))
        · <a href="tel:{{ $branding['support_phone'] }}" class="text-decoration-none">
            <i class="fa-solid fa-headset"></i> {{ $branding['support_phone'] }}
        </a>
    @endif
</footer>

{{-- Modals are mounted here, outside <main>, so Bootstrap controls
     the backdrop & focus trap without z-index fights. --}}
@stack('modals')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show full-page loading overlay on any form marked .wa-submit-loader
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.classList && form.classList.contains('wa-submit-loader')) {
        const ov = document.getElementById('wa-submit-overlay');
        if (ov) ov.classList.add('show');
    }
}, true);
</script>
@stack('scripts')
</body>
</html>
