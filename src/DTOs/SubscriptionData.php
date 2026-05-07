<?php

namespace ConstTech\WhatsappGateway\DTOs;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

class SubscriptionData implements Arrayable
{
    /** @var string */
    public $instanceId;
    /** @var string|null */
    public $token;
    /** @var string|null */
    public $remoteId;
    /** @var string */
    public $packageId;
    /** @var string */
    public $status;
    /** @var Carbon|null */
    public $expiresAt;
    /** @var string|null */
    public $dashboardUrl;
    /** @var array<string,mixed> */
    public $raw;

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->instanceId   = (string) ($data['instance_id'] ?? $data['instance'] ?? '');
        // API returns access_token; internal storage uses token
        $self->token        = isset($data['access_token']) ? (string) $data['access_token']
            : (isset($data['token'])       ? (string) $data['token'] : null);
        $self->remoteId     = isset($data['id']) ? (string) $data['id'] : null;
        // API returns plan_id; fall back to package_id for internal DTOs
        $self->packageId    = (string) ($data['plan_id'] ?? $data['package_id'] ?? 'free');
        $self->status       = (string) ($data['status'] ?? 'pending');
        $self->dashboardUrl = $data['dashboard_url'] ?? null;
        // API returns subscription_end as a unix timestamp
        if (isset($data['subscription_end']) && $data['subscription_end']) {
            $self->expiresAt = Carbon::createFromTimestamp((int) $data['subscription_end']);
        } elseif (isset($data['expires_at'])) {
            $self->expiresAt = Carbon::parse($data['expires_at']);
        }
        $self->raw = $data;
        return $self;
    }

    public function toArray(): array
    {
        return [
            'instance_id'   => $this->instanceId,
            'token'         => $this->token,
            'id'            => $this->remoteId,
            'package_id'    => $this->packageId,
            'status'        => $this->status,
            'expires_at'    => $this->expiresAt ? $this->expiresAt->toIso8601String() : null,
            'dashboard_url' => $this->dashboardUrl,
        ];
    }
}
