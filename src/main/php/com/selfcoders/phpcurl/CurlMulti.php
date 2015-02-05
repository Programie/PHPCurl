<?php
namespace com\selfcoders\phpcurl;

class CurlMulti
{
	/**
	 * @var array An array holding all Curl instances
	 */
	private $curlInstances;

	public function __construct()
	{
		$this->curlInstances = array();
	}

	/**
	 * Add a Curl instance to this Curl Multi instance
	 *
	 * @param Curl $curlInstance Instance of Curl
	 * @param string|null $name An optional name which will be used as key in the instance array
	 *
	 * @return mixed The name of the instance (same as $name or the index in array if name is not given)
	 */
	public function addInstance(Curl $curlInstance, $name = null)
	{
		if ($name)
		{
			$this->curlInstances[$name] = $curlInstance;
		}
		else
		{
			$this->curlInstances[] = $curlInstance;
		}

		end($this->curlInstances);
		return key($this->curlInstances);
	}

	/**
	 * Set the given cURL option for all previously added cURL instances.
	 *
	 * @param int $option The cURL option to set (CURLOPT_* constants)
	 * @param mixed $value The new value for the option
	 *
	 * @see curl_setopt
	 */
	public function setOpt($option, $value)
	{
		/**
		 * @var Curl $instance
		 */
		foreach ($this->curlInstances as $instance)
		{
			$instance->setOpt($option, $value);
		}
	}

	/**
	 * Set multiple cURL options for all previously added cURL instances.
	 *
	 * @param array $options An array containing the option as key and the new value as the element's value
	 *
	 * @see curl_setopt_array
	 */
	public function setOptsAsArray($options)
	{
		/**
		 * @var Curl $instance
		 */
		foreach ($this->curlInstances as $instance)
		{
			$instance->setOptsAsArray($options);
		}
	}

	/**
	 * Execute the requests of all given Curl instances
	 *
	 * @param array $instances An array of Curl instances (key = name of instance)
	 * @param int $retryCount The maximum number of retries
	 * @param int $retryWait Time in seconds to wait between each try
	 */
	private function subExec($instances, $retryCount = 0, $retryWait = 0)
	{
		$curlMultiInstance = curl_multi_init();

		/**
		 * @var $curlInstance Curl
		 */
		foreach ($instances as $name => $curlInstance)
		{
			curl_multi_add_handle($curlMultiInstance, $curlInstance->getHandle());
		}

		do
		{
			curl_multi_exec($curlMultiInstance, $stillRunning);
			curl_multi_select($curlMultiInstance);
		}
		while ($stillRunning);

		for ($retry = 1; $retry <= $retryCount; $retry++)
		{
			$retryRequired = false;

			/**
			 * @var $curlInstance Curl
			 */
			foreach ($instances as $curlInstance)
			{
				if (!$curlInstance->isSuccessful())
				{
					$retryRequired = true;
					break;
				}
			}

			if (!$retryRequired)
			{
				break;
			}

			sleep($retryWait);

			$this->retryFailed($instances);
		}

		/**
		 * @var $curlInstance Curl
		 */
		foreach ($instances as $curlInstance)
		{
			$curlInstance->setContent(curl_multi_getcontent($curlInstance->getHandle()));
		}

		curl_multi_close($curlMultiInstance);
	}

	/**
	 * Execute the requests of all added Curl instances
	 *
	 * @param int|null $maxRequests The maximum number of concurrent requests (null = infinite)
	 * @param int $retryCount The maximum number of retries
	 * @param int $retryWait Time in seconds to wait between each try
	 */
	public function exec($maxRequests = null, $retryCount = 0, $retryWait = 0)
	{
		if ($maxRequests == null or $maxRequests <= 0)
		{
			$this->subExec($this->curlInstances, $retryCount, $retryWait);
			return;
		}

		$instanceChunks = array_chunk($this->curlInstances, $maxRequests, true);

		foreach ($instanceChunks as $index => $instances)
		{
			$this->subExec($instances, $retryCount, $retryWait);
		}
	}

	/**
	 * Get the response content of the specified instance given by the name
	 *
	 * @param mixed $instanceName The name of the instance as returned by addInstance
	 *
	 * @return string The response content of the request
	 */
	public function getContent($instanceName)
	{
		/**
		 * @var $instance Curl
		 */
		$instance = $this->curlInstances[$instanceName];

		return $instance->getContent();
	}

	/**
	 * Get the Curl instance of the specified instance given by the name
	 *
	 * @param mixed $instanceName The name of the instances as returned by addInstance
	 *
	 * @return Curl The instance object
	 */
	public function getInstance($instanceName)
	{
		return $this->curlInstances[$instanceName];
	}

	/**
	 * Get an array of all Curl instances
	 *
	 * @return array An array containing all Curl instances
	 */
	public function getInstances()
	{
		return $this->curlInstances;
	}

	/**
	 * Check whether the specified curl instance is available
	 *
	 * @param mixed $instanceName The name of the instances as returned by addInstance
	 *
	 * @return bool Whether the instance exists (true) or not (false)
	 */
	public function isInstance($instanceName)
	{
		return isset($this->curlInstances[$instanceName]);
	}

	/**
	 * Remove the specified instance given by the name
	 *
	 * @param mixed $instanceName The name of the instances as returned by addInstance
	 *
	 * $return Curl The removed instance
	 */
	public function removeInstance($instanceName)
	{
		$curlInstance = $this->curlInstances[$instanceName];

		unset($this->curlInstances[$instanceName]);

		return $curlInstance;
	}

	/**
	 * Retry all failed requests of the given array of Curl instances.
	 *
	 * @param array $curlInstances An array of Curl instances of which failed ones should be retried
	 *
	 * @return int The number of failed requests which were retried.
	 */
	public function retryFailed($curlInstances)
	{
		$failed = 0;

		$curlMultiInstance = new CurlMulti();

		/**
		 * @var $curlInstance Curl
		 */
		foreach ($curlInstances as $curlInstance)
		{
			if ($curlInstance->isSuccessful())
			{
				continue;
			}

			$curlInstance->setRetryCount($curlInstance->getRetryCount() + 1);

			$curlMultiInstance->addInstance($curlInstance);

			$failed++;
		}

		$curlMultiInstance->exec();

		return $failed;
	}
}