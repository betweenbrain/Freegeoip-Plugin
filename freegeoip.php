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
	 * Gets the Freegeoip data about the user
	 *
	 * @return mixed
	 */
	private function getFreegeoip()
	{
		$debugIp   = $this->params->get('debugIp');
		$ipAddress = ($debugIp) ? $debugIp : $_SERVER['REMOTE_ADDR'];

		$curl = curl_init();
		curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL            => 'http://freegeoip.net/json/' . $ipAddress,
				CURLOPT_FAILONERROR    => true,
				CURLOPT_TIMEOUT        => 1
			)
		);
		$response = curl_exec($curl);

		if (!curl_exec($curl))
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

			JLog::add(JText::sprintf('PLG_SYSTEM_FREEGEOIP_CURL_ERROR', curl_error($curl), curl_errno($curl)), JLog::WARNING, 'plg_freegeopip');
		}

		curl_close($curl);

		return $response;
	}

	/**
	 * Sets the Freegeoip data in the user's session
	 *
	 * @return bool
	 */
	private function setFreegeoip()
	{
		$response = $this->getFreegeoip();

		if ($response)
		{
			foreach (json_decode($response) as $key => $value)
			{
				$this->session->set('freegeoip_' . $key, $value);
			}

			return true;

		}

		return false;
	}
}
