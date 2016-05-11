<?php
namespace Unit;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->mocked_curl_requester = $this->getMockBuilder('\DuoAPI\CurlRequester')
                                            ->setMethods(['execute', 'options'])
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $nop = function (...$params) {
            return;

        };
        $this->mocked_curl_requester->method('options')
                                    ->will($this->returnCallback($nop));
    }

    protected function getMockedClient($client, $will_return = null, $paged = false)
    {
        if ($will_return) {
            if ($paged) {
                $this->mocked_curl_requester->method('execute')
                                            ->will($this->onConsecutiveCalls(...$will_return));
            } else {
                $this->mocked_curl_requester->method('execute')
                                            ->willReturn($will_return);
            }
        }

        $class = new \ReflectionClass(sprintf("\\DuoAPI\\%s", $client));
        return $class->newInstanceArgs([
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $this->mocked_curl_requester
        ]);
    }

    protected static function makeUnicode($s)
    {
        return json_decode('"' . $s . '"');
    }

    protected static function getUnsuccessfulResponse()
    {
        $unsuccessful_preauth_response = [
            "response" => json_encode([
                "stat" => "FAIL",
                "code" => 40002,
                "message" => "Invalid request parameters",
                "message_detail" => "username"
            ]),
            "success" => true,
        ];

        return $unsuccessful_preauth_response;
    }
}
