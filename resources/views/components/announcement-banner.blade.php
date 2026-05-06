@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur']);
    $g = $gradient();
    $waLink = $waLink();
@endphp

<!-- Slide 2 -->
<div class="swiper-slide slide-2">
    <div class="star star-top-left">✦</div>
    <div class="star star-top-right">✦</div>
    <div class="star star-bottom-left">✦</div>
    <div class="star star-bottom-right">✦</div>

    <div class="content">
        <h1 class="title">
            {{ $title }}
        </h1>
        @if ($description)
            <p class="description">
                {!! $description !!}
            </p>
        @endif
        <div class="buttons">
            @if ($phone)
                <a href="tel:{{ $phone }}" class="btn-slider btn btn-primary"><i
                        class="fa-solid fa-phone me-1"></i> {{ $contactCallLabel }} {{ $phone }}</a>
            @endif

            @if ($waLink)
                <a href="{{ $waLink }}" target="_blank" class="btn-slider btn btn-secondary"><i
                        class="fa-brands fa-whatsapp me-1"></i> {{ $contactWaLabel }}</a>
            @endif

            @if ($ctaUrl)
                <a href="{{ $ctaUrl }}" class="btn-slider btn btn-light">
                    <i class="fa-solid fa-arrow-{{ $isRtl ? 'left' : 'right' }} me-1"></i> {{ $ctaLabel }}
                </a>
            @endif
        </div>
        {{-- <p class="trial-note">{{ $trialNote2 }}</p> --}}
    </div>
</div>
