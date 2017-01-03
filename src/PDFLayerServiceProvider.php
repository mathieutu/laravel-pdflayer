<?php
namespace MathieuTu\PDFLayer;

use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class PDFLayerServiceProvider extends ServiceProvider
{

    protected $defer = true;

    public function boot()
    {
        $configPath = __DIR__ . '/../config/';
        $this->mergeConfigFrom($configPath . 'pdflayer.php', 'pdflayer');
        $this->publishes([$configPath => config_path()], 'config');
    }

    public function register()
    {
        $this->app->bind('pdflayer', function (Application $app) {
            return new PDF($app->make(Client::class), $app['config'], $app['files'], $app['view']);
        });

        $this->app->alias('pdflayer', PDF::class);
    }

    public function provides()
    {
        return ['pdflayer', PDF::class];
    }

}
