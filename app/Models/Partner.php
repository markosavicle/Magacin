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

    /**
     * Get the transactions for the partner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Calculate the current balance for the partner.
     *
     * @return float
     */
    public function balance()
    {
        $out = $this->invoices()
            ->where('type', 'out')
            ->where('status', false)
            ->sum('total_amount');

        $in = $this->invoices()
            ->where('type', 'in')
            ->where('status', false)
            ->sum('total_amount');

        $payments = $this->transactions()
            ->where('type', 'payment')
            ->sum('amount');

        $credits = $this->transactions()
            ->where('type', 'credit')
            ->sum('amount');

        return $out - $in - $payments + $credits;
    }
}