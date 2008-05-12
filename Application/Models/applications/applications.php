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

class applicationsModel extends Model {
	
	public function get_person_applications($id)
	{
		global $db;
		$ret = array();
		$id = $db->addslashes($id);
		$res = $db->query("select applications.*, person_applications.id as mod_id from person_applications, applications where person_applications.person_id = $id and applications.id = person_applications.application_id");
		while ( $row = $db->fetch_array($res, MYSQLI_ASSOC) ) {
			$row['user_prefs'] = $this->get_application_prefs($id, $row['id'], $row['mod_id']);
			$ret[] = $row;
		}
		return $ret;
	}
	
	public function get_all_applications()
	{
		global $db;
		$ret = array();
		$res = $db->query("select * from applications order by directory_title, title");
		while ( $row = $db->fetch_array($res, MYSQLI_ASSOC) ) {
			$row['user_prefs'] = array();
			$ret[] = $row;
		}
		return $ret;	
	}
	
	public function set_application_pref($person_id, $app_id, $mod_id, $key, $value)
	{
		global $db;
		$person_id = $db->addslashes($person_id);
		$app_id = $db->addslashes($app_id);
		$mod_id = $db->addslashes($mod_id);
		$key = $db->addslashes($key);
		$value = $db->addslashes($value);
		$db->query("insert into application_settings (application_id, person_id, module_id, name, value) values ($app_id, $person_id, $mod_id, '$key', '$value')
					on duplicate key update value = '$value'");
	}
	
	public function get_application_prefs($person_id, $app_id, $mod_id)
	{
		global $db;
		$person_id = $db->addslashes($person_id);
		$app_id = $db->addslashes($app_id);
		$mod_id = $db->addslashes($mod_id);
		$prefs = array();
		$res = $db->query("select name, value from application_settings where application_id = $app_id and module_id = $mod_id and person_id = $person_id");
		while (list($name, $value) = $db->fetch_row($res)) {
			$prefs[$name] = $value;
		}
		return $prefs;
	}
	
	public function get_person_application($person_id, $app_id, $mod_id)
	{
		global $db;
		$ret = array();
		$person_id = $db->addslashes($person_id);
		$app_id = $db->addslashes($app_id);
		$mod_id = $db->addslashes($mod_id);
		$res = $db->query("select url from applications where id = $app_id");
		if ($db->num_rows($res)) {
			list($app_url) = $db->fetch_row($res);
			$ret = $this->get_application($app_url);
			$ret['mod_id'] = $mod_id;
			$ret['user_prefs'] = $this->get_application_prefs($person_id, $app_id, $mod_id);
		}
		return $ret;
	}
	
	private function fetch_gadget_metadata($app_url)
	{
		$request = json_encode(array('context' => array('country' => 'US', 'language' => 'en', 'view' => 'default', 'container' => 'default'), 'gadgets' => array(array('url' => $app_url, 'moduleId' => '1'))));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, Config::get('gadget_server') . '/gadgets/metadata');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'request=' . urlencode($request));
		$content = @curl_exec($ch);
		return json_decode($content);
	}
	
	public function get_application_by_id($id)
	{
		global $db;
		$id = $db->addslashes($id);
		$res = $db->query("select url from applications where id = $id");
		if ($db->num_rows($res)) {
			list($url) = $db->fetch_row($res);
			return $this->get_application($url);
		}
		return false;
	}
	
	// This function either returns a valid applications record or
	// the error (string) that occured in ['error'].
	// After this function you can assume there is a valid, and up to date gadget metadata
	// record in the database.
	public function get_application($app_url)
	{
		global $db;
		$error = false;
		$info = array();
		// see if we have up-to-date info in our db. Cut-off time is 1 day (aka refresh module info once a day)
		$time = time() - (24 * 60 * 60);
		$url = $db->addslashes($app_url);
		$res = $db->query("select * from applications where url = '$url' and modified > $time");
		if ($db->num_rows($res)) {
			// we have an entry with up-to-date info
			$info = $db->fetch_array($res, MYSQLI_ASSOC);
		} else {
			// Either we dont have a record of this module or its out of date, so we retrieve the app meta data.
			$response = $this->fetch_gadget_metadata($app_url);
			if (! is_object($response) && ! is_array($response)) {
				// invalid json object, something bad happened on the shindig metadata side.
				$error = 'An error occured while retrieving the gadget information';
			} else {
				// valid response, process it
				$gadget = $response->gadgets[0];
				if (isset($gadget->errors) && ! empty($gadget->errors[0])) {
					// failed to retrieve gadget, or failed parsing it
					$error = $gadget->errors[0];
				} else {
					// retrieved and parsed gadget ok, store it in db
					$info['url'] = $db->addslashes($gadget->url);
					$info['title'] = $gadget->title;
					$info['directory_title'] = $gadget->directoryTitle;
					$info['height'] = $gadget->height;
					$info['screenshot'] = $gadget->screenshot;
					$info['thumbnail'] = $gadget->thumbnail;
					$info['author'] = $gadget->author;
					$info['author_email'] = $gadget->authorEmail;
					$info['description'] = $gadget->description;
					$info['settings'] = serialize($gadget->userPrefs);
					$info['scrolling'] = !empty($gadget->scrolling) ? $gadget->scrolling : '0';
					$info['height'] = !empty($gadget->height) ? $gadget->height : '0';
					// extract the version from the iframe url
					$iframe_url = $gadget->iframeUrl;
					$iframe_params = array();
					parse_str($iframe_url, $iframe_params);
					$info['version'] = isset($iframe_params['v']) ? $iframe_params['v'] : '';
					$info['modified'] = time();
					// Insert new application into our db, or if it exists (but had expired info) update the meta data
					$db->query("insert into applications
								(id, url, title, directory_title, screenshot, thumbnail, author, author_email, description, settings, version, height, scrolling, modified)
								values
								(
									0,
									'" . $db->addslashes($info['url']) . "',
									'" . $db->addslashes($info['title']) . "',
									'" . $db->addslashes($info['directory_title']) . "',
									'" . $db->addslashes($info['screenshot']) . "',
									'" . $db->addslashes($info['thumbnail']) . "',
									'" . $db->addslashes($info['author']) . "',
									'" . $db->addslashes($info['author_email']) . "',
									'" . $db->addslashes($info['description']) . "',
									'" . $db->addslashes($info['settings']) . "',
									'" . $db->addslashes($info['version']) . "',
									'" . $db->addslashes($info['height']) . "',
									'" . $db->addslashes($info['scrolling']) . "',
									'" . $db->addslashes($info['modified']) . "'
								) on duplicate key update
									url = '" . $db->addslashes($info['url']) . "',
									title = '" . $db->addslashes($info['title']) . "',
									directory_title = '" . $db->addslashes($info['directory_title']) . "',
									screenshot = '" . $db->addslashes($info['screenshot']) . "',
									thumbnail = '" . $db->addslashes($info['thumbnail']) . "',
									author = '" . $db->addslashes($info['author']) . "',
									author_email = '" . $db->addslashes($info['author_email']) . "',
									description = '" . $db->addslashes($info['description']) . "',
									settings = '" . $db->addslashes($info['settings']) . "',
									version = '" . $db->addslashes($info['version']) . "',
									height = '" . $db->addslashes($info['height']) . "',
									scrolling = '" . $db->addslashes($info['scrolling']) . "',
									modified = '" . $db->addslashes($info['modified']) . "'
								");
					$res = $db->query("select id from applications where url = '" . $db->addslashes($info['url']) . "'");
					if (!$db->num_rows($res)) {
						$error = "Could not store application in registry";
					} else {
						list($id) = $db->fetch_row($res);
						$info['id'] = $id;
					}
				}
			}
		}
		$info['error'] = $error;
		return $info;
	}
	
	public function add_application($person_id, $app_url)
	{
		global $db;
		$mod_id = false;	
		$app = $this->get_application($app_url);
		$app_id = isset($app['id']) ? $app['id'] : false;
		$error = $app['error'];
		if ($app_id && ! $error) {
			// we now have a valid gadget record in $info, with no errors occured, proceed to add it to the person
			// keep in mind a person -could- have two the same apps on his page (though with different module_id's) so no
			// unique check is done.
			$person_id = $db->addslashes($person_id);
			$app_id = $db->addslashes($app_id);
			$db->query("insert into person_applications (id, person_id, application_id) values (0, $person_id, $app_id)");
			$mod_id = $db->insert_id();
		}
		return array('app_id' => $app_id, 'mod_id' => $mod_id, 'error' => $app['error']);
	}
	
	public function remove_application($person_id, $app_id, $mod_id)
	{
		global $db;
		$person_id = $db->addslashes($person_id);
		$app_id = $db->addslashes($app_id);
		$mod_id = $db->addslashes($mod_id);
		$db->query("delete from person_applications where id = $mod_id and person_id = $person_id and application_id = $app_id");
		return ($db->affected_rows() != 0);
	}
}