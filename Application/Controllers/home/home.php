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

class homeController extends baseController {
	public function index($params, $message = false)
	{
		if (isset($_SESSION['id'])) {
			$people = $this->model('people');
			$apps = $this->model('applications');
			$activities = $this->model('activities');
			$person = $people->get_person($_SESSION['id'], true);
			$friends = $people->get_friends($_SESSION['id']);
			$friend_requests = $people->get_friend_requests($_SESSION['id']);
			$applications = $apps->get_person_applications($_SESSION['id']);
			$friend_activities = $activities->get_friend_activities($_SESSION['id'], 20);
			//TODO add activities here and parse in template..
			$this->template('profile/home.php', array('activities' => $friend_activities, 'applications' => $applications, 'message' => $message, 'person' => $person, 'friend_requests' => $friend_requests, 'friends' => $friends, 'is_owner' => true));
		} else {
			$this->template('home/home.php');
		}
	}
	
	public function addfriend($params)
	{
		$message = '';
		$people = $this->model('people');
		if (isset($params[3]) && is_numeric($params[3]) && isset($_SESSION['id'])) {
			if ($people->add_friend_request($_SESSION['id'], $params[3])) {
				$info = $people->get_person_info($params[3]);
				$message = 'Send friend request to '.$info['first_name'].' '.$info['last_name'];
			} else {
				$message = 'Could not send friend request, request already pending';
			}
		} else {
			$message = 'Could not send friend request, invalid friend id';
		}
		$this->index($params, $message);
	}
	
	public function acceptfriend($params)
	{
		$message = '';
		$people = $this->model('people');
		if (isset($params[3]) && is_numeric($params[3]) && isset($_SESSION['id'])) {
			$people = $this->model('people');
			if ($people->accept_friend_request($_SESSION['id'], $params[3])) {
				$message = 'Friend request accepted';
			} else {
				$message = 'Could not accept friend request';
			}
		} else {
			$message = 'Could not accept friend request, invalid friend id';
		}
		$this->index($params, $message);
	}
	
	public function rejectfriend($params)
	{
		$message = '';
		$people = $this->model('people');
		if (isset($params[3]) && is_numeric($params[3]) && isset($_SESSION['id'])) {
			$people = $this->model('people');
			if ($people->reject_friend_request($_SESSION['id'], $params[3])) {
				$message = 'Friend request removed';
			} else {
				$message = 'Could not remove friend request';
			}
		} else {
			$message = 'Could not remove friend request, invalid friend id';
		}
		$this->index($params, $message);
	}
}