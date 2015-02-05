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

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_RESOURCE, $curl->getHandle());

		$this->assertNull($curl->getHeaderFileHandle());
		$this->assertNull($curl->getVerboseFileHandle());
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
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/status/418");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->exec();

		$info = $curl->getInfo();

		$this->assertEquals(418, $info["http_code"]);
		$this->assertFalse($curl->isSuccessful());
	}

	public function testRetryNonFailed()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/status/200");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->exec();

		$this->assertEquals(0, $curl->getRetryCount());
		$this->assertFalse($curl->retryIfFailed());
		$this->assertEquals(0, $curl->getRetryCount());
	}

	public function testRetryFailed()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/status/418");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->exec();

		$this->assertEquals(0, $curl->getRetryCount());
		$this->assertTrue($curl->retryIfFailed());
		$this->assertEquals(1, $curl->getRetryCount());
	}

	public function testHeaders()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/status/418");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->enableHeaderOutput();

		$curl->exec();

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_RESOURCE, $curl->getHeaderFileHandle());

		$this->assertStringStartsWith("HTTP/1.1 418 I'M A TEAPOT", $curl->getHeaderContent());
	}

	public function testVerbose()
	{
		$curl = new \com\selfcoders\phpcurl\Curl("http://httpbin.org/status/418");

		$curl->setOpt(CURLOPT_RETURNTRANSFER, true);
		$curl->setOpt(CURLOPT_USERAGENT, "PHPCurl");

		$curl->enableVerboseOutput();

		$curl->exec();

		$this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_RESOURCE, $curl->getVerboseFileHandle());

		$this->assertContains("> GET /status/418 HTTP/1.1", $curl->getVerboseContent());
	}
}