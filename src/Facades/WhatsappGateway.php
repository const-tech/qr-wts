<?php

namespace Almarwa\WhatsappGateway\Facades;

use Almarwa\WhatsappGateway\DTOs\QrData;
use Almarwa\WhatsappGateway\DTOs\RegisterPayload;
use Almarwa\WhatsappGateway\DTOs\StatusData;
use Almarwa\WhatsappGateway\Models\WaSubscription;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Almarwa\WhatsappGateway\Contracts\GatewayDriver driver(?string $name = null)
 * @method static array packages()
 * @method static \Almarwa\WhatsappGateway\DTOs\PackageData|null freePackage()
 * @method static WaSubscription register(RegisterPayload $payload)
 * @method static QrData qr(WaSubscription $sub)
 * @method static StatusData status(WaSubscription $sub, bool $persist = true)
 * @method static bool restart(WaSubscription $sub)
 * @method static array upgrades(WaSubscription $sub)
 * @method static array send(WaSubscription $sub, string $phone, string $message)
 * @method static WaSubscription|null findByToken(string $token)
 */
class WhatsappGateway extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'whatsapp-gateway';
    }
}
