<?php

namespace App\Modules\Academico\Domain;

use Illuminate\Support\Str;

class GradeCatalog
{
    public static function all(): array
    {
        return [
            ['code' => '1-secundaria', 'name' => 'Primero de secundaria', 'level' => 'secundaria', 'order' => 1],
            ['code' => '2-secundaria', 'name' => 'Segundo de secundaria', 'level' => 'secundaria', 'order' => 2],
            ['code' => '3-secundaria', 'name' => 'Tercero de secundaria', 'level' => 'secundaria', 'order' => 3],
            ['code' => '4-secundaria', 'name' => 'Cuarto de secundaria', 'level' => 'secundaria', 'order' => 4],
            ['code' => '5-secundaria', 'name' => 'Quinto de secundaria', 'level' => 'secundaria', 'order' => 5],
        ];
    }

    public static function findByCodeOrName(string $value): ?array
    {
        $normalized = self::normalize($value);

        foreach (self::all() as $grade) {
            if ($grade['code'] === $normalized || self::normalize($grade['name']) === $normalized) {
                return $grade;
            }
        }

        return null;
    }

    public static function normalize(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replaceMatches('/\s+/', ' ')->trim()->replace(' ', '-')->toString();
    }
}
