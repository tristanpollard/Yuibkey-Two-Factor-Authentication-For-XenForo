<?php

class Sumo_Yubikey_Listener{
	
	public static function listen($class, array &$extend){
		
		if ($class == 'XenForo_ControllerAdmin_Login'){
			$extend[] = 'Sumo_Yubikey_ControllerAdmin_Login';
		}
		
	}
	
	public static function controller_dispatch_listen(XenForo_Controller $controller, $action){
		
		$visitor = XenForo_Visitor::getInstance();
		
		$options = XenForo_Application::get('options');
		
		if ($options->sumo_yubikey_forces){
			$currentClass = get_class($controller);
			if (stripos($currentClass, 'ControllerAdmin') !== false){
				if ($currentClass != 'Sumo_Yubikey_ControllerAdmin_Yubikey' && $action != 'Css'){
					if (!$visitor['yubikey_setup'])
					{
						throw $controller->responseException(
							$controller->responseReroute('Sumo_Yubikey_ControllerAdmin_Yubikey', 'manageyubikey')
						);
					}
				}
			}
		}
		
	}
	
	
}