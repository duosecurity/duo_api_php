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
            "http_status_code" => 200,
        ];

        return $successful_preauth_response;
    }

    protected static function getSuccessfulAuthResponse()
    {
        return [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "result" => "waiting",
                    "status" => "pushed",
                    "status_msg" => "Pushed a login request to your phone...",
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];
    }

    protected static function getSuccessfulAuthStatusResponse()
    {
        return [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "result" => "waiting",
                    "status" => "pushed",
                    "status_msg" => "Pushed a login request to your phone...",
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];
    }

    public function testPingCall()
    {
        $successful_ping_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "time" => 1357020061,
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $auth_client = self::getMockedClient("Auth", $successful_ping_response, $paged = false);

        $result = $auth_client->ping();

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);
    }

    public function testCheckCall()
    {
        $successful_check_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "time" => 1357020061,
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $auth_client = self::getMockedClient("Auth", $successful_check_response, $paged = false);

        $result = $auth_client->check();

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);
    }

    public function testEnrollCall()
    {
        $successful_enroll_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "activation_barcode" => "https =>//api-eval.duosecurity.com/frame/qr?value=8LIRa5danrICkhHtkLxi-cKLu2DWzDYCmBwBHY2YzW5ZYnYaRxA",
                    "activation_code" => "duo =>//8LIRa5danrICkhHtkLxi-cKLu2DWzDYCmBwBHY2YzW5ZYnYaRxA",
                    "expiration" => 1357020061,
                    "user_id" => "DU94SWSN4ADHHJHF2HXT",
                    "username" => "49c6c3097adb386048c84354d82ea63d",
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $auth_client = self::getMockedClient("Auth", $successful_enroll_response, $paged = false);

        $result = $auth_client->enroll('testuser');

        $this->assertEquals($result["response"]["stat"], "OK");
    }

    public function testEnrollStatusCall()
    {
        $successful_enroll_status_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => "success",
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $auth_client = self::getMockedClient("Auth", $successful_enroll_status_response, $paged = false);

        $result = $auth_client->enroll_status('testuser', 'activation');

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);
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

    public function testAuthCall()
    {
        $successful_auth_response = self::getSuccessfulAuthResponse();
        $auth_client = self::getMockedClient("Auth", $successful_auth_response, $paged = false);

        // Set up the expectation that the auth call will be made with the
        // default increased timeout for auths.
        $auth_client->requester->expects($this->once())
            ->method('options')
            ->with($this->equalTo([
                "timeout" => 60,
            ]));

        $result = $auth_client->auth('testuser', 'passcode', ['passcode' => '123']);

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);

        // Make sure the original requester timeout got restored when the
        // auth was done
        $this->assertEquals($auth_client->options["timeout"], 10);
    }

    public function testAuthCallTimeout()
    {
        $successful_auth_response = self::getSuccessfulAuthResponse();
        $auth_client = self::getMockedClient("Auth", $successful_auth_response, $paged = false);

        $test_timeout = 120;
        // Set up the expectation that the auth call will be made with the
        // timeout passed to auth()
        $auth_client->requester->expects($this->once())
            ->method('options')
            ->with($this->equalTo([
                "timeout" => $test_timeout,
            ]));


        $result = $auth_client->auth('testuser', 'passcode', ['passcode' => '123'], null, false, true, $test_timeout);

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);

        // Make sure the original requester timeout got restored when the
        // auth was done
        $this->assertEquals($auth_client->options["timeout"], 10);
    }

    public function testAuthCallNoRequesterTimeout()
    {
        $successful_auth_response = self::getSuccessfulAuthResponse();
        $auth_client = self::getMockedClient("Auth", $successful_auth_response, $paged = false);

        // Set up the expectation that the auth call will be made with the
        // default increased timeout for auths.
        $auth_client->requester->expects($this->once())
            ->method('options')
            ->with($this->equalTo([
                "timeout" => 60,
            ]));

        // Test that we still use the default timeout for the auth() method
        // even if someone blows away the timeout on the requester for some reason
        $auth_client->options = [];
        $result = $auth_client->auth('testuser', 'passcode', ['passcode' => '123']);

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);

        // Make sure the timeout got removed from the requester options when
        // the auth was done.
        $this->assertEquals($auth_client->options, []);

    }

    public function testAuthCallLargerRequesterTimeout()
    {
        $successful_auth_response = self::getSuccessfulAuthResponse();
        $auth_client = self::getMockedClient("Auth", $successful_auth_response, $paged = false);

        $test_timeout = 300;
        $auth_client->setRequesterOption("timeout", $test_timeout);

        // Set up the expectation that the auth call will be made with the
        // timeout on the requester since it's larger than auth()'s timeout
        // parameter
        $auth_client->requester->expects($this->once())
            ->method('options')
            ->with($this->equalTo([
                "timeout" => $test_timeout,
            ]));

        $result = $auth_client->auth('testuser', 'passcode', ['passcode' => '123']);

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);

        // The timeout on the requester should not have changed
        $this->assertEquals($auth_client->options["timeout"], $test_timeout);
    }

    public function testAuthStatusCall()
    {
        $successful_auth_status_response = self::getSuccessfulAuthStatusResponse();

        $auth_client = self::getMockedClient("Auth", $successful_auth_status_response, $paged = false);

        $result = $auth_client->auth_status('txidvalue');

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);
    }

    public function testAuthStatusHttpArguments()
    {
        $successful_auth_status_response = self::getSuccessfulAuthStatusResponse();
        $auth_client = self::getMockedClient("Auth", $successful_auth_status_response, $paged = false);

        // The actual test being performed is in the 'equalTo(...)' calls.
        $host = "api-duo.example.com";
        $txid = 'IDIDIDIDIDIDIDID';
        $auth_client->requester->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo("https://" . $host . "/auth/v2/auth_status?txid=" . $txid),
                $this->equalTo('GET'),
                $this->anything(),
                $this->anything()
            );

        $auth_client->auth_status($txid);
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
            "http_status_code" => 200,
        ];

        $auth_client = self::getMockedClient("Auth", $non_json_response, $paged = false);

        $result = $auth_client->logo();

        $this->assertIsString($result["response"]);
    }

    public function testLogoNotFound()
    {
        $unsuccessful_response = self::getUnsuccessfulResponse();

        $auth_client = self::getMockedClient("Auth", $unsuccessful_response, $paged = false);

        $result = $auth_client->logo();

        $this->assertIsArray($result);
    }
}
