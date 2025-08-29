<?php

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\TestCase;

uses(
    TestCase::class,
    RefreshDatabaseState::class,
)->in('Integration');