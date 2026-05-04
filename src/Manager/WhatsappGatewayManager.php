<?php

namespace Almarwa\WhatsappGateway\Manager;

use Almarwa\WhatsappGateway\Contracts\GatewayDriver;
use Almarwa\WhatsappGateway\DTOs\PackageData;
use Almarwa\WhatsappGateway\DTOs\QrData;
use Almarwa\WhatsappGateway\DTOs\RegisterPayload;
use Almarwa\WhatsappGateway\DTOs\StatusData;
use Almarwa\WhatsappGateway\DTOs\SubscriptionData;
use Almarwa\WhatsappGateway\Exceptions\GatewayException;
use Almarwa\WhatsappGateway\Models\WaSubscription;
use Illuminate\Contracts\Container\Container;

/**
 * Public entry point for the package. Resolves the configured driver and
 * exposes the high-level operations used by the controllers, livewire
 * components, or any external code.
 */
class WhatsappGatewayManager
{
    /** @var Container */
    protected $container;

    /** @var array<string,mixed> */
    protected $config;

    /** @var array<string,GatewayDriver> */
    protected $drivers = [];

    /** @param array<string,mixed> $config */
    public function __construct(Container $container, array $config)
    {
        $this->container = $container;
        $this->config    = $config;
    }

    public function driver(?string $name = null): GatewayDriver
    {
        $name = $name ?: ($this->config['driver'] ?? 'cwts');

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $cfg = $this->config['drivers'][$name] ?? null;
        if (! $cfg || empty($cfg['class'])) {
            throw new GatewayException("Driver [{$name}] is not configured.");
        }

        $class = $cfg['class'];
        return $this->drivers[$name] = new $class($cfg);
    }

    public function flow(): string
    {
        return $this->config['flow'] ?? 'claim';
    }

    public function isResellerFlow(): bool
    {
        return $this->flow() === 'reseller';
    }

    public function signupUrl(): ?string
    {
        return $this->config['drivers']['cwts']['signup_url'] ?? null;
    }

    public function loginUrl(): ?string
    {
        return $this->config['drivers']['cwts']['login_url'] ?? null;
    }

    /* ------------------------------------------------------------------ */
    /* Packages                                                            */
    /* ------------------------------------------------------------------ */

    /** @return array<int,PackageData> */
    public function packages(): array
    {
        try {
            $packages = $this->driver()->listPackages();
            if (! empty($packages)) {
                return $packages;
            }
        } catch (\Throwable $e) {
            // fall through to fallback
        }

        $fallback = $this->config['free_package'] ?? null;
        return $fallback ? [PackageData::fromArray($fallback)] : [];
    }

    public function freePackage(): ?PackageData
    {
        foreach ($this->packages() as $p) {
            if ($p->isFree) {
                return $p;
            }
        }
        return null;
    }

    /* ------------------------------------------------------------------ */
    /* Subscription creation                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Reseller flow — create a brand-new instance via the private API.
     */
    public function register(RegisterPayload $payload): WaSubscription
    {
        if (! $this->isResellerFlow()) {
            throw new GatewayException(
                'register() requires the reseller flow. Set whatsapp-gateway.flow=reseller ' .
                'and configure WHATSAPP_GATEWAY_REGISTER_ENDPOINT + WHATSAPP_GATEWAY_ADMIN_TOKEN.'
            );
        }

        $cwts = $this->config['drivers']['cwts'] ?? [];
        if (empty($cwts['admin_token']) || empty($cwts['endpoints']['register'])) {
            throw new GatewayException(
                'إعدادات الريسلر ناقصة: ضع WHATSAPP_GATEWAY_ADMIN_TOKEN و WHATSAPP_GATEWAY_REGISTER_ENDPOINT في .env (أو حوّل WHATSAPP_GATEWAY_FLOW=claim مؤقتاً).'
            );
        }

        $remote = $this->driver()->register($payload);
        return WaSubscription::recordRemote($remote, $payload);
    }

    /**
     * Claim flow — the customer signed up at c-wts.com and pasted their
     * {instance_id, access_token}. Verify they work, then store locally.
     */
    public function claim(RegisterPayload $payload, string $instanceId, string $accessToken): WaSubscription
    {
        $status = $this->driver()->verify($instanceId, $accessToken);

        $remote = SubscriptionData::fromArray([
            'instance_id' => $instanceId,
            'token'       => $accessToken,
            'package_id'  => $payload->packageId ?: 'free',
            'status'      => $status->state,
            'expires_at'  => $status->expiresAt ? $status->expiresAt->toIso8601String() : null,
        ]);

        $sub = WaSubscription::recordRemote($remote, $payload);
        $sub->applyStatus($status);
        return $sub;
    }

    /* ------------------------------------------------------------------ */
    /* Per-subscription operations                                         */
    /* ------------------------------------------------------------------ */

    public function qr(WaSubscription $sub): QrData
    {
        return $this->driver()->getQr($sub->instance_id, (string) $sub->token);
    }

    public function status(WaSubscription $sub, bool $persist = true): StatusData
    {
        $status = $this->driver()->getStatus($sub->instance_id, (string) $sub->token);
        if ($persist) {
            $sub->applyStatus($status);
        }
        return $status;
    }

    public function restart(WaSubscription $sub): bool
    {
        return $this->driver()->restartSession($sub->instance_id, (string) $sub->token);
    }

    /** @return array<int,PackageData> */
    public function upgrades(WaSubscription $sub): array
    {
        return $this->driver()->listUpgrades($sub->instance_id, (string) $sub->token);
    }

    public function send(WaSubscription $sub, string $phone, string $message): array
    {
        return $this->driver()->sendMessage($sub->instance_id, (string) $sub->token, $phone, $message);
    }

    public function findByToken(string $token): ?WaSubscription
    {
        return WaSubscription::where('local_token', $token)->first();
    }
}
