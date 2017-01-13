<?php
namespace Unit;

class AuthTest extends BaseTest
{
    // https://www.duosecurity.com/docs/authapi#/preauth
    protected static function getSuccessfulPreauthResponse()
    {
        $successful_preauth_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "result" => "auth",
                    "status_msg" => "Account is active",
                    "devices" => [
                        [
                            "device" => "DPFZRS9FB0D46QFTM891",
                            "type" => "phone",
                            "number" => "XXX-XXX-0100",
                            "name" => "",
                            "capabilities" => [
                                "push",
                                "sms",
                                "phone"
                            ]
                        ],
                        [
                            "device" => "DHEKH0JJIYC1LX3AZWO4",
                            "type" => "token",
                            "name" => "0"
                        ]
                    ],
                ]
            ]),
            "success" => true,
        ];

        return $successful_preauth_response;
    }

    public function testPreauthCall()
    {
        $successful_preauth_response = self::getSuccessfulPreauthResponse();

        $auth_client = self::getMockedClient("Auth", $successful_preauth_response, $paged = false);

        $result = $auth_client->preauth("testuser");

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertEquals($result["response"]["response"]["result"], "auth");
    }

    public function testPreauthHttpArguments()
    {
        $successful_preauth_response = self::getSuccessfulPreauthResponse();

        $curl_mock = $this->mocked_curl_requester;

        // The actual test being performed is in the 'equalTo(...)' calls.
        $host = "api-duo.example.com";
        $curl_mock->expects($this->once())
                  ->method('execute')
                  ->willReturn($successful_preauth_response)
                  ->with(
                      $this->equalTo("https://" . $host . "/auth/v2/preauth"),
                      $this->equalTo('POST'),
                      $this->anything(),
                      $this->anything()
                  );

        $duo = new \DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            $host,
            $curl_mock
        );
        $duo->preauth("testuser");
    }

    public function testAuthStatusHttpArguments()
    {
        $curl_mock = $this->mocked_curl_requester;

        $txid = 'IDIDIDIDIDIDIDID';

        // The actual test being performed is in the 'equalTo(...)' calls.
        $host = "api-duo.example.com";
        $curl_mock->expects($this->once())
                  ->method('execute')
                  ->with(
                      $this->equalTo("https://" . $host . "/auth/v2/auth_status?txid=" . $txid),
                      $this->equalTo('GET'),
                      $this->anything(),
                      $this->anything()
                  );

        $duo = new \DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            $host,
            $curl_mock
        );
        $duo->auth_status($txid);
    }

    public function testLogoHttpArguments()
    {
        $successful_preauth_response = self::getSuccessfulPreauthResponse();

        $curl_mock = $this->mocked_curl_requester;

        // The actual test being performed is in the 'equalTo(...)' calls.
        $host = "api-duo.example.com";
        $curl_mock->expects($this->once())
                  ->method('execute')
                  ->willReturn($successful_preauth_response)
                  ->with(
                      $this->equalTo("https://" . $host . "/auth/v2/logo"),
                      $this->equalTo('GET'),
                      $this->anything(),
                      $this->anything()
                  );

        $duo = new \DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            $host,
            $curl_mock
        );
        $duo->logo();
    }

    public function testLogoNonJson()
    {
        $non_json_response = [
            "response" => "NON JSON STRING",
            "success" => true,
        ];

        $auth_client = self::getMockedClient("Auth", $non_json_response, $paged = false);

        $result = $auth_client->logo();

        $this->assertInternalType('string', $result["response"]);
    }

    public function testLogoNotFound()
    {
        $unsuccessful_response = self::getUnsuccessfulResponse();

        $auth_client = self::getMockedClient("Auth", $unsuccessful_response, $paged = false);

        $result = $auth_client->logo();

        $this->assertInternalType('array', $result);
    }
}
