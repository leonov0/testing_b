<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Security');

/*
 * Add your own helpers here. One that most suites end up needing:
 *
 * function asAdmin(): Tests\TestCase
 * {
 *     return test()->withSession(['admin_authenticated' => true]);
 * }
 */
