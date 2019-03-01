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
            "http_status_code" => 200,
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

    public function testPagedIntegrationsCall()
    {
        $successful_response = [[
            "response" => json_encode([
                "stat" => "OK",
                "response" => ['integration1', 'integration2'],
                "metadata" => [
                    "next_offset" => 2
                ]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ],[
            "response" => json_encode([
                "stat" => "OK",
                "response" => ['integration3', 'integration4'],
                "metadata" => [
                ]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ]];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = true);

        $result = $admin_client->integrations();

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
        $this->assertEquals($result['response']['response'], ['integration1', 'integration2', 'integration3', 'integration4']);
    }

    public function testUsersCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [[
                  "user_id" => "DU3RP9I2WOC59VZX672N",
                  "username" => "jsmith",
                  "realname" => "Joe Smith",
                  "email" => "jsmith@example.com",
                  "status" => "active",
                  "groups" => [[
                    "desc" => "People with hardware tokens",
                    "name" => "token_users",
                  ]],
                  "last_login" => 1343921403,
                  "notes" => "",
                  "phones" => [[
                    "phone_id" => "DPFZRS9FB0D46QFTM899",
                    "number" => "+15555550100",
                    "extension" => "",
                    "name" => "",
                    "postdelay" => null,
                    "predelay" => null,
                    "type" => "Mobile",
                    "capabilities" => [
                      "sms",
                      "phone",
                      "push",
                    ],
                    "platform" => "Apple iOS",
                    "activated" => false,
                    "sms_passcodes_sent" => false,
                  ]],
                  "tokens" => [[
                    "serial" => "0",
                    "token_id" => "DHIZ34ALBA2445ND4AI2",
                    "type" => "d1",
                  ]]
                ]]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->users("username");

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }

    public function testPagesUsersCall()
    {
        $successful_response = [[
            "response" => json_encode([
                "stat" => "OK",
                "response" => ['user1', 'user2'],
                "metadata" => [
                  "next_offset" => 10
                ]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ],[
            "response" => json_encode([
                "stat" => "OK",
                "response" => ['user3', 'user4'],
                "metadata" => [
                ]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ]];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = true);

        $result = $admin_client->users();

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
        $this->assertEquals($result['response']['response'], ['user1', 'user2', 'user3', 'user4']);
    }

    public function testCreateUserCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                  "user_id" => "DU3RP9I2WOC59VZX672N",
                  "username" => "jsmith",
                  "realname" => "Joe Smith",
                  "email" => "jsmith@example.com",
                  "status" => "active",
                  "groups" => [[
                    "desc" => "People with hardware tokens",
                    "name" => "token_users",
                  ]],
                  "last_login" => 1343921403,
                  "notes" => "",
                  "phones" => [[
                    "phone_id" => "DPFZRS9FB0D46QFTM899",
                    "number" => "+15555550100",
                    "extension" => "",
                    "postdelay" => null,
                    "predelay" => null,
                    "type" => "Mobile",
                    "capabilities" => [
                      "sms",
                      "phone",
                      "push",
                    ],
                    "platform" => "Apple iOS",
                    "activated" => false,
                    "sms_passcodes_sent" => false,
                  ]],
                  "tokens" => [[
                    "serial" => "0",
                    "token_id" => "DHIZ34ALBA2445ND4AI2",
                    "type" => "d1",
                  ]],
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->create_user("username");

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }

    public function testCreatePhoneCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                  "phone_id" => "DPFZRS9FB0D46QFTM899",
                  "number" => "+15555550100",
                  "name" => "",
                  "extension" => "",
                  "postdelay" => null,
                  "predelay" => null,
                  "type" => "Mobile",
                  "capabilities" => [
                    "sms",
                    "phone",
                    "push",
                  ],
                  "platform" => "Apple iOS",
                  "activated" => false,
                  "sms_passcodes_sent" => false,
                  "users" => [[
                    "user_id" => "DUJZ2U4L80HT45MQ4EOQ",
                    "username" => "jsmith",
                    "realname" => "Joe Smith",
                    "email" => "jsmith@example.com",
                    "status" => "active",
                    "last_login" => 1343921403,
                    "notes" => "",
                  ]],
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->create_phone("8675309");

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }

    public function testUserAssociatePhoneCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => "",
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->user_associate_phone("user_id", "phone_id");

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }

    public function testUserAssociateTokenCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => "",
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->user_associate_token("user_id", "token_id");

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }

    public function testUserAssociateGroupCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => "",
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->user_associate_group("user_id", "group_id");

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }

    public function testGroupsCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [[
                  "desc" => "This is group A",
                  "group_id" => "DGXXXXXXXXXXXXXXXXXX",
                  "name" => "Group A",
                  "push_enabled" => true,
                  "sms_enabled" => true,
                  "status" => "active",
                  "voice_enabled" => true,
                  "mobile_otp_enabled" => true
                ],
                [
                  "desc" => "This is group B",
                  "group_id" => "DGXXXXXXXXXXXXXXXXXX",
                  "name" => "Group B",
                  "push_enabled" => true,
                  "sms_enabled" => true,
                  "status" => "active",
                  "voice_enabled" => true,
                  "mobile_otp_enabled" => true
                ]],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->groups("groupid");

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }

    public function testPagedGroupsCall()
    {
        $successful_response = [[
            "response" => json_encode([
                "stat" => "OK",
                "response" => ['group1', 'group2'],
                "metadata" => [
                    "next_offset" => 2
                ]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ],[
            "response" => json_encode([
                "stat" => "OK",
                "response" => ['group3', 'group4'],
                "metadata" => [
                ]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ]];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = true);

        $result = $admin_client->groups();

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
        $this->assertEquals(['group1', 'group2', 'group3', 'group4'], $result['response']['response']);
    }

    public function testSummaryCall()
    {
        $successful_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                  "admin_count" => 3,
                  "integration_count" => 9,
                  "telephony_credits_remaining" => 960,
                  "user_count" => 8
                ]
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        $admin_client = self::getMockedClient("Admin", $successful_response, $paged = false);

        $result = $admin_client->summary();

        $this->assertEquals("OK", $result["response"]["stat"]);
        $this->assertTrue($result["success"]);
    }
}
