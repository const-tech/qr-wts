<?php

namespace ConstTech\WhatsappGateway\Drivers;

use ConstTech\WhatsappGateway\Contracts\GatewayDriver;
use ConstTech\WhatsappGateway\DTOs\PackageData;
use ConstTech\WhatsappGateway\DTOs\QrData;
use ConstTech\WhatsappGateway\DTOs\RegisterPayload;
use ConstTech\WhatsappGateway\DTOs\StatusData;
use ConstTech\WhatsappGateway\DTOs\SubscriptionData;
use ConstTech\WhatsappGateway\Exceptions\GatewayException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Driver for the c-wts.com REST gateway.
 *
 * Public endpoints (per https://c-wts.com/docs):
 *   GET  /api/status   ?instance_id=&access_token=
 *   GET  /api/qrcode   ?instance_id=&access_token=
 *   POST /api/send     instance_id, access_token, number, message
 *
 * Optional reseller endpoints (private, requires admin_token):
 *   register / restart / packages / upgrades — only enabled if their
 *   endpoint paths are configured.
 */
class CwtsDriver implements GatewayDriver
{
    /** @var array<string,mixed> */
    protected $config;

    /** @param array<string,mixed> $config */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function verify(string $instanceId, string $accessToken): StatusData
    {
        return $this->getStatus($instanceId, $accessToken);
    }

    public function getStatus(string $instanceId, string $accessToken): StatusData
    {
        $response = $this->request()->get(
            $this->endpoint('status'),
            $this->credentials($instanceId, $accessToken)
        );
        $data = $this->unwrap($response, 'status');
        return StatusData::fromArray($data);
    }

    public function getQr(string $instanceId, string $accessToken): QrData
    {
        $response = $this->request()->get(
            $this->endpoint('qrcode'),
            $this->credentials($instanceId, $accessToken)
        );
        // c-wts.com may return either {qrcode: "base64..."} or a flat
        // top-level base64 — let unwrap() pick whichever matches.
        $data = $this->unwrap($response, 'qrcode');
        return QrData::fromArray($data);
    }

    public function sendMessage(string $instanceId, string $accessToken, string $phone, string $message): array
    {
        $response = $this->asForm()->post(
            $this->endpoint('send'),
            array_merge($this->credentials($instanceId, $accessToken), [
                'number'  => $phone,
                'message' => $message,
            ])
        );
        return $this->unwrap($response, 'message');
    }

    public function listPackages(): array
    {
        $path = $this->config['endpoints']['packages'] ?? null;
        if (! $path) {
            return []; // c-wts.com /docs does not expose a public packages endpoint
        }

        $response = $this->withAdminToken($this->request())->get($path);
        $data = $this->unwrap($response, 'packages');
        return array_map([PackageData::class, 'fromArray'], $data);
    }

    public function register(RegisterPayload $payload): SubscriptionData
    {
        $configured = $this->config['endpoints']['register'] ?? '/api/register';

        $response = $this->withAdminToken($this->request())
            ->asJson()
            ->post($configured, [
                'name'     => $payload->name,
                'phone'    => $payload->phone,
                'email'    => $payload->email    ?: null,
                'business' => $payload->business ?: null,
            ]);

        if (! $response->successful()) {
            $body = $this->safeJson($response);
            $code = is_array($body) ? ($body['code'] ?? $body['message'] ?? $response->body()) : $response->body();
            throw new GatewayException('Gateway register failed: ' . $code, $response->status());
        }

        $json   = $response->json();
        $client = is_array($json) ? ($json['client'] ?? $json) : [];
        return SubscriptionData::fromArray($client);
    }

    public function restartSession(string $instanceId, string $accessToken): bool
    {
        $path = $this->config['endpoints']['restart'] ?? null;
        if (! $path) {
            return false; // not exposed publicly
        }

        $response = $this->request()->post(
            $this->resolvePath($path, ['instance' => $instanceId]),
            $this->credentials($instanceId, $accessToken)
        );
        return $response->successful();
    }

    public function listUpgrades(string $instanceId, string $accessToken): array
    {
        $path = $this->config['endpoints']['upgrades'] ?? null;
        if (! $path) {
            return [];
        }

        $response = $this->request()->get(
            $this->resolvePath($path, ['instance' => $instanceId]),
            $this->credentials($instanceId, $accessToken)
        );
        $data = $this->unwrap($response, 'packages');
        return array_map([PackageData::class, 'fromArray'], $data);
    }

    /* ----------------------------------------------------------------- */
    /* Internals                                                          */
    /* ----------------------------------------------------------------- */

    protected function request(): PendingRequest
    {
        $client = Http::baseUrl(rtrim($this->config['base_url'] ?? '', '/'))
            ->timeout($this->config['timeout'] ?? 30)
            ->acceptJson();

        if (! ($this->config['verify_ssl'] ?? true)) {
            $client = $client->withOptions(['verify' => false]);
        }

        return $client;
    }

    protected function asForm(): PendingRequest
    {
        return $this->request()->asForm();
    }

    protected function withAdminToken(PendingRequest $client): PendingRequest
    {
        if (! empty($this->config['admin_token'])) {
            $client = $client->withToken($this->config['admin_token']);
        }
        return $client;
    }

    /** @return array<string,string> */
    protected function credentials(string $instanceId, string $accessToken): array
    {
        return [
            'instance_id'  => $instanceId,
            'access_token' => $accessToken,
        ];
    }

    protected function endpoint(string $key): string
    {
        $path = $this->config['endpoints'][$key] ?? null;
        if (! $path) {
            throw new GatewayException("Endpoint [{$key}] is not configured.");
        }
        return $path;
    }

    /** @param array<string,string> $vars */
    protected function resolvePath(string $path, array $vars): string
    {
        foreach ($vars as $name => $value) {
            $path = str_replace('{' . $name . '}', urlencode($value), $path);
        }
        return $path;
    }

    /**
     * @return array<int|string,mixed>
     */
    protected function unwrap(Response $response, string $key): array
    {
        if (! $response->successful()) {
            $body = $this->safeJson($response);
            $msg  = is_array($body) ? ($body['message'] ?? $body['error'] ?? $response->body()) : $response->body();
            throw GatewayException::fromResponse(
                "Gateway request failed: {$msg}",
                $response->status(),
                ['body' => $body]
            );
        }

        try {
            $json = $response->json();
        } catch (Throwable $e) {
            throw GatewayException::fromResponse('Invalid JSON response from gateway.', 0, ['body' => $response->body()], $e);
        }

        if (! is_array($json)) {
            return [];
        }

        // c-wts.com returns either {success, status, ...} envelopes or
        // flat objects. Normalize both shapes.
        if (isset($json[$key])) {
            return is_array($json[$key]) ? $json[$key] : ['value' => $json[$key]];
        }
        if (isset($json['data'])) {
            return (array) $json['data'];
        }
        return $json;
    }

    /** @return array<string,mixed>|null */
    protected function safeJson(Response $response): ?array
    {
        try {
            $json = $response->json();
            return is_array($json) ? $json : null;
        } catch (Throwable $e) {
            Log::debug('whatsapp-gateway: non-json error response', ['body' => $response->body()]);
            return null;
        }
    }
}
