<?php

namespace ConstTech\WhatsappGateway\Facades;

use ConstTech\WhatsappGateway\DTOs\QrData;
use ConstTech\WhatsappGateway\DTOs\RegisterPayload;
use ConstTech\WhatsappGateway\DTOs\StatusData;
use ConstTech\WhatsappGateway\Models\WaSubscription;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \ConstTech\WhatsappGateway\Contracts\GatewayDriver driver(?string $name = null)
 * @method static array packages()
 * @method static \ConstTech\WhatsappGateway\DTOs\PackageData|null freePackage()
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
