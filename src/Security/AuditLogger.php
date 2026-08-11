<?php

declare(strict_types=1);

namespace Richness\RichPayments\Security;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Richness\RichPayments\Models\PaymentAuditLog;
use Richness\RichPayments\Models\PaymentGateway;

final class AuditLogger
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'secret_key',
        'public_key',
        'api_key',
        'hmac_secret',
        'token',
    ];

    public function __construct(
        private readonly AuthFactory $auth,
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $changes
     */
    public function record(
        PaymentGateway $gateway,
        string $action,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $changes = [],
    ): PaymentAuditLog {
        return PaymentAuditLog::query()->create([
            'gateway_id' => $gateway->id,
            'actor_type' => $this->actor()?->getMorphClass(),
            'actor_id' => $this->actor()?->getKey(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'changes' => $this->sanitize($changes),
            'ip_address' => $this->request->ip(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function sanitize(array $changes): array
    {
        foreach (self::SENSITIVE_KEYS as $key) {
            if (array_key_exists($key, $changes)) {
                $changes[$key] = '[redacted]';
            }
        }

        return $changes;
    }

    private function actor(): ?Model
    {
        /** @var array<string, mixed> $guards */
        $guards = config('auth.guards', []);

        foreach (config('rich-payments.middleware.admin', ['web']) as $guard) {
            if (! is_string($guard) || ! array_key_exists($guard, $guards)) {
                continue;
            }

            try {
                $user = $this->auth->guard($guard)->user();
            } catch (AuthenticationException) {
                continue;
            }

            if ($user instanceof Model) {
                return $user;
            }
        }

        return null;
    }
}
