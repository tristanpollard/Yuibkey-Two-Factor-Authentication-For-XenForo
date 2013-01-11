<?php

class Sumo_Yubikey_Model_User extends XenForo_Model_User{
	
	public function verifyToken($userId, $yubikey_token){
		
		$db = $this->_getDb();
		
		$token = $this->getToken($userId);
		$token = $token['yubikey_token'];
		
		if ($this->generateToken($userId, $yubikey_token) == $token){
			return true;
		}
		
		return false;
		
	}
	
	public function getToken($userId){
		
		$db = $this->_getDb();
		
		$token = $db->fetchRow('SELECT yubikey_token FROM xf_user_authenticate WHERE user_id = ?', $userId);
		
		return $token;
		
	}
	
	public function generateToken($userId, $token){
		return md5($userId . '::' . $token);
		
	}
	
	public function saveToken($userId, $token){
		
		$db = $this->_getDb();
		
		if (strlen($token) != 32){
			return false;
		}
		
		$db->query('UPDATE xf_user_authenticate
		SET yubikey_token = ?
		WHERE user_id = ?', array($token, $userId));
		
		$this->setUserYubikeyActive($userId);
		
		return true;
	}
	
	public function setUserYubikeyActive($userId){
		$db = $this->_getDb();
		
		$db->query('UPDATE xf_user SET yubikey_setup = 1 WHERE user_id = ?', $userId);
	}
	
	public function setUserYubikeyInactive($userId){
		$db = $this->_getDb();
		
		$db->query('UPDATE xf_user SET yubikey_setup = 0 WHERE user_id = ?', $userId);
	}
}