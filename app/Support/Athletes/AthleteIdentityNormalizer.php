<?php

namespace App\Support\Athletes;

use Illuminate\Support\Str;

/**
 * Pure normalization only — no matching, no DB access. See
 * docs/adr/0004-athlete-canonical-identity.md §Identity normalization.
 * Everything here is deterministic and side-effect-free so
 * App\Services\Athletes\AthleteIdentityMatcher can call it on both the
 * incoming candidate and (implicitly, via the stored `normalized_*`
 * columns) every existing Athlete, and always get the same answer.
 */
final class AthleteIdentityNormalizer
{
    /**
     * trim + collapse internal whitespace + lowercase + strip accents
     * (`Str::ascii()`, no ext-intl dependency). The real, accented name a
     * human typed is NEVER touched — only this derived column is folded,
     * specifically so "José" and "Jose" compare equal for matching
     * purposes without a second column (docs/adr/0004 §15).
     */
    public function name(string $value): string
    {
        $value = trim($value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);
        $value = Str::ascii($value);

        return mb_strtolower($value);
    }

    public function fullName(string $first, string $last): string
    {
        return $this->name(trim("{$first} {$last}"));
    }

    /**
     * trim + lowercase only. Deliberately does NOT strip Gmail dots or
     * "+tag" addressing — that's a real, different mailbox as far as this
     * system is concerned, not a normalization detail (docs/adr/0004 §13).
     */
    public function email(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value !== null && $value !== '' ? mb_strtolower($value) : null;
    }

    /**
     * Conservative: strips whitespace, dashes, parentheses and dots, keeps
     * a leading `+` and every digit. Never assumes a country code — a bare
     * 10-digit number stays a bare 10-digit number, it is not coerced into
     * a Mexican or any other national format (docs/adr/0004 §14).
     */
    public function phone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $hasLeadingPlus = trim($value)[0] === '+';
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            return null;
        }

        return $hasLeadingPlus ? "+{$digits}" : $digits;
    }
}
