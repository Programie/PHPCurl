<?php
namespace com\selfcoders\phpcurl;

class CurlMultiTest extends \PHPUnit_Framework_TestCase
{
	public function testAddInstance()
	{
		$curlMulti = new CurlMulti();

		$this->assertEquals(0, $curlMulti->addInstance(new Curl("http://example.com")));
		$this->assertEquals(1, $curlMulti->addInstance(new Curl("http://example.com")));
		$this->assertEquals(2, $curlMulti->addInstance(new Curl("http://example.com")));

		$this->assertEquals("custom-key", $curlMulti->addInstance(new Curl("http://example.com"), "custom-key"));
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

		$curlMulti->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curlMulti->setOpt(CURLOPT_USERAGENT, "PHPCurl");

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
}