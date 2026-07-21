<?php
namespace Unit;

use PHPUnit\Framework\TestCase;
use DuoAPI\CurlRequester;

class CAPinningTestRequester extends CurlRequester
{
    public $lastCurlOptions = [];

    public function __construct()
    {
        $this->ch = \curl_init();
    }

    public function options($options)
    {
        assert(is_array($options));

        $possible_options = [
            CURLOPT_TIMEOUT => "timeout",
            CURLOPT_CAINFO => "ca",
            CURLOPT_USERAGENT => "user_agent",
            CURLOPT_PROXY => "proxy_url",
            CURLOPT_PROXYPORT => "proxy_port",
        ];

        $curl_options = array_filter($possible_options, function ($option) use ($options) {
            return array_key_exists($option, $options);
        });

        foreach ($curl_options as $key => $value) {
            $curl_options[$key] = $options[$value];
        }

        $ca_pinning_disabled = isset($options["disable_ca_pinning"]) && $options["disable_ca_pinning"];

        if ($ca_pinning_disabled) {
            unset($curl_options[CURLOPT_CAINFO]);
        } elseif (!isset($curl_options[CURLOPT_CAINFO])) {
            $curl_options[CURLOPT_CAINFO] = \DEFAULT_CA_CERTS;
        }

        if (isset($curl_options[CURLOPT_CAINFO])) {
            $capath = "/dev/null/" . \bin2hex(\random_bytes(16));
            $curl_options[CURLOPT_CAPATH] = $capath;
        }

        $curl_options[CURLOPT_RETURNTRANSFER] = 1;
        $curl_options[CURLOPT_FOLLOWLOCATION] = 1;
        $curl_options[CURLOPT_SSL_VERIFYPEER] = true;
        $curl_options[CURLOPT_SSL_VERIFYHOST] = 2;

        $this->lastCurlOptions = $curl_options;
    }
}

class CAPinningTest extends TestCase
{
    private const EXPECTED_CAPATH_PATTERN = '/^\/dev\/null\/[0-9a-f]{32}$/';

    private function getOptionsFromCall(array $inputOptions): array
    {
        $requester = new CAPinningTestRequester();
        $requester->options($inputOptions);
        return $requester->lastCurlOptions;
    }

    // ---------------------------------------------------------
    // CAPATH set when pinning is active (default)
    // ---------------------------------------------------------

    public function testCaPathSetToDevNullRandomWhenDefaultPinning(): void
    {
        $options = $this->getOptionsFromCall([]);

        $this->assertArrayHasKey(CURLOPT_CAPATH, $options);
        $this->assertMatchesRegularExpression(
            self::EXPECTED_CAPATH_PATTERN,
            $options[CURLOPT_CAPATH]
        );
    }

    public function testCaPathSetWhenCustomCaProvided(): void
    {
        $options = $this->getOptionsFromCall(["ca" => "/custom/ca.pem"]);

        $this->assertArrayHasKey(CURLOPT_CAPATH, $options);
        $this->assertMatchesRegularExpression(
            self::EXPECTED_CAPATH_PATTERN,
            $options[CURLOPT_CAPATH]
        );
    }

    // ---------------------------------------------------------
    // CAPATH NOT set when pinning is disabled
    // ---------------------------------------------------------

    public function testCaPathNotSetWhenDisableCaPinning(): void
    {
        $options = $this->getOptionsFromCall(["disable_ca_pinning" => true]);

        $this->assertArrayNotHasKey(CURLOPT_CAPATH, $options);
    }

    public function testCaPathNotSetWhenCaIsIgnoreViaCLient(): void
    {
        $client = new \DuoAPI\Client("IKEY", "SKEY", "host.example.com");
        $client->setRequesterOption("ca", "IGNORE");

        $this->assertTrue($client->options["disable_ca_pinning"]);
        $this->assertArrayNotHasKey("ca", $client->options);
    }

    // ---------------------------------------------------------
    // CAINFO behavior unchanged
    // ---------------------------------------------------------

    public function testDefaultCaInfoIsSetWhenNoCaOptionProvided(): void
    {
        $options = $this->getOptionsFromCall([]);

        $this->assertArrayHasKey(CURLOPT_CAINFO, $options);
        $this->assertEquals(\DEFAULT_CA_CERTS, $options[CURLOPT_CAINFO]);
    }

    public function testCaInfoNotSetWhenDisableCaPinning(): void
    {
        $options = $this->getOptionsFromCall(["disable_ca_pinning" => true]);

        $this->assertArrayNotHasKey(CURLOPT_CAINFO, $options);
    }

    public function testIgnoreNormalizesToDisableCaPinning(): void
    {
        $client = new \DuoAPI\Client("IKEY", "SKEY", "host.example.com");
        $client->setRequesterOption("ca", "IGNORE");

        $options = $this->getOptionsFromCall($client->options);
        $this->assertArrayNotHasKey(CURLOPT_CAINFO, $options);
        $this->assertArrayNotHasKey(CURLOPT_CAPATH, $options);
    }

    // ---------------------------------------------------------
    // SSL verification always enforced
    // ---------------------------------------------------------

    public function testSslVerifyPeerAlwaysTrue(): void
    {
        $options = $this->getOptionsFromCall([]);
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
    }

    public function testSslVerifyHostAlwaysTwo(): void
    {
        $options = $this->getOptionsFromCall([]);
        $this->assertEquals(2, $options[CURLOPT_SSL_VERIFYHOST]);
    }
}
