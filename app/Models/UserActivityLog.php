<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'metadata',
        'ip_address'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $target = null, $description = null, $metadata = [])
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target ? $target->id : null,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip()
        ]);
    }
}
