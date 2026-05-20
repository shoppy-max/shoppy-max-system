<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'action',
        'module',
        'description',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'method',
        'route_name',
        'url',
        'ip_address',
        'user_agent',
        'status_code',
        'request_data',
        'old_values',
        'new_values',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return self::labelForAction((string) $this->action);
    }

    public static function labelForAction(string $action): string
    {
        return match ($action) {
            'login_succeeded' => 'Login Success',
            'login_failed' => 'Login Failed',
            'logout' => 'Logout',
            'updated_request' => 'Update Submitted',
            'deleted_request' => 'Delete Submitted',
            'request_failed' => 'Failed Request',
            'submitted' => 'Submitted',
            'downloaded' => 'Downloaded',
            'viewed' => 'Viewed',
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'force_deleted' => 'Force Deleted',
            default => Str::headline($action),
        };
    }

    public function getOperationLabelAttribute(): string
    {
        $location = $this->locationSentenceLabel();
        $recordType = $this->recordTypeLabel();
        $downloadFormat = $this->downloadFormatLabel();

        return match ($this->action) {
            'login_succeeded' => 'Logged In',
            'login_failed' => 'Failed Login Attempt',
            'logout' => 'Logged Out',
            'viewed' => str_contains($location, 'Edit Page') || str_contains($location, 'Create Page')
                ? "Opened {$location}"
                : "Viewed {$location}",
            'submitted' => $this->submittedOperationLabel($location),
            'updated_request' => "Submitted Update For {$location}",
            'deleted_request' => "Submitted Delete For {$location}",
            'request_failed' => $this->isLoginPath() ? 'Failed Login Request' : "Failed {$location}",
            'downloaded' => trim("Downloaded {$location} {$downloadFormat}"),
            'created' => "Created {$recordType}",
            'updated' => "Updated {$recordType}",
            'deleted' => "Deleted {$recordType}",
            'restored' => "Restored {$recordType}",
            'force_deleted' => "Permanently Deleted {$recordType}",
            default => trim($this->action_label.' '.$location),
        };
    }

    public function getHumanDescriptionAttribute(): string
    {
        $parts = [$this->operation_label];

        if ($this->subject_label !== 'Request') {
            $parts[] = 'Subject: '.$this->subject_label;
        }

        $parts[] = 'Page: '.$this->location_label;

        if ($this->description && ! $this->isGenericDescription($this->description)) {
            $parts[] = 'Note: '.$this->description;
        }

        return implode(' | ', $parts);
    }

    public function getLocationLabelAttribute(): string
    {
        if (! $this->route_name || Str::startsWith($this->route_name, 'generated::')) {
            return $this->pathLabel();
        }

        $parts = collect(explode('.', $this->route_name))
            ->reject(fn ($part) => $part === '')
            ->map(fn ($part) => $this->routeSegmentLabel($part))
            ->values();

        if ($parts->isEmpty()) {
            return $this->pathLabel();
        }

        return $parts->join(' - ');
    }

    public function getSubjectLabelAttribute(): string
    {
        if ($this->auditable_label) {
            return $this->auditable_label;
        }

        if ($routeParameterLabel = $this->routeParameterSubjectLabel()) {
            return $routeParameterLabel;
        }

        if ($this->auditable_type && $this->auditable_id) {
            return class_basename($this->auditable_type).' #'.$this->auditable_id;
        }

        if ($this->isAuthPath()) {
            return 'Authentication';
        }

        if ($this->description && ! $this->isGenericDescription($this->description)) {
            return $this->description;
        }

        return match ($this->action) {
            'viewed' => 'Page View',
            'submitted', 'updated_request' => 'Form Submission',
            'deleted_request' => 'Delete Request',
            'downloaded' => 'Download Request',
            'request_failed' => 'Failed Request',
            'login_succeeded', 'login_failed', 'logout' => 'Authentication',
            default => 'System Request',
        };
    }

    public function getTechnicalSubjectLabelAttribute(): string
    {
        if (! $this->auditable_type) {
            return '-';
        }

        return class_basename($this->auditable_type).($this->auditable_id ? ' #'.$this->auditable_id : '');
    }

    public function getReadableRequestDataAttribute(): mixed
    {
        return $this->readableData($this->request_data);
    }

    public function getReadableOldValuesAttribute(): mixed
    {
        return $this->readableData($this->old_values);
    }

    public function getReadableNewValuesAttribute(): mixed
    {
        return $this->readableData($this->new_values);
    }

    public function getReadableMetadataAttribute(): mixed
    {
        return $this->readableData($this->metadata);
    }

    private function locationSentenceLabel(): string
    {
        return str_replace(' - ', ' ', $this->location_label);
    }

    private function submittedOperationLabel(string $location): string
    {
        if ($this->isLoginPath()) {
            return 'Submitted Login Form';
        }

        if ($this->isLogoutPath()) {
            return 'Submitted Logout Request';
        }

        return "Submitted {$location}";
    }

    private function recordTypeLabel(): string
    {
        if ($this->auditable_type) {
            return Str::headline(class_basename($this->auditable_type));
        }

        if ($this->module) {
            return Str::headline((string) $this->module);
        }

        return 'Record';
    }

    private function downloadFormatLabel(): string
    {
        $format = data_get($this->request_data, 'format')
            ?? data_get($this->request_data, 'export')
            ?? data_get($this->metadata, 'format')
            ?? data_get($this->metadata, 'download_format');

        if (is_string($format) && $format !== '') {
            return match (strtolower($format)) {
                'xlsx', 'xls', 'excel' => 'Excel',
                'pdf' => 'PDF',
                'csv' => 'CSV',
                default => Str::headline($format),
            };
        }

        $contentType = (string) (data_get($this->metadata, 'content_type') ?? '');
        $contentDisposition = (string) (data_get($this->metadata, 'content_disposition') ?? '');
        $combined = strtolower($contentType.' '.$contentDisposition.' '.$this->url);

        return match (true) {
            str_contains($combined, 'spreadsheet'),
            str_contains($combined, '.xlsx'),
            str_contains($combined, 'excel') => 'Excel',
            str_contains($combined, 'pdf') => 'PDF',
            str_contains($combined, 'csv') => 'CSV',
            default => '',
        };
    }

    private function routeParameterSubjectLabel(): ?string
    {
        $parameters = data_get($this->request_data, 'route_parameters');

        if (! is_array($parameters)) {
            return null;
        }

        foreach ($parameters as $parameter) {
            if (is_array($parameter) && isset($parameter['label']) && is_string($parameter['label']) && $parameter['label'] !== '') {
                return $parameter['label'];
            }
        }

        return null;
    }

    private function isAuthPath(): bool
    {
        return $this->isLoginPath() || $this->isLogoutPath();
    }

    private function isLoginPath(): bool
    {
        return $this->requestPath() === 'login';
    }

    private function isLogoutPath(): bool
    {
        return $this->requestPath() === 'logout';
    }

    private function requestPath(): string
    {
        if (! $this->url) {
            return '';
        }

        return trim((string) (parse_url($this->url, PHP_URL_PATH) ?: ''), '/');
    }

    private function isGenericDescription(string $description): bool
    {
        return in_array(Str::lower($description), [
            'viewed request',
            'submitted request',
            'downloaded request',
            'failed request',
            'updated request',
            'deleted request',
        ], true);
    }

    private function routeSegmentLabel(string $segment): string
    {
        return match ($segment) {
            'admin' => 'Admin',
            'users' => 'Users',
            'roles' => 'Roles',
            'permissions' => 'Permissions',
            'user-logs' => 'User Logs',
            'products' => 'Products',
            'barcode', 'barcodes' => 'Barcodes',
            'quick' => 'Quick Create',
            'category' => 'Category',
            'subcategory' => 'Sub Category',
            'sub-categories' => 'Sub Categories',
            'units' => 'Units',
            'attributes' => 'Attributes',
            'orders' => 'Orders',
            'waybill' => 'Waybill',
            'waybill-excel' => 'Waybill Excel',
            'bulk-pdf' => 'Bulk PDF Download',
            'call-list' => 'Call List',
            'search-products' => 'Product Search',
            'search-resellers' => 'Reseller Search',
            'search-customers' => 'Customer Search',
            'packing' => 'Packing',
            'pick-grn' => 'Pick GRN',
            'mark-picked' => 'Mark Picked',
            'mark-packed' => 'Mark Packed',
            'mark-dispatched' => 'Mark Dispatched',
            'mark-delivered' => 'Mark Delivered',
            'purchases' => 'Purchases',
            'store-placement' => 'Store Placement',
            'store-racks' => 'Store Racks',
            'moderation' => 'Moderation',
            'approve' => 'Approve',
            'grn' => 'GRN',
            'courier-receive' => 'Receive Courier Payment',
            'couriers' => 'Couriers',
            'courier-payments' => 'Courier Payments',
            'bank-accounts' => 'Bank Accounts',
            'resellers' => 'Direct Resellers',
            'direct-resellers' => 'Resellers',
            'reseller-payments' => 'Direct Reseller Payments',
            'direct-reseller-payments' => 'Reseller Payments',
            'reseller-dues' => 'Direct Reseller Dues',
            'direct-reseller-dues' => 'Reseller Dues',
            'reseller-targets' => 'Direct Reseller Targets',
            'customers' => 'Customers',
            'suppliers' => 'Suppliers',
            'cities' => 'Cities',
            'reports' => 'Reports',
            'packet-count' => 'Packed And Picked Count',
            'product-sales' => 'Product Wise Sales',
            'user-sales' => 'User Wise Sales',
            'stock' => 'Stock',
            'province' => 'Province Sales',
            'profit-loss' => 'Profit And Loss',
            'index' => 'List',
            'create' => 'Create Page',
            'store' => 'Create',
            'show' => 'View',
            'edit' => 'Edit Page',
            'update' => 'Update',
            'destroy' => 'Delete',
            'export' => 'Export',
            'download' => 'Download',
            'download-bulk' => 'Bulk Download',
            'template' => 'Template Download',
            'import' => 'Import',
            'preview' => 'Preview',
            'pdf' => 'PDF',
            'print' => 'Print',
            'reprint' => 'Reprint',
            'reprint-bulk' => 'Bulk Reprint',
            'scan' => 'Scan',
            'status' => 'Status',
            'ready' => 'Ready To Pick',
            'picking' => 'Picking',
            'packed' => 'Packed',
            'dispatched' => 'Dispatched',
            'process' => 'Process',
            'success' => 'Success',
            default => Str::headline(str_replace('-', ' ', $segment)),
        };
    }

    private function pathLabel(): string
    {
        if (! $this->url) {
            return 'System';
        }

        $path = parse_url($this->url, PHP_URL_PATH) ?: $this->url;
        $path = trim((string) $path, '/');

        return $path === '' ? 'Home' : Str::headline(str_replace(['/', '-'], ' ', $path));
    }

    private function readableData(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $this->readableScalar($value, null);
        }

        $readable = [];
        foreach ($value as $key => $item) {
            $readable[$this->readableKey($key)] = is_array($item)
                ? $this->readableData($item)
                : $this->readableScalar($item, is_string($key) ? $key : null);
        }

        return $readable;
    }

    private function readableKey(int|string $key): int|string
    {
        if (is_int($key)) {
            return $key;
        }

        return Str::headline(str_replace(['_', '-'], ' ', $key));
    }

    private function readableScalar(mixed $value, ?string $key): mixed
    {
        if ($key && in_array($key, ['type', 'auditable_type'], true) && is_string($value)) {
            return class_basename($value);
        }

        return $value;
    }
}
