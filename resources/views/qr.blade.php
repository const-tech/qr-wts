@extends('whatsapp-gateway::layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-9">

        <div class="step-indicator mb-3 justify-content-center">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot active"></span>
        </div>

        <div class="wa-card bg-white p-4 p-md-5">

            <div id="wa-pending" class="text-center">
                <h3 class="fw-bold">{{ __('whatsapp-gateway::messages.connect_title') }}</h3>
                <p class="text-muted mb-4">{{ __('whatsapp-gateway::messages.connect_subtitle') }}</p>
                <div class="qr-frame mb-3">
                    <div id="wa-qr-placeholder" class="d-flex align-items-center justify-content-center"
                         style="width:280px; height:280px;">
                        <div class="spinner-border text-success" role="status"></div>
                    </div>
                    <img id="wa-qr-img" alt="QR" style="display:none">
                </div>
                <div class="text-muted">
                    <span class="pulse-dot"></span>
                    <span id="wa-status-label" class="ms-2">{{ __('whatsapp-gateway::messages.connect_polling') }}</span>
                </div>
                <div id="wa-qr-error" class="alert alert-warning mt-3 d-none"></div>
            </div>

            <div id="wa-connected" class="text-center d-none">
                <i class="fa-solid fa-circle-check text-success" style="font-size:4rem"></i>
                <h3 class="fw-bold mt-3">{{ __('whatsapp-gateway::messages.connected_title') }}</h3>
                <p class="text-muted mb-4">{{ __('whatsapp-gateway::messages.connected_subtitle') }}</p>

                <div class="row g-3 my-4 text-start">
                    <div class="col-md-6">
                        <div class="stat-pill">
                            <small class="text-muted d-block">{{ __('whatsapp-gateway::messages.instance_id') }}</small>
                            <code class="fs-6" id="wa-instance-id">{{ $sub->instance_id }}</code>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-pill">
                            <small class="text-muted d-block">{{ __('whatsapp-gateway::messages.token') }}</small>
                            <code class="fs-6 text-truncate d-inline-block" style="max-width:100%;" id="wa-token">{{ $sub->token }}</code>
                        </div>
                    </div>
                    @if ($sub->expires_at)
                    <div class="col-md-12">
                        <div class="stat-pill">
                            <small class="text-muted d-block">{{ __('whatsapp-gateway::messages.expires_at') }}</small>
                            <strong>{{ $sub->expires_at->format('Y-m-d H:i') }}</strong>
                        </div>
                    </div>
                    @endif
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
@endsection

@push('scripts')
<script>
(function() {
    const pollUrl = @json(route('whatsapp-gateway.poll', $sub->local_token));
    const expiredUrl = @json(route('whatsapp-gateway.expired', $sub->local_token));

    const $qrImg     = document.getElementById('wa-qr-img');
    const $qrPlace   = document.getElementById('wa-qr-placeholder');
    const $qrError   = document.getElementById('wa-qr-error');
    const $pending   = document.getElementById('wa-pending');
    const $connected = document.getElementById('wa-connected');
    const $statusLbl = document.getElementById('wa-status-label');
    const $instId    = document.getElementById('wa-instance-id');
    const $tokenEl   = document.getElementById('wa-token');

    let stopped = false;

    async function tick() {
        if (stopped) return;
        try {
            const res = await fetch(pollUrl, { headers: { 'Accept': 'application/json' }});
            const json = await res.json();
            if (!json.ok) throw new Error(json.error || 'gateway error');

            const state = json.status.state;

            // Update credentials in the DOM as they become available — the
            // poll endpoint auto-saves them server-side, we just reflect.
            if (json.sub && json.sub.instance_id && $instId) {
                $instId.textContent = json.sub.instance_id;
            }

            if (state === 'connected') {
                stopped = true;
                $pending.classList.add('d-none');
                $connected.classList.remove('d-none');
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
                $qrError.classList.add('d-none');
            } else if (json.qr_error) {
                $qrError.textContent = json.qr_error;
                $qrError.classList.remove('d-none');
            }

            $statusLbl.textContent = '{{ __("whatsapp-gateway::messages.connect_polling") }} (' + state + ')';
        } catch (e) {
            $qrError.textContent = e.message;
            $qrError.classList.remove('d-none');
        } finally {
            if (!stopped) setTimeout(tick, 3000);
        }
    }
    tick();
})();
</script>
@endpush
