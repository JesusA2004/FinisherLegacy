<?php

test('the health endpoint is public and reports ok', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertOk();
    $response->assertJson(['status' => 'ok']);
    expect($response->json('api_version'))->not->toBeNull();
});
