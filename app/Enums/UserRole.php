<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Parent = 'parent';
    case Student = 'student';
    case Evaluator = 'evaluator';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Parent => 'Parent',
            self::Student => 'Student',
            self::Evaluator => 'Evaluator',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
