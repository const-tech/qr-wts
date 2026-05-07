<?php

namespace ConstTech\WhatsappGateway\Drivers;

use ConstTech\WhatsappGateway\Contracts\GatewayDriver;
use ConstTech\WhatsappGateway\DTOs\PackageData;
use ConstTech\WhatsappGateway\DTOs\QrData;
use ConstTech\WhatsappGateway\DTOs\RegisterPayload;
use ConstTech\WhatsappGateway\DTOs\StatusData;
use ConstTech\WhatsappGateway\DTOs\SubscriptionData;
use Illuminate\Support\Str;

class NullDriver implements GatewayDriver
{
    public function verify(string $instanceId, string $accessToken): StatusData
    {
        return $this->getStatus($instanceId, $accessToken);
    }

    public function listPackages(): array
    {
        return [
            PackageData::fromArray([
                'id' => 'free', 'name' => 'الباقة المجانية', 'price' => 0,
                'currency' => 'SAR', 'duration_days' => 14, 'is_free' => true,
                'features' => ['تفعيل عبر QR', 'إرسال 100 رسالة', 'دعم عربي'],
            ]),
        ];
    }

    public function register(RegisterPayload $payload): SubscriptionData
    {
        return SubscriptionData::fromArray([
            'instance_id' => 'instance' . random_int(10000, 99999),
            'token'       => Str::random(32),
            'package_id'  => $payload->packageId ?: 'free',
            'status'      => 'pending',
            'expires_at'  => now()->addDays(14)->toIso8601String(),
        ]);
    }

    public function getQr(string $instanceId, string $accessToken): QrData
    {
        return QrData::fromArray([
            'url'        => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($instanceId),
            'expires_in' => 60,
        ]);
    }

    public function getStatus(string $instanceId, string $accessToken): StatusData
    {
        return StatusData::fromArray(['state' => 'pending']);
    }

    public function restartSession(string $instanceId, string $accessToken): bool
    {
        return true;
    }

    public function listUpgrades(string $instanceId, string $accessToken): array
    {
        return [
            PackageData::fromArray([
                'id' => 'pro', 'name' => 'الباقة الاحترافية', 'price' => 49,
                'currency' => 'SAR', 'duration_days' => 30, 'is_free' => false,
                'features' => ['رسائل غير محدودة', 'وسائط', 'تقارير متقدمة'],
            ]),
        ];
    }

    public function sendMessage(string $instanceId, string $accessToken, string $phone, string $message): array
    {
        return ['sent' => true, 'instance' => $instanceId, 'to' => $phone];
    }
}
