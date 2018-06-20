<?php

namespace Larasaas\DistributedTransaction\Models;

use Illuminate\Database\Eloquent\Model;

class TransApplied extends Model
{

    public $table = 'trans_applied';
    protected $fillable = [
        'tenant_id',
        'trans_id',
        'consumer',
        'producer'
    ];

}
