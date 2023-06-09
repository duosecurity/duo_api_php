<?php
namespace DuoAPI;

class FileRequester implements Requester
{
    public $http_options;

    public function __construct()
    {
        $this->http_options = [
            "http" => [
                /*
                 * We'll handle HTTP errors on our own
                 */
                "ignore_errors" => true,
            ],
            "ssl" => [
                /*
                 * Disallow self-signed certificates
                 */
                "allow_self_signed" => false,
                /*
                 * Enforce CN verification
                 */
                "verify_peer" => true,
                /*
                 * Avoid compression (CRIME attack)
                 */
                "disable_compression" => true,
                /*
                 * Require good ciphers. View the list with:
                 *
                 *     openssl ciphers -v 'HIGH:!SSLv2:!SSLv3'
                 */
                "ciphers" => "HIGH:!SSLv2:!SSLv3",
            ],
        ];
    }

    public function __destruct()
    {
    }

    protected static function parse_http_response_header($headers)
    {
        /*
         * It's possible that there will be multiple HTTP status codes in
         * our array. For example, this can happen when we're redirected so
         * we receive a 301 then 200. We should take the last status code
         * received.
         */
        $status_code_regex = '#^HTTP/\d\.\d[ \t]+(?<http_status_code>\d+)#i';

        $status_code_headers = preg_grep($status_code_regex, $headers);
        $status_codes = array_map(
            function ($header) use ($status_code_regex) {
                $status_code = preg_match($status_code_regex, $header, $matches);
                return (int) $matches['http_status_code'];
            },
            $status_code_headers
        );

        return end($status_codes);
    }

    public function options($options)
    {
        assert(is_array($options));

        if (isset($options["user_agent"])) {
            $this->http_options["http"]["user_agent"] = $options["user_agent"];
        }
        if (isset($options["timeout"])) {
            $this->http_options["http"]["timeout"] = $options["timeout"];
        }
        if (isset($options["proxy_url"])) {
            $uri  = $options["proxy_url"];
            $uri .= (isset($options["proxy_port"]) ? ":" . $options["proxy_port"] : "");
            $this->http_options["http"]["proxy"] = $uri;
        }
        if (isset($options["ca"])) {
            $this->http_options["ssl"]["cafile"] = $options["ca"];
        }
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

        $this->http_options['http']['method'] = $method;
        $this->http_options['http']['header'] = $headers;

        if ($method === "POST") {
            $this->http_options['http']['content'] = $body;
        }

        $context = stream_context_create($this->http_options);

        $result = @file_get_contents($url, false, $context);

        $http_status_code = null;
        $success = true;
        if ($result === false) {
            $error = error_get_last();
            $errno = $error["type"];
            $message = $error["message"];

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
                    'message' => $message,
                ]
            );
            $success = false;
        } else {
            // https://secure.php.net/manual/en/reserved.variables.httpresponseheader.php
            $http_status_code = self::parse_http_response_header($http_response_header);
        }

        return [
            "response" => $result,
            "success" => $success,
            "http_status_code" => $http_status_code
        ];
    }
}
