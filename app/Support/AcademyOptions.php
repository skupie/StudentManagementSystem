<?php

namespace App\Support;

use App\Models\ClassOption;
use App\Models\SectionOption;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AcademyOptions
{
    public static function classes(): array
    {
        return Cache::remember('academy.classes', 300, function () {
            if (Schema::hasTable('class_options')) {
                $db = ClassOption::where('is_active', true)
                    ->orderBy('label')
                    ->get()
                    ->pluck('label', 'key')
                    ->toArray();
                if (! empty($db)) {
                    return $db;
                }
            }

            return config('academy.classes');
        });
    }

    public static function sections(): array
    {
        return Cache::remember('academy.sections', 300, function () {
            if (Schema::hasTable('section_options')) {
                $db = SectionOption::where('is_active', true)
                    ->orderBy('label')
                    ->get()
                    ->pluck('label', 'key')
                    ->toArray();
                if (! empty($db)) {
                    return $db;
                }
            }

            return config('academy.sections');
        });
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

    public static function normalizeSubjectKey(?string $subject): ?string
    {
        $raw = strtolower(trim((string) $subject));
        if ($raw === '') {
            return null;
        }

        $subjects = self::subjects();
        if (array_key_exists($raw, $subjects)) {
            return $raw;
        }

        $aliases = array_change_key_case((array) config('academy.subject_aliases', []), CASE_LOWER);
        if (isset($aliases[$raw])) {
            return (string) $aliases[$raw];
        }

        $token = self::normalizeSubjectToken($raw);
        if ($token === '') {
            return null;
        }

        if (isset($aliases[$token])) {
            return (string) $aliases[$token];
        }

        // Support legacy/free-text variants that mention core subjects.
        if (self::tokenHasWord($token, 'bangla')) {
            return 'bangla_1st';
        }
        if (self::tokenHasWord($token, 'english')) {
            return 'english_1st';
        }
        if (
            self::tokenHasWord($token, 'ict')
            || (
                self::tokenHasWord($token, 'information')
                && self::tokenHasWord($token, 'communication')
                && self::tokenHasWord($token, 'technology')
            )
        ) {
            return 'ict';
        }

        foreach ($subjects as $key => $label) {
            if ($token === self::normalizeSubjectToken((string) $key)) {
                return (string) $key;
            }
            if ($token === self::normalizeSubjectToken((string) $label)) {
                return (string) $key;
            }
        }

        return null;
    }

    public static function isGlobalSubject(?string $subject): bool
    {
        $key = self::normalizeSubjectKey($subject);
        if (! $key) {
            return false;
        }

        return $key === 'ict'
            || str_starts_with($key, 'bangla_')
            || str_starts_with($key, 'english_');
    }

    public static function sectionsForSubject(?string $subject): array
    {
        $key = self::normalizeSubjectKey($subject);
        if (! $key) {
            return [];
        }

        if (self::isGlobalSubject($key)) {
            return array_keys(self::sections());
        }

        $bySection = (array) config('academy.subjects.by_section', []);
        $resolved = [];

        foreach ($bySection as $sectionKey => $subjectMap) {
            $sectionSubjects = array_keys((array) $subjectMap);
            foreach ($sectionSubjects as $sectionSubject) {
                if ($key === self::normalizeSubjectKey((string) $sectionSubject)) {
                    $resolved[] = (string) $sectionKey;
                    break;
                }
            }
        }

        return array_values(array_unique($resolved));
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
        $normalized = self::normalizeSubjectKey($key);
        if ($normalized && isset(self::subjects()[$normalized])) {
            return self::subjects()[$normalized];
        }

        return self::subjects()[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    protected static function normalizeSubjectToken(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['&'], ' and ', $value);
        $value = str_replace(['(', ')', ',', '.'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;

        return trim($value, '_');
    }

    protected static function tokenHasWord(string $token, string $word): bool
    {
        return preg_match('/(?:^|_)' . preg_quote($word, '/') . '(?:$|_)/', $token) === 1;
    }
}
