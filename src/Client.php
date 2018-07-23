<?php
namespace DuoAPI;

use DateTime;

const SIGNATURE_CANON_QUERY_STRING_BODY = 2;
const SIGNATURE_CANON_JSON_STRING_BODY = 4;

const SIGNATURE_CANONS = [
    SIGNATURE_CANON_QUERY_STRING_BODY,
    SIGNATURE_CANON_JSON_STRING_BODY,
];

class Client
{
    const DEFAULT_PAGING_LIMIT = '100';

    public function __construct(
        $ikey,
        $skey,
        $host,
        $requester = null,
        $paging = true,
        $signature_version = SIGNATURE_CANON_QUERY_STRING_BODY
    ) {
        assert(is_string($ikey));
        assert(is_string($skey));
        assert(is_string($host));
        assert(is_null($requester) || is_subclass_of($requester, "DuoAPI\\Requester"));
        assert(is_bool($paging));
        assert(is_int($signature_version) && in_array($signature_version, SIGNATURE_CANONS, true));

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
        $this->signature_version = $signature_version;

        // Default requester options
        $this->options = [
            "timeout" => 10,
        ];
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

    private function signParameters($method, $host, $path, $params, $skey, $ikey, $now)
    {
        assert(is_string($method));
        assert(is_string($host));
        assert(is_string($path));
        assert(is_array($params));
        assert(is_string($skey));
        assert(is_string($ikey));
        assert(is_string($now));

        $canon = self::canonicalize($method, $host, $path, $params, $now);
        
        if ($this->signature_version === SIGNATURE_CANON_QUERY_STRING_BODY) {
            $algo = "sha1";
        } elseif ($this->signature_version === SIGNATURE_CANON_JSON_STRING_BODY) {
            $algo = "sha512";
        }

        $signature = self::sign($canon, $skey, $algo);
        $auth = sprintf("%s:%s", $ikey, $signature);
        $b64auth = base64_encode($auth);

        return sprintf("Basic %s", $b64auth);
    }

    private function sign($msg, $key, $algo = "sha1")
    {
        assert(is_string($msg));
        assert(is_string($key));

        return hash_hmac($algo, $msg, $key);
    }

    private function canonicalize($method, $host, $path, $params, $now)
    {
        assert(is_string($method));
        assert(is_string($host));
        assert(is_string($path));
        assert(is_array($params));
        assert(is_string($now));

        if ($this->signature_version === SIGNATURE_CANON_QUERY_STRING_BODY) {
            $canon = [
                $now,
                strtoupper($method),
                strtolower($host),
                $path,
                self::urlEncodeParameters($params)
            ];
        } elseif ($this->signature_version === SIGNATURE_CANON_JSON_STRING_BODY) {
            $canon = [
                $now,
                strtoupper($method),
                strtolower($host),
                $path,
                "",
                hash("sha512", self::bodyEncodeParameters($params))
            ];
        }

        $canon = implode("\n", $canon);

        return $canon;
    }

    private function bodyEncodeParameters($params)
    {
        assert(is_array($params));

        ksort($params);
        return json_encode($params);
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
        $result = $this->requester->execute($url, $method, $headers, $body);

        return $result;
    }

    public function apiCall($method, $path, $params)
    {
        assert(is_string($method));
        assert(is_string($path));
        assert(is_array($params));

        $now = date(DateTime::RFC2822);

        $headers = [];
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

        if (in_array($method, ["POST", "PUT"], true)) {
            if ($this->signature_version === SIGNATURE_CANON_QUERY_STRING_BODY) {
                $body = http_build_query($params);
                $headers["Content-Type"] = "application/x-www-form-urlencoded";
            } elseif ($this->signature_version === SIGNATURE_CANON_JSON_STRING_BODY) {
                $body = self::bodyEncodeParameters($params);
                $headers["Content-Type"] = "application/json";
            }
            $headers["Content-Length"] = strval(strlen($body));
            $uri = $path;
        } else {
            $body = null;
            $uri = $path . (!empty($params) ? "?" . self::urlEncodeParameters($params) : "");
        }

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
