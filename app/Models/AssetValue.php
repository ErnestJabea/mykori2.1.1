<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetValue extends Model
{
    protected $fillable = ['product_id', 'vl', 'date_vl'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
