<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public function partner()
{
    return $this->belongsTo(Partner::class);
}
}
