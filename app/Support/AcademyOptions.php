<?php

namespace App\Support;

class AcademyOptions
{
    public static function classes(): array
    {
        return config('academy.classes');
    }

    public static function sections(): array
    {
        return config('academy.sections');
    }

    public static function absenceCategories(): array
    {
        return config('academy.absence_categories');
    }

    public static function paymentModes(): array
    {
        return config('academy.payment_modes');
    }

    public static function expenseCategories(): array
    {
        return config('academy.expense_categories');
    }

    public static function subjects(?string $section = null): array
    {
        $common = config('academy.subjects.common', []);
        $bySection = config('academy.subjects.by_section', []);

        if ($section === null) {
            return array_reduce(
                $bySection,
                fn ($carry, $items) => $carry + $items,
                $common
            );
        }

        $sectionSubjects = $bySection[$section] ?? [];

        return $common + $sectionSubjects;
    }

    public static function subjectsForSection(?string $section = null): array
    {
        return self::subjects($section);
    }

    public static function classLabel(string $key): string
    {
        return self::classes()[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function sectionLabel(string $key): string
    {
        return self::sections()[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function subjectLabel(string $key): string
    {
        return self::subjects()[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
