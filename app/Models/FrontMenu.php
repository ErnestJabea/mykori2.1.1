<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'route',
        'icon',
        'section',
        'roles_json', // JSON array of role IDs
        'order',
        'is_active',
        'permission'
    ];

    protected $casts = [
        'roles_json' => 'array',
        'is_active' => 'boolean',
    ];
}
