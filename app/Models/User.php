<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'password', 'range', 'check_privacy', 'first_time', 'isActive',
        'instructor', 'service_chief', 'operator'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    public function groups(){
        return $this->belongsToMany(Group::class, 'group_user')->withTimestamps();
    }

    public function sessions(){
        return $this->hasMany(Session::class);
    }

    public function profile(){
        return $this->hasOne(Profile::class);
    }

    public function guards(){
        return $this->hasMany(Guard::class);
    }

    public function guard_user(){
        return $this->belongsToMany(Guard::class, 'guard_user')
            ->withPivot('role', 'isActive')
            ->withTimestamps();
    }

    public function certificates(){
        return $this->hasMany(Certificate::class)->select(["id","nombre_curso","tipo","fecha_ac","fecha_cad","duracion","user_id","estado_solicitud"]);//tal vez sea necesario crear una vista de detalles para ver las observaciones y fechas.
    }
}
