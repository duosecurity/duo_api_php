<?php
namespace DuoAPI;

/*
 * https://www.duosecurity.com/docs/adminapi
 */

class Admin extends Client
{

    /*
     * Values a user/group's status can be set to. Note that this is what
     * they can be SET to, there are additional values that can be retrieved.
     */
    private static $SET_STATUS = array("active", "bypass", "disabled");
    private function is_status($status)
    {
        return is_string($status) && in_array($status, self::$SET_STATUS);
    }

    /*
     * This API is only partially implemented. This is intended to serve as an
     * example. For more information regarding Duo's Admin API please visit
     * https://www.duosecurity.com/docs/adminapi.
     */

    public function users($username = null, $userid = false)
    {
        assert(is_string($username) || is_null($username));

        $method = "GET";
        $endpoint = "/admin/v1/users";
        $params = array();

        if ($username && !$userid) {
            $params["username"] = $username;
        } elseif ($username && $userid) {
            $endpoint .= ("/" . $username);
        }

        if ($this->paging) {
            return self::jsonPagingApiCall($method, $endpoint, $params);
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function create_user(
        $username,
        $realname = null,
        $email = null,
        $status = null,
        $notes = null
    ) {
        assert(is_string($username));
        assert(is_string($realname) || is_null($realname));
        assert(is_string($email) || is_null($email));
        assert(self::is_status($status) || is_null($status));
        assert(is_string($notes) || is_null($notes));

        $method = "POST";
        $endpoint = "/admin/v1/users";
        $params = array(
            "username" => $username,
        );

        if ($realname) {
            $params["realname"] = $realname;
        }
        if ($email) {
            $params["email"] = $email;
        }
        if ($status) {
            $params["status"] = $status;
        }
        if ($notes) {
            $params["notes"] = $notes;
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function create_phone(
        $number = null,
        $name = null,
        $extension = null,
        $type = null,
        $platform = null,
        $predelay = null,
        $postdelay = null
    ) {
        assert(is_string($number) || is_null($number));
        assert(is_string($name) || is_null($name));
        assert(is_string($extension) || is_null($extension));
        assert(is_string($type) || is_null($type));
        assert(is_string($platform) || is_null($platform));
        assert(is_string($predelay) || is_null($predelay));
        assert(is_string($postdelay) || is_null($postdelay));

        $method = "POST";
        $endpoint = "/admin/v1/phones";
        $params = array();

        if ($number) {
            $params["number"] = $number;
        }
        if ($name) {
            $params["name"] = $name;
        }
        if ($extension) {
            $params["extension"] = $extension;
        }
        if ($type) {
            $params["type"] = $type;
        }
        if ($platform) {
            $params["platform"] = $platform;
        }
        if ($predelay) {
            $params["predelay"] = $predelay;
        }
        if ($postdelay) {
            $params["postdelay"] = $postdelay;
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function user_associate_phone($userid, $phoneid)
    {
        assert(is_string($userid));
        assert(is_string($phoneid));

        $method = "POST";
        $endpoint = "/admin/v1/users/" . $userid . "/phones";
        $params = array(
            "phone_id" => $phoneid,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function user_associate_token($userid, $tokenid)
    {
        assert(is_string($userid));
        assert(is_string($tokenid));

        $method = "POST";
        $endpoint = "/admin/v1/users/" . $userid . "/tokens";
        $params = array(
            "token_id" => $tokenid,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function user_associate_group($userid, $groupid)
    {
        assert(is_string($userid));
        assert(is_string($groupid));

        $method = "POST";
        $endpoint = "/admin/v1/users/" . $userid . "/groups";
        $params = array(
            "group_id" => $groupid,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function groups($groupid = null)
    {
        $method = "GET";
        $endpoint = "/admin/v1/groups";
        $params = array();

        if ($groupid) {
            $endpoint .= ("/" . $groupid);
        }

        if ($this->paging) {
            return self::jsonPagingApiCall($method, $endpoint, $params);
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function integrations($ikey = null)
    {
        $method = "GET";
        $endpoint = "/admin/v1/integrations";
        $params = array();

        if ($ikey) {
            $endpoint .= ("/" . $ikey);
        }

        if ($this->paging) {
            return self::jsonPagingApiCall($method, $endpoint, $params);
        }

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function summary()
    {

        $method = "GET";
        $endpoint = "/admin/v1/info/summary";
        $params = array();

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function user_create_bypass($userid, $count = 10, $valid_secs = 0, $reuse_count = 1)
    {
        assert(is_string($username));
        assert(is_int($count));
        assert(is_int($valid_secs));
        assert(is_int($reuse_count));

        $method = 'POST';
        $endpoint = '/admin/v1/users/' . $userid . '/bypass_codes';
        $params = array(
            'count' => $count,
            'valid_secs' => $valid_secs,
            'reuse_count' => $reuse_count
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }
}
