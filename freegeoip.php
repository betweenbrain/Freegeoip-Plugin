<?php defined('_JEXEC') or die;

/**
 * File       freegeoip.php
 * Created    9/17/14 9:20 AM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2014 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v2 or later
 */
class plgSystemFreegeoip extends JPlugin
{

	/**
	 * Constructor.
	 *
	 * @param   object &$subject The object to observe
	 * @param   array  $config   An optional associative array of configuration settings.
	 *
	 * @since   0.1
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app     = JFactory::getApplication();
		$this->session = JFactory::getSession();

		// Load the language file on instantiation
		$this->loadLanguage();
	}

	/**
	 * Event triggered after the framework has loaded and the application initialise method has been called.
	 *
	 * @return bool
	 */
	function onAfterInitialise()
	{
		if ($this->app->isSite())
		{
			if (!$this->session->get('freegeoip_ip'))
			{
				$this->setFreegeoip();
			}
		}

		return true;
	}

	/**
	 * Checks all the things in order to try and get the user's IP
	 *
	 * @return string
	 */
	private function getUserIP()
	{

		if (isset($_SERVER))
		{

			if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			{
				return filter_var($_SERVER["HTTP_X_FORWARDED_FOR"], FILTER_VALIDATE_IP);
			}

			if (isset($_SERVER["HTTP_CLIENT_IP"]))
			{
				return filter_var($_SERVER["HTTP_CLIENT_IP"], FILTER_VALIDATE_IP);
			}

			return filter_var($_SERVER["REMOTE_ADDR"], FILTER_VALIDATE_IP);
		}

		if (getenv('HTTP_X_FORWARDED_FOR'))
		{
			return filter_var(getenv('HTTP_X_FORWARDED_FOR'), FILTER_VALIDATE_IP);
		}

		if (getenv('HTTP_CLIENT_IP'))
		{
			return filter_var(getenv('HTTP_CLIENT_IP'), FILTER_VALIDATE_IP);
		}

		return filter_var(getenv('REMOTE_ADDR'), FILTER_VALIDATE_IP);
	}

	/**
	 * Gets the Freegeoip data about the user
	 *
	 * @return mixed
	 */
	private function getFreegeoip()
	{
		$altProvider = rtrim($this->params->get('altProvider', ''), '/');
		$debugIp     = $this->params->get('debugIp');
		$ipAddress   = ($debugIp) ? $debugIp : $this->getUserIP();

		$curl = curl_init();

		if ($altProvider)
		{
			curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL            => $altProvider . '/json/' . $ipAddress,
					CURLOPT_FAILONERROR    => true,
					CURLOPT_TIMEOUT        => 1
				)
			);
			$response = curl_exec($curl);
		}

		if (!$altProvider || ($altProvider && curl_getinfo($curl, CURLINFO_HTTP_CODE) != '200'))
		{
			curl_setopt_array($curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL            => 'http://freegeoip.net/json/' . $ipAddress,
					CURLOPT_FAILONERROR    => true,
					CURLOPT_TIMEOUT        => 1
				)
			);
			$response = curl_exec($curl);
		}

		if (!curl_exec($curl))
		{
			$this->addLogEntry(JText::sprintf('PLG_SYSTEM_FREEGEOIP_CURL_ERROR', curl_error($curl), curl_errno($curl)));
		}

		curl_close($curl);

		return $response;
	}

	/**
	 * Adds entry to a log file
	 *
	 * @param $entry
	 */
	private function addLogEntry($entry)
	{
		JLog::addLogger(
			array(
				// Sets file name
				'text_file' => 'plg_freegeoip.errors.php'
			),
			// Sets messages of all log levels to be sent to the file
			JLog::WARNING,
			// The log category/categories which should be recorded in this file
			array(
				'plg_freegeopip'
			)
		);

		JLog::add($entry, JLog::WARNING, 'plg_freegeopip');
	}

	/**
	 * Sets the Freegeoip data in the user's session
	 *
	 * @return bool
	 */
	private function setFreegeoip()
	{
		$enableDiagnostic = $this->params->get('enableDiagnostic');
		$response         = $this->getFreegeoip();

		if ($response)
		{
			foreach (json_decode($response) as $key => $value)
			{
				$this->session->set('freegeoip_' . $key, $value);

				if ($enableDiagnostic)
				{
					$this->addLogEntry('freegeoip_' . $key . ': ' . $value);
				}
			}

			return true;
		}

		if (!$response && $enableDiagnostic)
		{
			$this->addLogEntry(JText::_('PLG_SYSTEM_FREEGEOIP_NO_RESPONSE'));
		}

		return false;
	}
}
