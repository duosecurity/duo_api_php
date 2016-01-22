<?php
namespace DuoAPI;

/*
 * https://www.duosecurity.com/docs/accountsapi
 */

require_once("Client.php");

class Accounts extends Client {

    public function list_accounts() {
        $method = "POST";
        $endpoint = "/accounts/v1/account/list";
        $params = array();

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function create_account($name) {
        assert('is_string($name)');

        $method = "POST";
        $endpoint = "/accounts/v1/account/create";
        $params = array(
            "name" => $name,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }

    public function delete_account($account_id) {
        assert('is_string($account_id)');

        $method = "POST";
        $endpoint = "/accounts/v1/account/delete";
        $params = array(
            "account_id" => $account_id,
        );

        return self::jsonApiCall($method, $endpoint, $params);
    }

}

?>
