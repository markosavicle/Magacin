<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

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
}