<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;

readonly class ProvisionedTenant
{
    public function __construct(
        public Organization $organization,
        public User $user,
        public string $plainVerificationToken,
    ) {}
}
