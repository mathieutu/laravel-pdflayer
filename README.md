## PDFLayer API bridge for Laravel 5.5+ (for 5.2+ take the 1.* version)
[pdflayer.com](https://pdflayer.com) is an HTML to PDF conversion API for developers. 
This package is an unofficial bridge to use this api with the PHP Laravel framework.

[![Travis build](https://img.shields.io/travis/mathieutu/laravel-pdflayer/master.svg?style=flat-square&label=Build)](https://travis-ci.org/mathieutu/laravel-pdflayer?branch=master) 
[![StyleCI](https://styleci.io/repos/77931503/shield?branch=master)](https://styleci.io/repos/77931503) 
[![Test coverage](https://img.shields.io/scrutinizer/coverage/g/mathieutu/laravel-pdflayer.svg?style=flat-square&label=Coverage)](https://scrutinizer-ci.com/g/mathieutu/laravel-pdflayer/?branch=master) 
[![Code quality](https://img.shields.io/scrutinizer/g/mathieutu/laravel-pdflayer.svg?style=flat-square&label=Quality)](https://scrutinizer-ci.com/g/mathieutu/laravel-pdflayer/?branch=master) 
[![Packagist downloads](https://img.shields.io/packagist/dt/mathieutu/laravel-pdflayer.svg?style=flat-square&label=Downloads)](https://packagist.org/packages/mathieutu/laravel-pdflayer)
[![Stable version](https://img.shields.io/packagist/v/mathieutu/laravel-pdflayer.svg?style=flat-square&label=Packagist)](https://packagist.org/packages/mathieutu/laravel-pdflayer)

## Installation

Require this package in your composer.json and update composer.
```bash
composer require mathieutu/laravel-pdflayer
```
 
## Usage

You can create a new PDFLayer instance and load a HTML string, file, view name or even an url. 
You can save it to a file, stream (show in browser) or download.

To create an new instance, you can use the `App` class, the `app()` helper, use the [facade](https://laravel.com/docs/5.5/facades), or (better) use [automatic dependency injection](https://laravel.com/docs/5.5/controllers#dependency-injection-and-controllers) :
```php
$pdf = App::make('pdflayer');
$pdf = app('pdflayer');
$pdf = PDF::anyMethod();
public function downloadPdf(MathieuTu\PDFLayer\PDF $pdf) {}
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

This PDFLayer Bridge for Laravel is an open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

The developer is not affiliated in any way with the [pdflayer.com](https://pdflayer.com) service.

This Readme and some methods of the `PDF` class are adapted from the [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) package. Thanks to him for his job.


### Contributing

Issues and PRs are obviously welcomed and encouraged, as well for new features than documentation.
Each piece of code added should be fully tested, but we can do that all together, so please don't be afraid by that. 
