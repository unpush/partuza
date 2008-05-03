<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 * 
 */

$config = array(
	// Language to use, used for gettext / setenv LC_ALL
	'language'         => 'en_US',

	// Max age of a security token, defaults to one hour
	'st_max_age' => 60 * 60,

	// Security token keys
	'token_cipher_key' => 'INSECURE_DEFAULT_KEY',
	'token_hmac_key' => 'INSECURE_DEFAULT_KEY',

	// MySql server settings
	'db_host'          => 'localhost',
	'db_user'          => 'root',
	'db_passwd'        => '',
	'db_database'      => 'partuza',

	// gadget server url
	'gadget_server'    => 'http://shindig',

	/* No need to edit the settings below in general, unless you modified the directory layout */
	'site_root'        => realpath(dirname(__FILE__)),
	'library_root'     => realpath(dirname(__FILE__)."/../Library"),
	'application_root' => realpath(dirname(__FILE__)."/../Application"),
	'views_root'       => realpath(dirname(__FILE__)."/../Application/Views"),
	'models_root'      => realpath(dirname(__FILE__)."/../Application/Models"),
	'controllers_root' => realpath(dirname(__FILE__)."/../Application/Controllers")
);

/**
 * Abstracts how to retrieve configuration values so we can replace the
 * not so pretty $config array some day.
 *
 */
class Config {
	static function get($key)
	{
		global $config;
		return $config[$key];
	}
}