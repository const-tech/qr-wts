<?php

namespace ConstTech\WhatsappGateway\View\Components;

use Illuminate\View\Component;

/**
 * <x-whatsapp-gateway::promo-banner />
 *
 * Drop-in green WhatsApp promo card. Links to the package's landing page
 * by default; every text/feature/CTA can be overridden via attributes.
 */
class PromoBanner extends Component
{
    public string $eyebrow;
    public string $title;
    public string $subtitle;
    /** @var array<int,string> */
    public array $features;
    public string $ctaUrl;
    public string $ctaLabel;
    public string $secondaryUrl;
    public string $secondaryLabel;
    public ?string $contactPhone;
    public string $price;
    public string $priceLabel;

    /**
     * @param array<int,string>|string|null $features Comma-separated string or array of bullets
     */
    public function __construct(
        ?string $eyebrow = null,
        ?string $title = null,
        ?string $subtitle = null,
        $features = null,
        ?string $ctaUrl = null,
        ?string $ctaLabel = null,
        ?string $secondaryUrl = null,
        ?string $secondaryLabel = null,
        ?string $contactPhone = null,
        ?string $price = null,
        ?string $priceLabel = null
    ) {
        $this->eyebrow  = $eyebrow  ?? 'جديد · مجاناً';
        $this->title    = $title    ?? 'تواصل مع عملائك على الواتساب — رسائل تلقائية';
        $this->subtitle = $subtitle ?? 'فعّل خدمة الواتساب اليوم وأرسل تذكيرات وتأكيدات وإلغاء، ورسائل ترحيب بالعملاء الجدد — كل ذلك تلقائياً وبدون تكلفة بدء.';

        if (is_string($features)) {
            $features = array_map('trim', explode(',', $features));
        }
        $this->features = $features ?: [
            'اشتراك مجاني فوري',
            'ربط حسابك بـ QR Code',
            'قوالب جاهزة قابلة للتعديل',
            'تقارير الرسائل المرسلة',
        ];

        $this->ctaUrl       = $ctaUrl       ?: $this->safeRoute('whatsapp-gateway.landing', '#');
        $this->ctaLabel     = $ctaLabel     ?: 'اشترك مجاناً الآن';
        $this->secondaryUrl   = $secondaryUrl   ?: $this->ctaUrl;
        $this->secondaryLabel = $secondaryLabel ?: 'تفاصيل المنصة';
        $this->contactPhone   = $contactPhone   ?: config('whatsapp-gateway.branding.support_phone');
        $this->price       = $price      ?: '0';
        $this->priceLabel  = $priceLabel ?: 'رسوم بدء الخدمة';
    }

    public function render()
    {
        return view('whatsapp-gateway::components.promo-banner');
    }

    protected function safeRoute(string $name, string $fallback): string
    {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
