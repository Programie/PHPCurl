<?php
class CurlTest extends PHPUnit_Framework_TestCase
{
	public function testGetRequest()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/get");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->exec();

		$info = $curl->getInfo();

		$this->assertEquals("http://httpbin.org/get", $info["url"]);
		$this->assertEquals(200, $info["http_code"]);
		$this->assertTrue($curl->isSuccessful());
	}

	public function testRedirectRequestNonFollowing()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/redirect/1");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->exec();

		$info = $curl->getInfo();

		$this->assertEquals("http://httpbin.org/redirect/1", $info["url"]);
		$this->assertEquals(302, $info["http_code"]);
	}

	public function testRedirectRequestFollowing()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/redirect/1");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->exec();

		$info = $curl->getInfo();

		$this->assertEquals("http://httpbin.org/get", $info["url"]);
		$this->assertEquals(200, $info["http_code"]);
		$this->assertEquals("http://httpbin.org/redirect/1", $curl->getOldUrl());
	}

	public function testUnsuccessful()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/status/500");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->exec();

		$info = $curl->getInfo();

		$this->assertEquals(500, $info["http_code"]);
		$this->assertFalse($curl->isSuccessful());
	}
}