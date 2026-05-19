<?php

namespace App\Services;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ResellerAccountService
{
    public const ROLE_RESELLER = 'reseller';
    public const ROLE_DIRECT_RESELLER = 'direct reseller';

    /**
     * @return array{email: string, password: string, login_url: string, role: string, headline: string, message: string}
     */
    public function createAccount(Reseller $reseller): array
    {
        $plainPassword = Str::password(14, letters: true, numbers: true, symbols: false, spaces: false);
        $user = User::create([
            'name' => $reseller->name,
            'email' => $this->normalizeEmail($reseller->email),
            'phone' => $reseller->mobile,
            'password' => $plainPassword,
            'email_verified_at' => now(),
        ]);

        $this->syncRole($user, $reseller);
        $reseller->forceFill(['user_id' => $user->id])->save();

        return $this->loginDetails($reseller, $plainPassword);
    }

    public function syncAccount(Reseller $reseller): ?array
    {
        $reseller->loadMissing('userAccount');

        if (! $reseller->userAccount) {
            return $this->createAccount($reseller);
        }

        $reseller->userAccount->forceFill([
            'name' => $reseller->name,
            'email' => $this->normalizeEmail($reseller->email),
            'phone' => $reseller->mobile,
            'email_verified_at' => $reseller->userAccount->email_verified_at ?? now(),
        ])->save();

        $this->syncRole($reseller->userAccount, $reseller);

        return null;
    }

    public function syncSeedAccount(Reseller $reseller, string $plainPassword = 'password'): void
    {
        $reseller->loadMissing('userAccount');
        $user = $reseller->userAccount ?: User::query()
            ->where('email', $this->normalizeEmail($reseller->email))
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $reseller->name,
                'email' => $this->normalizeEmail($reseller->email),
                'phone' => $reseller->mobile,
                'password' => $plainPassword,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'name' => $reseller->name,
                'phone' => $reseller->mobile,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        $this->syncRole($user, $reseller);
        $reseller->forceFill(['user_id' => $user->id])->save();
    }

    public function retireAccount(Reseller $reseller): void
    {
        $reseller->loadMissing('userAccount');

        if (! $reseller->userAccount) {
            return;
        }

        $user = $reseller->userAccount;
        if ($user->hasRole($this->roleFor($reseller))) {
            $user->removeRole($this->roleFor($reseller));
        }

        if ($user->roles()->count() === 0) {
            $user->delete();
        }
    }

    /**
     * @return array{email: string, password: string, login_url: string, role: string, headline: string, message: string}
     */
    public function resetPassword(Reseller $reseller): array
    {
        $reseller->loadMissing('userAccount');

        if (! $reseller->userAccount) {
            return $this->createAccount($reseller);
        }

        $plainPassword = Str::password(14, letters: true, numbers: true, symbols: false, spaces: false);

        $reseller->userAccount->forceFill([
            'name' => $reseller->name,
            'email' => $this->normalizeEmail($reseller->email),
            'phone' => $reseller->mobile,
            'password' => $plainPassword,
            'email_verified_at' => $reseller->userAccount->email_verified_at ?? now(),
        ])->save();

        $this->syncRole($reseller->userAccount, $reseller);

        return $this->loginDetails(
            $reseller,
            $plainPassword,
            'Login password reset',
            'Copy and share this new password now. The old password no longer works.'
        );
    }

    public function roleFor(Reseller $reseller): string
    {
        return $reseller->reseller_type === Reseller::TYPE_DIRECT_RESELLER
            ? self::ROLE_DIRECT_RESELLER
            : self::ROLE_RESELLER;
    }

    private function syncRole(User $user, Reseller $reseller): void
    {
        $dashboardPermission = Permission::firstOrCreate([
            'name' => 'view dashboard',
            'guard_name' => 'web',
        ]);

        $resellerRole = Role::firstOrCreate([
            'name' => self::ROLE_RESELLER,
            'guard_name' => 'web',
        ]);
        $directResellerRole = Role::firstOrCreate([
            'name' => self::ROLE_DIRECT_RESELLER,
            'guard_name' => 'web',
        ]);
        $resellerRole->givePermissionTo($dashboardPermission);
        $directResellerRole->givePermissionTo($dashboardPermission);

        $otherRole = $this->roleFor($reseller) === self::ROLE_RESELLER
            ? self::ROLE_DIRECT_RESELLER
            : self::ROLE_RESELLER;

        $user->removeRole($otherRole);
        $user->assignRole($this->roleFor($reseller));
    }

    /**
     * @return array{email: string, password: string, login_url: string, role: string, headline: string, message: string}
     */
    private function loginDetails(
        Reseller $reseller,
        string $plainPassword,
        string $headline = 'Login account created',
        string $message = 'Share these details with the reseller. The password is shown only once.'
    ): array
    {
        return [
            'email' => $this->normalizeEmail($reseller->email),
            'password' => $plainPassword,
            'login_url' => route('login'),
            'role' => $this->roleFor($reseller),
            'headline' => $headline,
            'message' => $message,
        ];
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
