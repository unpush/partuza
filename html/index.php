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

require "config.php";
require "../Library/PartuzaConfig.php";

// An "Accept : application/xrds+xml" header means they want our XRDS document (and nothing else) 
if (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/xrds+xml') !== false || $_SERVER["REQUEST_URI"] == '/xrds') {
	require PartuzaConfig::get('library_root') . "/XRDS.php";
	die();
}

// Basic sanity check if we have all required modules,
// this is the same list as shindig + mysqli
$modules = array('json', 'SimpleXML', 'libxml', 'curl', 'mysqli', 'gd');
// if plain text tokens are disallowed we require mcrypt
if (! PartuzaConfig::get('allow_plaintext_token')) {
	$modules[] = 'mcrypt';
}
foreach ($modules as $module) {
	if (! extension_loaded($module)) {
		die("Shindig requires the {$module} extention, see <a href='http://www.php.net/{$module}'>http://www.php.net/{$module}</a> for more info");
	}
}
$cache = PartuzaConfig::get('data_cache');

// Basic library requirements that are always needed
require PartuzaConfig::get('library_root') . "/Image.php";
require PartuzaConfig::get('library_root') . "/Language.php";
require PartuzaConfig::get('library_root') . "/Database.php";
require PartuzaConfig::get('library_root') . "/Dispatcher.php";
require PartuzaConfig::get('library_root') . "/Controller.php";
require PartuzaConfig::get('library_root') . "/Model.php";
require PartuzaConfig::get('library_root') . "/Cache.php";
require PartuzaConfig::get('library_root') . "/{$cache}.php";
require PartuzaConfig::get('controllers_root') . "/base/base.php";

// Files copied from shindig, required to make the security token
require PartuzaConfig::get('library_root') . "/Crypto.php";
require PartuzaConfig::get('library_root') . "/BlobCrypter.php";
require PartuzaConfig::get('library_root') . "/SecurityToken.php";
require PartuzaConfig::get('library_root') . "/BasicBlobCrypter.php";
require PartuzaConfig::get('library_root') . "/BasicSecurityToken.php";

// Initialize envirioment, and start the dispatcher
Language::set(PartuzaConfig::get('language'));
$db = new DB(PartuzaConfig::get('db_host'), PartuzaConfig::get('db_port'), PartuzaConfig::get('db_user'), PartuzaConfig::get('db_passwd'), PartuzaConfig::get('db_database'), false);
$uri = $_SERVER["REQUEST_URI"];
$cache = PartuzaConfig::get('data_cache');
$cache = new $cache();
if (($pos = strpos($_SERVER["REQUEST_URI"], '?')) !== false) {
	$uri = substr($_SERVER["REQUEST_URI"], 0, $pos);
}
new Dispatcher($uri);
