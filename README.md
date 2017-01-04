## PDFLayer API bridge for Laravel 5

[![Travis Build](https://img.shields.io/travis/mathieutu/laravel-pdflayer/master.svg)](https://travis-ci.org/mathieutu/laravel-pdflayer?branch=master) 
[![StyleCI](https://styleci.io/repos/77931503/shield?branch=master)](https://styleci.io/repos/77931503) 
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/mathieutu/laravel-pdflayer.svg)](https://scrutinizer-ci.com/g/mathieutu/laravel-pdflayer/?branch=master) 
[![Code Quality](https://img.shields.io/scrutinizer/g/mathieutu/laravel-pdflayer.svg)](https://scrutinizer-ci.com/g/mathieutu/laravel-pdflayer/?branch=master) 

Require this package in your composer.json and update composer. This will download the package and the dompdf + fontlib libraries also.
```bash
composer require mathieutu/laravel-pdflayer
```

## Installation
### Laravel 5.x:

After updating composer, add the ServiceProvider to the providers array in config/app.php
```php
MathieuTu\PDFLayer\PDFLayerServiceProvider::class,
```

You can optionally use the facade for shorter code. Add this to your facades:
```php
'PDF' => MathieuTu\PDFLayer\Facades\PDF::class,
```
  
## Using

You can create a new PDFLayer instance and load a HTML string, file, view name or even an url. 
You can save it to a file, stream (show in browser) or download.

To create an new instance, you can use the `App` class, the `app()` helper, use the [facade](https://laravel.com/docs/5.3/facades), or (better) use [automatic dependency injection](https://laravel.com/docs/5.3/controllers#dependency-injection-and-controllers) :
```php
$pdf = App::make('pdflayer');
$pdf = app('pdflayer');
$pdf = PDF::anyMethod();
function (MathieuTu\PDFLayer\PDF $pdf) {}
```
You can chain the methods:
```php
return $pdf->loadView('pdf.invoice', $data)->setPaper('a4', 'landscape')->save('/path-to/my_stored_file.pdf')->stream('download.pdf');
```
You can set all the parameters given by the [pdflayer documentation](https://pdflayer.com/documentation) by using the `setXXX` method where XXX is the parameter in StudlyCase, or just set the parameter (in original snake_case) as attribute of the object.
```php
$pdf->loadHTML('<h1>Hello!</h1>')->setWatermarkInBackground(true);
$pdf->margin_top = 134;
$pdf->save('myfile.pdf');
```
If you need the output as a string, you can get the rendered PDF with the output() function, so you can directly send it by email, for example.

### Configuration
The defaults configuration settings are set in `config/pdflayer.php`. Copy this file to your own config directory to modify the values. You can publish the config using this shell command:
```bash
php artisan vendor:publish --provider="MathieuTu\PDFLayer\PDFLayerServiceProvider"
```
    
### License and thanks

This PDFLayer Bridge for Laravel is an open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

This readme and some methods of the `PDF` class are adapted from the [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) package thanks to him for his job.

This package was originally written for a project by [Neoxia](http://neoxia.com/), a web dev company in Paris, which works a lot with Laravel, and with love! We're hiring, contact us!
