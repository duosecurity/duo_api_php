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
        $params = [];

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function check()
    {
        $method = "GET";
        $endpoint = "/auth/v2/check";
        $params = [];

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function logo()
    {
        $method = "GET";
        $endpoint = "/auth/v2/logo";
        $params = [];

        return self::apiCall($method, $endpoint, $params);
    }

    public function enroll($username = null, $valid_secs = null)
    {
        assert(is_string($username) || is_null($username));
        assert(is_int($valid_secs) || is_null($valid_secs));

        $method = "POST";
        $endpoint = "/auth/v2/enroll";
        $params = [];

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
        assert(is_string($user_id));
        assert(is_string($activation_code));

        $method = "POST";
        $endpoint = "/auth/v2/enroll_status";
        $params = [
            "user_id" => $user_id,
            "activation_code" => $activation_code,
        ];

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function preauth(
        $user_identifier,
        $ipaddr = null,
        $trusted_device_token = null,
        $username = true
    ) {
        assert(is_string($user_identifier));
        assert(is_string($ipaddr) || is_null($ipaddr));
        assert(is_string($trusted_device_token) || is_null($trusted_device_token));
        assert(is_bool($username));

        $method = "POST";
        $endpoint = "/auth/v2/preauth";
        $params = [];

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
        $username = true,
        $timeout = 60
    ) {
        assert(is_string($user_identifier));
        assert(
            is_string($factor) &&
            in_array($factor, ["auto", "push", "passcode", "sms", "phone"], true)
        );
        assert(is_array($factor_params));
        assert(is_string($ipaddr) || is_null($ipaddr));
        assert(is_bool($async));
        assert(is_bool($username));

        $method = "POST";
        $endpoint = "/auth/v2/auth";
        $params = [];

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
            assert(array_key_exists("device", $factor_params) && is_string($factor_params["device"]));
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
            assert(array_key_exists("passcode", $factor_params) && is_string($factor_params["passcode"]));
            $params["passcode"] = $factor_params["passcode"];
        } elseif ($factor === "phone") {
            assert(array_key_exists("device", $factor_params) && is_string($factor_params["device"]));
            $params["device"] = $factor_params["device"];
        } elseif ($factor === "sms") {
            assert(array_key_exists("device", $factor_params) && is_string($factor_params["device"]));
            $params["device"] = $factor_params["device"];
        } elseif ($factor === "auto") {
            assert(array_key_exists("device", $factor_params) && is_string($factor_params["device"]));
            $params["device"] = $factor_params["device"];
        }

        // For auth calls, use the timeout provided if it's greater than the
        // requester timeout to allow for sufficient time to respond to 2FA
        $requester_timeout = array_key_exists("timeout", $this->options) ? $this->options["timeout"] : null;
        if (!$requester_timeout || $requester_timeout < $timeout) {
            self::setRequesterOption("timeout", $timeout);
        }

        try {
            $result = self::jsonApiCall($method, $endpoint, $params);
        } finally {
            // If the requester had a timeout set, restore it. Otherwise delete
            // the timeout we set just for this auth call.
            if ($requester_timeout) {
                self::setRequesterOption("timeout", $requester_timeout);
            } else {
                unset($this->options["timeout"]);
            }
        }

        return $result;
    }

    public function auth_status($txid)
    {
        assert(is_string($txid));

        $method = "GET";
        $endpoint = "/auth/v2/auth_status";
        $params = [
            "txid" => $txid,
        ];

        return self::jsonApiCall($method, $endpoint, $params);
    }
}
