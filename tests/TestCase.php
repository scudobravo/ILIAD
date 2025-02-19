<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\DatabaseMigrations;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disabilita il handling delle eccezioni per vedere gli errori reali
        $this->withoutExceptionHandling();
        
        // Assicuriamoci che l'applicazione non sia in modalità manutenzione
        if (file_exists(storage_path('framework/down'))) {
            unlink(storage_path('framework/down'));
        }
        
        // Esegui le migrazioni del database di test
        $this->artisan('migrate:fresh');
    }
}
