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

            <div class="d-flex flex-wrap gap-3 align-items-center">
                <a href="{{ route('whatsapp-gateway.register.show') }}"
                   class="btn btn-warning fw-bold text-dark shadow wa-cta-primary">
                    <i class="fa-solid fa-rocket"></i> {{ __('whatsapp-gateway::messages.cta_start_free') }}
                    <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} ms-1"></i>
                </a>
                <button type="button" class="btn btn-outline-light btn-lg"
                        data-bs-toggle="modal" data-bs-target="#waFeaturesModal">
                    <i class="fa-solid fa-circle-info"></i> {{ __('whatsapp-gateway::messages.features') }}
                </button>
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

{{-- ======================== Features modal ======================== --}}
<div class="modal fade" id="waFeaturesModal" tabindex="-1" aria-labelledby="waFeaturesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:1.25rem; overflow:hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg,var(--wa-deep) 0%,var(--wa-green) 100%);">
                <h5 class="modal-title fw-bold" id="waFeaturesModalLabel">
                    <i class="fa-solid fa-bolt"></i>
                    {{ __('whatsapp-gateway::messages.features_modal_title') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('whatsapp-gateway::messages.modal_close') }}"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="wa-feat-card h-100 p-3 rounded-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="wa-feat-icon"><i class="fa-solid fa-paper-plane"></i></span>
                                <h6 class="m-0 fw-bold">{{ __('whatsapp-gateway::messages.auto_send_title') }}</h6>
                            </div>
                            <ul class="check-list list-unstyled mb-0">
                                <li><i class="fa-solid fa-circle-check"></i> {{ __('whatsapp-gateway::messages.auto_send_welcome') }}</li>
                                <li><i class="fa-solid fa-circle-check"></i> {{ __('whatsapp-gateway::messages.auto_send_appointments') }}</li>
                                <li><i class="fa-solid fa-circle-check"></i> {{ __('whatsapp-gateway::messages.auto_send_invoices') }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="wa-feat-card h-100 p-3 rounded-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="wa-feat-icon"><i class="fa-brands fa-whatsapp"></i></span>
                                <h6 class="m-0 fw-bold">{{ __('whatsapp-gateway::messages.direct_contact_title') }}</h6>
                            </div>
                            <p class="text-muted mb-0 small">{{ __('whatsapp-gateway::messages.direct_contact_desc') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <a href="{{ route('whatsapp-gateway.register.show') }}" class="btn btn-success fw-bold">
                    <i class="fa-solid fa-rocket"></i> {{ __('whatsapp-gateway::messages.cta_start_free') }}
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    {{ __('whatsapp-gateway::messages.modal_close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .wa-cta-primary { font-size:1.35rem; padding:1rem 2.4rem; border-radius:1rem; }
    .wa-cta-primary:hover { transform: translateY(-2px); transition: transform .2s ease; }
    .wa-feat-card { background: linear-gradient(135deg, rgba(37,211,102,.06), rgba(18,140,126,.04)); border:1px solid rgba(37,211,102,.18); }
    .wa-feat-icon { width:42px; height:42px; border-radius:50%; background:var(--wa-green); color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
</style>
@endsection
