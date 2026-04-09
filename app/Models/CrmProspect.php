<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CrmProspect extends Model
{
    protected $table = 'crm_prospects';

    protected $fillable = [
        'commercial_id',
        'full_name',
        'email',
        'phone',
        'amount_expected',
        'status',
        'notes',
    ];

    /**
     * Le commercial en charge du prospect.
     */
    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }
}
