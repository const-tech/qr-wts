@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur']);
    $waPhone = ltrim(preg_replace('/[^0-9]/', '', $contactPhone ?? ''), '0');
@endphp
<div {{ $attributes->merge(['class' => 'whatsapp-promo-card position-relative overflow-hidden rounded-4 shadow-lg my-3 p-4 p-md-5']) }}
     style="background: linear-gradient(135deg,#128C7E 0%,#25D366 50%,#34e57a 100%); color:#fff;">
    <div class="position-absolute top-0 {{ $isRtl ? 'start-0' : 'end-0' }} opacity-25"
         style="font-size:9rem;line-height:1;pointer-events:none;">
        <i class="fa-brands fa-whatsapp"></i>
    </div>
    <div class="row align-items-center g-4 position-relative">
        <div class="col-12 col-lg-8">
            <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-2 fs-6">
                <i class="fa-solid fa-fire me-1"></i> {{ $eyebrow }}
            </span>
            <h2 class="fw-bold mb-2 text-white" style="font-size:1.9rem;">
                {{ $title }}
            </h2>
            <p class="mb-3 fs-6" style="opacity:.95;">
                {!! $subtitle !!}
            </p>
            <ul class="list-unstyled d-flex flex-wrap gap-3 mb-3 mb-lg-4">
                @foreach ($features as $feature)
                    <li><i class="fa-solid fa-circle-check"></i> {{ $feature }}</li>
                @endforeach
            </ul>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $ctaUrl }}"
                   class="btn btn-warning btn-lg fw-bold text-dark shadow-sm">
                    <i class="fa-solid fa-rocket me-1"></i> {{ $ctaLabel }}
                </a>
                <a href="{{ $secondaryUrl }}" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-circle-info me-1"></i> {{ $secondaryLabel }}
                </a>
                @if ($contactPhone)
                    <a target="_blank" rel="noopener"
                       href="https://wa.me/{{ $waPhone ? '966' . $waPhone : '' }}"
                       class="btn btn-outline-light btn-lg">
                        <i class="fa-brands fa-whatsapp me-1"></i> تواصل معنا
                    </a>
                @endif
            </div>
        </div>
        <div class="col-12 col-lg-4 text-center d-none d-lg-block">
            <div class="bg-white text-dark rounded-3 p-3 shadow-sm">
                <div class="fs-1 fw-bold text-success">{{ $price }} ر.س</div>
                <div class="text-muted">{{ $priceLabel }}</div>
                <hr>
                <div class="small">
                    <div class="mb-1"><i class="fa-solid fa-bolt text-warning"></i> تفعيل خلال دقائق</div>
                    <div class="mb-1"><i class="fa-solid fa-shield-halved text-success"></i> آمن وموثوق</div>
                    <div><i class="fa-solid fa-headset text-primary"></i> دعم فني 7/24</div>
                </div>
            </div>
        </div>
    </div>
</div>
