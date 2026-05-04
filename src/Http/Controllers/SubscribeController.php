<?php

namespace Almarwa\WhatsappGateway\Http\Controllers;

use Almarwa\WhatsappGateway\DTOs\RegisterPayload;
use Almarwa\WhatsappGateway\DTOs\StatusData;
use Almarwa\WhatsappGateway\DTOs\SubscriptionData;
use Almarwa\WhatsappGateway\Manager\WhatsappGatewayManager;
use Almarwa\WhatsappGateway\Models\WaSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SubscribeController extends Controller
{
    /** @var WhatsappGatewayManager */
    protected $gateway;

    public function __construct(WhatsappGatewayManager $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Step 0 — landing page with the free package preview.
     */
    public function landing()
    {
        $packages = $this->gateway->packages();
        $free     = $this->gateway->freePackage();
        return view('whatsapp-gateway::landing', compact('packages', 'free'));
    }

    /**
     * Step 1 — show the registration form (basic customer info).
     *
     * Default is the reseller flow (no manual paste). The form falls back
     * to claim mode (paste credentials) if the reseller flow is disabled
     * or its credentials are not configured.
     */
    public function showRegister()
    {
        $free      = $this->gateway->freePackage();
        $signupUrl = $this->gateway->signupUrl();
        $claimMode = ! $this->gateway->isResellerFlow();
        return view('whatsapp-gateway::register', compact('free', 'signupUrl', 'claimMode'));
    }

    /**
     * Step 1 (POST) — handle both flows:
     *   - reseller: call private register API, return token
     *   - claim:    customer pasted instance_id + access_token, verify, store
     */
    public function register(Request $request)
    {
        $rules = [
            'name'     => 'required|string|max:120',
            'phone'    => ['required', 'string', 'max:32', 'regex:/^[+0-9 \-]{6,}$/'],
            'email'    => 'nullable|email|max:150',
            'business' => 'nullable|string|max:160',
            'package'  => 'nullable|string|max:60',
        ];

        if (! $this->gateway->isResellerFlow()) {
            $rules['instance_id']  = 'required|string|max:120';
            $rules['access_token'] = 'required|string|max:120';
        }

        $data = Validator::make($request->all(), $rules)->validate();

        $payload = new RegisterPayload(
            $data['name'],
            $data['phone'],
            $data['email']    ?? null,
            $data['business'] ?? null,
            $data['package']  ?? 'free',
            app()->getLocale()
        );

        // Dedupe — if a customer with the same phone already registered,
        // just refresh their session and redirect them to their existing
        // QR / connect screen instead of creating a duplicate row.
        $existing = WaSubscription::where('phone', $payload->phone)->latest()->first();
        if ($existing) {
            $existing->fill([
                'name'       => $payload->name,
                'email'      => $payload->email     ?: $existing->email,
                'business'   => $payload->business  ?: $existing->business,
                'package_id' => $payload->packageId ?: $existing->package_id,
            ]);

            // If we have fallback creds and the existing record has none, attach them
            if (empty($existing->instance_id) || empty($existing->token)) {
                $fallback = $this->gateway->fallbackCredentials();
                if ($fallback) {
                    $existing->instance_id = $fallback['instance_id'];
                    $existing->token       = $fallback['access_token'];
                }
            }
            $existing->save();

            return redirect()
                ->route('whatsapp-gateway.connect', ['token' => $existing->local_token])
                ->with('status', __('whatsapp-gateway::messages.welcome_back'));
        }

        try {
            if (! $this->gateway->isResellerFlow()) {
                $sub = $this->gateway->claim($payload, $data['instance_id'], $data['access_token']);
            } else {
                try {
                    // Manager::register() already falls through to fallback
                    // credentials when the remote gateway has no register
                    // endpoint, so the QR screen always loads.
                    $sub = $this->gateway->register($payload);
                } catch (Throwable $e) {
                    Log::warning('whatsapp-gateway: auto-provision failed (no fallback)', [
                        'error' => $e->getMessage(),
                    ]);
                    // Last resort — create a placeholder; the QR page will
                    // surface the inline claim form.
                    $sub = WaSubscription::recordRemote(
                        SubscriptionData::fromArray([
                            'instance_id' => '',
                            'token'       => '',
                            'package_id'  => $payload->packageId ?: 'free',
                            'status'      => 'pending',
                        ]),
                        $payload
                    );
                }
            }
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['gateway' => __('whatsapp-gateway::messages.gateway_error') . ' — ' . $e->getMessage()]);
        }

        return redirect()->route('whatsapp-gateway.connect', ['token' => $sub->local_token]);
    }

    /**
     * Step 2 — render the QR + status screen.
     */
    public function connect(string $token)
    {
        $sub = $this->resolve($token);

        if ($sub->isExpired()) {
            return redirect()->route('whatsapp-gateway.expired', ['token' => $token]);
        }

        return view('whatsapp-gateway::qr', [
            'sub'        => $sub,
            'qr'         => null, // loaded async by JS to avoid blocking the page
            'needsCreds' => empty($sub->instance_id) || empty($sub->token),
            'signupUrl'  => $this->gateway->signupUrl(),
        ]);
    }

    /**
     * AJAX — return QR + status as JSON for live polling.
     */
    public function poll(string $token): JsonResponse
    {
        $sub = $this->resolve($token);

        // If we don't have credentials yet (auto-provision failed earlier),
        // try once more so the QR screen "self-heals" instead of erroring.
        if (empty($sub->instance_id) || empty($sub->token)) {
            $this->tryAutoProvision($sub);
        }

        if (empty($sub->instance_id) || empty($sub->token)) {
            // Diagnose why we're still without creds so the customer sees
            // a useful message instead of an endless spinner. Most likely:
            // c-wts.com has no public register endpoint AND no fallback
            // credentials are configured.
            $hasFallback = (bool) $this->gateway->fallbackCredentials();
            $message = $hasFallback
                ? __('whatsapp-gateway::messages.provision_pending')
                : __('whatsapp-gateway::messages.provision_unavailable');

            return response()->json([
                'ok'     => true,
                'status' => ['state' => 'pending'],
                'sub'    => ['instance_id' => null, 'expires_at' => null],
                'qr_error' => $message,
            ]);
        }

        try {
            $status = $this->gateway->status($sub);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 502);
        }

        $payload = [
            'ok'     => true,
            'status' => $status->toArray(),
            'sub'    => [
                'instance_id' => $sub->instance_id,
                'expires_at'  => $sub->expires_at ? $sub->expires_at->toIso8601String() : null,
            ],
        ];

        if ($status->state === StatusData::STATE_PENDING) {
            try {
                $payload['qr'] = $this->gateway->qr($sub)->toArray();
            } catch (Throwable $e) {
                $payload['qr_error'] = $e->getMessage();
            }
        }

        return response()->json($payload);
    }

    /**
     * Re-attempt auto-provision for a subscription that doesn't yet have
     * remote credentials. Silent on failure. Falls back to configured
     * credentials so the QR screen never gets stuck.
     */
    protected function tryAutoProvision(WaSubscription $sub): void
    {
        $payload = new RegisterPayload(
            $sub->name,
            $sub->phone,
            $sub->email,
            $sub->business,
            $sub->package_id,
            app()->getLocale()
        );

        try {
            $remote = $this->gateway->driver()->register($payload);
            $sub->instance_id = $remote->instanceId;
            $sub->token       = $remote->token;
            $sub->remote_id   = $remote->remoteId;
            $sub->status      = $remote->status ?: 'pending';
            if ($remote->expiresAt) {
                $sub->expires_at = $remote->expiresAt;
            }
            $sub->save();
            return;
        } catch (Throwable $e) {
            Log::debug('whatsapp-gateway: poll auto-provision retry failed', ['error' => $e->getMessage()]);
        }

        $fallback = $this->gateway->fallbackCredentials();
        if ($fallback) {
            $sub->instance_id = $fallback['instance_id'];
            $sub->token       = $fallback['access_token'];
            $sub->save();
        }
    }

    /**
     * Attach existing c-wts.com credentials to a subscription. Used as a
     * fallback when auto-provisioning isn't available — the customer
     * pastes their {instance_id, access_token} on the QR page itself.
     */
    public function attach(Request $request, string $token)
    {
        $sub = $this->resolve($token);

        $data = Validator::make($request->all(), [
            'instance_id'  => 'required|string|max:120',
            'access_token' => 'required|string|max:120',
        ])->validate();

        try {
            $this->gateway->driver()->verify($data['instance_id'], $data['access_token']);
        } catch (Throwable $e) {
            return back()->withErrors(['gateway' => __('whatsapp-gateway::messages.gateway_error') . ' — ' . $e->getMessage()]);
        }

        $sub->instance_id = $data['instance_id'];
        $sub->token       = $data['access_token'];
        $sub->save();

        return redirect()->route('whatsapp-gateway.connect', ['token' => $sub->local_token]);
    }

    /**
     * Restart / re-pair an existing instance (for expired or stuck sessions).
     */
    public function restart(string $token)
    {
        $sub = $this->resolve($token);

        try {
            $ok = $this->gateway->restart($sub);
        } catch (Throwable $e) {
            return back()->withErrors(['gateway' => $e->getMessage()]);
        }

        if (! $ok) {
            // Fall back to opening the c-wts.com login so the user can
            // restart the session from the dashboard.
            return back()->with('status', __('whatsapp-gateway::messages.restart_unavailable'));
        }

        return redirect()->route('whatsapp-gateway.connect', ['token' => $token])
            ->with('status', __('whatsapp-gateway::messages.session_restarted'));
    }

    /**
     * Expired screen with upgrade options + restart + back-to-site.
     */
    public function expired(string $token)
    {
        $sub      = $this->resolve($token);
        $upgrades = [];
        try {
            $upgrades = $this->gateway->upgrades($sub);
        } catch (Throwable $e) {
            // non-fatal — show empty list
        }
        $loginUrl = $this->gateway->loginUrl();
        return view('whatsapp-gateway::expired', compact('sub', 'upgrades', 'loginUrl'));
    }

    protected function resolve(string $token): WaSubscription
    {
        $sub = $this->gateway->findByToken($token);
        if (! $sub) {
            abort(404);
        }
        return $sub;
    }
}
