<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatementBatch extends Model
{
    protected $fillable = [
        'user_id', 'periode', 'client_count', 'success_count', 'error_count', 'report_path'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
