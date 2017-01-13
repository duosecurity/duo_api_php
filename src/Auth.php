<?php
namespace DuoAPI;

/*
 * https://www.duosecurity.com/docs/authapi
 */

class Auth extends Client
{

    public function ping()
    {
        $method = "GET";
        $endpoint = "/auth/v2/ping";
        $params = array();

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function check()
    {
        $method = "GET";
        $endpoint = "/auth/v2/check";
        $params = array();

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function logo()
    {
        $method = "GET";
        $endpoint = "/auth/v2/logo";
        $params = array();

        return self::apiCall($method, $endpoint, $params);
    }

    public function enroll($username = null, $valid_secs = null)
    {
        assert('is_string($username) || is_null($username)');
        assert('is_int($valid_secs) || is_null($valid_secs)');

        $method = "POST";
        $endpoint = "/auth/v2/enroll";
        $params = array();

        if ($username) {
            $params["username"] = $username;
        }
        if ($valid_secs) {
            $params["valid_secs"] = $valid_secs;
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function enroll_status($user_id, $activation_code)
    {
        assert('is_string($user_id)');
        assert('is_string($activation_code)');

        $method = "POST";
        $endpoint = "/auth/v2/enroll_status";
        $params = array(
            "user_id" => $user_id,
            "activation_code" => $activation_code,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function preauth(
        $user_identifier,
        $ipaddr = null,
        $trusted_device_token = null,
        $username = true
    ) {
        assert('is_string($user_identifier)');
        assert('is_string($ipaddr) || is_null($ipaddr)');
        assert('is_string($trusted_device_token) || is_null($trusted_device_token)');
        assert('is_bool($username)');

        $method = "POST";
        $endpoint = "/auth/v2/preauth";
        $params = array();

        if ($username) {
            $params["username"] = $user_identifier;
        } else {
            $params["user_id"] = $user_identifier;
        }
        if ($ipaddr) {
            $params["ipaddr"] = $ipaddr;
        }
        if ($trusted_device_token) {
            $params["trusted_device_token"] = $trusted_device_token;
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function auth(
        $user_identifier,
        $factor,
        $factor_params,
        $ipaddr = null,
        $async = false,
        $username = true
    ) {
        assert('is_string($user_identifier)');
        assert('is_string($factor) && in_array($factor,
            array("auto", "push", "passcode", "sms", "phone"))');
        assert('is_array($factor_params)');
        assert('is_string($ipaddr) || is_null($ipaddr)');
        assert('is_bool($async)');
        assert('is_bool($username)');

        $method = "POST";
        $endpoint = "/auth/v2/auth";
        $params = array();

        if ($username) {
            $params["username"] = $user_identifier;
        } else {
            $params["user_id"] = $user_identifier;
        }
        if ($ipaddr) {
            $params["ipaddr"] = $ipaddr;
        }
        if ($async) {
            $params["async"] = "1";
        }

        $params["factor"] = $factor;
        if ($factor === "push") {
            assert('array_key_exists("device", $factor_params) && is_string($factor_params["device"])');
            $params["device"] = $factor_params["device"];

            if (array_key_exists("type", $factor_params)) {
                $params["type"] = $factor_params["type"];
            }
            if (array_key_exists("display_username", $factor_params)) {
                $params["display_username"] = $factor_params["display_username"];
            }
            if (array_key_exists("pushinfo", $factor_params)) {
                $params["pushinfo"] = $factor_params["pushinfo"];
            }
        } elseif ($factor === "passcode") {
            assert('array_key_exists("passcode", $factor_params) && is_string($factor_params["passcode"])');
            $params["passcode"] = $factor_params["passcode"];
        } elseif ($factor === "phone") {
            assert('array_key_exists("device", $factor_params) && is_string($factor_params["device"])');
            $params["device"] = $factor_params["device"];
        } elseif ($factor === "sms") {
            assert('array_key_exists("device", $factor_params) && is_string($factor_params["device"])');
            $params["device"] = $factor_params["device"];
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function auth_status($txid)
    {
        assert('is_string($txid)');

        $method = "GET";
        $endpoint = "/auth/v2/auth_status";
        $params = array(
            "txid" => $txid,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }
}
