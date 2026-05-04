<?php

namespace Almarwa\WhatsappGateway\Contracts;

use Almarwa\WhatsappGateway\DTOs\PackageData;
use Almarwa\WhatsappGateway\DTOs\QrData;
use Almarwa\WhatsappGateway\DTOs\RegisterPayload;
use Almarwa\WhatsappGateway\DTOs\StatusData;
use Almarwa\WhatsappGateway\DTOs\SubscriptionData;

interface GatewayDriver
{
    /**
     * Validate {instance_id, access_token} against the remote gateway.
     * Returns the live status if the credentials work; throws otherwise.
     */
    public function verify(string $instanceId, string $accessToken): StatusData;

    /**
     * Catalog of subscription packages on the remote gateway. Optional:
     * implementations may return an empty array if the remote does not
     * expose a public packages endpoint.
     *
     * @return array<int,PackageData>
     */
    public function listPackages(): array;

    /**
     * (Reseller flow only) create a brand-new subscription on behalf of a
     * customer. Requires the remote to expose a private reseller API and
     * a configured admin_token.
     */
    public function register(RegisterPayload $payload): SubscriptionData;

    /**
     * Fetch the current pairing QR code for an instance.
     */
    public function getQr(string $instanceId, string $accessToken): QrData;

    /**
     * Fetch the live connection status for an instance.
     */
    public function getStatus(string $instanceId, string $accessToken): StatusData;

    /**
     * Restart / re-pair the WhatsApp session. May be a no-op on remotes
     * that do not expose a restart endpoint.
     */
    public function restartSession(string $instanceId, string $accessToken): bool;

    /**
     * (Reseller flow only) list upgrade plans for an instance.
     *
     * @return array<int,PackageData>
     */
    public function listUpgrades(string $instanceId, string $accessToken): array;

    /**
     * Send a WhatsApp message through an instance owned by the caller.
     */
    public function sendMessage(string $instanceId, string $accessToken, string $phone, string $message): array;
}
