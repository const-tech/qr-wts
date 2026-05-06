@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur']);
    $waPhone = ltrim(preg_replace('/[^0-9]/', '', $contactPhone ?? ''), '0');
@endphp


<!-- Slide 1 -->
<div class="swiper-slide">
    <div class="star star-top-left">✦</div>
    <div class="star star-top-right">✦</div>
    <div class="star star-bottom-left">✦</div>
    <div class="star star-bottom-right">✦</div>

    <div class="content">
        <h1 class="title">
            {{ $title }}
        </h1>
        <p class="description">
            {!! $subtitle !!}
        </p>
        <ul class="list-op list-unstyled d-flex flex-wrap gap-3 mb-3 mb-lg-4">
            @foreach ($features as $feature)
                <li><i class="fa-solid fa-circle-check"></i> {{ $feature }}</li>
            @endforeach
        </ul>
        <div class="buttons">
            <a href="{{ $ctaUrl }}" class="btn-slider btn btn-primary">{{ $ctaLabel }}</a>
            <a href="{{ $secondaryUrl }}" class="btn-slider btn btn-secondary">{{ $secondaryLabel }}</a>
            @if ($contactPhone)
                <a target="_blank" rel="noopener" href="https://wa.me/{{ $waPhone ? '966' . $waPhone : '' }}"
                    class="btn-slider btn btn-light">
                    تواصل معنا
                </a>
            @endif
        </div>
        <p class="trial-note">تجربة مجانية لمدة 7 أيام، لا حاجة لبطاقة ائتمان.</p>
    </div>
</div>
