<?php
namespace DuoAPI;

use DateTime;

require_once("Requester.php");
require_once("CurlRequester.php");
require_once("FileRequester.php");

class Client {

    function __construct($ikey, $skey, $host, $requester = NULL) {
        assert('is_string($ikey)');
        assert('is_string($skey)');
        assert('is_string($host)');
        assert('is_null($requester) || is_subclass_of($requester, "DuoAPI\\Requester")');

        $this->ikey = $ikey;
        $this->skey = $skey;
        $this->host = $host;

        if ($requester !== NULL) {
            $this->requester = $requester;
        } else if (in_array("curl", get_loaded_extensions())) {
            $this->requester = new CurlRequester();
        } else {
            $this->requester = new FileRequester();
        }

        // Default requester options
        $this->options = array(
            "timeout" => 10,
        );
    }

    /*
     * We're trusting the caller to set appropriate types here. We won't
     * assert on the type. We're also providing a fluent interface so it can
     * be called like:
     *
     *      $client->setRequesterOption("option1", "value1")
     *             ->setRequesterOption("option2", "value2")
     *             ->setRequesterOption("option3", "value3");
     */
    public function setRequesterOption($option, $value) {
        $this->options[$option] = $value;
        return $this;
    }

    private function signParameters($method, $host, $path, $params, $skey, $ikey, $now) {
        assert('is_string($method)');
        assert('is_string($host)');
        assert('is_string($path)');
        assert('is_array($params)');
        assert('is_string($skey)');
        assert('is_string($ikey)');
        assert('is_string($now)');

        $canon = self::canonicalize($method, $host, $path, $params, $now);
        
        $signature = self::sign($canon, $skey);
        $auth = sprintf("%s:%s", $ikey, $signature);
        $b64auth = base64_encode($auth);

        return sprintf("Basic %s", $b64auth);
    }

    private function sign($msg, $key) {
        assert('is_string($msg)');
        assert('is_string($key)');

        return hash_hmac("sha1", $msg, $key);
    }

    private function canonicalize($method, $host, $path, $params, $now) {
        assert('is_string($method)');
        assert('is_string($host)');
        assert('is_string($path)');
        assert('is_array($params)');
        assert('is_string($now)');

        $args = self::urlEncodeParameters($params);
        $canon = array($now, strtoupper($method), strtolower($host), $path, $args);
        $canon = implode("\n", $canon);
        return $canon;
    }

    private function urlEncodeParameters($params) {
        assert('is_array($params)');

        ksort($params);
        $args = array_map(function($key, $value) {
            return sprintf("%s=%s", rawurlencode($key), rawurlencode($value));
        }, array_keys($params), array_values($params));
        return implode("&", $args);
    }

    private function makeRequest($method, $uri, $body, $headers) {
        assert('is_string($method)');
        assert('is_string($uri)');
        assert('is_string($body) || is_null($body)');
        assert('is_array($headers)');

        $url = "https://" . $this->host . $uri;

        $this->requester->options($this->options);
        $result = $this->requester->execute($url, $method, $headers, $body);

        return $result;
    }

    public function apiCall($method, $path, $params) {
        assert('is_string($method)');
        assert('is_string($path)');
        assert('is_array($params)');

        $now = date(DateTime::RFC2822);

        $headers = array();
        $headers["Date"] = $now;
        $headers["Host"] = $this->host;
        $headers["Authorization"] = self::signParameters(
            $method,
            $this->host,
            $path,
            $params,
            $this->skey,
            $this->ikey,
            $now
        );

        if (in_array($method, array("POST", "PUT"))) {
            $body = http_build_query($params);
            $headers["Content-Type"] = "application/x-www-form-urlencoded";
            $headers["Content-Length"] = strval(strlen($body));
            $uri = $path;
        } else {
            $body = NULL;
            $uri = $path . (!empty($params) ? "?" . self::urlEncodeParameters($params) : "");
        }

        return self::makeRequest($method, $uri, $body, $headers);
    }

    public function jsonApiCall($method, $path, $params) {
        assert('is_string($method)');
        assert('is_string($path)');
        assert('is_array($params)');

        $result = self::apiCall($method, $path, $params);
        $result["response"] = json_decode($result["response"], TRUE);
        return $result;
    }

}
