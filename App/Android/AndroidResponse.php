<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 09/01/2019
 * Time: 12:08
 * @param $content
 * @param int $status
 * @param array $header
 * @return AndroidResponse
 */

namespace App\Android;
use App\Contracts\Support\Arrayable;

class AndroidResponse
{
    private $payload;
    private $status_code;
    private $header;

    /**
     * AndroidResponse constructor.
     * @param $content
     * @param $status
     * @param $header
     */
    public function __construct($content = '', $status = 200, array $header = array())
    {
        $this->payload = '';
        $this->status_code = 200;
        $this->header = [];
        $this->setter($content, $status, $header);
    }

    public function setter($content = '', $status = 200, array $header = array())
    {
        $this->payload = $content;
        if (is_array($content) || is_object($content)) {
            $header[] = 'Content-Type: application/json';
        }
        $this->status_code = $status;
        switch ($status) {
            case 401:
                $header[] = 'WWW-Authenticate: Basic realm="Welcome to PrestaShop Webservice, please enter the authentication key as the login. No password required."';
                $header[] = $_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized';
                break;
        }
        $this->mergeHeader($header);
    }

    private function mergeHeader(array $header = array())
    {
        $this->header = array_merge($this->header ?: [], $header ?: []);
    }


    public function response($content, $status = 200, $header = array())
    {
        $this->setter($content, $status, $header);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        if($this->payload instanceof Arrayable){
            return $this->payload->toArray();
        }
        return $this->payload;
    }

    /**
     * @param string $payload
     * @return AndroidResponse
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * @param int $status_code
     * @return AndroidResponse
     */
    public function setStatusCode($status_code)
    {
        $this->status_code = $status_code;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param array $header
     * @return AndroidResponse
     */
    public function setHeader($header)
    {
        $this->header = $header;
        return $this;
    }

    public function __http_response_code($code = null)
    {
        if (function_exists('http_response_code')) {
            return http_response_code($code);
        }
        switch ($code) {
            case 100:
                $text = 'Continue';
                break;
            case 101:
                $text = 'Switching Protocols';
                break;
            case 200:
                $text = 'OK';
                break;
            case 201:
                $text = 'Created';
                break;
            case 202:
                $text = 'Accepted';
                break;
            case 203:
                $text = 'Non-Authoritative Information';
                break;
            case 204:
                $text = 'No Content';
                break;
            case 205:
                $text = 'Reset Content';
                break;
            case 206:
                $text = 'Partial Content';
                break;
            case 300:
                $text = 'Multiple Choices';
                break;
            case 301:
                $text = 'Moved Permanently';
                break;
            case 302:
                $text = 'Moved Temporarily';
                break;
            case 303:
                $text = 'See Other';
                break;
            case 304:
                $text = 'Not Modified';
                break;
            case 305:
                $text = 'Use Proxy';
                break;
            case 400:
                $text = 'Bad Request';
                break;
            case 401:
                $text = 'Unauthorized';
                break;
            case 402:
                $text = 'Payment Required';
                break;
            case 403:
                $text = 'Forbidden';
                break;
            case 404:
                $text = 'Not Found';
                break;
            case 405:
                $text = 'Method Not Allowed';
                break;
            case 406:
                $text = 'Not Acceptable';
                break;
            case 407:
                $text = 'Proxy Authentication Required';
                break;
            case 408:
                $text = 'Request Time-out';
                break;
            case 409:
                $text = 'Conflict';
                break;
            case 410:
                $text = 'Gone';
                break;
            case 411:
                $text = 'Length Required';
                break;
            case 412:
                $text = 'Precondition Failed';
                break;
            case 413:
                $text = 'Request Entity Too Large';
                break;
            case 414:
                $text = 'Request-URI Too Large';
                break;
            case 415:
                $text = 'Unsupported Media Type';
                break;
            case 500:
                $text = 'Internal Server Error';
                break;
            case 501:
                $text = 'Not Implemented';
                break;
            case 502:
                $text = 'Bad Gateway';
                break;
            case 503:
                $text = 'Service Unavailable';
                break;
            case 504:
                $text = 'Gateway Time-out';
                break;
            case 505:
                $text = 'HTTP Version not supported';
                break;
            default:
                exit('Unknown http status code "' . htmlentities($code) . '"');
        }
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
        header($protocol . ' ' . $code . ' ' . $text);
        return $code;
    }

    public function printContent()
    {
        $this->__http_response_code($this->getStatusCode());
        foreach ($this->header as $h) {
            header($h);
        }
        if (is_string($this->getPayload())) {
            exit($this->getPayload());
        } else {
            exit(json_encode($this->getPayload()));
        }
    }

}
