<?php

it('schedules automated backup and activity log cleanup', function (): void {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('backup:run')
        ->expectsOutputToContain('backup:clean')
        ->expectsOutputToContain('activitylog:clean')
        ->assertSuccessful();
});
