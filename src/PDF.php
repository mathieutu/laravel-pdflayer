<?php

namespace MathieuTu\PDFLayer;

use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use MathieuTu\PDFLayer\Exceptions\PDFLayerException;

/**
 * MathieuTu\PDFLayer\PDF
 *
 * @property Collection $params
 * @property string $pdf
 * @property array $config
 * @property \GuzzleHttp\Client $httpClient
 * @property \Illuminate\View\Factory $view
 * @property \Illuminate\Filesystem\Filesystem $files
 */
class PDF
{
    private $params;
    private $pdf;
    private $config;
    private $httpClient;
    private $view;
    private $files;

    public function __construct(Client $httpClient, ConfigRepository $config, Filesystem $files, ViewFactory $view)
    {
        $this->httpClient = $httpClient;
        $this->view = $view;
        $this->files = $files;

        $this->initializeConfig($config);
        $this->initializeParams();
    }

    /**
     * Load parameters from config file
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    private function initializeConfig(ConfigRepository $config)
    {
        $this->config = $config->get('pdflayer');
    }

    private function initializeParams()
    {
        $this->params = collect($this->config['default_params']);
        if ($this->config['sandbox']) {
            $this->params['test'] = 1;
        }
    }

    /**
     * @param $url
     *
     * @return $this
     */
    public function loadUrl($url)
    {
        $this->params['document_url'] = urlencode($url);

        if (!empty($this->config['secret_keyword'])) {
            $this->params['secret_key'] = md5($url . $this->config['secret_keyword']);
        }
        return $this;
    }

    public function loadView($view, $data = [], $mergeData = [], $encoding = null)
    {
        $html = $this->view->make($view, $data, $mergeData)->render();

        return $this->loadHTML($html, $encoding);
    }

    public function loadHTML($html, $encoding = null)
    {
        $this->params['document_html'] = $html;

        if ($encoding) {
            $this->params['text_encoding'] = $encoding;
        }

        return $this;
    }

    public function loadFile($file)
    {
        $html = file_get_contents($file);
        $encoding = null;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $_header) {
                if (preg_match("@Content-Type:\s*[\w/]+;\s*?charset=([\S]+)@i", $_header, $matches)) {
                    $encoding = strtoupper($matches[1]);
                    break;
                }
            }
        }
        $this->loadHTML($html, $encoding);
    }

    public function setPaper($layout, $orientation = 'portrait')
    {
        $this->params['page_size'] = $layout;
        $this->params['orientation'] = $orientation;

        return $this;
    }

    /**
     * Return a response with the PDF to show in the browser
     *
     * @param string $filename
     *
     * @return \Illuminate\Http\Response
     * @throws \InvalidArgumentException
     */
    public function stream($filename = 'document.pdf')
    {
        $output = $this->output();

        return new Response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Output the PDF as a string.
     *
     * @return string The rendered PDF as string
     */
    public function output()
    {
        if (!$this->pdf) {
            $this->render();
        }

        return $this->pdf;
    }

    /**
     * Prepare the request and make the api call
     *
     * @throws \Exception
     */
    private function render()
    {
        list($uri, $postParams) = $this->prepareRequest();
        $this->pdf = $this->doRequest($uri, $postParams);
    }

    /**
     * @return array
     */
    private function prepareRequest()
    {
        $uri = $this->config['endpoint'] . '?access_key=' . $this->config['access_key'];

        $postKeys = ['document_html', 'header_html'];
        $getParams = $this->params->except($postKeys)->toArray();

        $uri .= '&' . http_build_query($getParams);
        $postParams = $this->params->diff($getParams)->toArray();

        return [$uri, $postParams];
    }

    /**
     * @param $uri
     * @param $postParams
     *
     * @return string
     * @throws \MathieuTu\PDFLayer\Exceptions\PDFLayerException
     */
    private function doRequest($uri, $postParams)
    {
        $response = $this->httpClient->post($uri, [
            'form_params' => $postParams,
        ]);

        $content = $response->getBody()->getContents();

        $error = json_decode($content);
        if (is_object($error)) {
            throw new PDFLayerException($error);
        }

        return $content;
    }

    /**
     * Make the PDF downloadable by the user
     *
     * @param string $filename
     *
     * @return \Illuminate\Http\Response
     * @throws \InvalidArgumentException
     */
    public function download($filename = 'document.pdf')
    {
        $output = $this->output();

        return new Response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Save the PDF to a file
     *
     * @param $filename
     *
     * @return $this
     */
    public function save($filename)
    {
        $this->files->put($filename, $this->output());

        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return $this|mixed
     * @throws \BadMethodCallException
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        if (Str::startsWith($name, 'set')) {
            $propertyCamel = Str::substr($name, Str::length('set'));
            $property = Str::snake($propertyCamel);

            $this->params[$property] = $arguments;

            return $this;
        }

        throw new \BadMethodCallException('Call to undefined method ' . static::class . '::' . $name . '()');
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            return $this->{$name} = $value;
        }

        return $this->params[$name] = $value;
    }

    public function seeParams()
    {
        return $this->params->toArray();
    }

    /**
     * @param string $key uri or postParams
     *
     * @return array
     */
    public function seeRequestArgs($key = null)
    {
        list($uri, $postParams) = $this->prepareRequest();

        if ($key) {
            return ${$key};
        }

        return compact('uri', 'postParams');
    }
}
