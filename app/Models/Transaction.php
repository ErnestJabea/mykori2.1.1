<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $casts = [
        'date_validation' => 'date',
    ];

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
        'title', 'ref', 'payment_mode', 'amount', 'fees', 'status', 'user_id', 
        'product_id', 'vl_buy', 'nb_part', 'date_validation', 
        'montant_initiale', 'type', 'duree', 'date_echeance',
        'is_compliance_validated', 'is_backoffice_validated', 'is_dg_validated',
        'compliance_validated_at', 'backoffice_validated_at', 'dg_validated_at'
    ];

    public function checkValidationStatus()
    {
        // Seule la Compliance est obligatoire pour activer la transaction
        if ($this->is_compliance_validated) {
            $this->status = 'Succès';
            $this->save();
            return true;
        }
        return false;
    }

    public function sousTransactions()
    {
        return $this->hasMany(TransactionSupplementaire::class);
    }
}
