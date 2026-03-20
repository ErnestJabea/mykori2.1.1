<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Transaction;

class FinancialMovement extends Model
{
    use HasFactory;

    protected $table = 'financial_movements';

    protected $fillable = [
        'transaction_id',
        'type',
        'amount',
        'capital_before',
        'capital_after',
        'date_operation',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'capital_before' => 'decimal:2',
        'capital_after' => 'decimal:2',
        'date_operation' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with Transaction
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    /**
     * Get movements of type 'souscription'
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('type', 'souscription');
    }

    /**
     * Get movements of type 'rachat_partiel' or 'rachat_total'
     */
    public function scopeRedemptions($query)
    {
        return $query->whereIn('type', ['rachat_partiel', 'rachat_total']);
    }

    /**
     * Get movements of type 'versement_libre'
     */
    public function scopeAdditionalPayments($query)
    {
        return $query->where('type', 'versement_libre');
    }

    /**
     * Get movements of type 'capitalisation_interets'
     */
    public function scopeInterestCapitalizations($query)
    {
        return $query->where('type', 'capitalisation_interets');
    }

    /**
     * Get movements of type 'frais_gestion'
     */
    public function scopeManagementFees($query)
    {
        return $query->where('type', 'frais_gestion');
    }
}
