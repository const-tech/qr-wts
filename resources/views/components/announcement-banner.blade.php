@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur']);
    $g = $gradient();
    $waLink = $waLink();
@endphp
<div {{ $attributes->merge(['class' => 'wa-announcement position-relative overflow-hidden rounded-4 shadow-lg my-3 p-4 p-md-5']) }}
     style="background: linear-gradient(135deg, {{ $g['from'] }} 0%, {{ $g['to'] }} 100%); color: {{ $g['fg'] }};">

    <div class="position-absolute top-0 {{ $isRtl ? 'start-0' : 'end-0' }} opacity-25"
         style="font-size:9rem; line-height:1; pointer-events:none; transform: translate(-1rem, -1rem);">
        <i class="fa-solid {{ $icon }}"></i>
    </div>

    <div class="row align-items-center g-4 position-relative">
        <div class="col-12 col-lg-9">
            @if ($eyebrow)
                <span class="badge bg-white text-dark fw-bold mb-2 px-3 py-2 fs-6">
                    <i class="fa-solid fa-sparkles me-1"></i> {{ $eyebrow }}
                </span>
            @endif

            <h2 class="fw-bold mb-2" style="font-size:1.7rem; color:{{ $g['fg'] }};">
                {{ $title }}
            </h2>

            @if ($description)
                <p class="mb-3 fs-6" style="opacity:.92;">
                    {!! $description !!}
                </p>
            @endif

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if ($phone)
                    <a href="tel:{{ $phone }}" class="btn btn-light fw-bold">
                        <i class="fa-solid fa-phone me-1"></i> {{ $contactCallLabel }} {{ $phone }}
                    </a>
                @endif
                @if ($waLink)
                    <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-success fw-bold">
                        <i class="fa-brands fa-whatsapp me-1"></i> {{ $contactWaLabel }}
                    </a>
                @endif
                @if ($ctaUrl)
                    <a href="{{ $ctaUrl }}" class="btn btn-outline-light fw-bold">
                        <i class="fa-solid fa-arrow-{{ $isRtl ? 'left' : 'right' }} me-1"></i> {{ $ctaLabel }}
                    </a>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-3 text-center d-none d-lg-block">
            <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center shadow-sm"
                 style="width:120px; height:120px;">
                <i class="fa-solid {{ $icon }}" style="font-size:3.5rem; color:{{ $g['from'] }};"></i>
            </div>
        </div>
    </div>
</div>
