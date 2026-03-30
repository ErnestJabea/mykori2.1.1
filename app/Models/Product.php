<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'product_id');
    }

    public function transactionssupplementaires()
    {
        return $this->hasMany(TransactionSupplementaire::class, 'product_id');
    }

    public function scopeTypeTwo($query)
    {
        return $query->where('products_category_id', 1);
    }
}
