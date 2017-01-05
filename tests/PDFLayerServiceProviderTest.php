<?php

namespace MathieuTu\PDFLayer\Tests;

use MathieuTu\PDFLayer\PDF;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use MathieuTu\PDFLayer\PDFLayerServiceProvider;

class PDFLayerServiceProviderTest extends TestCase
{
    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $this->registerProvider($app);

        return $app;
    }

    /**
     * @param Application $app
     */
    private function registerProvider(Application $app)
    {
        $providers = config('app.providers');
        $providers[] = PDFLayerServiceProvider::class;
        config(['app.providers' => $providers]);

        $app->registerConfiguredProviders();
        AliasLoader::getInstance()->alias('PDF', \MathieuTu\PDFLayer\Facades\PDF::class);
    }

    public function testConfig()
    {
        // Provider is deferred so the config should be null before it is called.
        $this->assertNull(config('pdflayer'));

        app('pdflayer');
        $config = require __DIR__ . '/../config/pdflayer.php';
        $this->assertEquals($config, config('pdflayer'));
    }

    public function testRegistering()
    {
        $this->assertInstanceOf(PDF::class, app('pdflayer'));
        $this->assertInstanceOf(PDF::class, app(PDF::class));
        $this->assertInstanceOf(PDF::class, \PDF::setParams([]));
    }

    public function testDeferring()
    {
        $this->assertArraySubset([
            'pdflayer' => PDFLayerServiceProvider::class,
            PDF::class => PDFLayerServiceProvider::class,
        ], $this->app->getDeferredServices());
    }
}
