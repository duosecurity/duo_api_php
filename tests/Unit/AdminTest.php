<?php
namespace Unit;

class AdminTest extends BaseTest
{
    // https://www.duosecurity.com/docs/adminapi#/integrations
    protected static function getSuccessfulIntegrationsResponse()
    {
        $successful_integrations_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "enroll_policy" => "enroll",
                    "greeting" => "",
                    "groups_allowed" => [],
                    "integration_key" => "DIRWIH0ZZPV4G88B37VQ",
                    "ip_whitelist" => [
                        0 => "192.0.2.8",
                        1 => "198.51.100.0-198.51.100.20",
                        2 => "203.0.113.0/24",
                    ],
                    "ip_whitelist_enroll_policy" => "enforce",
                    "name" => "Integration for the web server",
                    "notes" => "",
                    "secret_key" => "QO4ZLqQVRIOZYkHfdPDORfcNf8LeXIbCWwHazY7o",
                    "type" => "websdk",
                    "trusted_device_days" => 0,
                    "username_normalization_policy" => "None",
                ],
            ]),
            "success" => true,
        ];

        return $successful_integrations_response;
    }

    public function testIntegrationsWhenSuccessful()
    {
        $successful_response = self::getSuccessfulIntegrationsResponse();

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->integrations("IKEYIKEYIKEYIKEYIKEY");

        $expected_response = json_decode($successful_response["response"], true)["response"];
        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertEquals($expected_response, $result["response"]["response"]);
    }

    public function testIntegrationsWhenUnsuccessfulResponse()
    {
        $unsuccessful_response = self::getUnsuccessfulResponse();

        $admin_client = self::getMockedClient("Admin", $unsuccessful_response, $paged = false);

        $result = $admin_client->integrations();

        $expected_response = json_decode($unsuccessful_response["response"], true);
        $this->assertTrue($result["success"]);
        $this->assertEquals($expected_response, $result["response"]);
    }
}
