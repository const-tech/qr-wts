<?php

namespace ConstTech\WhatsappGateway\DTOs;

class RegisterPayload
{
    /** @var string */
    public $name;
    /** @var string */
    public $phone;
    /** @var string|null */
    public $email;
    /** @var string|null */
    public $business;
    /** @var string|null */
    public $packageId;
    /** @var string|null */
    public $locale;
    /** @var array<string,mixed> */
    public $extra;

    /** @param array<string,mixed> $extra */
    public function __construct(
        string $name,
        string $phone,
        ?string $email = null,
        ?string $business = null,
        ?string $packageId = null,
        ?string $locale = null,
        array $extra = []
    ) {
        $this->name      = $name;
        $this->phone     = $phone;
        $this->email     = $email;
        $this->business  = $business;
        $this->packageId = $packageId;
        $this->locale    = $locale;
        $this->extra     = $extra;
    }

    public function toArray(): array
    {
        return array_merge([
            'name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'business'   => $this->business,
            'package_id' => $this->packageId,
            'locale'     => $this->locale,
        ], $this->extra);
    }
}
