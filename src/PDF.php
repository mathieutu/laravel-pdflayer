<?php

namespace MathieuTu\PDFLayer;

use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use MathieuTu\PDFLayer\Exceptions\PDFLayerException;

class PDF
{
    private $httpClient;
    private $view;
    private $files;
    private $config;
    private $params;
    private $pdf;

    public function __construct(Client $httpClient, ConfigRepository $config, Filesystem $files, ViewFactory $view)
    {
        $this->httpClient = $httpClient;
        $this->view = $view;
        $this->files = $files;
        $this->config = $this->prepareConfig($config);
        $this->params = $this->prepareParams();
    }

    /**
     * @param \Illuminate\Contracts\Config\Repository $config
     *
     * @return array
     */
    private function prepareConfig(ConfigRepository $config)
    {
        return $config->get('pdflayer');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    private function prepareParams()
    {
        $params = collect($this->config['default_params']);
        if ($this->config['sandbox']) {
            $params['test'] = 1;
        }

        return $params;
    }

    /**
     * Load HTML and encoding from an URL.
     *
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

    /**
     * Load a View and convert it to HTML.
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @param string $encoding
     *
     * @return $this
     */
    public function loadView($view, $data = [], $mergeData = [], $encoding = null)
    {
        $html = $this->view->make($view, $data, $mergeData)->render();

        return $this->loadHTML($html, $encoding);
    }

    /**
     * Load a HTML string.
     *
     * @param string $html
     * @param string $encoding
     *
     * @return $this
     */
    public function loadHTML($html, $encoding = null)
    {
        $this->params['document_html'] = $html;

        if ($encoding) {
            $this->params['text_encoding'] = $encoding;
        }

        return $this;
    }

    /**
     * Load a HTML file.
     *
     * @param string $file
     */
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

    /**
     * Set the paper size and orientation.
     *
     * @param string $layout
     * @param string $orientation
     *
     * @return $this
     */
    public function setPaper($layout, $orientation = 'portrait')
    {
        $this->params['page_size'] = $layout;
        $this->params['orientation'] = $orientation;

        return $this;
    }

    /**
     * Return a response with the PDF to show in the browser.
     *
     * @param string $filename
     *
     * @throws \InvalidArgumentException
     *
     * @return \Illuminate\Http\Response
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
     * @throws \Exception
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
     * @throws \RuntimeException
     * @throws \MathieuTu\PDFLayer\Exceptions\PDFLayerException
     *
     * @return string
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
     * Make the PDF downloadable by the user.
     *
     * @param string $filename
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     *
     * @return \Illuminate\Http\Response
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
     * Save the PDF to a file.
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
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (Str::startsWith($name, 'set')) {
            $propertyCamel = Str::substr($name, Str::length('set'));
            $property = Str::snake($propertyCamel);

            $this->params[$property] = $arguments[0];

            return $this;
        }

        throw new \BadMethodCallException('Call to undefined method ' . static::class . '::' . $name . '()');
    }

    /**
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function __set($name, $value)
    {
        return $this->params[$name] = $value;
    }

    /**
     * Add some parameters.
     *
     * @param array $params
     *
     * @return $this
     */
    public function addParams(array $params)
    {
        return $this->setParams($params);
    }

    /**
     * Add/Replace some parameters.
     *
     * @param array $params
     * @param bool $replace
     *
     * @return $this
     */
    public function setParams(array $params, $replace = false)
    {
        if ($replace) {
            $this->params = collect($params);
        } else {
            $this->params = $this->params->merge($params);
        }

        return $this;
    }

    /**
     * Replace some parameters.
     *
     * @param array $params
     *
     * @return $this
     */
    public function replaceParams(array $params)
    {
        return $this->setParams($params, true);
    }

    /**
     * See all the current parameters.
     *
     * @return array
     */
    public function seeParams()
    {
        return $this->params->toArray();
    }

    /**
     * See the arguments which will be sent during the request.
     * @param string $key
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
