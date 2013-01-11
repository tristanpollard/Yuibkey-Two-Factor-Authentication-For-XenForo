<?php

class Sumo_Yubikey_ControllerAdmin_Login extends XenForo_ControllerAdmin_Login{

	public function actionForm()
	{
		$publicSession = XenForo_Session::getPublicSession($this->_request);
		if ($publicSession->get('user_id'))
		{
			$publicVisitor = $this->getModelFromCache('XenForo_Model_User')->getUserById($publicSession->get('user_id'));
			if ($publicVisitor)
			{
				XenForo_Visitor::getInstance()->setVisitorLanguage($publicVisitor['language_id']);
			}
		}
		else
		{
			$publicVisitor = false;
		}

		if ($this->_request->isPost())
		{
			$repost = true;
			$postVars = $_POST;
			if (!isset($postVars['redirect']))
			{
				$postVars['redirect'] = $this->getDynamicRedirect();
			}
		}
		else
		{
			$repost = false;
			$postVars = false;
		}

		$viewParams = array(
			'publicVisitor' => $publicVisitor,

			'repost' => $repost,
			'postVars' => $postVars
		);

		$containerParams = array(
			'containerTemplate' => 'LOGIN_PAGE'
		);

		return $this->responseView('XenForo_ViewAdmin_Login_Form', 'yubikey_login', $viewParams, $containerParams);
	}
	
	public function actionLogin()
	{
		if (!$this->_request->isPost())
		{
			return $this->responseRedirect(
				 XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				 XenForo_Link::buildAdminLink('index')
			);
		}

		$data = $this->_input->filter(array(
			'login' => XenForo_Input::STRING,
			'password' => XenForo_Input::STRING,
			'redirect' => XenForo_Input::STRING,
			'cookie_check' => XenForo_Input::UINT,
			'yubikey' => XenForo_Input::STRING
		));
		

		$redirect = ($data['redirect'] ? $data['redirect'] : XenForo_Link::buildAdminLink('index'));

		$loginModel = $this->_getLoginModel();

		if ($data['cookie_check'] && count($_COOKIE) == 0)
		{
			// login came from a page, so we should at least have a session cookie.
			// if we don't, assume that cookies are disabled
			return $this->responseError(new XenForo_Phrase('cookies_required_to_log_in_to_site'));
		}

		$needCaptcha = $loginModel->requireLoginCaptcha($data['login']);
		if ($needCaptcha)
		{
			// just block logins here instead of using the captcha
			return $this->responseError(new XenForo_Phrase('your_account_has_temporarily_been_locked_due_to_failed_login_attempts'));
		}

		$userModel = $this->_getUserModel();
		
		//Proccess YubiKey stuff
		
		$user = $userModel->getUserByNameOrEmail($data['login']);
		
		if (!$user['user_id']){
			return $this->responseError('Invalid User');
		}
		
		if ($user['yubikey_setup']){
		
			$yubikey_otp = $data['yubikey'];
			
			if ($yubikey_otp == ''){
				return $this->responseError('Yubikey Required!');
			}
			
			$yubikey_usermodel = $this->getModelFromCache('Sumo_Yubikey_Model_User');
			
			//Verify it is their key.
			$yubikey_token = substr($yubikey_otp, 0, 12);
			if (!$yubikey_usermodel->verifyToken($user['user_id'], $yubikey_token)){
				return $this->responseError('Invalid Verification Token!');
			}
			
			//Check if it's valid
			$yubikeyModel = $this->getModelFromCache('Sumo_Yubikey_Model_Yubikey');
			
			if (!$yubikeyModel->verify($yubikey_otp)){
				return $this->responseError('Failed to Verify! - ' . $yubikeyModel->getLastResponse());
			}
		}

		$userId = $userModel->validateAuthentication($data['login'], $data['password'], $error);
		if (!$userId)
		{
			$loginModel->logLoginAttempt($data['login']);

			if ($loginModel->requireLoginCaptcha($data['login']))
			{
				return $this->responseError(new XenForo_Phrase('your_account_has_temporarily_been_locked_due_to_failed_login_attempts'));
			}

			if ($this->_input->filterSingle('upgrade', XenForo_Input::UINT))
			{
				return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $redirect);
			}
			else
			{
				// note - JSON view will return responseError($text)
				return $this->responseView(
					'XenForo_ViewAdmin_Login_Error',
					'login_form',
					array(
						'text' => $error,
						'defaultLogin' => $data['login'],
						'redirect' => $redirect
					), array(
						'containerTemplate' => 'LOGIN_PAGE'
				));
			}
		}

		$loginModel->clearLoginAttempts($data['login']);

		XenForo_Model_Ip::log($userId, 'user', $userId, 'login_admin');

		XenForo_Application::get('session')->changeUserId($userId);
		XenForo_Visitor::setup($userId);

		// if guest on front-end, login there too
		$publicSession = new XenForo_Session();
		$publicSession->start();
		if (!$publicSession->get('user_id'))
		{
			$publicSession->changeUserId($userId);
			$publicSession->save();
		}

		$visitor = XenForo_Visitor::getInstance();

		// now check that the user will be able to get into the ACP (is_admin)
		if (!$visitor->is_admin)
		{
			return $this->responseError(new XenForo_Phrase('your_account_does_not_have_admin_privileges'));
		}

		if ($this->_input->filterSingle('repost', XenForo_Input::UINT))
		{
			$postVars = $this->_input->filterSingle('postVars', XenForo_Input::JSON_ARRAY);
			$postVars['_xfToken'] = $visitor['csrf_token_page'];

			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $redirect, '', array(
				'repost' => 1,
				'postVars' => $postVars
			));
		}
		else
		{
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, $redirect);
		}
	}
	
}