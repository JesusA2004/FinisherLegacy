<?php

namespace App\Enums;

/**
 * Deterministic match signals, strongest first — see
 * App\Services\Athletes\AthleteIdentityMatcher and
 * docs/adr/0004-athlete-canonical-identity.md §Confidence. No ML, no fuzzy
 * scoring beyond this fixed table — every score is explainable by exactly
 * one of these reasons.
 */
enum AthleteMatchReason: string
{
    case ExternalIdentityExact = 'external_identity_exact';
    case VerifiedEmailAndBirthdate = 'verified_email_and_birthdate';
    case EmailExact = 'email_exact';
    case PhoneAndBirthdate = 'phone_and_birthdate';
    case NameAndBirthdate = 'name_and_birthdate';
    case NameOnly = 'name_only';

    /**
     * The confidence table — see docs/adr/0004 §Confidence for the
     * reasoning behind each number and the auto-link/conflict/no-match
     * thresholds built on top of it
     * (App\Services\Athletes\AthleteIdentityMatcher::AUTO_LINK_THRESHOLD /
     * CONFLICT_THRESHOLD).
     */
    public function confidence(): int
    {
        return match ($this) {
            self::ExternalIdentityExact => 100,
            self::VerifiedEmailAndBirthdate => 100,
            self::EmailExact => 95,
            self::PhoneAndBirthdate => 95,
            self::NameAndBirthdate => 80,
            self::NameOnly => 30,
        };
    }
}
