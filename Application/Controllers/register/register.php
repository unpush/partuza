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

/*
 * TODO
 * - field validation
 * - add more fields such as date of birth, name pre/suffix, etc
 * - link to 'did you forget your password'
 * - Accompanying text to explain what this is, that email/passwd will be your login, etc
 * - bonus: quick ajax check on email once it has been filled in & life feedback
 * - bonus: password strength meter
 */

class registerController extends baseController {
	public function index($params)
	{
		$error = '';
		if (count($_POST)) {
			// check to see if all required fields are filled in
			if (empty($_POST['register_email']) || empty($_POST['register_password']) ||
			    empty($_POST['register_first_name']) || empty($_POST['register_last_name'])) {
			    	$error = 'Fill in all fields to continue';
			} else {
				// check availability of the email addr used
				$register = $this->model('register');
				try {
					// attempt to register this person, any error in registration will cause an exception
					$personId = $register->register($_POST['register_email'], $_POST['register_password'], $_POST['register_first_name'], $_POST['register_last_name']);
					
					// registration went ok, set up the session (and cookie)
					$this->authenticate($_POST['register_email'], $_POST['register_password']);

					// and finally, redirect the user to his new profile page
					header("Location: /profile/$personId");
					
					// don't continue output, all we want is a redirection
					die();
				} catch (Exception $e) {
					// something went wrong with the registration
					$error = $e->getMessage();
				}
			}
		}
		$this->template('register/register.php', array('error' => $error));
	}
}