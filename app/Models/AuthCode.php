<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthCode extends Model
{
    use HasFactory;
    protected $table = 'auth_code'; // Nom de la table

    protected $fillable = ['user_id', 'verification_code_expires_at', 'status', 'verification_code']; // Colonnes pouvant être remplies

    public static function createAuthCode($userId, $code)
    {
        return self::create([
            'user_id' => $userId,
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(5),
            'status' => 0  // Code indiquant que le code est valide et en attente d’utilisation
        ]);
    }
}
