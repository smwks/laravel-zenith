<?php

use SMWks\LaravelZenith\Http\Policies\ZenithPolicy;

it('returns false from manage when no auth middleware is configured', function () {
    config()->set('zenith.route.middleware', ['web']);

    $policy = new ZenithPolicy;

    expect($policy->manage(null))->toBeFalse();
});

it('returns true from manage when auth middleware is configured', function () {
    config()->set('zenith.route.middleware', ['web', 'auth']);

    $policy = new ZenithPolicy;

    expect($policy->manage(null))->toBeTrue();
});

it('returns true from manage when a namespaced auth middleware is configured', function () {
    config()->set('zenith.route.middleware', ['web', 'auth:sanctum']);

    $policy = new ZenithPolicy;

    expect($policy->manage(null))->toBeTrue();
});
