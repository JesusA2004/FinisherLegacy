<?php

/**
 * The branded Inertia error page (bootstrap/app.php `$exceptions->respond`)
 * is deliberately skipped in local/testing so Laravel's own debug page shows
 * during development — so this test forces the app into "production" env to
 * actually exercise the branded path Pest runs in by default.
 */
test('a 404 in a non-local environment renders the branded Inertia error page, not a raw Laravel error page', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/this-route-does-not-exist-xyz');

    $response->assertStatus(404);
    $response->assertInertia(fn ($page) => $page
        ->component('errors/Error')
        ->where('status', 404)
    );
});
