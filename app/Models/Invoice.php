<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Partner;

class Invoice extends Model
{

const STATUS_UNPAID = 0;
const STATUS_PAID = 1;

protected $fillable = [
    'partner_id',
    'invoice_number',
    'date',
    'amount',
    'status',
    'note',
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
    'status' => 'integer',
    'date' => 'date',
];

}

    