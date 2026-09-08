<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Actions;

use App\Enums\WorkspaceRole;
use App\Models\EmailAllowlist;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Support\NormalizedEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class RegisterUser
{
    public function __invoke(string $name, string $email, string $password): User
    {
        $email = NormalizedEmail::make($email);

        if (! EmailAllowlist::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail não está autorizado a criar uma conta.',
            ]);
        }

        return DB::transaction(function () use ($name, $email, $password): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_platform_admin' => false,
            ]);

            $workspace = Workspace::query()->create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'owner_user_id' => $user->id,
                'max_instances' => null,
            ]);

            WorkspaceMember::query()->create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceRole::Owner,
            ]);

            return $user;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'workspace';
        }

        $slug = $base;
        $suffix = 1;

        while (Workspace::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
