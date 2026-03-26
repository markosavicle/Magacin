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
        //out = fakture koje mi dajemo partneru, tj. koje partner duguje nama
        $out = ($this->invoices()
            ->where('type', 'out')
            ->where('status', 0)
            ->sum('amount')) ?? 0;

        // in = fakture koje partner daje nama, tj. koje mi dugujemo partneru
        $in = ($this->invoices()
            ->where('type', 'in')
            ->where('status', 0)
            ->sum('amount')) ?? 0;

        // payments = uplate koje je partner izvršio
        $payments = ($this->transactions()
            ->where('type', 'payment')
            ->sum('amount')) ?? 0;

        // credits = krediti koje smo dali partneru
        $credits = ($this->transactions()
            ->where('type', 'credit')
            ->sum('amount')) ?? 0;

        return $out + $credits - $in - $payments;
    }
}