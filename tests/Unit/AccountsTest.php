<?php
namespace Unit;

class AccountsTest extends BaseTest
{
    public function testListAccounts()
    {
        $unsuccessful_response = self::getUnsuccessfulResponse();

        $accounts_client = self::getMockedClient("Accounts", $unsuccessful_response, $paged = false);

        $result = $accounts_client->list_accounts();

        $this->assertEquals($result["response"]["stat"], "FAIL");
    }

    public function testCreateAccount()
    {
        $unsuccessful_response = self::getUnsuccessfulResponse();

        $accounts_client = self::getMockedClient("Accounts", $unsuccessful_response, $paged = false);

        $result = $accounts_client->create_account("username");

        $this->assertEquals($result["response"]["stat"], "FAIL");
    }

    public function testDeleteAccount()
    {
        $unsuccessful_response = self::getUnsuccessfulResponse();

        $accounts_client = self::getMockedClient("Accounts", $unsuccessful_response, $paged = false);

        $result = $accounts_client->delete_account("userid");

        $this->assertEquals($result["response"]["stat"], "FAIL");
    }
}
