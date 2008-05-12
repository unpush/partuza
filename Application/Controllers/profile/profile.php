<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

class profileController extends baseController {
	
	public function index($params)
	{
		$id = isset($params[2]) && is_numeric($params[2]) ? $params[2] : false;
		if (! $id) {
			//TODO add a proper 404 / profile not found here
			header("Location: /");
			die();
		}
		$people = $this->model('people');
		$person = $people->get_person($id, true);
		$friends = $people->get_friends($id);
		$friend_requests = isset($_SESSION['id']) ? $people->get_friend_requests($_SESSION['id']) : array();
		$apps = $this->model('applications');
		$applications = $apps->get_person_applications($id);
		$this->template('profile/profile.php', array('applications' => $applications, 'person' => $person, 'friend_requests' => $friend_requests, 'friends' => $friends, 'is_owner' => isset($_SESSION['id']) ? ($_SESSION['id'] == $id) : false));
	}
	
	public function preview($params)
	{
		if (!isset($params[3]) || !is_numeric($params[3])) {
			header("Location: /");
			die();
		}
		$app_id = intval($params[3]);
		$people = $this->model('people');
		$person = isset($_SESSION['id']) ? $people->get_person($_SESSION['id'], true) : false;
		$apps = $this->model('applications');
		$application = $apps->get_application_by_id($app_id);
		$applications = isset($_SESSION['id']) ? $apps->get_person_applications($_SESSION['id']) : array();
		$this->template('applications/application_preview.php', array('applications' => $applications, 'application' => $application, 'person' => $person, 'is_owner' => true));
	}
	
	public function application($params)
	{
		$id = isset($params[3]) && is_numeric($params[3]) ? $params[3] : false;
		if (! $id || (! isset($params[4]) || ! is_numeric($params[4])) || (! isset($params[5]) || ! is_numeric($params[5]))) {
			header("Location: /");
			die();
		}
		$app_id = intval($params[4]);
		$mod_id = intval($params[5]);
		$people = $this->model('people');
		$person = $people->get_person($id, true);
		$friends = $people->get_friends($id);
		$friend_requests = isset($_SESSION['id']) ? $people->get_friend_requests($_SESSION['id']) : array();
		$apps = $this->model('applications');
		$applications = $apps->get_person_applications($id);
		$application = $apps->get_person_application($id, $app_id, $mod_id);
		$this->template('applications/application_canvas.php', array('applications' => $applications, 'application' => $application, 'person' => $person, 'friend_requests' => $friend_requests, 'friends' => $friends, 'is_owner' => isset($_SESSION['id']) ? ($_SESSION['id'] == $id) : false));
	}

	
	public function myapps($param)
	{
		if (! isset($_SESSION['id'])) {
			header("Location: /");
		}
		$id = $_SESSION['id'];
		$people = $this->model('people');
		$apps = $this->model('applications');
		$applications = $apps->get_person_applications($_SESSION['id']);
		$person = $people->get_person($id, true);
		$this->template('applications/applications_manage.php', array('person' => $person, 'is_owner' => true, 'applications' => $applications));
	}
	
	public function appgallery($params)
	{
		if (! isset($_SESSION['id'])) {
			header("Location: /");
		}
		$id = $_SESSION['id'];
		$people = $this->model('people');
		$apps = $this->model('applications');
		$app_gallery = $apps->get_all_applications();
		$applications = $apps->get_person_applications($_SESSION['id']);
		$person = $people->get_person($id, true);
		$this->template('applications/applications_gallery.php', array('person' => $person, 'is_owner' => true, 'applications' => $applications, 'app_gallery' => $app_gallery));
	}
	
	public function addapp($params)
	{
		if (! isset($_SESSION['id']) || ! isset($_GET['appUrl'])) {
			header("Location: /");
		}
		$url = urldecode($_GET['appUrl']);
		$apps = $this->model('applications');
		$ret = $apps->add_application($_SESSION['id'], $url);
		if ($ret['app_id'] && $ret['mod_id'] && ! $ret['error']) {
			// App added ok, goto app settings
			header("Location: /profile/application/{$_SESSION['id']}/{$ret['app_id']}/{$ret['mod_id']}");
		} else {
			// Using the home controller to display the error on the person's home page
			include_once Config::get('controllers_root') . "/home/home.php";
			$homeController = new homeController();
			$message = "Could not add application: {$ret['error']}";
			$homeController->index($params, $message);
		}
	}
	
	public function removeapp($params)
	{
		if (! isset($_SESSION['id']) || (! isset($params[3]) || ! is_numeric($params[3])) || (! isset($params[4]) || ! is_numeric($params[4]))) {
			header("Location: /");
		}
		$app_id = intval($params[3]);
		$mod_id = intval($params[4]);
		$apps = $this->model('applications');
		if ($apps->remove_application($_SESSION['id'], $app_id, $mod_id)) {
			$message = 'Application removed';
		} else {
			$message = 'Could not remove application, invalid id';
		}
		header("Location: /profile/myapps");
	}
	
	public function appsettings($params)
	{
		if (! isset($_SESSION['id']) || (! isset($params[3]) || ! is_numeric($params[3])) || (! isset($params[4]) || ! is_numeric($params[4]))) {
			header("Location: /");
		}
		$app_id = intval($params[3]);
		$mod_id = intval($params[4]);
		$apps = $this->model('applications');
		$people = $this->model('people');
		$person = $people->get_person($_SESSION['id'], true);
		$friends = $people->get_friends($_SESSION['id']);
		$friend_requests = isset($_SESSION['id']) ? $people->get_friend_requests($_SESSION['id']) : array();
		$app = $apps->get_person_application($_SESSION['id'], $app_id, $mod_id);
		$applications = $apps->get_person_applications($_SESSION['id']);		
		if (count($_POST)) {
			$settings = unserialize($app['settings']);
			if (is_object($settings)) {
				foreach ($_POST as $key => $value) {
					// only store if the gadget indeed knows this setting, otherwise it could be abuse..
					if (isset($settings->$key)) {
						$apps->set_application_pref($_SESSION['id'], $app_id, $mod_id, $key, $value);
					}
				}
			}
			header("Location:/profile/application/{$_SESSION['id']}/$app_id/$mod_id");
			die();
		}
		$this->template('applications/application_settings.php', array('applications' => $applications, 'application' => $app, 'person' => $person, 'friend_requests' => $friend_requests, 'friends' => $friends, 'is_owner' => true));
	}
}