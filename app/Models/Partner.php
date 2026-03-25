<?php

namespace App\Models;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [  
        'name',
        'type',
        'phone',
        'note',
        'email',
        'address',
        'city',
        'vat_number',
        'contact_person',
    ];

    /**
     * Get the invoices for the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}