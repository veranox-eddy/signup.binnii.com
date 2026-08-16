<?php

// Schema owner is the app.binnii.com repo. Do not add migrations here.

namespace App\Models;

use App\Enums\AccessLevel;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

/**
 * Signup-only view of the console's users table: rows are created via
 * forceCreate() in TenantProvisioner and never authenticated here — login
 * lives on app.binnii.com.
 */
class User extends Model
{
    use HasRoles, SoftDeletes;

    protected $guard_name = 'web';

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'type' => UserType::class,
            'access_level' => AccessLevel::class,
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
