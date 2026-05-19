<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class AuditModelObserver
{
    public function created(Model $model): void
    {
        $this->auditLog()->recordModelEvent($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->auditLog()->recordModelEvent($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->auditLog()->recordModelEvent($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->auditLog()->recordModelEvent($model, 'restored');
    }

    public function forceDeleted(Model $model): void
    {
        $this->auditLog()->recordModelEvent($model, 'force_deleted');
    }

    private function auditLog(): AuditLogService
    {
        return app(AuditLogService::class);
    }
}
