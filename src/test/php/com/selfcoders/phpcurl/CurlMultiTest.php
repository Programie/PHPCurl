<?php
namespace com\selfcoders\phpcurl;

use PHPUnit_Framework_TestCase;

class CurlMultiTest extends PHPUnit_Framework_TestCase
{
    public function testAddRemoveInstance()
    {
        $curlMulti = new CurlMulti();

        $curl0 = new Curl("http://example.com");
        $curl1 = new Curl("http://example.com");
        $curl2 = new Curl("http://example.com");
        $curlCustomKey = new Curl("http://example.com");

        $this->assertEquals(0, $curlMulti->addInstance($curl0));
        $this->assertEquals(1, $curlMulti->addInstance($curl1));
        $this->assertEquals(2, $curlMulti->addInstance($curl2));

        $this->assertEquals("custom-key", $curlMulti->addInstance($curlCustomKey, "custom-key"));

        $this->assertCount(4, $curlMulti->getInstances());

        $this->assertTrue($curlMulti->isInstance(1));
        $this->assertEquals($curl1, $curlMulti->removeInstance(1));
        $this->assertFalse($curlMulti->isInstance(1));

        $this->assertCount(3, $curlMulti->getInstances());

        $this->assertEquals($curlCustomKey, $curlMulti->getInstance("custom-key"));
    }

    public function testSimpleExec()
    {
        $curlMulti = new CurlMulti();

        $curl1 = new Curl("http://httpbin.org/get");
        $curl2 = new Curl("http://httpbin.org/status/418");
        $curl3 = new Curl("http://httpbin.org/redirect/1");

        $curlMulti->addInstance($curl1, "curl1");
        $curlMulti->addInstance($curl2, "curl2");
        $curlMulti->addInstance($curl3, "curl3");

        $curlMulti->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curlMulti->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curlMulti->exec();

        $this->assertEquals(200, $curlMulti->getInstance("curl1")->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(418, $curlMulti->getInstance("curl2")->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(302, $curlMulti->getInstance("curl3")->getInfo(CURLINFO_HTTP_CODE));

        $this->assertJson($curlMulti->getContent("curl1"));
    }

    public function testRetry()
    {
        $curlMulti = new CurlMulti();

        $curl1 = new Curl("http://httpbin.org/get");
        $curl2 = new Curl("http://httpbin.org/status/418");
        $curl3 = new Curl("http://httpbin.org/redirect/1");

        $curlMulti->addInstance($curl1, "curl1");
        $curlMulti->addInstance($curl2, "curl2");
        $curlMulti->addInstance($curl3, "curl3");

        $curlMulti->setOptsAsArray(array
        (
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "PHPCurl"
        ));

        $curlMulti->exec(null, 3);

        $curl = $curlMulti->getInstance("curl1");
        $this->assertEquals(200, $curl->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(0, $curl->getRetryCount());

        $curl = $curlMulti->getInstance("curl2");
        $this->assertEquals(418, $curl->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(3, $curl->getRetryCount());

        $curl = $curlMulti->getInstance("curl3");
        $this->assertEquals(302, $curl->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(3, $curl->getRetryCount());
    }

    public function testChunkedExec()
    {
        $curlMulti = new CurlMulti();

        $curl1 = new Curl("http://httpbin.org/get");
        $curl2 = new Curl("http://httpbin.org/status/418");
        $curl3 = new Curl("http://httpbin.org/redirect/1");
        $curl4 = new Curl("http://httpbin.org/redirect/2");
        $curl5 = new Curl("http://httpbin.org/redirect/3");
        $curl6 = new Curl("http://httpbin.org/redirect/4");

        $curl6->setOpt(CURLOPT_FOLLOWLOCATION, true);

        $curlMulti->addInstance($curl1, "curl1");
        $curlMulti->addInstance($curl2, "curl2");
        $curlMulti->addInstance($curl3, "curl3");
        $curlMulti->addInstance($curl4, "curl4");
        $curlMulti->addInstance($curl5, "curl5");
        $curlMulti->addInstance($curl6, "curl6");

        $curlMulti->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curlMulti->setOpt(CURLOPT_USERAGENT, "PHPCurl");

        $curlMulti->exec(2);

        $this->assertEquals(200, $curlMulti->getInstance("curl1")->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(418, $curlMulti->getInstance("curl2")->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(302, $curlMulti->getInstance("curl3")->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(302, $curlMulti->getInstance("curl4")->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(302, $curlMulti->getInstance("curl5")->getInfo(CURLINFO_HTTP_CODE));
        $this->assertEquals(200, $curlMulti->getInstance("curl6")->getInfo(CURLINFO_HTTP_CODE));
    }
}