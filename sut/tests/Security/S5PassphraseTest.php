<?php

// S5 - the passphrase is never printed back, and too many wrong tries get rate limited.

use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('login:127.0.0.1');
    config(['catalogue.admin_passphrase' => 'zzq-configured-passphrase']);
});

it('never echoes a wrong passphrase back to the visitor', function () {
    $body = $this->from('/login')
        ->followingRedirects()
        ->post('/login', ['passphrase' => 'wrong-passphrase-echo-probe'])
        ->getContent();

    expect($body)->not->toContain('wrong-passphrase-echo-probe');
});

it('never echoes the submitted passphrase in the login form after a failure', function () {
    $this->from('/login')->post('/login', ['passphrase' => 'wrong-passphrase-echo-probe']);

    expect($this->get('/login')->getContent())->not->toContain('wrong-passphrase-echo-probe');
});

it('never prints the configured passphrase on the login page', function () {
    expect($this->get('/login')->getContent())->not->toContain('zzq-configured-passphrase');
});

it('never prints the configured passphrase after a failed attempt', function () {
    $body = $this->from('/login')
        ->followingRedirects()
        ->post('/login', ['passphrase' => 'wrong'])
        ->getContent();

    expect($body)->not->toContain('zzq-configured-passphrase');
});

it('never prints the configured passphrase in the management area', function () {
    $body = asAdmin()->get('/products')->getContent();

    expect($body)->not->toContain('zzq-configured-passphrase');
});

it('counts failed passphrase attempts against a rate limiter', function () {
    foreach (range(1, 3) as $attempt) {
        $this->from('/login')->post('/login', ['passphrase' => 'wrong-passphrase']);
    }

    expect(RateLimiter::attempts('login:127.0.0.1'))->toBeGreaterThan(0);
});

it('does not authenticate the correct passphrase once the limiter is tripped', function () {
    foreach (range(1, 10) as $attempt) {
        $this->from('/login')->post('/login', ['passphrase' => 'wrong-passphrase']);
    }

    $this->from('/login')->post('/login', ['passphrase' => 'zzq-configured-passphrase'])
        ->assertSessionMissing('admin_authenticated');
});

it('keeps the management area closed after a tripped limiter and a correct passphrase', function () {
    foreach (range(1, 10) as $attempt) {
        $this->from('/login')->post('/login', ['passphrase' => 'wrong-passphrase']);
    }
    $this->from('/login')->post('/login', ['passphrase' => 'zzq-configured-passphrase']);

    $this->get('/products')->assertStatus(401);
});

it('authenticates the correct passphrase while the limiter is clear', function () {
    $this->post('/login', ['passphrase' => 'zzq-configured-passphrase'])
        ->assertSessionHas('admin_authenticated', true);
});

it('does not accept a passphrase that only shares a prefix with the configured one', function () {
    $this->from('/login')->post('/login', ['passphrase' => 'zzq-configured'])
        ->assertSessionMissing('admin_authenticated');
});

it('does not accept a passphrase differing only in case', function () {
    $this->from('/login')->post('/login', ['passphrase' => 'ZZQ-CONFIGURED-PASSPHRASE'])
        ->assertSessionMissing('admin_authenticated');
});
