<?php
namespace DuoAPI;

/*
 * https://duo.com/docs/duoweb
 */

class Frame extends Client
{

    public function init($username, $app_blob, $expire, $client_version, $enroll_only = false)
    {
        assert(is_string($username));
        assert(is_string($app_blob));
        assert(is_int($expire));
        assert(is_string($client_version));
        assert(is_bool($enroll_only));

        $method = "POST";
        $endpoint = "/frame/init";
        $params = array(
            'user' => $username,
            'app_blob' => $app_blob,
            'expire' => $expire,
            'client_version' => $client_version,
        );

        if ($enroll_only) {
            $params['enroll_only'] = $enroll_only;
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function auth_response($response_txid)
    {
        assert(is_string($response_txid));

        $method = "POST";
        $endpoint = "/frame/auth_response";
        $params = array(
            'response_txid' => $response_txid,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }
}
