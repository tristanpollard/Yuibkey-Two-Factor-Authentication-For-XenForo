<?php

class Sumo_Yubikey_Route_YubikeyAdmin implements XenForo_Route_Interface{

	 public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router){
		
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'user_id');
		return $router->getRouteMatch('Sumo_Yubikey_ControllerAdmin_Yubikey', $action, 'home');
        
	}
       
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		$param = 'user_id';
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, $param, 'title');
	}
	       
}