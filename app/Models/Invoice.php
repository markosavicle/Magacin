<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Partner;

class Invoice extends Model
{

protected $fillable = [
    'partner_id',
    'invoice_number',
    'date',
    'amount',
    'note',
    'type',
]; 

public function partner()
{
    return $this->belongsTo(Partner::class);
}

public function items()
{
    return $this->hasMany(InvoiceItem::class);
}

protected $casts = [
    'date' => 'date',
];

protected static function booted()
{
    static::created(function ($invoice) {
        History::log('created', $invoice, null, $invoice->toArray(), "Faktura kreirana: {$invoice->invoice_number}");
    });

    static::updated(function ($invoice) {
        $changes = $invoice->getChanges();
        $original = $invoice->getOriginal();

        if (!empty($changes)) {
            $oldValues = array_intersect_key($original, $changes);
            $newValues = $changes;

            History::log('updated', $invoice, $oldValues, $newValues, "Faktura izmenjena: {$invoice->invoice_number}");
        }
    });

    static::deleted(function ($invoice) {
        History::log('deleted', $invoice, $invoice->toArray(), null, "Faktura obrisana: {$invoice->invoice_number}");
    });
}

}

    