<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

const PRIVATE_KEY_PATH = __DIR__ . '/fixtures/auth/private.pem';

uses(
    TestCase::class,
    RefreshDatabase::class
)->in('Feature');