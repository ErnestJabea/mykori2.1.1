<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPortfolio extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'reference',
        'status',
    ];

    /**
     * Get the user that owns the portfolio.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
