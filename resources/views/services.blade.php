@extends('whatsapp-gateway::layout')

@section('title', __('whatsapp-gateway::messages.our_services'))

@section('content')
    @php
        $phone = '0506499275';
        $productsUrl = config('whatsapp-gateway.admin.products_url');
        $backRoute = config('whatsapp-gateway.admin.back_route');

        $modelClass = config('whatsapp-gateway.admin.services_model');
        $hasModel = $modelClass && class_exists($modelClass);
        $services = $hasModel ? $modelClass::latest()->get() : collect();
    @endphp

    <div class="wa-card p-4 mb-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <i class="fa-solid fa-grid-2 me-2" style="color:var(--wa-green)"></i>
                {{ __('whatsapp-gateway::messages.our_services') }}
            </h4>
            @if ($backRoute && \Illuminate\Support\Facades\Route::has($backRoute))
                <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-1"></i>
                    {{ __('whatsapp-gateway::messages.back') }}
                </a>
            @endif
        </div>

        {{-- Slider component --}}
        <x-whatsapp-gateway::slider />

        {{-- Contact hint --}}
        <div class="alert alert-info mt-3">
            {{ __('whatsapp-gateway::messages.services_contact_hint') }}
            <a href="tel:{{ $phone }}" class="text-decoration-underline fw-bold">{{ $phone }}</a>
        </div>

        @if ($hasModel)
            {{-- Services table --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:3rem">#</th>
                            <th>{{ __('whatsapp-gateway::messages.service') }}</th>
                            <th style="width:6rem">{{ __('whatsapp-gateway::messages.service_description') }}</th>
                            <th class="text-center" style="width:10rem">
                                {{ __('whatsapp-gateway::messages.request_service') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $service->name }}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#waSvc{{ $service->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <a target="_blank" href="https://wa.me/+966{{ ltrim($phone, '0') }}"
                                        class="btn btn-sm btn-success">
                                        <i class="fa-brands fa-whatsapp me-1"></i>
                                        {{ __('whatsapp-gateway::messages.request_service') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    {{ __('whatsapp-gateway::messages.no_services') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Products link --}}
        @if ($productsUrl)
            <div class="alert alert-info mt-2">
                {{ __('whatsapp-gateway::messages.services_products_hint') }}
                <a href="{{ $productsUrl }}" target="_blank" class="fw-bold">
                    {{ __('whatsapp-gateway::messages.here') }}
                </a>
            </div>
        @endif

    </div>

    {{-- Description modals pushed into the layout's @stack('modals') --}}
    @foreach ($services as $service)
        @push('modals')
            <div class="modal fade" id="waSvc{{ $service->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h6 class="modal-title fw-bold">{{ $service->name }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-wrap">
                            {{ $service->description }}
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                                {{ __('whatsapp-gateway::messages.back') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endpush
    @endforeach
@endsection
