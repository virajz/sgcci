<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exhibition;
use Illuminate\Support\Facades\Session;

class CurrentExhibition
{
    private const SESSION_KEY = 'current_exhibition_id';

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return $id === null ? null : (int) $id;
    }

    public static function model(): ?Exhibition
    {
        $id = self::id();

        if (! $id) {
            return null;
        }

        $exhibition = Exhibition::find($id);

        if (! $exhibition) {
            self::clear();

            return null;
        }

        return $exhibition;
    }

    public static function set(int $id): void
    {
        Session::put(self::SESSION_KEY, $id);
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public static function isSelected(): bool
    {
        return self::model() !== null;
    }
}
