<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'partner_id',
        'amount',
        'type',
        'date',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted()
    {
        static::created(function ($transaction) {
            History::log('created', $transaction, null, $transaction->toArray(), "Transakcija kreirana: {$transaction->type} - {$transaction->amount} RSD");
        });

        static::updated(function ($transaction) {
            $changes = $transaction->getChanges();
            $original = $transaction->getOriginal();

            if (!empty($changes)) {
                $oldValues = array_intersect_key($original, $changes);
                $newValues = $changes;

                History::log('updated', $transaction, $oldValues, $newValues, "Transakcija izmenjena: {$transaction->type} - {$transaction->amount} RSD");
            }
        });

        static::deleted(function ($transaction) {
            History::log('deleted', $transaction, $transaction->toArray(), null, "Transakcija obrisana: {$transaction->type} - {$transaction->amount} RSD");
        });
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
