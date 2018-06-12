<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransApplied extends Model
{
    protected $fillable = [
        'trans_id',
        'consumer',
    ];

}
