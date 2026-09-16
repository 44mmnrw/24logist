<?php

namespace App\Observers;

use App\Models\ReferralAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class ReferralAuditObserver
{
    public function created(Model $model): void
    {
        $this->write('created', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->write('updated', $model, $model->getOriginal(), $model->getAttributes());
    }

    public function deleted(Model $model): void
    {
        $this->write('deleted', $model, $model->getOriginal(), null);
    }

    /** @param array<string,mixed>|null $before @param array<string,mixed>|null $after */
    private function write(string $action, Model $model, ?array $before, ?array $after): void
    {
        if (! Schema::hasTable('referral_audit_logs')) {
            return;
        }
        ReferralAuditLog::query()->create([
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $model::class,
            'target_id' => (string) $model->getKey(),
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'created_at' => now(),
        ]);
    }

    /** @param array<string,mixed>|null $values @return array<string,mixed>|null */
    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }
        foreach (['bank_details'] as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }
}
