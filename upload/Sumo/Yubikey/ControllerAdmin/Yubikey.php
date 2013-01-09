<?php

class Sumo_Yubikey_ControllerAdmin_Yubikey extends XenForo_ControllerAdmin_Abstract{
	
	public function actionIndex(){
		return $this->responseView('Sumo_Yubikey_ViewAdmin_Admin', 'yubikey_edit');
	}
	
	public function actionManageYubikey(){
		return $this->actionIndex();
	}
	
	public function actionUpdate(){
	
		$this->_assertPostOnly();
		
		$data = $this->_input->filter(array(
			'visitor_password' => XenForo_Input::STRING,
			'yubikey_otp' => XenForo_Input::STRING));
			
		$visitorPassword = $data['visitor_password'];
		$yubikey_otp = $data['yubikey_otp'];
			
		$this->getHelper('Admin')->assertVisitorPasswordCorrect($visitorPassword);
		
		$yubikey_model = $this->getModelFromCache('Sumo_Yubikey_Model_Yubikey');
		if (!$yubikey_model->verify($yubikey_otp)){
			return $this->responseError($yubikey_model->getLastResponse());
		}
		
		$yubikeyuser_model = $this->getModelFromCache('Sumo_Yubikey_Model_User');
		
		$visitor = XenForo_Visitor::getInstance();
		
		$userId = $visitor['user_id'];
		
		$token = substr($yubikey_otp, 0, 12);
		$save_token = $yubikeyuser_model->generateToken($userId, $token);
		$yubikeyuser_model->saveToken($userId, $save_token);
	
		return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				'admin.php'
		);
	}
	
}