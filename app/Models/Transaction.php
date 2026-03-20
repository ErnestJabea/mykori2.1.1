<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $dispatchesEvents = [
        'updated' => \App\Events\UserBalanceUpdated::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relation avec le client
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected $fillable = [
        'title', 'ref', 'payment_mode', 'amount', 'status', 'user_id', 
        'product_id', 'vl_buy', 'nb_part', 'date_validation', 
        'montant_initiale', 'type', 'duree', 'date_echeance'
    ];

    public function sousTransactions()
    {
        return $this->hasMany(TransactionSupplementaire::class);
    }
}
