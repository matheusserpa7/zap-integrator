<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\NormalizedEmail;
use Database\Factories\EmailAllowlistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['email', 'created_by_user_id'])]
class EmailAllowlist extends Model
{
    /** @use HasFactory<EmailAllowlistFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'email';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => NormalizedEmail::make($value),
        );
    }
}
