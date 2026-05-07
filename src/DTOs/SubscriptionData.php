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
        $self->token        = isset($data['token']) ? (string) $data['token'] : null;
        $self->remoteId     = isset($data['id']) ? (string) $data['id'] : null;
        $self->packageId    = (string) ($data['package_id'] ?? 'free');
        $self->status       = (string) ($data['status'] ?? 'pending');
        $self->dashboardUrl = $data['dashboard_url'] ?? null;
        $self->expiresAt    = isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
        $self->raw          = $data;
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
