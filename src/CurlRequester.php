<?php
namespace DuoAPI;

define('DEFAULT_CA_CERTS', __DIR__."/ca_certs.pem");

class CurlRequester implements Requester
{
    public $ch;
    
    public function __construct()
    {
        $this->ch = curl_init();
    }

    public function __destruct()
    {
        if (isset($this->ch)) {
            curl_close($this->ch);
        }
    }

    public function options($options)
    {
        assert(is_array($options));

        /*
         * These are the cURL options we support. The key represents the
         * cURL option, and the value represents the key in the options
         * argument.
         */
        $possible_options = [
            CURLOPT_TIMEOUT => "timeout",
            CURLOPT_CAINFO => "ca",
            CURLOPT_USERAGENT => "user_agent",
            CURLOPT_PROXY => "proxy_url",
            CURLOPT_PROXYPORT => "proxy_port",
        ];

        $curl_options = array_filter($possible_options, function ($option) use ($options) {
            return array_key_exists($option, $options);
        });

        foreach ($curl_options as $key => $value) {
            $curl_options[$key] = $options[$value];
        }

        if (!isset($curl_options[CURLOPT_CAINFO])) {
            $curl_options[CURLOPT_CAINFO] = DEFAULT_CA_CERTS;
        };

        if ($curl_options[CURLOPT_CAINFO] == "IGNORE") {
            unset($curl_options[CURLOPT_CAINFO]);
        };

        // Mandatory configuration options
        $curl_options[CURLOPT_RETURNTRANSFER] = 1;
        $curl_options[CURLOPT_FOLLOWLOCATION] = 1;
        $curl_options[CURLOPT_SSL_VERIFYPEER] = true;
        $curl_options[CURLOPT_SSL_VERIFYHOST] = 2;

        curl_setopt_array($this->ch, $curl_options);
    }

    public function execute($url, $method, $headers, $body = null)
    {
        assert(is_string($url));
        assert(is_string($method));
        assert(is_array($headers));
        assert(is_string($body) || is_null($body));

        $headers = array_map(function ($key, $value) {
            return sprintf("%s: %s", $key, $value);
        }, array_keys($headers), array_values($headers));

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === "POST") {
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, null);
        } elseif ($method === "GET") {
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, null);
        } else {
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $result = curl_exec($this->ch);

        $http_status_code = null;
        $success = true;
        if ($result === false) {
            $error = curl_error($this->ch);
            $errno = curl_errno($this->ch);

            /**
             * We could simply leave the result as FALSE and return that, but
             * let's convert it to what looks like an actual Duo web response.
             * This is beneficial because it simplifies the two error cases
             * we expect:
             *
             *  1. We had some sort of malformed request and Duo rejected it.
             *
             *  2. We couldn't reach Duo (this is the case we'd expect to
             *     return FALSE).
             */
            $result = json_encode(
                [
                    'stat' => 'FAIL',
                    'code' => $errno,
                    'message' => $error,
                ]
            );
            $success = false;
        } else {
            $http_status_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        }

        return [
            "response" => $result,
            "success" => $success,
            "http_status_code" => $http_status_code
        ];
    }
}
