<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // ContentRepository caches under fixed keys and the test `array` cache
        // persists for the whole process, so clear it before each test to keep
        // content/fixture data from leaking between tests.
        Cache::flush();
    }
}
