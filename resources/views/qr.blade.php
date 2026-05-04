@extends('whatsapp-gateway::layout')

@php
    $brand = __('whatsapp-gateway::messages.gateway_brand');
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-9">

        <div class="step-indicator mb-3 justify-content-center">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot active"></span>
        </div>

        <div class="wa-card bg-white p-4 p-md-5">

            @if (session('status'))
                <div class="alert alert-success border-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-check fs-4"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{-- ================= PENDING / QR ================= --}}
            <div id="wa-pending" class="text-center">
                <h3 class="fw-bold">{{ __('whatsapp-gateway::messages.connect_title') }}</h3>
                <p class="text-muted mb-4">{{ __('whatsapp-gateway::messages.connect_subtitle') }}</p>
                <div class="qr-frame mb-3">
                    <div id="wa-qr-placeholder" class="d-flex flex-column align-items-center justify-content-center text-muted"
                         style="width:280px; height:280px; gap:.75rem;">
                        <div class="spinner-border text-success" role="status"></div>
                        <small id="wa-prep-msg" class="px-3 small">{{ __('whatsapp-gateway::messages.preparing_session') }}</small>
                    </div>
                    <img id="wa-qr-img" alt="QR" style="display:none">
                </div>
                <div class="text-muted">
                    <span class="pulse-dot"></span>
                    <span id="wa-status-label" class="ms-2">{{ __('whatsapp-gateway::messages.connect_polling') }}</span>
                </div>
                <div id="wa-prep-help" class="alert alert-info mt-3 d-none">
                    <i class="fa-solid fa-circle-info"></i>
                    {{ __('whatsapp-gateway::messages.preparing_long') }}
                    @if (config('whatsapp-gateway.branding.support_phone'))
                        <a href="tel:{{ config('whatsapp-gateway.branding.support_phone') }}" class="alert-link ms-2">
                            <i class="fa-solid fa-headset"></i> {{ config('whatsapp-gateway.branding.support_phone') }}
                        </a>
                    @endif
                </div>
            </div>

            {{-- ================= CONNECTED (success) ================= --}}
            <div id="wa-connected" class="text-center d-none">
                <div class="celebration-icon">
                    <span style="font-size:4rem">🎉</span>
                </div>
                <h3 class="fw-bold mt-3 text-success">{{ __('whatsapp-gateway::messages.success_title') }}</h3>
                <p class="text-muted mb-1">{{ __('whatsapp-gateway::messages.connected_subtitle') }}</p>
                <p class="fw-bold text-success mb-4">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ __('whatsapp-gateway::messages.success_message') }}
                </p>

                {{-- Plan info — populated live from /poll --}}
                <div id="wa-plan-info" class="wa-card bg-light p-3 mt-3 d-none">
                    <h6 class="fw-bold mb-3 text-success">
                        <i class="fa-solid fa-box-open"></i>
                        {{ __('whatsapp-gateway::messages.plan_info_title') }}
                    </h6>
                    <div class="row g-2 text-start">
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">{{ __('whatsapp-gateway::messages.plan_status') }}</small>
                            <strong id="wa-plan-state" class="text-success">—</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">{{ __('whatsapp-gateway::messages.plan_used') }}</small>
                            <strong id="wa-plan-used">—</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">{{ __('whatsapp-gateway::messages.plan_limit') }}</small>
                            <strong id="wa-plan-limit">—</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">{{ __('whatsapp-gateway::messages.expires_at') }}</small>
                            <strong id="wa-plan-expires">—</strong>
                        </div>
                    </div>
                    <div id="wa-plan-progress-wrap" class="mt-3 d-none">
                        <div class="progress" style="height:.6rem;">
                            <div id="wa-plan-progress" class="progress-bar bg-success" style="width:0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nav-actions">
                <a href="{{ route('whatsapp-gateway.register.show') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                    {{ __('whatsapp-gateway::messages.cta_back') }}
                </a>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('whatsapp-gateway.restart', $sub->local_token) }}">
                        @csrf
                        <button class="btn btn-outline-secondary"><i class="fa-solid fa-rotate"></i> {{ __('whatsapp-gateway::messages.restart_session') }}</button>
                    </form>
                    <a href="{{ config('whatsapp-gateway.branding.home_url') ?? url('/') }}" class="btn btn-success">
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
                        {{ __('whatsapp-gateway::messages.go_to_program') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .celebration-icon { animation: wa-pop 600ms cubic-bezier(.2,1.4,.6,1); display:inline-block; }
    @keyframes wa-pop { 0%{transform:scale(0); opacity:0;} 60%{transform:scale(1.2);} 100%{transform:scale(1); opacity:1;} }
</style>
@endsection

@push('scripts')
<script>
(function() {
    const pollUrl = @json(route('whatsapp-gateway.poll', $sub->local_token));
    const expiredUrl = @json(route('whatsapp-gateway.expired', $sub->local_token));
    const labelPolling = @json(__('whatsapp-gateway::messages.connect_polling'));
    const dateLocale  = @json(app()->getLocale() === 'ar' ? 'ar-SA' : 'en-US');

    const $qrImg     = document.getElementById('wa-qr-img');
    const $qrPlace   = document.getElementById('wa-qr-placeholder');
    const $prepMsg   = document.getElementById('wa-prep-msg');
    const $prepHelp  = document.getElementById('wa-prep-help');
    const $pending   = document.getElementById('wa-pending');
    const $connected = document.getElementById('wa-connected');
    const $statusLbl = document.getElementById('wa-status-label');
    const $planInfo  = document.getElementById('wa-plan-info');
    const $planState = document.getElementById('wa-plan-state');
    const $planUsed  = document.getElementById('wa-plan-used');
    const $planLimit = document.getElementById('wa-plan-limit');
    const $planExp   = document.getElementById('wa-plan-expires');
    const $planProgWrap = document.getElementById('wa-plan-progress-wrap');
    const $planProg  = document.getElementById('wa-plan-progress');

    let stopped = false;
    let attempts = 0;
    const HELP_AFTER = 5; // show "still preparing" help after 5 ticks (~15s)

    function fmtDate(iso) {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleString(dateLocale, {
                year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'
            });
        } catch (e) { return iso; }
    }

    function renderPlan(status) {
        const used  = status.messages_used;
        const limit = status.messages_limit;
        const exp   = status.expires_at;
        const hasAny = used != null || limit != null || exp;
        if (!hasAny) return;

        $planInfo.classList.remove('d-none');
        $planState.textContent = status.state;
        $planUsed.textContent  = used  != null ? used  : '—';
        $planLimit.textContent = limit != null ? limit : '—';
        $planExp.textContent   = fmtDate(exp);

        if (limit && used != null && limit > 0) {
            const pct = Math.min(100, Math.round((used / limit) * 100));
            $planProg.style.width = pct + '%';
            $planProgWrap.classList.remove('d-none');
            if (pct >= 90) $planProg.classList.replace('bg-success','bg-danger');
            else if (pct >= 70) $planProg.classList.replace('bg-success','bg-warning');
        }
    }

    async function tick() {
        if (stopped) return;
        attempts++;
        try {
            const res = await fetch(pollUrl, { headers: { 'Accept': 'application/json' }});
            const json = await res.json();
            if (!json.ok) throw new Error(json.error || 'gateway error');

            const state = json.status.state;
            const provisioning = !json.sub || !json.sub.instance_id;

            if (state === 'connected') {
                stopped = true;
                $pending.classList.add('d-none');
                $connected.classList.remove('d-none');
                renderPlan(json.status);
                try { localStorage.removeItem('wa_register_form'); } catch(e) {}
                return;
            }

            if (state === 'expired' || state === 'blocked') {
                stopped = true;
                window.location.href = expiredUrl;
                return;
            }

            if (json.qr && (json.qr.base64 || json.qr.url)) {
                $qrImg.src = json.qr.base64 || json.qr.url;
                $qrImg.style.display = 'block';
                $qrPlace.style.display = 'none';
                $prepHelp.classList.add('d-none');
            } else {
                // Still preparing — show friendly help after a few attempts.
                if (provisioning && attempts >= HELP_AFTER) {
                    $prepHelp.classList.remove('d-none');
                }
                if (json.qr_error && $prepMsg) {
                    $prepMsg.textContent = json.qr_error;
                }
            }

            $statusLbl.textContent = labelPolling;
        } catch (e) {
            if ($prepMsg) $prepMsg.textContent = e.message;
        } finally {
            if (!stopped) setTimeout(tick, 3000);
        }
    }
    tick();
})();
</script>
@endpush
