<?php
namespace Unit;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    public $mocked_curl_requester;
    public $random_numbers;
    public $mock_sleep_svc;

    public function setUp() : void
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

        // Map out the first 100 random numbers that will be called during tests
        srand(1);
        $this->random_numbers = [];
        foreach (range(0, 100) as $_) {
            array_push($this->random_numbers, rand(0, 1000));
        }
        srand(1);
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
        $client = $class->newInstanceArgs([
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            "api-duo.example.com",
            $this->mocked_curl_requester
        ]);

        $this->mock_sleep_svc = new MockSleepService();
        $client->sleep_service = $this->mock_sleep_svc;
        return $client;
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
            "http_status_code" => 400,
        ];

        return $unsuccessful_preauth_response;
    }
}

class MockSleepService implements \DuoAPI\SleepService
{
    public $sleep_calls = [];
    public function sleep($secs)
    {
        array_push($this->sleep_calls, $secs);
    }
}
