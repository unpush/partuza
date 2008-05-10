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

// Basic library requirements that are always needed
require Config::get('library_root')."/Language.php";
require Config::get('library_root')."/Database.php";
require Config::get('library_root')."/Dispatcher.php";
require Config::get('library_root')."/Controller.php";
require Config::get('library_root')."/Model.php";
require Config::get('controllers_root')."/base/base.php";

// Files copied from shindig, required to make the security token
require Config::get('library_root')."/Crypto.php";
require Config::get('library_root')."/BlobCrypter.php";
require Config::get('library_root')."/SecurityToken.php";
require Config::get('library_root')."/BasicBlobCrypter.php";
require Config::get('library_root')."/BasicSecurityToken.php";

// Initialize envirioment, and start the dispatcher
Language::set(Config::get('language'));
$db         = new DB(Config::get('db_host'), Config::get('db_user'), Config::get('db_passwd'), Config::get('db_database'), false);
$uri = $_SERVER["REQUEST_URI"];
if (($pos = strpos($_SERVER["REQUEST_URI"], '?')) !== false) {
	$uri = substr($_SERVER["REQUEST_URI"], 0, $pos);
}
new Dispatcher($uri);
