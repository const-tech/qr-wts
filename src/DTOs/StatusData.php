<?php

namespace ConstTech\WhatsappGateway\DTOs;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;

class StatusData implements Arrayable
{
    public const STATE_PENDING    = 'pending';
    public const STATE_CONNECTED  = 'connected';
    public const STATE_DISCONNECTED = 'disconnected';
    public const STATE_EXPIRED    = 'expired';
    public const STATE_BLOCKED    = 'blocked';

    /** @var string */
    public $state;
    /** @var Carbon|null */
    public $expiresAt;
    /** @var int|null */
    public $messagesUsed;
    /** @var int|null */
    public $messagesLimit;
    /** @var array<string,mixed> */
    public $raw;

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->state          = self::normalizeState($data['state'] ?? $data['status'] ?? 'pending');
        $self->expiresAt      = isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
        $self->messagesUsed   = isset($data['messages_used'])  ? (int) $data['messages_used']  : null;
        $self->messagesLimit  = isset($data['messages_limit']) ? (int) $data['messages_limit'] : null;
        $self->raw            = $data;

        if ($self->expiresAt && $self->expiresAt->isPast() && $self->state === self::STATE_CONNECTED) {
            $self->state = self::STATE_EXPIRED;
        }

        return $self;
    }

    protected static function normalizeState(string $value): string
    {
        $value = strtolower(trim($value));
        $map = [
            'authenticated' => self::STATE_CONNECTED,
            'ready'         => self::STATE_CONNECTED,
            'connected'     => self::STATE_CONNECTED,
            'online'        => self::STATE_CONNECTED,
            'pending'       => self::STATE_PENDING,
            'qr'            => self::STATE_PENDING,
            'init'          => self::STATE_PENDING,
            'loading'       => self::STATE_PENDING,
            'disconnected'  => self::STATE_DISCONNECTED,
            'offline'       => self::STATE_DISCONNECTED,
            'expired'       => self::STATE_EXPIRED,
            'blocked'       => self::STATE_BLOCKED,
            'banned'        => self::STATE_BLOCKED,
        ];
        return $map[$value] ?? $value;
    }

    public function isConnected(): bool { return $this->state === self::STATE_CONNECTED; }
    public function isExpired(): bool   { return $this->state === self::STATE_EXPIRED; }
    public function isPending(): bool   { return $this->state === self::STATE_PENDING; }

    public function toArray(): array
    {
        return [
            'state'          => $this->state,
            'expires_at'     => $this->expiresAt ? $this->expiresAt->toIso8601String() : null,
            'messages_used'  => $this->messagesUsed,
            'messages_limit' => $this->messagesLimit,
        ];
    }
}
