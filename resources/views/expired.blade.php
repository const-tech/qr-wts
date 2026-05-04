@extends('whatsapp-gateway::layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="wa-card bg-white p-4 p-md-5 text-center">
            <i class="fa-solid fa-hourglass-end text-warning" style="font-size:3.5rem"></i>
            <h3 class="fw-bold mt-3">{{ __('whatsapp-gateway::messages.expired_title') }}</h3>
            <p class="text-muted">{{ __('whatsapp-gateway::messages.expired_subtitle') }}</p>

            @if (count($upgrades))
                <div class="row g-3 mt-4 text-start">
                    @foreach ($upgrades as $p)
                        <div class="col-md-6 col-lg-4">
                            <div class="wa-card p-4 h-100 border" style="border-color:rgba(0,0,0,.05) !important;">
                                <h5 class="fw-bold">{{ $p->name }}</h5>
                                <div class="fs-3 fw-bold text-success">
                                    {{ number_format($p->price, 0) }} <small class="fs-6 text-muted">{{ $p->currency }}</small>
                                </div>
                                <small class="text-muted">{{ $p->durationDays }} {{ __('whatsapp-gateway::messages.days') }}</small>
                                <ul class="check-list list-unstyled mt-3">
                                    @foreach ($p->features as $f)
                                        <li><i class="fa-solid fa-circle-check"></i> {{ $f }}</li>
                                    @endforeach
                                </ul>
                                @if (!empty($p->raw['checkout_url']))
                                    <a href="{{ $p->raw['checkout_url'] }}" class="btn btn-success w-100 mt-2">
                                        <i class="fa-solid fa-credit-card"></i> {{ __('whatsapp-gateway::messages.upgrade_now') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info mt-3">
                    {{ __('whatsapp-gateway::messages.no_upgrades') }}
                </div>
            @endif

            <hr class="my-4">

            <div class="d-flex justify-content-center gap-2 flex-wrap">
                @if (!empty($loginUrl))
                    <a href="{{ $loginUrl }}" target="_blank" rel="noopener" class="btn btn-warning btn-lg fw-bold text-dark">
                        <i class="fa-solid fa-external-link-alt"></i>
                        {{ __('whatsapp-gateway::messages.open_dashboard', ['brand' => __('whatsapp-gateway::messages.gateway_brand')]) }}
                    </a>
                @endif
                <form method="POST" action="{{ route('whatsapp-gateway.restart', $sub->local_token) }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-lg">
                        <i class="fa-solid fa-rotate"></i> {{ __('whatsapp-gateway::messages.restart_session') }}
                    </button>
                </form>
                <a href="{{ config('whatsapp-gateway.branding.home_url') ?? url('/') }}" class="btn btn-success btn-lg">
                    <i class="fa-solid fa-arrow-right"></i> {{ __('whatsapp-gateway::messages.go_to_program') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
