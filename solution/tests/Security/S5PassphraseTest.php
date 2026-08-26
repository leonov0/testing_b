<?php

use Illuminate\Support\Facades\RateLimiter;

// S5 - the passphrase gate itself.

beforeEach(fn () => RateLimiter::clear('login:127.0.0.1'));

it('never echoes the submitted passphrase back to the browser', function () {
    $body = $this->followingRedirects()
        ->post('/login', ['passphrase' => 'sup3r-secret-guess'])
        ->getContent();

    expect($body)->not->toContain('sup3r-secret-guess');
});

it('never renders the configured passphrase in any page', function () {
    $pages = ['/login', '/verify'];

    foreach ($pages as $page) {
        expect($this->get($page)->getContent())->not->toContain(config('catalogue.admin_passphrase'));
    }
});

it('rate limits repeated wrong passphrases', function () {
    $accepted = 0;

    foreach (range(1, 8) as $attempt) {
        $this->post('/login', ['passphrase' => 'wrong-'.$attempt]);
        $accepted++;
    }

    // After the limit is reached the session must still not be authenticated.
    expect(session()->get('admin_authenticated'))->toBeNull()
        ->and(RateLimiter::tooManyAttempts('login:127.0.0.1', 5))->toBeTrue();
});

it('does not authenticate the session while the limiter is tripped', function () {
    foreach (range(1, 6) as $attempt) {
        $this->post('/login', ['passphrase' => 'wrong']);
    }

    $this->post('/login', ['passphrase' => 'admin']);

    expect(session()->get('admin_authenticated'))->toBeNull();
});

it('does not authenticate on an empty passphrase', function () {
    $this->post('/login', ['passphrase' => '']);

    expect(session()->get('admin_authenticated'))->toBeNull();
});

it('does not authenticate when the passphrase field is missing', function () {
    $this->post('/login', []);

    expect(session()->get('admin_authenticated'))->toBeNull();
});
