<?php

namespace larasaas\DistributedTransaction\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

    public $table = 'transaction';
    protected $fillable = [
        'producer',
        'consumer',
        'tenant_id',
        'user_id',
        'message'
    ];
    
}
