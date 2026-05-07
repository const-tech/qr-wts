<?php

namespace ConstTech\WhatsappGateway\Manager;

use ConstTech\WhatsappGateway\Contracts\GatewayDriver;
use ConstTech\WhatsappGateway\DTOs\PackageData;
use ConstTech\WhatsappGateway\DTOs\QrData;
use ConstTech\WhatsappGateway\DTOs\RegisterPayload;
use ConstTech\WhatsappGateway\DTOs\StatusData;
use ConstTech\WhatsappGateway\DTOs\SubscriptionData;
use ConstTech\WhatsappGateway\Exceptions\GatewayException;
use ConstTech\WhatsappGateway\Models\WaSubscription;
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
     * Auto-provision flow — give the customer a usable WhatsApp session.
     *
     * Resolution order (first one that succeeds wins):
     *   1. Explicit reseller register endpoint, if configured.
     *   2. Fallback (shared admin) credentials, if configured. This is the
     *      fastest path to a visible QR for projects that don't have a
     *      reseller agreement with the remote gateway.
     *   3. Auto-discovery — try a list of well-known register paths.
     */
    public function register(RegisterPayload $payload): WaSubscription
    {
        $cwts = $this->config['drivers']['cwts'] ?? [];
        $configuredEndpoint = $cwts['endpoints']['register'] ?? null;

        // 1. Explicit reseller endpoint
        if ($configuredEndpoint) {
            try {
                $remote = $this->driver()->register($payload);
                return WaSubscription::recordRemote($remote, $payload);
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // 2. Fallback (shared admin) credentials
        $fallback = $this->fallbackCredentials();
        if ($fallback) {
            return $this->claim($payload, $fallback['instance_id'], $fallback['access_token']);
        }

        // 3. Auto-discovery (CwtsDriver tries several common paths)
        $remote = $this->driver()->register($payload);
        return WaSubscription::recordRemote($remote, $payload);
    }

    /**
     * @return array{instance_id:string,access_token:string}|null
     */
    public function fallbackCredentials(): ?array
    {
        // Read live config so host projects can set values at runtime
        // (e.g. AppServiceProvider::boot reading from a settings table).
        $live = function_exists('config')
            ? config('whatsapp-gateway.fallback_credentials')
            : null;
        $f = $live ?: ($this->config['fallback_credentials'] ?? null);

        if (! $f || empty($f['instance_id']) || empty($f['access_token'])) {
            return null;
        }
        return [
            'instance_id'  => (string) $f['instance_id'],
            'access_token' => (string) $f['access_token'],
        ];
    }

    /** Allow host projects to set credentials at runtime. */
    public function setFallbackCredentials(?string $instanceId, ?string $accessToken): void
    {
        $this->config['fallback_credentials'] = [
            'instance_id'  => $instanceId,
            'access_token' => $accessToken,
        ];
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
