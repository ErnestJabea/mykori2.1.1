<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatementVersion extends Model
{
    use HasFactory;

    protected $table = 'statement_versions';

    protected $fillable = [
        'user_id',
        'product_id',
        'period_name',
        'statement_date',
        'version_number',
        'status',
        'pdf_path',
        'sha256_hash',
        'payload_sha256_hash',
        'summary_payload',
        'sent_at',
        'created_by',
        'validated_by',
        'replaces_version_id',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'sent_at' => 'datetime',
        'summary_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function replacesVersion()
    {
        return $this->belongsTo(StatementVersion::class, 'replaces_version_id');
    }

    public function corrections()
    {
        return $this->hasMany(StatementCorrection::class, 'statement_version_id');
    }

    public function isImmutable(): bool
    {
        return in_array($this->status, ['Envoye', 'Verrouille', 'Valide']);
    }
}
