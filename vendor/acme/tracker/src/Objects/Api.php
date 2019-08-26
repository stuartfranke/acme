<?php

namespace Acme\Tracker\Objects;

/**
 * Class Api
 * @package Acme\Tracker\Objects
 */
class Api
{
    /**
     * @var string
     */
    protected $requestMethod;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $postFields;

    /**
     * @param string $requestMethod
     * @return $this
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;

        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param array|string $postFields
     * @return $this
     */
    public function setPostFields($postFields)
    {
        $this->postFields = $postFields;

        return $this;
    }

    /**
     * @return mixed
     */
    public function request()
    {
        $isPost = strtoupper($this->requestMethod) === 'POST' ? true : false;

        if (isset($_SESSION['access_token'])) {
            $this->headers[] = 'Authorization: bearer ' . $_SESSION['access_token'];
        }

        $this->headers[] = 'User-Agent: GitHub-Issue-Tracker-App';

        $curlResource = curl_init($this->url);
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curlResource, CURLOPT_POST, $isPost);
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);

        if (!empty($this->postFields)) {
            curl_setopt($curlResource, CURLOPT_POSTFIELDS, $this->postFields);
        }

        $responseData = curl_exec($curlResource);
        $responseCode = curl_getinfo($curlResource, CURLINFO_RESPONSE_CODE);
        $responseError = curl_error($curlResource);

        curl_close($curlResource);

        return $this->setReturnData($responseData, $responseCode, $responseError);
    }

    /**
     * @param string|bool $responseData
     * @param array $responseCode
     * @param string $responseError
     * @return \stdClass|array
     */
    protected function setReturnData($responseData, $responseCode, $responseError)
    {
        $result = json_decode($responseData);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $result = [];
        }

        return ['data' => $result, 'response_code' => $responseCode, 'response_error' => $responseError];
    }
}
