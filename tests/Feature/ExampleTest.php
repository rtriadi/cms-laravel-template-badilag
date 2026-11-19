<?php

use App\Models\SatkerSetting;

test('the application returns a successful response after setup', function (): void {
    SatkerSetting::factory()->setupCompleted()->create();

    $response = $this->get('/');

    $response->assertStatus(200);
});
