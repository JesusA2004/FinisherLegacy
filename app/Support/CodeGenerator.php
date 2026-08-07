<?php

namespace App\Support;

use RuntimeException;

class CodeGenerator
{
    /**
     * Characters chosen to avoid visual ambiguity (no 0/O, 1/I/L).
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function generate(string $prefix, int $length = 7): string
    {
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $prefix === '' ? $code : "{$prefix}-{$code}";
    }

    /**
     * Generate a code, retrying until $exists() reports it is free.
     *
     * @param  callable(string): bool  $exists
     */
    public static function unique(string $prefix, callable $exists, int $length = 7, int $maxAttempts = 20): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = self::generate($prefix, $length);

            if (! $exists($code)) {
                return $code;
            }
        }

        throw new RuntimeException("Unable to generate a unique code with prefix [{$prefix}] after {$maxAttempts} attempts.");
    }
}
