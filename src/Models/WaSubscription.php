<?php

namespace Almarwa\WhatsappGateway\Models;

use Almarwa\WhatsappGateway\DTOs\RegisterPayload;
use Almarwa\WhatsappGateway\DTOs\StatusData;
use Almarwa\WhatsappGateway\DTOs\SubscriptionData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int    $id
 * @property string $local_token
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $business
 * @property string $package_id
 * @property string $instance_id
 * @property string|null $token
 * @property string|null $remote_id
 * @property string $status
 * @property \Carbon\Carbon|null $expires_at
 * @property string|null $dashboard_url
 * @property array|null $meta
 */
class WaSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta'       => 'array',
    ];

    public function getTable()
    {
        return config('whatsapp-gateway.storage.table', 'wa_subscriptions');
    }

    public function getConnectionName()
    {
        return config('whatsapp-gateway.storage.connection') ?: parent::getConnectionName();
    }

    public static function recordRemote(SubscriptionData $remote, RegisterPayload $payload): self
    {
        $sub = new self([
            'local_token'   => Str::uuid()->toString(),
            'name'          => $payload->name,
            'phone'         => $payload->phone,
            'email'         => $payload->email,
            'business'      => $payload->business,
            'package_id'    => $remote->packageId ?: ($payload->packageId ?: 'free'),
            'instance_id'   => $remote->instanceId,
            'token'         => $remote->token,
            'remote_id'     => $remote->remoteId,
            'status'        => $remote->status,
            'expires_at'    => $remote->expiresAt,
            'dashboard_url' => $remote->dashboardUrl,
            'meta'          => ['raw' => $remote->raw, 'extra' => $payload->extra],
        ]);
        $sub->save();
        return $sub;
    }

    public function applyStatus(StatusData $status): void
    {
        $this->status = $status->state;
        if ($status->expiresAt) {
            $this->expires_at = $status->expiresAt;
        }
        $meta = $this->meta ?: [];
        $meta['last_status'] = $status->toArray();
        $this->meta = $meta;
        $this->save();
    }

    public function isExpired(): bool
    {
        return $this->status === StatusData::STATE_EXPIRED
            || ($this->expires_at && $this->expires_at->isPast());
    }
}
