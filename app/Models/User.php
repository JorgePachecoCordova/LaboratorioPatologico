<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'mobile',
        'address',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function createdPathologyRequests(): HasMany
    {
        return $this->hasMany(PathologyRequest::class, 'created_by');
    }

    public function updatedPathologyRequests(): HasMany
    {
        return $this->hasMany(PathologyRequest::class, 'updated_by');
    }

    public function recordedPathologyResults(): HasMany
    {
        return $this->hasMany(PathologyResult::class, 'recorded_by');
    }

    public function validatedPathologyResults(): HasMany
    {
        return $this->hasMany(PathologyResult::class, 'validated_by');
    }
}
