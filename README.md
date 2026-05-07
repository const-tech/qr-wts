# const-tech/whatsapp-gateway

Reusable Laravel package that provides:

1. A PHP client for the **c-wts.com REST gateway** — drivers, DTOs, exceptions.
2. A self-service **public subscription flow** (Landing → Register → QR → Expired) ready to drop into any Laravel project.
3. An authenticated **Our Services** page auto-registered on every install.
4. Local mirror of subscriptions in `wa_subscriptions` so customers can resume without re-registering.

**Requirements:** Laravel 8–11, PHP 7.3+.

---

## 1. The two flows

### Claim flow (default)

The customer signs up directly on **c-wts.com**, gets back `instance_id` and
`access_token`, then pastes them on your registration page. The package
verifies the credentials against the live gateway and saves a local row.

### Reseller flow (private API — for c-wts.com resellers only)

If you have a private reseller agreement with c-wts.com, switch the flow:

```dotenv
WHATSAPP_GATEWAY_FLOW=reseller
WHATSAPP_GATEWAY_ADMIN_TOKEN=your_reseller_token
WHATSAPP_GATEWAY_REGISTER_ENDPOINT=/api/reseller/register
WHATSAPP_GATEWAY_RESTART_ENDPOINT=/api/instances/{instance}/restart
WHATSAPP_GATEWAY_PACKAGES_ENDPOINT=/api/packages
WHATSAPP_GATEWAY_UPGRADES_ENDPOINT=/api/instances/{instance}/upgrades
```

The customer fills the form, your server calls the private register endpoint,
and you get back `{instance_id, access_token}` — no manual paste required.

Default: **claim**.

---

## 2. Install

```bash
composer require const-tech/whatsapp-gateway
php artisan migrate
```

The service provider and `WhatsappGateway` facade are auto-discovered.

---

## 3. Configure

Publish the config (optional — defaults work out of the box):

```bash
php artisan vendor:publish --tag=whatsapp-gateway-config
```

Key `.env` values:

```dotenv
# Gateway
WHATSAPP_GATEWAY_DRIVER=cwts
WHATSAPP_GATEWAY_FLOW=claim

# Branding shown in the package views
APP_NAME="My Website"
WHATSAPP_GATEWAY_HOME_URL=https://your-app.test

# Public subscription route prefix  (default: whatsapp)
WHATSAPP_GATEWAY_ROUTE_PREFIX=whatsapp

# Our Services page (default: our-services)
WHATSAPP_GATEWAY_ADMIN_PREFIX=our-services
WHATSAPP_GATEWAY_BACK_ROUTE=front.home
WHATSAPP_GATEWAY_SERVICES_MODEL=App\Models\OurService
```

> `WHATSAPP_GATEWAY_SERVICES_MODEL` — the Eloquent model that supplies the services
> table rows (needs `name` and `description` columns). If the class does not
> exist the services table is silently hidden; the rest of the page still renders.

---

## 4. Routes

All routes are auto-registered. With **Mcamara/LaravelLocalization** installed
they're nested under the active locale and pick up localization middleware
automatically.

### Public subscription flow

| URL | Name | Purpose |
|---|---|---|
| `GET  /whatsapp` | `whatsapp-gateway.landing` | Landing + free package preview |
| `GET  /whatsapp/register` | `whatsapp-gateway.register.show` | Registration form |
| `POST /whatsapp/register` | `whatsapp-gateway.register` | Submit → verify → store |
| `GET  /whatsapp/connect/{token}` | `whatsapp-gateway.connect` | QR + live status polling |
| `GET  /whatsapp/poll/{token}` | `whatsapp-gateway.poll` | JSON: latest QR + status (AJAX) |
| `POST /whatsapp/restart/{token}` | `whatsapp-gateway.restart` | Re-pair session |
| `GET  /whatsapp/expired/{token}` | `whatsapp-gateway.expired` | Upgrade options |

### Our Services page (authenticated)

| URL | Name | Middleware |
|---|---|---|
| `GET /our-services` | `whatsapp-gateway.admin.index` | `web`, `auth` |

The page uses the package's own Bootstrap/WhatsApp layout — no host-app layout
needed. To disable it:

```php
// config/whatsapp-gateway.php
'admin' => [ 'enabled' => false ],
```

To disable the subscription routes:

```php
'routes' => [ 'enabled' => false ],
```

---

## 5. Programmatic API

```php
use ConstTech\WhatsappGateway\Facades\WhatsappGateway;
use ConstTech\WhatsappGateway\DTOs\RegisterPayload;

$payload = new RegisterPayload(
    name: 'My Clinic',
    phone: '0506499275',
    email: 'info@example.com',
    business: 'My Clinic LLC',
    packageId: 'free',
);

// Claim flow — verify pasted credentials and store
$sub = WhatsappGateway::claim($payload, 'instance12345', 'access_token_here');

// Reseller flow — create on c-wts.com on behalf of the customer
$sub = WhatsappGateway::register($payload);

// Session ops
$qr     = WhatsappGateway::qr($sub);
$status = WhatsappGateway::status($sub);
WhatsappGateway::send($sub, '+966501234567', 'Hello');
WhatsappGateway::restart($sub);

// Resume flow by URL token
$sub = WhatsappGateway::findByToken($localToken);
```

---

## 6. Customizing the views

```bash
php artisan vendor:publish --tag=whatsapp-gateway-views
```

Override any of:

- `layout.blade.php` — RTL/LTR aware HTML wrapper
- `landing.blade.php` — promo + free package card
- `register.blade.php` — form (claim or reseller mode auto-detected)
- `qr.blade.php` — QR code + AJAX polling
- `expired.blade.php` — upgrade options
- `services.blade.php` — Our Services page

Branding (app name, logo, home URL) is read from `config/whatsapp-gateway.php#branding`.
Set it once per project — no template edits required.

---

## 7. Drivers

| Driver | Class | Purpose |
|---|---|---|
| `cwts` | `ConstTech\WhatsappGateway\Drivers\CwtsDriver` | Real REST gateway |
| `null` | `ConstTech\WhatsappGateway\Drivers\NullDriver` | Tests / local dev |

To add a custom gateway implement
`ConstTech\WhatsappGateway\Contracts\GatewayDriver` and register it under
`config('whatsapp-gateway.drivers.<name>')`.

---

## 8. Public c-wts.com endpoints used

Documented at <https://c-wts.com/docs>:

| Method | Path | Params |
|---|---|---|
| GET | `/api/status` | `instance_id`, `access_token` |
| GET | `/api/qrcode` | `instance_id`, `access_token` |
| POST | `/api/send` | `instance_id`, `access_token`, `number`, `message` |

> **Security:** `access_token` is never exposed to the frontend — all calls
> are proxied server-side.

---

## 9. Database

```
wa_subscriptions
├── local_token     (uuid — public token used in URLs)
├── name, phone, email, business
├── package_id, instance_id, token, remote_id
├── status, expires_at, dashboard_url
└── meta (json)
```

Customize the table name / connection:

```php
'storage' => [
    'table'      => 'wa_subscriptions',
    'connection' => null,
],
```

---

## 10. Reusing across Laravel projects

```bash
composer require const-tech/whatsapp-gateway
php artisan migrate
```

Then set the `.env` values listed in section 3. Same code, same config keys,
no copy-paste.