<?php

namespace Almarwa\WhatsappGateway\View\Components;

use Illuminate\View\Component;

/**
 * <x-whatsapp-gateway::announcement-banner
 *      title="الآن لدينا خدمة الربط مع الزكاة - المرحلة الثانية"
 *      description="..."
 *      icon="fa-receipt"
 *      theme="indigo"
 *      phone="0506499275" />
 *
 * Drop-in announcement / new-feature banner. Themes are baked into the
 * component CSS so any host project can drop it on any page.
 */
class AnnouncementBanner extends Component
{
    public string $eyebrow;
    public string $title;
    public string $description;
    public string $icon;
    public string $theme;
    public ?string $phone;
    public ?string $whatsapp;
    public ?string $ctaUrl;
    public string $ctaLabel;
    public string $contactWaLabel;
    public string $contactCallLabel;

    public function __construct(
        ?string $eyebrow = null,
        ?string $title = null,
        ?string $description = null,
        ?string $icon = null,
        ?string $theme = null,
        ?string $phone = null,
        ?string $whatsapp = null,
        ?string $ctaUrl = null,
        ?string $ctaLabel = null,
        ?string $contactWaLabel = null,
        ?string $contactCallLabel = null
    ) {
        $this->eyebrow      = $eyebrow ?? 'خدمة جديدة';
        $this->title        = $title ?? '';
        $this->description  = $description ?? '';
        $this->icon         = $icon ?? 'fa-bullhorn';
        $this->theme        = $theme ?? 'indigo';
        $this->phone        = $phone ?: config('whatsapp-gateway.branding.support_phone');
        $this->whatsapp     = $whatsapp ?: $this->phone;
        $this->ctaUrl       = $ctaUrl;
        $this->ctaLabel     = $ctaLabel ?? 'تفاصيل أكثر';
        $this->contactWaLabel   = $contactWaLabel   ?? 'واتساب';
        $this->contactCallLabel = $contactCallLabel ?? 'اتصال';
    }

    /**
     * @return array{from:string,to:string,fg:string}
     */
    public function gradient(): array
    {
        $themes = [
            'indigo' => ['from' => '#4338ca', 'to' => '#7c3aed', 'fg' => '#fff'],
            'amber'  => ['from' => '#d97706', 'to' => '#f59e0b', 'fg' => '#1f1f1f'],
            'rose'   => ['from' => '#be123c', 'to' => '#fb7185', 'fg' => '#fff'],
            'sky'    => ['from' => '#0369a1', 'to' => '#38bdf8', 'fg' => '#fff'],
            'green'  => ['from' => '#075E54', 'to' => '#25D366', 'fg' => '#fff'],
            'slate'  => ['from' => '#1e293b', 'to' => '#475569', 'fg' => '#fff'],
        ];
        return $themes[$this->theme] ?? $themes['indigo'];
    }

    public function waLink(): ?string
    {
        if (! $this->whatsapp) return null;
        $digits = preg_replace('/[^0-9]/', '', $this->whatsapp);
        if (! $digits) return null;
        // Default to Saudi country code if number starts with 0
        if (strlen($digits) === 10 && $digits[0] === '0') {
            $digits = '966' . substr($digits, 1);
        }
        return 'https://wa.me/' . $digits;
    }

    public function render()
    {
        return view('whatsapp-gateway::components.announcement-banner');
    }
}
