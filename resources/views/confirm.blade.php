@extends('whatsapp-gateway::layout')

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
                    <i class="fa-solid fa-circle-check text-success" style="font-size:3rem"></i>
                    <h3 class="fw-bold mt-2">{{ __('whatsapp-gateway::messages.confirm_title') }}</h3>
                    <p class="text-muted mb-0">{{ __('whatsapp-gateway::messages.confirm_subtitle') }}</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                {{-- Personal info (read-only) --}}
                <h6 class="text-muted fw-bold small text-uppercase mb-3">
                    <i class="fa-solid fa-user me-1"></i>
                    {{ __('whatsapp-gateway::messages.your_info') }}
                </h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('whatsapp-gateway::messages.name') }}</label>
                        <input type="text" class="form-control form-control-lg bg-light" value="{{ $sub->name }}"
                            disabled readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('whatsapp-gateway::messages.phone') }}</label>
                        <input type="tel" class="form-control form-control-lg bg-light" value="{{ $sub->phone }}"
                            dir="ltr" disabled readonly>
                    </div>
                    @if ($sub->email)
                        <div class="col-md-6">
                            <label class="form-label">{{ __('whatsapp-gateway::messages.email') }}</label>
                            <input type="text" class="form-control bg-light" value="{{ $sub->email }}" disabled
                                readonly>
                        </div>
                    @endif
                    @if ($sub->business)
                        <div class="col-md-6">
                            <label class="form-label">{{ __('whatsapp-gateway::messages.business') }}</label>
                            <input type="text" class="form-control bg-light" value="{{ $sub->business }}" disabled
                                readonly>
                        </div>
                    @endif
                </div>

                {{-- Obtained credentials --}}
                @if ($sub->instance_id)
                    <hr>
                    <h6 class="text-muted fw-bold small text-uppercase mb-3">
                        <i class="fa-solid fa-key me-1 text-success"></i>
                        {{ __('whatsapp-gateway::messages.confirm_credentials_title') }}
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('whatsapp-gateway::messages.instance_id') }}</label>
                            <div class="input-group">
                                <input type="text" id="confirmInstanceId" class="form-control font-monospace bg-light"
                                    value="{{ $sub->instance_id }}" dir="ltr" disabled readonly>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="navigator.clipboard.writeText('{{ $sub->instance_id }}')">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('whatsapp-gateway::messages.access_token') }}</label>
                            <div class="input-group">
                                <input type="password" id="confirmAccessToken" class="form-control font-monospace bg-light"
                                    value="{{ $sub->token }}" dir="ltr" disabled readonly>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="this.previousElementSibling.type = this.previousElementSibling.type === 'password' ? 'text' : 'password'">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="navigator.clipboard.writeText('{{ $sub->token }}')">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- CTA --}}
                <div class="nav-actions">
                    <span></span>
                    <a href="{{ route('whatsapp-gateway.connect', ['token' => $sub->local_token]) }}"
                        class="btn btn-success btn-lg fw-bold flex-grow-1">
                        <i class="fa-brands fa-whatsapp me-1"></i>
                        {{ __('whatsapp-gateway::messages.confirm_cta') }}
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} ms-1"></i>
                    </a>
                </div>

                <p class="text-center text-muted small mt-3 mb-0">
                    <i class="fa-solid fa-lock me-1"></i>
                    {{ __('whatsapp-gateway::messages.confirm_note') }}
                </p>
            </div>
        </div>
    </div>
@endsection
