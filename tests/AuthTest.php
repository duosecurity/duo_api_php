<?php

class AuthTest extends PHPUnit_Framework_TestCase {

    // https://www.duosecurity.com/docs/authapi#/preauth
    protected function getSuccessfulPreauthResponse() {
        $successful_preauth_response = array(
            "response" => json_encode(array(
                "stat" => "OK",
                "response" => array(
                    "result" => "auth",
                    "status_msg" => "Account is active",
                    "devices" => array(
                        array(
                            "device" => "DPFZRS9FB0D46QFTM891",
                            "type" => "phone",
                            "number" => "XXX-XXX-0100",
                            "name" => "",
                            "capabilities" => [
                                "push",
                                "sms",
                                "phone"
                            ]
                        ),
                        array(
                            "device" => "DHEKH0JJIYC1LX3AZWO4",
                            "type" => "token",
                            "name" => "0"
                        )
                    ),
                )
            )),
            "success" => TRUE,
        );

        return $successful_preauth_response;
    }

    // https://www.duosecurity.com/docs/authapi#base-url
    protected function getUnsuccessfulResponse() {
        $unsuccessful_preauth_response = array(
            "response" => json_encode(array(
                "stat" => "FAIL",
                "code" => 40002,
                "message" => "Invalid request parameters",
                "message_detail" => "username"
            )),
            "success" => TRUE,
        );

        return $unsuccessful_preauth_response;
    }

    public function testPreauthCall() {
        $successful_preauth_response = self::getSuccessfulPreauthResponse();

        $curl_mock = $this->getMockBuilder('DuoAPI\CurlRequester')
                          ->setMethods(array('execute', 'options'))
                          ->disableOriginalConstructor()
                          ->getMock();

        $curl_mock->method('execute')
                  ->willReturn($successful_preauth_response);

        $nop = function(...$params) { return; };
        $curl_mock->method('options')
                  ->will($this->returnCallback($nop));

        $duo = new DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $curl_mock
        );
        $result = $duo->preauth("testuser");

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertEquals($result["response"]["response"]["result"], "auth");
    }

    public function testPreauthHttpArguments() {
        $successful_preauth_response = self::getSuccessfulPreauthResponse();

        $curl_mock = $this->getMockBuilder('DuoAPI\CurlRequester')
                          ->setMethods(array('execute', 'options'))
                          ->disableOriginalConstructor()
                          ->getMock();

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

        $nop = function(...$params) { return; };
        $curl_mock->method('options')
                  ->will($this->returnCallback($nop));

        $duo = new DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            $host,
            $curl_mock
        );
        $duo->preauth("testuser");
    }

    public function testLogoHttpArguments() {
        $successful_preauth_response = self::getSuccessfulPreauthResponse();

        $curl_mock = $this->getMockBuilder('DuoAPI\CurlRequester')
                          ->setMethods(array('execute', 'options'))
                          ->disableOriginalConstructor()
                          ->getMock();

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

        $nop = function(...$params) { return; };
        $curl_mock->method('options')
                  ->will($this->returnCallback($nop));

        $duo = new DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            $host,
            $curl_mock
        );
        $duo->logo();
    }

    public function testLogoNonJson() {
        $non_json_response = array(
            "response" => "NON JSON STRING",
            "success" => TRUE,
        );

        $curl_mock = $this->getMockBuilder('DuoAPI\CurlRequester')
                          ->setMethods(array('execute', 'options'))
                          ->disableOriginalConstructor()
                          ->getMock();

        $curl_mock->method('execute')
                  ->willReturn($non_json_response);

        $nop = function(...$params) { return; };
        $curl_mock->expects($this->once())
                  ->method('options')
                  ->will($this->returnCallback($nop));

        $duo = new DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $curl_mock
        );
        $result = $duo->logo();
        $this->assertInternalType('string', $result["response"]);
    }

    public function testLogoNotFound() {
        $unsuccessful_response = self::getUnsuccessfulResponse();

        $curl_mock = $this->getMockBuilder('DuoAPI\CurlRequester')
                          ->setMethods(array('execute', 'options'))
                          ->disableOriginalConstructor()
                          ->getMock();

        $curl_mock->method('execute')
                  ->willReturn($unsuccessful_response);

        $nop = function(...$params) { return; };
        $curl_mock->expects($this->once())
                  ->method('options')
                  ->will($this->returnCallback($nop));

        $duo = new DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $curl_mock
        );
        $result = $duo->logo();
        $this->assertInternalType('array', $result);
    }

}

?>
