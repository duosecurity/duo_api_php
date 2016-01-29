<?php

class AdminTest extends PHPUnit_Framework_TestCase {

    // https://www.duosecurity.com/docs/adminapi#/integrations
    protected function getSuccessfulIntegrationsResponse() {
        $successful_integrations_response = array(
            "response" => json_encode(array(
                "stat" => "OK",
                "response" => array(
                    "enroll_policy" => "enroll",
                    "greeting" => "",
                    "groups_allowed" => array (),
                    "integration_key" => "DIRWIH0ZZPV4G88B37VQ",
                    "ip_whitelist" => array (
                        0 => "192.0.2.8",
                        1 => "198.51.100.0-198.51.100.20",
                        2 => "203.0.113.0/24",
                        ),
                    "ip_whitelist_enroll_policy" => "enforce",
                    "name" => "Integration for the web server",
                    "notes" => "",
                    "secret_key" => "QO4ZLqQVRIOZYkHfdPDORfcNf8LeXIbCWwHazY7o",
                    "type" => "websdk",
                    "trusted_device_days" => 0,
                    "username_normalization_policy" => "None",
                ),
            )),
            "success" => TRUE,
        );

        return $successful_integrations_response;
    }

    // https://www.duosecurity.com/docs/adminapi#base-url
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

      public function testIntegrations_whenSuccessful() {
        $successful_response = self::getSuccessfulIntegrationsResponse();

        $curl_mock = $this->getMockBuilder('DuoAPI\CurlRequester')
                          ->setMethods(array('execute', 'options'))
                          ->disableOriginalConstructor()
                          ->getMock();

        $curl_mock->method('execute')
                  ->willReturn($successful_response);

        $nop = function(...$params) { return; };
        $curl_mock->method('options')
                  ->will($this->returnCallback($nop));

        $duo = new DuoAPI\Admin(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $curl_mock
        );
        $result = $duo->integrations("IKEYIKEYIKEYIKEYIKEY");

        $expected_response = json_decode($successful_response["response"], TRUE)["response"];
        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertEquals($expected_response, $result["response"]["response"]);
    }

    public function testIntegrations_whenUnsuccessfulResponse() {
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

        $duo = new DuoAPI\Admin(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $curl_mock
        );
        $result = $duo->integrations();

        $expected_response = json_decode($unsuccessful_response["response"], TRUE);
        $this->assertTrue($result["success"]);
        $this->assertEquals($expected_response, $result["response"]);
    }

}

?>
