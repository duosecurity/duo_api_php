<?php
namespace SSL;

/*
 * This (https://jamielinux.com/docs/openssl-certificate-authority/) page
 * was extremely helpful for setting up the certificate testing environment.
 */

class SSLTest extends \PHPUnit_Framework_TestCase
{

    public function __construct()
    {
        $this->good_chain = dirname(__FILE__) . "/" . "ca-chain-self.cert.pem";
        $this->bad_chain = dirname(__FILE__) . "/" . "ca-chain-mozilla.cert.pem";
    }

    public static function setUpBeforeClass()
    {
        $silence = '>/dev/null 2>&1 & echo $!';

        $commands = array(
            sprintf("php -S %s", PHP_SERVER),
            sprintf("stunnel3 -d %s -r %s -p %s -P '' -f", GOOD_STUNNEL_SERVER, PHP_SERVER, dirname(__FILE__) . "/" . "good.pem"),
            sprintf("stunnel3 -d %s -r %s -p %s -P '' -f", SELF_SIGNED_STUNNEL_SERVER, PHP_SERVER, dirname(__FILE__) . "/" . "self.pem"),
            sprintf("stunnel3 -d %s -r %s -p %s -P '' -f", BAD_HOSTNAME_STUNNEL_SERVER, PHP_SERVER, dirname(__FILE__) . "/" . "badhost.pem"),
        );

        $pids = array();

        foreach ($commands as $command) {
            $output = array();
            exec($command . $silence, $output);
            $pid = (int) $output[0];
            array_push($pids, $pid);
        }

        // Allow processes to start
        sleep(1);

        register_shutdown_function(function () use ($pids) {
            foreach ($pids as $pid) {
                exec('kill ' . $pid);
            }
        });
    }

    public function pingSSLServer($requester, $host, $certificate)
    {
        $duo = new \DuoAPI\Auth(
            "IKEYIKEYIKEYIKEYIKEY",
            "SKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEYSKEY",
            $host,
            $requester
        );
        $duo->setRequesterOption("ca", $certificate);
        $result = $duo->ping();

        return $result;
    }

    /*
     * Test a custom certificate that was signed by our custom CA against
     * the certificate chain created by our custom CA.
     *
     * This test confirms the behavior we expect in a correctly implemented
     * SSL connection without allowing for environmental factors such as local
     * CA files.
     *
     * This test exercises peer verification.
     */
    public function testCorrectlySignedCertificateCurl()
    {
        $requester = new \DuoAPI\CurlRequester();
        $result = $this->pingSSLServer(
            $requester,
            GOOD_STUNNEL_SERVER,
            $this->good_chain
        );

        $this->assertTrue($result["success"]);
    }

    public function testCorrectlySignedCertificateFile()
    {
        $requester = new \DuoAPI\FileRequester();
        $result = $this->pingSSLServer(
            $requester,
            GOOD_STUNNEL_SERVER,
            $this->good_chain
        );

        /*
         * A '404' here is fine. We're simply trying to test if a good
         * SSL *connection* is made, there's not a fully implemented API
         * waiting for us on the other side of the connection.
         */
        $this->assertContains("404", $result["response"]["message"]);
    }

    /*
     * Test our custom certificate that was signed by our custom CA against
     * a third-party certificate chain.
     *
     * This test confirms that the selected certificate chain only allows
     * connections to servers with certificates we trust. This certificate
     * chain is valid for large parts of the Internet, but isn't valid for
     * the server we've selected.
     *
     * This test exercises peer verification.
     */
    public function testMismatchedCertificateCurl()
    {
        $requester = new \DuoAPI\CurlRequester();
        $result = $this->pingSSLServer(
            $requester,
            GOOD_STUNNEL_SERVER,
            $this->bad_chain
        );

        $this->assertFalse($result["success"]);
        $this->assertEquals($result["response"]["stat"], "FAIL");
        $this->assertEquals(
            $result["response"]["message"],
            "SSL certificate problem: unable to get local issuer certificate"
        );
    }

    public function testMismatchedCertificateFile()
    {
        $requester = new \DuoAPI\FileRequester();
        $result = $this->pingSSLServer(
            $requester,
            GOOD_STUNNEL_SERVER,
            $this->bad_chain
        );

        $this->assertFalse($result["success"]);
        $this->assertEquals($result["response"]["stat"], "FAIL");
        $this->assertContains(
            "failed to open stream: operation failed",
            $result["response"]["message"]
        );
    }

    /*
     * Test an unsigned certificate against our custom certificate chain.
     *
     * This test confirms that we only allow connections to servers hosting
     * signed certificates.
     *
     * This test exercises peer verification.
     */
    public function testSelfSignedCertificateCurl()
    {
        $requester = new \DuoAPI\CurlRequester();
        $result = $this->pingSSLServer(
            $requester,
            SELF_SIGNED_STUNNEL_SERVER,
            $this->good_chain
        );

        $this->assertFalse($result["success"]);
        $this->assertEquals($result["response"]["stat"], "FAIL");
        $this->assertEquals(
            $result["response"]["message"],
            "SSL certificate problem: self signed certificate"
        );
    }

    public function testSelfSignedCertificateFile()
    {
        $requester = new \DuoAPI\FileRequester();
        $result = $this->pingSSLServer(
            $requester,
            SELF_SIGNED_STUNNEL_SERVER,
            $this->good_chain
        );

        $this->assertFalse($result["success"]);
        $this->assertEquals($result["response"]["stat"], "FAIL");
        $this->assertContains(
            "failed to open stream: operation failed",
            $result["response"]["message"]
        );
    }

    /*
     * Test a custom certificate with an incorrect hostname that was signed
     * by our custom CA against the certificate chain created by our custom CA.
     *
     * This test confirms that we're verifying a servers hostname, even when
     * the certificate is signed by the correct CA.
     *
     * This test exercises hostname verification.
     */
    public function testCertificateBadHostnameCurl()
    {
        $requester = new \DuoAPI\CurlRequester();
        $result = $this->pingSSLServer(
            $requester,
            BAD_HOSTNAME_STUNNEL_SERVER,
            $this->good_chain
        );

        $this->assertFalse($result["success"]);
        $this->assertEquals($result["response"]["stat"], "FAIL");

        /*
         * The certificate was generated with hostname 'test'.
         */
        $this->assertEquals(
            $result["response"]["message"],
            "SSL: certificate subject name 'test' does not match target host name 'localhost'"
        );
    }

    public function testCertificateBadHostnameFile()
    {
        $requester = new \DuoAPI\FileRequester();
        $result = $this->pingSSLServer(
            $requester,
            BAD_HOSTNAME_STUNNEL_SERVER,
            $this->good_chain
        );

        $this->assertFalse($result["success"]);
        $this->assertEquals($result["response"]["stat"], "FAIL");
        $this->assertContains(
            "failed to open stream: operation failed",
            $result["response"]["message"]
        );
    }
}
