<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'cellphone',
        'cpf',
        'role',
        'email',
        'password',
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
        'password' => 'hashed',
    ];


    public static function createUser($data)
    {
        $data['password'] = hash::make($data['password']);
        return self::create($data);
    }
    public static function getOneUser($id)
    {
        return self::where('id', $id)->first();
    }
    public static function allUsers()
    {
        return self::get();
    }

    public static function allUsersFiltro($query = null) {
        $queryBuilder = self::query();

        if ($query) {
            $queryBuilder->where(function($q) use ($query) {
                $q->where('email', 'like', '%' . $query . '%')
                  ->orWhere('cellphone', 'like', '%' . $query . '%');
            });
        }

        $result = $queryBuilder->get();

        return $result ?? false;
    }

}
