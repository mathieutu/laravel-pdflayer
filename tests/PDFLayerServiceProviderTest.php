<?php

namespace MathieuTu\PDFLayer\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use MathieuTu\PDFLayer\PDF;
use MathieuTu\PDFLayer\PDFLayerServiceProvider;
use Mockery as m;

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
        /** @var Application $app */
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register(PDFLayerServiceProvider::class);
        AliasLoader::getInstance()->alias('PDF', \MathieuTu\PDFLayer\Facades\PDF::class);
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function testConfig()
    {
        $config = require __DIR__ . '/../config/pdflayer.php';
        $this->assertEquals($config, config('pdflayer'));
    }

    public function testRegistering()
    {
        $this->assertInstanceOf(PDF::class, app('pdflayer'));
        $this->assertInstanceOf(PDF::class, app(PDF::class));
        $this->assertInstanceOf(PDF::class, \PDF::setParams([]));
    }
}
