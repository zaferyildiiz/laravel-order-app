<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    public  $timestamps = false;
    protected $hidden = ['item_ids'];

    public function items()
    {
        return $this->hasMany(Item::class,'order_id','order_id');
    }
}
