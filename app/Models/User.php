<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Transaction;
use App\Models\TransactionSupplementaire;
use App\Models\CrmProspect;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'localisation',
        'bp',
        'genre',
        'commercial_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }
    public function transactionssupplementaires()
    {
        return $this->hasMany(TransactionSupplementaire::class, 'user_id');
    }

    public function clients()
    {
        return $this->hasMany(User::class, 'commercial_id');
    }

    public function leads()
    {
        return $this->hasMany(CrmProspect::class, 'commercial_id');
    }

    public function portfolios()
    {
        return $this->hasMany(CustomerPortfolio::class, 'user_id');
    }
}
