<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AuditLogService
{
    private bool $recording = false;

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $action, array $context = []): ?UserLog
    {
        if ($this->recording || ! $this->canWrite()) {
            return null;
        }

        $this->recording = true;

        try {
            $request = request();
            $routeName = $this->routeNameFor($request);
            $user = $context['user'] ?? $this->currentUser();
            $auditable = $context['auditable'] ?? null;

            if ($auditable instanceof UserLog) {
                return null;
            }

            $payload = [
                'user_id' => $context['user_id'] ?? ($user instanceof User ? $user->id : null),
                'user_name' => $context['user_name'] ?? ($user instanceof User ? $user->name : null),
                'user_email' => $context['user_email'] ?? ($user instanceof User ? $user->email : null),
                'action' => $action,
                'module' => $context['module'] ?? $this->moduleFor($auditable, $routeName),
                'description' => $context['description'] ?? $this->descriptionFor($action, $auditable),
                'auditable_type' => $context['auditable_type'] ?? ($auditable instanceof Model ? $auditable::class : null),
                'auditable_id' => $context['auditable_id'] ?? ($auditable instanceof Model ? $auditable->getKey() : null),
                'auditable_label' => $context['auditable_label'] ?? ($auditable instanceof Model ? $this->labelFor($auditable) : null),
                'method' => $context['method'] ?? $request->method(),
                'route_name' => $context['route_name'] ?? $routeName,
                'url' => $context['url'] ?? $request->fullUrl(),
                'ip_address' => $context['ip_address'] ?? $request->ip(),
                'user_agent' => $context['user_agent'] ?? (string) $request->userAgent(),
                'status_code' => $context['status_code'] ?? null,
                'request_data' => array_key_exists('request_data', $context) ? $this->sanitize($context['request_data']) : null,
                'old_values' => array_key_exists('old_values', $context) ? $this->sanitize($context['old_values']) : null,
                'new_values' => array_key_exists('new_values', $context) ? $this->sanitize($context['new_values']) : null,
                'metadata' => array_key_exists('metadata', $context) ? $this->sanitize($context['metadata']) : null,
                'occurred_at' => $context['occurred_at'] ?? now(),
            ];

            return UserLog::create($payload);
        } catch (Throwable) {
            return null;
        } finally {
            $this->recording = false;
        }
    }

    /**
     * @param  array<string, mixed>  $requestData
     */
    public function recordRequest(string $action, array $requestData, int $statusCode, ?float $durationMs = null): ?UserLog
    {
        return $this->record($action, [
            'request_data' => $requestData,
            'status_code' => $statusCode,
            'metadata' => array_filter([
                'duration_ms' => $durationMs !== null ? round($durationMs, 2) : null,
            ], fn ($value) => $value !== null),
        ]);
    }

    public function recordModelEvent(Model $model, string $event): ?UserLog
    {
        if ($model instanceof UserLog) {
            return null;
        }

        $oldValues = null;
        $newValues = null;

        if ($event === 'updated') {
            $changed = Arr::except($model->getChanges(), ['updated_at']);

            if ($changed === []) {
                return null;
            }

            $oldValues = [];
            foreach (array_keys($changed) as $key) {
                $oldValues[$key] = $model->getOriginal($key);
            }
            $newValues = $changed;
        } elseif (in_array($event, ['created', 'deleted', 'restored', 'force_deleted'], true)) {
            $newValues = $event === 'created' ? Arr::except($model->getAttributes(), ['created_at', 'updated_at']) : null;
            $oldValues = in_array($event, ['deleted', 'force_deleted'], true)
                ? Arr::except($model->getOriginal(), ['created_at', 'updated_at'])
                : null;
        }

        return $this->record($event, [
            'auditable' => $model,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function sanitize($value)
    {
        if ($value instanceof UploadedFile) {
            return [
                'name' => $value->getClientOriginalName(),
                'mime_type' => $value->getClientMimeType(),
                'size' => $value->getSize(),
                'error' => $value->getError(),
            ];
        }

        if ($value instanceof Model) {
            return [
                'type' => $value::class,
                'id' => $value->getKey(),
                'label' => $this->labelFor($value),
            ];
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                $stringKey = is_string($key) ? $key : (string) $key;
                $clean[$key] = $this->isSensitiveKey($stringKey)
                    ? '[redacted]'
                    : $this->sanitize($item);
            }

            return $clean;
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : get_debug_type($value);
        }

        return $value;
    }

    private function canWrite(): bool
    {
        try {
            return Schema::hasTable('user_logs');
        } catch (Throwable) {
            return false;
        }
    }

    private function currentUser(): ?User
    {
        try {
            $user = request()->user();

            return $user instanceof User ? $user : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function routeNameFor($request): ?string
    {
        $routeName = $request->route()?->getName();

        return is_string($routeName) && ! str_starts_with($routeName, 'generated::') ? $routeName : null;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match('/(^_token$|csrf|password|passphrase|remember|token|secret|authorization|cookie|application[_-]?key|private[_-]?key|client[_-]?secret|access[_-]?key|session)/i', $key) === 1;
    }

    private function moduleFor(mixed $auditable, ?string $routeName): string
    {
        $class = $auditable instanceof Model ? $auditable::class : null;
        $path = request()->path();

        return match (true) {
            in_array($path, ['login', 'logout', 'register', 'forgot-password', 'confirm-password'], true) || Str::startsWith($path, ['reset-password', 'verify-email']) => 'Auth',
            $routeName !== null && Str::startsWith($routeName, ['login', 'logout', 'register', 'password.', 'verification.']) => 'Auth',
            $routeName !== null && Str::startsWith($routeName, ['user-logs.']) => 'Audit',
            $routeName !== null && Str::startsWith($routeName, ['profile.']) => 'Profile',
            $routeName !== null && Str::startsWith($routeName, ['orders.']) => 'Orders',
            $routeName !== null && Str::startsWith($routeName, ['purchases.']) => 'Purchases',
            $routeName !== null && Str::startsWith($routeName, ['reports.']) => 'Reports',
            $routeName !== null && Str::startsWith($routeName, ['products.', 'quick.', 'categories.', 'sub-categories.', 'units.', 'attributes.']) => 'Product Metadata',
            $routeName !== null && Str::startsWith($routeName, ['courier-', 'couriers.', 'courier-payments.', 'bank-accounts.']) => 'Courier Settlement',
            $routeName !== null && Str::startsWith($routeName, ['resellers.', 'direct-resellers.', 'reseller-', 'direct-reseller-']) => 'Resellers',
            $routeName !== null && Str::startsWith($routeName, ['customers.', 'suppliers.', 'cities.']) => 'Contacts',
            $routeName !== null && Str::startsWith($routeName, ['admin.']) => 'Admin',
            $class !== null && Str::contains($class, ['Product', 'Category', 'SubCategory', 'Unit', 'Attribute']) => 'Product Metadata',
            $class !== null && Str::contains($class, ['Purchase', 'InventoryUnit', 'StoreRack']) => 'Purchases',
            $class !== null && Str::contains($class, ['Order', 'Waybill']) => 'Orders',
            $class !== null && Str::contains($class, ['Courier', 'BankAccount']) => 'Courier Settlement',
            $class !== null && Str::contains($class, ['Reseller']) => 'Resellers',
            $class !== null && Str::contains($class, ['Customer', 'Supplier', 'City']) => 'Contacts',
            $class !== null && Str::contains($class, ['User', 'Role', 'Permission']) => 'Admin',
            default => 'System',
        };
    }

    private function descriptionFor(string $action, mixed $auditable): string
    {
        $label = $auditable instanceof Model ? $this->labelFor($auditable) : null;
        $subject = $auditable instanceof Model ? class_basename($auditable) : 'request';

        return trim(Str::headline($action).' '.$subject.($label ? ': '.$label : ''));
    }

    private function labelFor(Model $model): string
    {
        foreach ([
            'order_number',
            'purchase_number',
            'unit_code',
            'sku',
            'business_name',
            'company_name',
            'name',
            'title',
            'email',
            'mobile',
            'phone',
            'waybill_number',
        ] as $column) {
            $value = $model->getAttribute($column);

            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }
}
