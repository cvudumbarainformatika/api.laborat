<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // pastikan kamu jalanin seeder di awal unit test
        $this->artisan('db:seed', ['--class' => 'TestMutasiSeeder']);
    }
}
