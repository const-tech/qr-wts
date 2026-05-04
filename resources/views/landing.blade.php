@extends('whatsapp-gateway::layout')

@section('content')
<div class="wa-hero wa-card position-relative overflow-hidden p-4 p-md-5 mb-4">
    <div class="wa-icon-watermark" style="@if(app()->getLocale()==='ar') left:-3rem; @else right:-3rem; @endif top:-3rem;">
        <i class="fa-brands fa-whatsapp"></i>
    </div>
    <div class="row align-items-center g-4 position-relative">
        <div class="col-12 col-lg-7">
            <div class="step-indicator mb-3">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
            <span class="badge-eyebrow mb-3">
                <i class="fa-solid fa-fire"></i> {{ __('whatsapp-gateway::messages.hero_eyebrow') }}
            </span>
            <h1 class="fw-bold mb-3" style="font-size:2.1rem; line-height:1.4;">
                {{ __('whatsapp-gateway::messages.hero_title') }}
            </h1>
            <p class="fs-5 mb-4" style="opacity:.95;">
                {{ __('whatsapp-gateway::messages.hero_subtitle') }}
            </p>

            @if ($free)
                <ul class="list-unstyled d-flex flex-wrap gap-2 mb-4">
                    @foreach ($free->features as $feature)
                        <li class="feature-pill">
                            <i class="fa-solid fa-circle-check text-warning"></i>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('whatsapp-gateway.register.show') }}"
                   class="btn btn-warning btn-lg fw-bold text-dark shadow-sm px-4">
                    <i class="fa-solid fa-rocket"></i> {{ __('whatsapp-gateway::messages.cta_start_free') }}
                    <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} ms-1"></i>
                </a>
                <a href="#packages" class="btn btn-outline-light btn-lg">
                    <i class="fa-solid fa-circle-info"></i> {{ __('whatsapp-gateway::messages.features') }}
                </a>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            @if ($free)
                <div class="bg-white text-dark rounded-4 p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <h3 class="m-0 fw-bold">{{ $free->name }}</h3>
                        <span class="badge bg-success-subtle text-success-emphasis fs-6">
                            {{ __('whatsapp-gateway::messages.free_for_days', ['days' => $free->durationDays]) }}
                        </span>
                    </div>
                    <div class="display-5 fw-bold text-success my-2">
                        {{ number_format($free->price, 0) }}
                        <small class="fs-5 text-muted">{{ __('whatsapp-gateway::messages.currency_sar') }}</small>
                    </div>
                    <ul class="check-list list-unstyled mb-0">
                        @foreach ($free->features as $feature)
                            <li><i class="fa-solid fa-circle-check"></i> {{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

@if (count($packages) > 1)
    <h4 id="packages" class="fw-bold mb-3 mt-5">{{ __('whatsapp-gateway::messages.features') }}</h4>
    <div class="row g-3">
        @foreach ($packages as $p)
            <div class="col-md-6 col-lg-4">
                <div class="wa-card p-4 h-100 bg-white">
                    <h5 class="fw-bold">{{ $p->name }}</h5>
                    <div class="fs-3 fw-bold text-success">
                        {{ $p->isFree ? __('whatsapp-gateway::messages.free_label') : number_format($p->price, 0) . ' ' . __('whatsapp-gateway::messages.currency_sar') }}
                    </div>
                    <small class="text-muted">{{ $p->durationDays }} {{ __('whatsapp-gateway::messages.days') }}</small>
                    <ul class="check-list list-unstyled mt-3 mb-0">
                        @foreach ($p->features as $feature)
                            <li><i class="fa-solid fa-circle-check"></i> {{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="nav-actions">
    <a href="{{ config('whatsapp-gateway.branding.home_url') ?? url('/') }}"
       class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
        {{ __('whatsapp-gateway::messages.go_to_program') }}
    </a>
    <a href="{{ route('whatsapp-gateway.register.show') }}" class="btn btn-success">
        {{ __('whatsapp-gateway::messages.cta_continue') }}
        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
    </a>
</div>
@endsection
