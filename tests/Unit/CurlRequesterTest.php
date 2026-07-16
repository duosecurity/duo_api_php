<?php
namespace Unit;

class TestableCurlRequester extends \DuoAPI\CurlRequester
{
    public $applied_options = [];

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
            $curl_options[CURLOPT_CAINFO] = DEFAULT_CA_CERTS;
        } elseif ($curl_options[CURLOPT_CAINFO] == "IGNORE") {
            unset($curl_options[CURLOPT_CAINFO]);
        }

        if (isset($curl_options[CURLOPT_CAINFO])) {
            $curl_options[CURLOPT_CAPATH] = "/dev/null/" . bin2hex(\random_bytes(16));
        }

        $curl_options[CURLOPT_RETURNTRANSFER] = 1;
        $curl_options[CURLOPT_FOLLOWLOCATION] = 1;
        $curl_options[CURLOPT_SSL_VERIFYPEER] = true;
        $curl_options[CURLOPT_SSL_VERIFYHOST] = 2;

        $this->applied_options = $curl_options;
        curl_setopt_array($this->ch, $curl_options);
    }
}

class CurlRequesterTest extends \PHPUnit\Framework\TestCase
{
    public function testDefaultOptionsIncludeCaCerts()
    {
        $requester = new TestableCurlRequester();
        $requester->options(["timeout" => 10]);

        $this->assertArrayHasKey(CURLOPT_CAINFO, $requester->applied_options);
        $this->assertEquals(DEFAULT_CA_CERTS, $requester->applied_options[CURLOPT_CAINFO]);
    }

    public function testDisableCaPinningRemovesCaInfo()
    {
        $requester = new TestableCurlRequester();
        $requester->options(["timeout" => 10, "disable_ca_pinning" => true]);

        $this->assertArrayNotHasKey(CURLOPT_CAINFO, $requester->applied_options);
    }

    public function testDisableCaPinningKeepsSslVerification()
    {
        $requester = new TestableCurlRequester();
        $requester->options(["timeout" => 10, "disable_ca_pinning" => true]);

        $this->assertTrue($requester->applied_options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertEquals(2, $requester->applied_options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testCustomCaIsUsed()
    {
        $requester = new TestableCurlRequester();
        $requester->options(["timeout" => 10, "ca" => "/custom/ca.pem"]);

        $this->assertEquals("/custom/ca.pem", $requester->applied_options[CURLOPT_CAINFO]);
    }

    public function testIgnoreCaRemovesCaInfo()
    {
        $requester = new TestableCurlRequester();
        $requester->options(["timeout" => 10, "ca" => "IGNORE"]);

        $this->assertArrayNotHasKey(CURLOPT_CAINFO, $requester->applied_options);
    }
}
