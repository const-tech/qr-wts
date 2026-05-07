<?php

namespace ConstTech\WhatsappGateway\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class PackageData implements Arrayable
{
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var float */
    public $price;
    /** @var string */
    public $currency;
    /** @var int */
    public $durationDays;
    /** @var bool */
    public $isFree;
    /** @var array<int,string> */
    public $features;
    /** @var array<string,mixed> */
    public $raw;

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->id            = (string) ($data['id'] ?? $data['slug'] ?? '');
        $self->name          = (string) ($data['name'] ?? $data['title'] ?? '');
        $self->price         = (float)  ($data['price'] ?? 0);
        $self->currency      = (string) ($data['currency'] ?? 'SAR');
        $self->durationDays  = (int)    ($data['duration_days'] ?? $data['duration'] ?? 0);
        $self->isFree        = (bool)   ($data['is_free'] ?? ($self->price <= 0));
        $self->features      = (array)  ($data['features'] ?? []);
        $self->raw           = $data;
        return $self;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'price'         => $this->price,
            'currency'      => $this->currency,
            'duration_days' => $this->durationDays,
            'is_free'       => $this->isFree,
            'features'      => $this->features,
        ];
    }
}
