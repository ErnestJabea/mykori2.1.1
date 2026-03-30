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
        'transaction_id',
        'is_compliance_validated',
        'is_backoffice_validated',
        'is_dg_validated',
        'compliance_validated_at',
        'backoffice_validated_at',
        'dg_validated_at',
        'date_validation'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function checkValidationStatus()
    {
        if ($this->is_compliance_validated && $this->is_backoffice_validated) {
            $this->status = 'Succès';
            $this->save();
            return true;
        }
        return false;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
