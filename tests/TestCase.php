<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        // Docker injects MySQL variables at process level. Override them before
        // Laravel boots so RefreshDatabase can never touch development data.
        foreach (['APP_ENV' => 'testing', 'APP_LOCALE' => 'pt_BR', 'APP_FALLBACK_LOCALE' => 'pt_BR', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        putenv('DB_URL');
        unset($_ENV['DB_URL'], $_SERVER['DB_URL']);

        return parent::createApplication();
    }
}
