<?php

namespace Almarwa\WhatsappGateway\DTOs;

use Illuminate\Contracts\Support\Arrayable;

class QrData implements Arrayable
{
    /** @var string|null */
    public $base64;
    /** @var string|null */
    public $url;
    /** @var string|null */
    public $raw;
    /** @var int */
    public $expiresIn;

    public function __construct(?string $base64 = null, ?string $url = null, ?string $raw = null, int $expiresIn = 60)
    {
        $this->base64    = $base64;
        $this->url       = $url;
        $this->raw       = $raw;
        $this->expiresIn = $expiresIn;
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        // c-wts.com /docs returns the QR as a base64 PNG. Accept the
        // common key names used by Ultramsg-style gateways.
        $base64 = $data['base64']
            ?? $data['qr_base64']
            ?? $data['qrcode']
            ?? $data['image']
            ?? (isset($data['value']) ? $data['value'] : null);
        $url    = $data['url'] ?? $data['qr_url'] ?? null;
        $raw    = $data['raw'] ?? $data['qr']     ?? null;
        $exp    = (int) ($data['expires_in'] ?? 60);

        if (is_string($base64) && $base64 !== '') {
            // Heuristic: very long strings without "data:" prefix are raw base64.
            if (strpos($base64, 'data:') !== 0 && strlen($base64) > 80) {
                $base64 = 'data:image/png;base64,' . $base64;
            }
        } else {
            $base64 = null;
        }

        return new self($base64, $url, $raw, $exp);
    }

    public function imageSrc(): ?string
    {
        return $this->base64 ?: $this->url;
    }

    public function toArray(): array
    {
        return [
            'base64'     => $this->base64,
            'url'        => $this->url,
            'raw'        => $this->raw,
            'expires_in' => $this->expiresIn,
        ];
    }
}
