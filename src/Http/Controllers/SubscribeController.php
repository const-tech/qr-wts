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

        try {
            if (! $this->gateway->isResellerFlow()) {
                $sub = $this->gateway->claim($payload, $data['instance_id'], $data['access_token']);
            } else {
                try {
                    $sub = $this->gateway->register($payload);
                } catch (Throwable $e) {
                    // Auto-provision failed (gateway down, endpoint not yet
                    // exposed publicly, etc). Don't block the customer —
                    // create a placeholder subscription so they reach the
                    // QR screen and can re-pair from there.
                    Log::warning('whatsapp-gateway: auto-provision failed', [
                        'error' => $e->getMessage(),
                    ]);
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
            'sub'     => $sub,
            'qr'      => null, // loaded async by JS to avoid blocking the page
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
            return response()->json([
                'ok'     => true,
                'status' => ['state' => 'pending'],
                'sub'    => ['instance_id' => null, 'expires_at' => null],
                'qr_error' => __('whatsapp-gateway::messages.provision_pending'),
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
     * remote credentials. Silent on failure.
     */
    protected function tryAutoProvision(WaSubscription $sub): void
    {
        try {
            $payload = new RegisterPayload(
                $sub->name,
                $sub->phone,
                $sub->email,
                $sub->business,
                $sub->package_id,
                app()->getLocale()
            );
            $remote = $this->gateway->driver()->register($payload);
            $sub->instance_id = $remote->instanceId;
            $sub->token       = $remote->token;
            $sub->remote_id   = $remote->remoteId;
            $sub->status      = $remote->status ?: 'pending';
            if ($remote->expiresAt) {
                $sub->expires_at = $remote->expiresAt;
            }
            $sub->save();
        } catch (Throwable $e) {
            Log::debug('whatsapp-gateway: poll auto-provision retry failed', ['error' => $e->getMessage()]);
        }
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
