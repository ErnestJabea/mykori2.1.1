<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatementCorrection extends Model
{
    use HasFactory;

    protected $table = 'statement_corrections';

    protected $fillable = [
        'statement_version_id',
        'user_id',
        'product_id',
        'correction_type',
        'target_entity',
        'target_id',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'description',
        'attachment_path',
        'simulation_payload',
        'status',
        'operator_id',
        'controller_id',
        'validated_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'simulation_payload' => 'array',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function version()
    {
        return $this->belongsTo(StatementVersion::class, 'statement_version_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function controller()
    {
        return $this->belongsTo(User::class, 'controller_id');
    }
}
