<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class History extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'model_type',
        'model_id',
        'action',
        'user_id',
        'old_values',
        'new_values',
        'ip_address',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    public static function log(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): void
    {
        static::create([
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'action' => $action,
            'user_id' => auth()->id(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'description' => $description,
        ]);
    }
}
