<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

trait HasPublicId
{
    abstract protected static function publicIdPrefix(): string;

    public static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('public_id'))) {
                $model->setAttribute('public_id', PublicId::make(static::publicIdPrefix()));
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
