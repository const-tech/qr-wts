# const-tech/whatsapp-gateway

Reusable Laravel package that provides:

1. A clean PHP client for the **c-wts.com REST gateway** — drivers, DTOs, exceptions.
2. A self-service **public subscription flow** (Landing → Register → QR + Status → Expired) ready to drop into any Laravel project.
3. Local mirror of subscriptions in the `wa_subscriptions` table so customers can resume the flow without authenticating.

Target: Laravel 8 → 11, PHP 7.3+.

---

## 1. The two flows

### Claim flow (default — works with the public c-wts.com API)

The customer signs up directly on **c-wts.com**, gets back `instance_id` and
`access_token`, then pastes them on your registration page. The package
verifies the credentials against the live gateway via `GET /api/status` and
saves a local row in `wa_subscriptions`.

This is what `https://c-wts.com/docs` exposes publicly.

### Reseller flow (private API — for c-wts.com resellers only)

If you have a private reseller agreement with c-wts.com that lets you create
instances on behalf of customers, switch the flow:

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

### Option A — local path (recommended while iterating)

In the host project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/const-tech/whatsapp-gateway",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "const-tech/whatsapp-gateway": "*"
    }
}
```

Drop the package folder under `packages/const-tech/whatsapp-gateway`, then:

```bash
composer require const-tech/whatsapp-gateway:*
php artisan migrate
```

The service provider is auto-discovered. The `WhatsappGateway` facade is
auto-aliased.

### Option B — private Git repo

Push this folder to its own repo and reference it from `composer.json` as a
`vcs` repository.

---

## 3. Configure

Publish the config (optional — defaults work):

```bash
php artisan vendor:publish --tag=whatsapp-gateway-config
```

`.env` defaults:

```dotenv
WHATSAPP_GATEWAY_DRIVER=cwts
WHATSAPP_GATEWAY_FLOW=claim
WHATSAPP_GATEWAY_BASE_URL=https://c-wts.com
WHATSAPP_GATEWAY_VERIFY_SSL=false
WHATSAPP_GATEWAY_SIGNUP_URL=https://c-wts.com/signup
WHATSAPP_GATEWAY_LOGIN_URL=https://c-wts.com/login
WHATSAPP_GATEWAY_ROUTE_PREFIX=whatsapp
WHATSAPP_GATEWAY_SUPPORT_PHONE=0506499275
WHATSAPP_GATEWAY_HOME_URL=https://your-app.test
WHATSAPP_GATEWAY_LOGO=/img/logo.svg
```

---

## 4. Routes

Auto-registered. With **Mcamara/LaravelLocalization** installed they're nested
under the active locale and pick up the localization middleware automatically.

| URL                                       | Name                              | Purpose                            |
| ----------------------------------------- | --------------------------------- | ---------------------------------- |
| `GET  /{locale}/whatsapp`                 | `whatsapp-gateway.landing`        | Landing + free package preview     |
| `GET  /{locale}/whatsapp/register`        | `whatsapp-gateway.register.show`  | Form (info + paste credentials)    |
| `POST /{locale}/whatsapp/register`        | `whatsapp-gateway.register`       | Submit → verify → store            |
| `GET  /{locale}/whatsapp/connect/{token}` | `whatsapp-gateway.connect`        | QR + live status polling           |
| `GET  /{locale}/whatsapp/poll/{token}`    | `whatsapp-gateway.poll`           | JSON: latest QR + status (AJAX)    |
| `POST /{locale}/whatsapp/restart/{token}` | `whatsapp-gateway.restart`        | Re-pair (no-op if not exposed)     |
| `GET  /{locale}/whatsapp/expired/{token}` | `whatsapp-gateway.expired`        | Upgrade options + back-to-site     |

Disable auto-routes:

```php
'routes' => [ 'enabled' => false ],
```

---

## 5. Programmatic API

```php
use ConstTech\WhatsappGateway\Facades\WhatsappGateway;
use ConstTech\WhatsappGateway\DTOs\RegisterPayload;

// claim flow — verify pasted credentials and store
$payload = new RegisterPayload(
    name: 'عيادة المروة',
    phone: '0506499275',
    email: 'info@example.com',
    business: 'Al Marwa Clinic',
    packageId: 'free',
);
$sub = WhatsappGateway::claim($payload, 'instance12345', 'access_token_here');

// reseller flow — create on c-wts.com on behalf of the customer
$sub = WhatsappGateway::register($payload);

// session ops
$qr     = WhatsappGateway::qr($sub);          // base64 / url
$status = WhatsappGateway::status($sub);       // pending|connected|expired|...
WhatsappGateway::send($sub, '+966501234567', 'مرحبا');
WhatsappGateway::restart($sub);

// resume flow by URL token
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

Branding (app name, support phone, logo, home URL) is read from
`config/whatsapp-gateway.php#branding`. Set it once per project — no template
edits required.

---

## 7. Drivers

| Driver | Class                                        | Purpose            |
| ------ | -------------------------------------------- | ------------------ |
| `cwts` | `ConstTech\WhatsappGateway\Drivers\CwtsDriver` | Real REST gateway  |
| `null` | `ConstTech\WhatsappGateway\Drivers\NullDriver` | Tests / local dev  |

To plug in a different gateway (Whapi, Baileys server, etc.) implement
`ConstTech\WhatsappGateway\Contracts\GatewayDriver` and register it under
`config('whatsapp-gateway.drivers.<name>')`.

---

## 8. Public c-wts.com endpoints used

Documented at <https://c-wts.com/docs>:

| Method | Path           | Params                                              |
| ------ | -------------- | --------------------------------------------------- |
| GET    | `/api/status`  | `instance_id`, `access_token`                       |
| GET    | `/api/qrcode`  | `instance_id`, `access_token`                       |
| POST   | `/api/send`    | `instance_id`, `access_token`, `number`, `message`  |

> **Security:** never expose `access_token` to the frontend. The package
> always proxies these calls server-side.

---

## 9. Database

Single table mirrors remote subscriptions for resumable flows:

```
wa_subscriptions
├── local_token     (uuid, public token used in URLs)
├── name, phone, email, business
├── package_id, instance_id, token, remote_id
├── status, expires_at, dashboard_url
└── meta (json)
```

Customize the table name / connection in `config('whatsapp-gateway.storage')`.

---

## 10. Reusing across Laravel projects

For each new project:

1. Copy `packages/const-tech/whatsapp-gateway` → same path in the new repo.
2. Add the path repository + require in the new project's `composer.json`.
3. `composer require const-tech/whatsapp-gateway:*`
4. `php artisan migrate`
5. Set the env vars.

Same code, same config keys, no edits.
