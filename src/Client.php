<?php
namespace DuoAPI;

use DateTime;

const VERSION = "1.2.0-dev";
const INITIAL_BACKOFF_SECONDS = 1;
const MAX_BACKOFF_SECONDS = 32;
const BACKOFF_FACTOR = 2;
const RATE_LIMIT_HTTP_CODE = 429;


class Client
{
    const DEFAULT_PAGING_LIMIT = '100';
    
    public $ikey;
    public $skey;
    public $host;
    public $requester;
    public $paging;
    public $options;
    public $sleep_service;

    public function __construct(
        $ikey,
        $skey,
        $host,
        $requester = null,
        $paging = true
    ) {
        assert(is_string($ikey));
        assert(is_string($skey));
        assert(is_string($host));
        assert(is_null($requester) || is_subclass_of($requester, "DuoAPI\\Requester"));
        assert(is_bool($paging));

        $this->ikey = $ikey;
        $this->skey = $skey;
        $this->host = $host;

        if ($requester !== null) {
            $this->requester = $requester;
        } elseif (in_array("curl", get_loaded_extensions(), true)) {
            $this->requester = new CurlRequester();
        } else {
            $this->requester = new FileRequester();
        }

        $this->paging = $paging;

        // Default requester options
        $this->options = [
            "timeout" => 10,
        ];

        $this->sleep_service = new USleepService();
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
    public function setRequesterOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    private function signParameters($method, $host, $path, $params, $skey, $ikey, $now, $body, $additional_headers)
    {
        assert(is_string($method));
        assert(is_string($host));
        assert(is_string($path));
        assert(is_array($params));
        assert(is_string($skey));
        assert(is_string($ikey));
        assert(is_string($now));

        $canon = self::canonicalize($method, $host, $path, $params, $now, $body, $additional_headers);
        
        $signature = self::sign($canon, $skey);
        $auth = sprintf("%s:%s", $ikey, $signature);
        $b64auth = base64_encode($auth);

        return sprintf("Basic %s", $b64auth);
    }

    private function sign($msg, $key)
    {
        assert(is_string($msg));
        assert(is_string($key));

        $msg = mb_convert_encoding($msg ?? '', 'UTF-8', 'ISO-8859-1');
        $key = mb_convert_encoding($key ?? '', 'UTF-8', 'ISO-8859-1');

        return hash_hmac("sha512", $msg, $key);
    }

    private function canonicalize($method, $host, $path, $params, $now, $body = null, $additional_headers = [])
    {
        assert(is_string($method));
        assert(is_string($host));
        assert(is_string($path));
        assert(is_array($params));
        assert(is_string($now));
        assert(is_string($body) || $body === null);
        assert(is_array($additional_headers));

        $args = self::urlEncodeParameters($params);
        
        $canon = array(
            $now,
            strtoupper($method),
            strtolower($host),
            $path,
            $args,
            hash('sha512', mb_convert_encoding($body ?? '', 'UTF-8', 'ISO-8859-1')),
            self::canonXDuoHeaders($additional_headers),
        );

        $canon = implode("\n", $canon);

        return $canon;
    }

    private function canonXDuoHeaders($additional_headers = [])
    {
        assert(is_array($additional_headers));
        
        $lowered_headers = array_change_key_case($additional_headers, CASE_LOWER);
        ksort($lowered_headers);

        $canon_list = [];
        $added_headers = [];

        foreach ($lowered_headers as $header_name => $value) {
            self::validateAdditionalHeader($header_name, $value, $added_headers);
            array_push($canon_list, $header_name, $value);
            array_push($added_headers, $header_name);
        }

        $canon = implode("\x00", $canon_list);
        return hash('sha512', mb_convert_encoding($canon ?? '', 'UTF-8', 'ISO-8859-1'));
    }

    private function validateAdditionalHeader($name, $value, $addedHeaders)
    {
        if ($name === null || $value === null)
        {
            throw new \InvalidArgumentException("Not allowed 'null' as a header name or value");
        } elseif (str_contains($name,"\x00"))
        {
            throw new \InvalidArgumentException("Not allowed 'Null' character in header name");
        } elseif (str_contains($value,"\x00"))
        {
            throw new \InvalidArgumentException("Not allowed 'Null' character in header value");
        } elseif (!str_starts_with(strtolower($name),"x-duo-"))
        {
            throw new \InvalidArgumentException("Additional headers must start with 'X-Duo-'");
        } elseif (in_array(strtolower($name), $addedHeaders, true))
        {
            throw new \InvalidArgumentException("Duplicate header passed, header=$name");
        }
    }

    private function urlEncodeParameters($params)
    {
        assert(is_array($params));

        ksort($params);
        $args = array_map(function ($key, $value) {
            return sprintf("%s=%s", rawurlencode($key), rawurlencode($value));
        }, array_keys($params), array_values($params));
        return implode("&", $args);
    }

    private function makeRequest($method, $uri, $body, $headers)
    {
        assert(is_string($method));
        assert(is_string($uri));
        assert(is_string($body) || is_null($body));
        assert(is_array($headers));

        $url = "https://" . $this->host . $uri;

        $this->requester->options($this->options);

        $backoff_seconds = INITIAL_BACKOFF_SECONDS;
        while (true) {
            $result = $this->requester->execute($url, $method, $headers, $body);
            if ($result["http_status_code"] != RATE_LIMIT_HTTP_CODE || $backoff_seconds > MAX_BACKOFF_SECONDS) {
                return $result;
            }

            $this->sleep_service->sleep($backoff_seconds + (rand(0, 1000) / 1000.0));
            $backoff_seconds *= BACKOFF_FACTOR;
        }
    }

    public function apiCall($method, $path, $params, $additional_headers = [])
    {
        assert(is_string($method));
        assert(is_string($path));
        assert(is_array($params));
        assert(is_array($additional_headers));

        $now = date(DateTime::RFC2822);
        $headers = [];
        if (in_array($method, ["POST", "PUT", "PATCH"], true)) {
            ksort($params);
            $body = json_encode($params);
            $params = [];
            $headers["Content-Type"] = "application/json";
            $uri = $path;
        } else {
            $body = "";
            $uri = $path . (!empty($params) ? "?" . self::urlEncodeParameters($params) : "");
        }

        $headers["Date"] = $now;
        $headers["User-Agent"] = "duo_api_php/" . VERSION;
        $headers["Authorization"] = self::signParameters(
            $method,
            $this->host,
            $path,
            $params,
            $this->skey,
            $this->ikey,
            $now,
            $body,
            $additional_headers,
        );

        return self::makeRequest($method, $uri, $body, $headers);
    }

    public function jsonApiCall($method, $path, $params)
    {
        assert(is_string($method));
        assert(is_string($path));
        assert(is_array($params));

        $result = self::apiCall($method, $path, $params);
        $result["response"] = json_decode($result["response"], true);
        return $result;
    }

    public function jsonPagingApiCall($method, $path, $params)
    {
        assert(is_string($method));
        assert(is_string($path));
        assert(is_array($params));

        $offset = 0;

        if (!isset($params["limit"])) {
            $params["limit"] = self::DEFAULT_PAGING_LIMIT;
        }

        $result = [];
        while ($offset !== false) {
            $params["offset"] = strval($offset);
            $paged_result = self::jsonApiCall($method, $path, $params);

            /*
             * If we receive any sort of error during paging calls we're going
             * to bail. This is so we don't return partial results.
             */
            $network_error = !isset($paged_result["success"]) || $paged_result["success"] !== true;
            $api_error = !isset($paged_result["response"]["stat"]) || $paged_result["response"]["stat"] !== "OK";
            if ($network_error || $api_error) {
                return $paged_result;
            }

            $offset = isset($paged_result["response"]["metadata"]["next_offset"]) ?
                $paged_result["response"]["metadata"]["next_offset"] : false;

            if (isset($paged_result["response"]["metadata"])) {
                unset($paged_result["response"]["metadata"]);
            }

            /*
             * All the auxiliary data should be the same for successful paged
             * calls. So let's just take the first one and merge all the
             * subsequent response data into a single list to make it look
             * like it was a single call.
             */
            if (empty($result)) {
                $result = $paged_result;
            } else {
                $result["response"]["response"] = array_merge(
                    $result["response"]["response"],
                    $paged_result["response"]["response"]
                );
            }
        }

        return $result;
    }
}
