<?php

class AccountsTest extends PHPUnit_Framework_TestCase {

    // https://duo.com/support/documentation/adminapi#api-details
    protected function getUnsuccessfulResponse() {
        $unsuccessful_api_response = array(
            "response" => json_encode(array(
                "stat" => "FAIL",
                "code" => 40002,
                "message" => "Invalid request parameters",
                "message_detail" => "username"
            )),
            "success" => TRUE,
        );

        return $unsuccessful_api_response;
    }

    protected function getUnsuccessfulMockedAccountsClient() {
        $unsuccessful_api_response = self::getUnsuccessfulResponse();

        $curl_mock = $this->getMockBuilder('DuoAPI\CurlRequester')
                          ->setMethods(array('execute', 'options'))
                          ->disableOriginalConstructor()
                          ->getMock();

        $curl_mock->method('execute')
                  ->willReturn($unsuccessful_api_response);

        $nop = function(...$params) { return; };
        $curl_mock->method('options')
                  ->will($this->returnCallback($nop));

        $client = new DuoAPI\Accounts(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $curl_mock
        );

        return $client;
    }

    public function testListAccounts() {
        $client = self::getUnsuccessfulMockedAccountsClient();
        $result = $client->list_accounts();

        $this->assertEquals($result["response"]["stat"], "FAIL");
    }

    public function testCreateAccount() {
        $client = self::getUnsuccessfulMockedAccountsClient();
        $result = $client->create_account("username");

        $this->assertEquals($result["response"]["stat"], "FAIL");
    }

    public function testDeleteAccount() {
        $client = self::getUnsuccessfulMockedAccountsClient();
        $result = $client->delete_account("userid");

        $this->assertEquals($result["response"]["stat"], "FAIL");
    }

}

?>
