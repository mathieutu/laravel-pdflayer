<?php

namespace MathieuTu\PDFLayer\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\View\Factory;
use Illuminate\View\View;
use MathieuTu\PDFLayer\PDF;
use MathieuTu\PDFLayer\PDFLayerServiceProvider;
use Mockery as m;

class PDFTest extends TestCase
{
    /** @var PDF */
    private $pdf;

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

        $app->register(PDFLayerServiceProvider::class);
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->app['config']->set('pdflayer.access_key', 'testAccessKey');
        $this->newPDF();
    }

    private function newPDF($httpClient = null, $config = null, $files = null, $view = null)
    {
        $httpClient = $httpClient ?: $this->app->make(Client::class);
        $config = $config ?: $this->app->make(Repository::class);
        $files = $files ?: $this->app->make(Filesystem::class);
        $view = $view ?: $this->app->make(Factory::class);

        $this->pdf = new PDF($httpClient, $config, $files, $view);
    }

    public function testInitialize()
    {
        $this->app['config']->set('pdflayer.default_params', ['author' => 'Mathieu TUDISCO']);
        $this->newPDF();

        $this->seeInRequest([
            'author'     => 'Mathieu TUDISCO',
            'test'       => true,
            'access_key' => 'testAccessKey',
        ]);
    }

    private function seeInRequest(array $subset, $equals = false)
    {
        if (empty($subset)) {
            return;
        }

        $requestArgs = $this->pdf->seeRequestArgs();
        $httpArgs = explode('?', $requestArgs['uri'])[1];
        parse_str($httpArgs, $args);
        $args += $requestArgs['postParams'];

        if ($equals) {
            $this->assertEquals($subset, $args);
        } else {
            $this->assertArraySubset($subset, $args);
        }
    }

    public function testLoadUrl()
    {
        $this->app['config']->set('pdflayer.secret_keyword', 'foo');
        $this->newPDF();

        $this->pdf->loadUrl('essai/ma page.php?avec&des&paramètres');

        $this->seeInRequest([
            'document_url' => 'essai%2Fma+page.php%3Favec%26des%26param%C3%A8tres',
            'secret_key'   => 'd2da2a1f04d1afad1fb5b0289abcb8de', //md5(url . 'foo')
        ]);
    }

    public function testLoadView()
    {
        $view = m::mock(View::class);
        $view->shouldReceive('render')->andReturn('<h1>Hello world!</h1>');
        $viewFactory = m::mock(Factory::class);
        $viewFactory->shouldReceive('make')->with('test_view', ['foo' => 'bar'], [])->andReturn($view);
        $this->newPDF(null, null, null, $viewFactory);

        $this->pdf->loadView('test_view', ['foo' => 'bar']);

        $this->seeInRequest([
            'document_html' => '<h1>Hello world!</h1>',
        ]);
    }

    public function testLoadHTML()
    {
        $this->pdf->loadHTML('<h1>Hello world!</h1>', 'UTF-16');

        $this->seeInRequest([
            'document_html' => '<h1>Hello world!</h1>',
            'text_encoding' => 'UTF-16',
        ]);
    }

    public function testLoadFile()
    {
        $this->pdf->loadFile(__DIR__ . '/test_file.html');
        $this->seeInRequest([
            'document_html' => '<h1>Hello world!</h1>',
        ]);

        $this->pdf->loadFile('http://neoxia.com/');
        $this->seeInRequest([
            'text_encoding' => 'UTF-8',
        ]);
    }

    public function testSetPaper()
    {
        $this->pdf->setPaper('a4', 'landscape');

        $this->seeInRequest([
            'page_size'   => 'a4',
            'orientation' => 'landscape',
        ]);
    }

    /**
     * @expectedException     \MathieuTu\PDFLayer\Exceptions\PDFLayerException
     * @expectedExceptionCode    134
     * @expectedExceptionMessage Error message
     */
    public function testOutputOK()
    {
        $this->mockClient();
        $this->assertEquals('generatedPDF', $this->pdf->output());

        $this->mockClient(new GuzzleResponse(200, [], json_encode([
            'success' => false,
            'error'   => ['code' => 134, 'type' => 'foo', 'info' => 'Error message'],
        ])));
        $this->pdf->output();
    }

    private function mockClient($response = null, $return = false)
    {
        $handler = HandlerStack::create(new MockHandler([
            $response ?: new GuzzleResponse(200, [], 'generatedPDF'),
        ]));
        $httpClient = new Client(['handler' => $handler]);
        if ($return) {
            return $httpClient;
        }
        $this->newPDF($httpClient);
    }

    public function testStream()
    {
        $this->mockClient();
        $response = $this->pdf->stream('foo.pdf');
        $this->assertEquals('generatedPDF', $response->content());
        $this->assertResponseHeadersContains($response, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="foo.pdf"',
        ]);
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    private function assertResponseHeadersContains(\Illuminate\Http\Response $response, array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->assertTrue($response->headers->contains($key, $value));
        }
    }

    public function testDownload()
    {
        $this->mockClient();
        $response = $this->pdf->download('bar.pdf');
        $this->assertEquals('generatedPDF', $response->content());
        $this->assertResponseHeadersContains($response, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bar.pdf"',
        ]);
    }

    public function testSave()
    {
        $httpClient = $this->mockClient(null, true);
        $files = m::mock(Filesystem::class);
        $files->shouldReceive('put');

        $this->newPDF($httpClient, null, $files);
        $this->pdf->save('baz.php');
    }

    /**
     * @expectedException     \BadMethodCallException
     * @expectedExceptionMessage Call to undefined method MathieuTu\PDFLayer\PDF::foo()
     */
    public function testMagicCallerAndSetter()
    {
        $this->pdf->setWatermarkInBackground(true);
        $this->pdf->no_images = true;
        $this->pdf->params = 'foo';
        $this->seeInRequest([
            'watermark_in_background' => 1,
            'no_images'               => 1,
            'params'                  => 'foo',
        ]);

        $this->pdf->foo();
    }

    public function testParamsMethods()
    {
        $this->pdf->addParams(['creator' => 'someone', 'watermark_in_background' => 1]);
        $this->pdf->addParams(['creator' => 'mathieutu', 'no_hyperlinks' => 1]);
        $this->seeInRequest([
            'watermark_in_background' => true,
            'creator'                 => 'mathieutu',
            'no_hyperlinks'           => true,

        ]);

        $this->pdf->replaceParams(['grayscale' => true]);
        $this->pdf->setParams(['low_quality' => true]);
        $this->assertEquals(['grayscale' => true, 'low_quality' => true], $this->pdf->seeParams());
        $this->assertEquals(
            'https://api.pdflayer.com/api/convert?access_key=testAccessKey&grayscale=1&low_quality=1',
            $this->pdf->seeRequestArgs('uri')
        );
        $this->seeInRequest([
            'grayscale'   => true,
            'low_quality' => true,
            'access_key'  => 'testAccessKey',
        ], true);
    }
}
