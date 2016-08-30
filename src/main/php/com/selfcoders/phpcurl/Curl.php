<?php
namespace com\selfcoders\phpcurl;

class Curl
{
    /**
     * @var string The content returned from the requested URL (Only available if CURLOPT_RETURNTRANSFER is set to true)
     */
    private $content;
    /**
     * @var resource Curl resource handler
     */
    private $handle;
    /**
     * @var resource File handle used for header file writing (Temporary file)
     */
    private $headerFileHandle;
    /**
     * @var array An array of HTTP status codes which should be handled as OK
     */
    private $okHttpStatusCodes;
    /**
     * @var string The old URL (As specified in constructor)
     */
    private $oldUrl;
    /**
     * @var int The number of retries used
     */
    private $retryCount;
    /*
     * @var resource File handle used for verbose file writing (Temporary file)
     */
    private $verboseFileHandle;

    /**
     * @param string $url URL which should be called
     */
    public function __construct($url)
    {
        $this->oldUrl = $url;
        $this->handle = curl_init($url);
        $this->retryCount = 0;

        $this->okHttpStatusCodes = range(100, 299);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close the internal cURL handle.
     */
    public function close()
    {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
    }

    /**
     * Return the last error number.
     *
     * @return int The error number or 0 (zero) if no error occurred.
     */
    public function getErrorNumber()
    {
        return curl_errno($this->handle);
    }

    /**
     * Return a string containing the last error for the current session.
     *
     * @return string The error message or '' (the empty string) if no error occurred.
     */
    public function getErrorString()
    {
        return curl_error($this->handle);
    }

    /**
     * Get the initial URL passed to the constructor.
     *
     * @return string The same string as passed as $url of the constructor
     */
    public function getOldUrl()
    {
        return $this->oldUrl;
    }

    /**
     * Perform the cURL session.
     *
     * @return mixed True on success or false on failure. However, if the CURLOPT_RETURNTRANSFER option is set, it will return the result on success, false on failure.
     */
    public function exec()
    {
        $this->content = curl_exec($this->handle);

        return $this->content;
    }

    /**
     * Get the content of the cURL session.
     * This is the same output as of the last call of curl_exec.
     *
     * @return string The content of the cURL session
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get the internal cURL handle as returned by curl_init.
     *
     * @return resource The cURL handle
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Get information regarding a specific transfer.
     *
     * @param int|null $option The option to get or null to get all information.
     *
     * @return mixed If $option is given, returns its value as a string. Otherwise, returns an associative array.
     */
    public function getInfo($option = null)
    {
        if ($option) {
            return curl_getinfo($this->handle, $option);
        } else {
            return curl_getinfo($this->handle);
        }
    }

    /**
     * Get the content of the header output if enabled with enabledHeaderOutput.
     *
     * @return array|null The header output or null if header output was not enabled
     */
    public function getHeaderContent()
    {
        if (!$this->headerFileHandle) {
            return null;
        }

        fseek($this->headerFileHandle, 0);

        $lines = array();
        while ($line = trim(fgets($this->headerFileHandle), "\n\r")) {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Get the content of the verbose output if enabled with enableVerboseOutput.
     *
     * @return array|null The verbose output or null if verbose mode was not enabled
     */
    public function getVerboseContent()
    {
        if (!$this->verboseFileHandle) {
            return null;
        }

        fseek($this->verboseFileHandle, 0);

        $lines = array();
        while ($line = trim(fgets($this->verboseFileHandle), "\n\r")) {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Get the handle of the temporary header output file.
     *
     * @return resource|null The file handle of the temporary file or null if not enabled
     */
    public function getHeaderFileHandle()
    {
        return $this->headerFileHandle;
    }

    /**
     * Get the handle of the temporary verbose output file.
     *
     * @return resource|null The file handle of the temporary file or null if not enabled
     */
    public function getVerboseFileHandle()
    {
        return $this->verboseFileHandle;
    }

    /**
     * Set the content of the cURL session.
     *
     * This is normally only called by CurlMulti.
     *
     * @param string $content The content of the cURL session
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Set the given cURL option.
     *
     * @param int $option The cURL option to set (CURLOPT_* constants)
     * @param mixed $value The new value for the option
     *
     * @return bool true on success or false on failure
     *
     * @see curl_setopt
     */
    public function setOpt($option, $value)
    {
        return curl_setopt($this->handle, $option, $value);
    }

    /**
     * Set multiple cURL options.
     *
     * @param array $options An array containing the option as key and the new value as the element's value
     *
     * @return bool true if all options were successfully set. If an option could not be successfully set, false is immediately returned, ignoring any future options in the options array
     *
     * @see curl_setopt_array
     */
    public function setOptsAsArray($options)
    {
        return curl_setopt_array($this->handle, $options);
    }

    /**
     * Set a list of HTTP status codes which should be handled as OK.
     *
     * @param array $statusCodes The array containing the status codes (e.g. [200, 201])
     */
    public function setOkHttpStatusCodes($statusCodes)
    {
        $this->okHttpStatusCodes = $statusCodes;
    }

    /**
     * Get a list of HTTP status codes which should be handled as OK.
     *
     * @return array The array containing the status codes (e.g. [200, 201])
     */
    public function getOkHttpStatusCodes()
    {
        return $this->okHttpStatusCodes;
    }

    /**
     * Enable writing header information to a temporary file which can be retrieved later using getHeaderContent.
     */
    public function enableHeaderOutput()
    {
        $this->headerFileHandle = tmpfile();
        $this->setOpt(CURLOPT_WRITEHEADER, $this->headerFileHandle);
    }

    /**
     * Set all future requests of this instance to verbose mode.
     *
     * The content of the verbose output can be retrieved later using getVerboseContent.
     */
    public function enableVerboseOutput()
    {
        $this->verboseFileHandle = tmpfile();
        $this->setOpt(CURLOPT_VERBOSE, true);
        $this->setOpt(CURLOPT_STDERR, $this->verboseFileHandle);
    }

    /**
     * Get the total number of retries of this instance.
     *
     * @return int The number of retries
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * Set the total number of retries of this instance.
     *
     * This is called by CurlMulti.
     *
     * @param int $count The new number of retries
     */
    public function setRetryCount($count)
    {
        $this->retryCount = $count;
    }

    /**
     * Check whether the last request of this instance was successful.
     *
     * This method checks the HTTP status code against a defined list of status codes.
     *
     * @return bool true if the request was successful, false otherwise
     */
    public function isSuccessful()
    {
        return in_array($this->getInfo(CURLINFO_HTTP_CODE), $this->okHttpStatusCodes);
    }

    /**
     * Retry if the previous request failed (returned a status code not defined in okHttpStatusCodes).
     *
     * @return bool Whether the request has been retried (true) or not (false)
     */
    public function retryIfFailed()
    {
        if ($this->isSuccessful()) {
            return false;
        }

        $this->exec();
        $this->retryCount++;

        return true;
    }
}