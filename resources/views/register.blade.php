@extends('whatsapp-gateway::layout')

@php
    $brand    = __('whatsapp-gateway::messages.gateway_brand');
    $termsUrl = config('whatsapp-gateway.branding.terms_url');
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <div class="step-indicator mb-3 justify-content-center">
            <span class="dot"></span>
            <span class="dot active"></span>
            <span class="dot"></span>
        </div>

        <div class="wa-card bg-white p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="fa-brands fa-whatsapp text-success" style="font-size:3rem"></i>
                <h3 class="fw-bold mt-2">{{ __('whatsapp-gateway::messages.form_title') }}</h3>
                <p class="text-muted">{{ __('whatsapp-gateway::messages.form_subtitle') }}</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('whatsapp-gateway.register') }}" novalidate>
                @csrf
                <input type="hidden" name="package" value="{{ $free->id ?? 'free' }}">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('whatsapp-gateway::messages.name') }}</label>
                        <input type="text" name="name" required
                               value="{{ old('name') }}"
                               class="form-control form-control-lg @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('whatsapp-gateway::messages.phone') }}</label>
                        <input type="tel" name="phone" required dir="ltr"
                               value="{{ old('phone') }}"
                               placeholder="0506499275"
                               class="form-control form-control-lg @error('phone') is-invalid @enderror">
                        <small class="text-muted">{{ __('whatsapp-gateway::messages.phone_hint') }}</small>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('whatsapp-gateway::messages.email') }}</label>
                        <input type="email" name="email" dir="ltr"
                               value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('whatsapp-gateway::messages.business') }}</label>
                        <input type="text" name="business"
                               value="{{ old('business') }}"
                               class="form-control @error('business') is-invalid @enderror">
                        @error('business')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if ($claimMode)
                    {{-- Fallback claim mode — only shown when reseller flow is disabled. --}}
                    <hr class="my-4">
                    <h6 class="fw-bold text-muted small">
                        <i class="fa-solid fa-key text-success"></i>
                        {{ __('whatsapp-gateway::messages.claim_step2_title', ['brand' => $brand]) }}
                    </h6>
                    <p class="text-muted small">{{ __('whatsapp-gateway::messages.claim_step2_subtitle', ['brand' => $brand]) }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('whatsapp-gateway::messages.instance_id') }}</label>
                            <input type="text" name="instance_id" required dir="ltr"
                                   value="{{ old('instance_id') }}"
                                   placeholder="instance12345"
                                   class="form-control font-monospace @error('instance_id') is-invalid @enderror">
                            @error('instance_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('whatsapp-gateway::messages.access_token') }}</label>
                            <input type="text" name="access_token" required dir="ltr"
                                   value="{{ old('access_token') }}"
                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                   class="form-control font-monospace @error('access_token') is-invalid @enderror">
                            @error('access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                @endif

                <div class="form-check mt-4">
                    <input type="checkbox" required class="form-check-input" id="agree">
                    <label class="form-check-label" for="agree">
                        {!! __('whatsapp-gateway::messages.agree_terms_html', ['url' => $termsUrl]) !!}
                    </label>
                </div>

                <div class="nav-actions">
                    <a href="{{ route('whatsapp-gateway.landing') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                        {{ __('whatsapp-gateway::messages.cta_back') }}
                    </a>
                    <button type="submit" class="btn btn-success btn-lg fw-bold flex-grow-1">
                        <i class="fa-solid fa-rocket"></i>
                        {{ __('whatsapp-gateway::messages.submit_register') }}
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} ms-1"></i>
                    </button>
                </div>

                <p class="text-center text-muted small mt-3 mb-0">
                    <i class="fa-solid fa-lock"></i>
                    {{ __('whatsapp-gateway::messages.auto_provision_note') }}
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Preserve the registration form across navigation (Back button) using
// localStorage. Cleared after the customer reaches the Connected screen.
(function() {
    const KEY  = 'wa_register_form';
    const form = document.querySelector('form[action="{{ route('whatsapp-gateway.register') }}"]');
    if (!form) return;

    // Restore previously typed values, only if the field is empty (so old()
    // values from a server-side validation error still take priority).
    try {
        const saved = JSON.parse(localStorage.getItem(KEY) || '{}');
        Object.entries(saved).forEach(([name, value]) => {
            const el = form.querySelector('[name="' + name + '"]');
            if (el && !el.value && value) el.value = value;
        });
    } catch (e) {}

    form.addEventListener('input', function() {
        try {
            const data = {};
            new FormData(form).forEach(function(v, k) {
                if (k !== '_token' && k !== 'package' && k !== 'access_token') data[k] = v;
            });
            localStorage.setItem(KEY, JSON.stringify(data));
        } catch (e) {}
    });
})();
</script>
@endpush
