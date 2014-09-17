<?php

/**
 * File       script.php
 * Created    3/31/14 9:35 PM
 * Author     Matt Thomas | matt@betweenbrain.com | http://betweenbrain.com
 * Support    https://github.com/betweenbrain/
 * Copyright  Copyright (C) 2014 betweenbrain llc. All Rights Reserved.
 * License    GNU GPL v2 or later
 */
class PlgSystemFreegeoipInstallerScript
{

	/**
	 * Constructor.
	 *
	 * @internal param object $subject The object to observe
	 * @internal param array $config An optional associative array of configuration settings.
	 *
	 * @since    0.2
	 */
	public function __construct()
	{
		$this->app = JFactory::getApplication();
		$this->db  = JFactory::getDbo();

	}

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @param $type
	 * @param $parent
	 *
	 * @return void
	 *
	 * @since    0.2
	 */
	function postflight($type, $parent)
	{

		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName(array('extension_id', 'element', 'folder')))
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('freegeoip') . ' AND ' . $this->db->quoteName('enabled') . ' = ' . $this->db->quote('0'));
		$this->db->setQuery($query);
		$results = $this->db->loadObjectList('extension_id');

		foreach ($results as $id => $extension)
		{
			$query->update($this->db->quoteName('#__extensions'))
				->set($this->db->quoteName('enabled') . ' = ' . $this->db->quote('1'))
				->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($id) . ' AND ' . $this->db->quoteName('enabled') . ' = ' . $this->db->quote('0')
				);

			$this->db->setQuery($query);

			if ($this->db->query() == 1)
			{
				JFactory::getApplication()->enqueueMessage(ucfirst($extension->element) . ' ' . ucfirst($extension->folder) . ' plugin has been installed and enabled.', 'notice');
			}
		}
	}
}
