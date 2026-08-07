<?php

test('how it works page loads', function () {
    $response = $this->get(route('how-it-works'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('HowItWorks'));
});

test('privacy page loads', function () {
    $response = $this->get(route('privacy'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Privacy'));
});

test('terms page loads', function () {
    $response = $this->get(route('terms'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Terms'));
});

test('contact page loads', function () {
    $response = $this->get(route('contact'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Contact'));
});
