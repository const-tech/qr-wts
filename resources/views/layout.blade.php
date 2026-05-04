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
    <title>@yield('title', $platformName) — {{ $appName }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap{{ $isRtl ? '.rtl' : '' }}.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --wa-green:#25D366; --wa-dark:#128C7E; --wa-deep:#075E54; }
        body { font-family: 'Tajawal', system-ui, sans-serif; background:#f4f7f9; min-height:100vh; }
        .wa-bg { background: radial-gradient(circle at 20% 0%, rgba(37,211,102,.12), transparent 40%),
                          radial-gradient(circle at 80% 100%, rgba(18,140,126,.12), transparent 40%); }
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
                <small class="text-muted fw-normal" style="font-size:.75rem;">{{ $appName }}</small>
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
    &copy; {{ date('Y') }} {{ $appName }}
    @if (!empty($branding['support_phone']))
        · <a href="tel:{{ $branding['support_phone'] }}" class="text-decoration-none">
            <i class="fa-solid fa-headset"></i> {{ $branding['support_phone'] }}
        </a>
    @endif
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
