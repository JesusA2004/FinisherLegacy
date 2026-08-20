<?php

use App\Support\Athletes\AthleteIdentityNormalizer;

beforeEach(function () {
    $this->normalizer = new AthleteIdentityNormalizer;
});

test('name normalization strips accents, collapses whitespace, and lowercases', function () {
    expect($this->normalizer->name('  José   María  Ñúñez '))->toBe('jose maria nunez');
});

test('two accented and unaccented spellings of the same name normalize identically', function () {
    expect($this->normalizer->name('José Pérez'))->toBe($this->normalizer->name('Jose Perez'));
});

test('full name normalization joins first and last name', function () {
    expect($this->normalizer->fullName('Juan', 'Pérez'))->toBe('juan perez');
});

test('email normalization trims and lowercases but never strips gmail dots or plus tags', function () {
    expect($this->normalizer->email(' Juan.Perez+running@GMAIL.com '))
        ->toBe('juan.perez+running@gmail.com');
});

test('email normalization returns null for null or empty input', function () {
    expect($this->normalizer->email(null))->toBeNull()
        ->and($this->normalizer->email(''))->toBeNull();
});

test('phone normalization strips everything but digits and preserves a leading plus', function () {
    expect($this->normalizer->phone('+52 (55) 1234-5678'))->toBe('+525512345678')
        ->and($this->normalizer->phone('55 1234 5678'))->toBe('5512345678');
});

test('phone normalization never assumes a country code for a bare number', function () {
    expect($this->normalizer->phone('5512345678'))->toBe('5512345678');
});

test('phone normalization returns null for null or empty input', function () {
    expect($this->normalizer->phone(null))->toBeNull()
        ->and($this->normalizer->phone('   '))->toBeNull();
});
