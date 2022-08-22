<?php
namespace com\selfcoders\phpcurl;

use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{
    public function testGetRequest()
    {
        $curl = new Curl("https://httpbin.org/get");

        $curl->setOptsAsArray(array
        (
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "PHPCurl"
        ));

        $curl->exec();

        $info = $curl->getInfo();

        $this->assertEquals("https://httpbin.org/get", $info["url"]);
        $this->assertEquals(200, $info["http_code"]);
        $this->assertTrue($curl->isSuccessful());

        $this->assertNull($curl->getHeaderFileHandle());
        $this->assertNull($curl->getVerboseFileHandle());

        $this->assertNull($curl->getHeaderContent());
        $this->assertNull($curl->getVerboseContent());
    }

    public function testRedirectRequestNonFollowing()
    {
        $curl = new Curl("https://httpbin.org/redirect/1");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->exec();

        $info = $curl->getInfo();

        $this->assertEquals("https://httpbin.org/redirect/1", $info["url"]);
        $this->assertEquals(302, $info["http_code"]);
    }

    public function testRedirectRequestFollowing()
    {
        $curl = new Curl("https://httpbin.org/redirect/1");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->exec();

        $info = $curl->getInfo();

        $this->assertEquals("https://httpbin.org/get", $info["url"]);
        $this->assertEquals(200, $info["http_code"]);
        $this->assertEquals("https://httpbin.org/redirect/1", $curl->getOldUrl());
    }

    public function testUnsuccessful()
    {
        $curl = new Curl("https://httpbin.org/status/418");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->exec();

        $info = $curl->getInfo();

        $this->assertEquals(418, $info["http_code"]);
        $this->assertFalse($curl->isSuccessful());
    }

    public function testRetryNonFailed()
    {
        $curl = new Curl("https://httpbin.org/status/200");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->exec();

        $this->assertEquals(0, $curl->getRetryCount());
        $this->assertFalse($curl->retryIfFailed());
        $this->assertEquals(0, $curl->getRetryCount());
    }

    public function testRetryFailed()
    {
        $curl = new Curl("https://httpbin.org/status/418");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->exec();

        $this->assertEquals(0, $curl->getRetryCount());
        $this->assertTrue($curl->retryIfFailed());
        $this->assertEquals(1, $curl->getRetryCount());
    }

    public function testHeaders()
    {
        $curl = new Curl("https://httpbin.org/status/202");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->enableHeaderOutput();

        $curl->exec();

        $this->assertIsResource($curl->getHeaderFileHandle());

        $headers = $curl->getHeaderContent();
        $this->assertStringContainsString("HTTP/2 202 ", $headers[0]);
    }

    public function testVerbose()
    {
        $curl = new Curl("https://httpbin.org/status/418");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->enableVerboseOutput();

        $curl->exec();

        $this->assertIsResource($curl->getVerboseFileHandle());

        $this->assertContains("> GET /status/418 HTTP/2", $curl->getVerboseContent());
    }

    public function testDnsError()
    {
        $curl = new Curl("https://not.existing");

        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curl->exec();

        $this->assertEquals(CURLE_COULDNT_RESOLVE_HOST, $curl->getErrorNumber());
        $this->assertStringContainsString("resolve host", $curl->getErrorString());
        $this->assertStringContainsString("not.existing", $curl->getErrorString());
    }

    public function testSettersGetters()
    {
        $curl = new Curl("https://example.com");

        $this->assertEquals("https://example.com", $curl->getOldUrl());

        $curl->setOkHttpStatusCodes(array(200, 400));
        $this->assertEquals(array(200, 400), $curl->getOkHttpStatusCodes());

        $curl->setContent("New content");
        $this->assertEquals("New content", $curl->getContent());

        $curl->setRetryCount(10);
        $this->assertEquals(10, $curl->getRetryCount());
    }
}