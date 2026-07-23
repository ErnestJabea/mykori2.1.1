<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class StatementAuditLog extends Model
{
    use HasFactory;

    protected $table = 'statement_audit_logs';

    protected $fillable = [
        'event_type',
        'statement_version_id',
        'correction_id',
        'user_id',
        'product_id',
        'target_entity',
        'target_id',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'comment',
        'status_before',
        'status_after',
        'version_number',
        'operator_id',
        'controller_id',
        'requested_at',
        'action_at',
        'ip_address',
        'user_agent',
        'attachment_path',
        'technical_context',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'action_at' => 'datetime',
        'technical_context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function controller()
    {
        return $this->belongsTo(User::class, 'controller_id');
    }

    public function version()
    {
        return $this->belongsTo(StatementVersion::class, 'statement_version_id');
    }

    public function correction()
    {
        return $this->belongsTo(StatementCorrection::class, 'correction_id');
    }

    /**
     * Enforce append-only policy (prevent updates and deletes)
     */
    public static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            throw new Exception("L'historique d'audit est append-only et ne peut pas être modifié.");
        });

        static::deleting(function ($model) {
            throw new Exception("L'historique d'audit est append-only et ne peut pas être supprimé.");
        });
    }

    /**
     * Helper static method to quickly record an audit log entry
     */
    public static function logEvent(array $data)
    {
        return self::create(array_merge([
            'operator_id' => auth()->id() ?? ($data['operator_id'] ?? 1),
            'action_at' => now(),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
        ], $data));
    }
}
