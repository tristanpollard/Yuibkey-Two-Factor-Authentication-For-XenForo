<?php

class Sumo_Yubikey_Install{
	
	
	public static function install(){
	
		$db = XenForo_Application::get('db');
		
		self::addColumnIfNotExist($db, 'xf_user_authenticate', 'yubikey_token', 'VARCHAR(32)');
		
		self::addColumnIfNotExist($db, 'xf_user', 'yubikey_setup', 'TINYINT(1)');
	
	}
	
	public static function uninstall(){
	
		$db = XenForo_Application::get('db');
		
		self::deleteColumnIfExist($db, 'xf_user', 'yubikey_setup');
		
		self::deleteColumnIfExist($db, 'xf_user_authenticate', 'yubikey_token');
	
	}
	
	public static function addColumnIfNotExist($db, $table, $field, $attr)
	{
		if ($db->fetchRow('SHOW columns FROM `'.$table.'` WHERE Field = ?', $field))
		{
			return false;
		}

		return $db->query("ALTER TABLE `".$table."` ADD `".$field."` ".$attr);
	}

	public static function deleteColumnIfExist($db, $table, $field)
	{
		if ($db->fetchRow('SHOW columns FROM `'.$table.'` WHERE Field = ?', $field))
		{
			return $db->query("ALTER TABLE `".$table."` DROP `".$field."`");
		}

		return false;
	}


}