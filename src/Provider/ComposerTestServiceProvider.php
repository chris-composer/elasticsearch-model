<?php

namespace ComposerTest\Provider;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class ComposerTestServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->publishes([
            __DIR__.'/../../config/composer_test.php' => config_path('composer_test.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../../database/migrations/create_test_table.php' => $this->getMigrationFileName($filesystem)
        ], 'migrations');
    }

    public function register()
    {

    }

    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_create_test_table.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_test_table.php")
            ->first();
    }
}
