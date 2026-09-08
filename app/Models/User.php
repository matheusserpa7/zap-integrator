<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkspaceRole;
use App\Support\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['public_id', 'name', 'email', 'password', 'is_platform_admin', 'disabled_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /**
     * @use HasApiTokens<PersonalAccessToken>
     * @use HasFactory<UserFactory>
     */
    use HasApiTokens, HasFactory, HasPublicId, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'disabled_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'usr_';
    }

    /**
     * @return HasOne<Workspace, $this>
     */
    public function ownedWorkspace(): HasOne
    {
        return $this->hasOne(Workspace::class, 'owner_user_id');
    }

    /**
     * @return HasMany<WorkspaceMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /**
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function currentWorkspace(): ?Workspace
    {
        return $this->ownedWorkspace ?? $this->workspaces()->first();
    }

    public function isMemberOf(Workspace $workspace): bool
    {
        return $this->memberships()->where('workspace_id', $workspace->id)->exists();
    }

    public function isOwnerOf(Workspace $workspace): bool
    {
        return $this->memberships()
            ->where('workspace_id', $workspace->id)
            ->where('role', WorkspaceRole::Owner)
            ->exists();
    }

    public function hasWorkspace(): bool
    {
        return $this->ownedWorkspace()->exists() || $this->memberships()->exists();
    }

    public function homePath(): string
    {
        if ($this->is_platform_admin && ! $this->hasWorkspace()) {
            return route('platform.allowlist.index');
        }

        return route('dashboard');
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? 'U', 0, 1);
        $last = count($parts) > 1 ? mb_substr((string) array_pop($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}
