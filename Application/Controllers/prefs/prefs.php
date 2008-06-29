<?php

class prefsController extends baseController {
	
	public function set($params)
	{
		if (empty($_GET['st']) || empty($_GET['name']) || !isset($_GET['value'])) {
			header("HTTP/1.0 400 Bad Request", true);
			echo "<html><body><h1>400 - Bad Request</h1></body></html>";
			die();
		}
		try {
			$st = urldecode(base64_decode($_GET['st']));
			$key = urldecode($_GET['name']);
			$value = urldecode($_GET['value']);
			$token = BasicSecurityToken::createFromToken($st, Config::get('st_max_age'));
			$app_id = $token->getAppId();
			$mod_id = $token->getModuleId();
			$viewer = $token->getViewerId();
			$apps = $this->model('applications');
			$apps->set_application_pref($viewer, $app_id, $mod_id, $key, $value);
		} catch (Exception $e) {
			header("HTTP/1.0 400 Bad Request", true);
			echo "<html><body><h1>400 - Bad Request</h1>".$e->getMessage()."</body></html>";
			die();
		}
	}
	
}
