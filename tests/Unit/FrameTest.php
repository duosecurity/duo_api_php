<?php
namespace Unit;

class FrameTest extends BaseTest
{
    protected static function getSuccessfulInitResponse()
    {
        $successful_init_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "txid" => "757ae7ee-ed89-4d07-b8c2-fff2d21b4d77",
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        return $successful_init_response;
    }

    protected static function getSuccessfulAuthResponse()
    {
        $successful_init_response = [
            "response" => json_encode([
                "stat" => "OK",
                "response" => [
                    "uname" => "testuser",
                    "ikey" => "ikeyikeyikeyikeyikeyikeyikeyikeyikeyikey",
                    "expire" => 300,
                ],
            ]),
            "success" => true,
            "http_status_code" => 200,
        ];

        return $successful_init_response;
    }

    public function testInitCall()
    {
        $successful_init_response = self::getSuccessfulInitResponse();

        $frame_client = self::getMockedClient("Frame", $successful_init_response);

        $result = $frame_client->init("username", "blob", 300, "version");

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);
    }

    public function testAuthResponseCall()
    {
        $txid = "757ae7ee-ed89-4d07-b8c2-fff2d21b4d77";
        $successful_auth_response = self::getSuccessfulAuthResponse();

        $frame_client = self::getMockedClient("Frame", $successful_auth_response);

        $result = $frame_client->auth_response($txid);

        $this->assertEquals($result["response"]["stat"], "OK");
        $this->assertTrue($result["success"]);
    }
}
