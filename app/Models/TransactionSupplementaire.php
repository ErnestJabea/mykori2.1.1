<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionSupplementaire extends Model
{
    use HasFactory;

    protected $dispatchesEvents = [
        'updated' => \App\Events\UserBalanceTransactionSupplemataireUpdated::class,
    ];

    protected $fillable = [
        'title',
        'ref',
        'payment_mode',
        'amount',
        'status',
        'user_id',
        'vl_buy',
        'nb_part',
        'product_id',
        'transaction_id'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
