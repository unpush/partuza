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
 */

class PartuzaHandler extends GadgetDataHandler {
	private $handles = array('FETCH_PEOPLE', 'FETCH_PERSON_APP_DATA', 'UPDATE_PERSON_APP_DATA', 'FETCH_ACTIVITIES', 'CREATE_ACTIVITY');

	public function shouldHandle($requestType)
	{
		return in_array($requestType, $this->handles);
	}

	public function handleRequest($request)
	{
		try {
			$params = $request->getParams();
			$type = $params['type'];
			$response = new ResponseItem(NOT_IMPLEMENTED, $type . " has not been implemented yet.", array());
			$idSpec = idSpec::fromJson($params['idSpec']);
			$peopleIds = $this->getIds($idSpec, $request->getToken());
			switch ($type) {
				
				case 'FETCH_PEOPLE':
					$profileDetail = $params["profileDetail"];
					$profileDetailFields = Array();
					foreach ($profileDetail as $detail) {
						$profileDetailFields[] = $detail;
					}
					$sortOrder = ! empty($params["sortOrder"]) ? $params["sortOrder"] : 'topFriends';
					$filter = ! empty($params["filter"]) ? $params["filter"] : 'all';
					$first = intval($params["first"]);
					$max = intval($params["max"]);
					// TODO: Should we put this in the requestitem and pass the whole
					// thing along?
					$response = $this->getPeople($peopleIds, $sortOrder, $filter, $first, $max, $profileDetailFields, $request->getToken());
					break;
				
				case 'FETCH_PERSON_APP_DATA':
					$jsonKeys = $params["keys"];
					$keys = array();
					foreach ($jsonKeys as $key) {
						$keys[] = $key;
					}
					$response = $this->getPersonData($peopleIds, $keys, $request->getToken());
					break;
				
				case 'UPDATE_PERSON_APP_DATA':
					// this is either viewer or owner right? lets hack in propper support shall we?
					// We only support updating one person right now
					$id = $peopleIds[0];
					$key = $params["key"];
					$value = ! empty($params["value"]) ? $params["value"] : '';
					$response = $this->updatePersonData($id, $key, $value, $request->getToken());
					break;
				
				case 'FETCH_ACTIVITIES':
					$response = $this->getActivities($peopleIds, $request->getToken());
					break;
				
				case 'CREATE_ACTIVITY':
					$response = $this->createActivity($peopleIds, $params['activity'], $request->getToken());
					break;
			}
		} catch (Exception $e) {
			$response = new ResponseItem(BAD_REQUEST, $e->getMessage());
		}
		return $response;
	}

	/* People */
	private function getPeople($ids, $sortOrder, $filter, $first, $max, $profileDetails, $token)
	{
		$allPeople = PartuzaDbFetcher::get()->getPeople($ids, $profileDetails);
		$people = array();
		foreach ($ids as $id) {
			$person = null;
			if (isset($allPeople[$id])) {
				$person = $allPeople[$id];
				if ($id == $token->getViewerId()) {
					$person->setIsViewer(true);
				}
				if ($id == $token->getOwnerId()) {
					$person->setIsOwner(true);
				}
				//FIXME (see note below)
				// The java sample container code returns everything that is listed in the XML file
				// and filters out all the null values. I -think- the more correct thing to do is 
				// return a json object with only the requested profile details ... 
				// but double check later to make sure :)
				if (is_array($profileDetails) && count($profileDetails)) {
					$newPerson = array();
					$newPerson['isOwner'] = $person->isOwner;
					$newPerson['isViewer'] = $person->isViewer;
					$newPerson['name'] = $person->name;
					foreach ($profileDetails as $field) {
						if (isset($person->$field) && ! empty($person->$field)) {
							$newPerson[$field] = $person->$field;
						}
					}
					$person = $newPerson;
					// return only the requested profile detail fields
				}
				$people[] = $person;
			}
		}		
		//TODO: The Partuza doesn't support any filters yet. We should fix this.
		$totalSize = count($people);
		$last = $first + $max;
		$last = min($last, $totalSize);
		$people = array_slice($people, $first, $last);
		$collection = new ApiCollection($people, $first, $totalSize);
		return new ResponseItem(null, null, $collection);
	}

	private function getIds($idSpec, $token)
	{
		$ids = array();
		switch ($idSpec->getType()) {
			case 'OWNER':
				$ids[] = $token->getOwnerId();
				break;
			case 'VIEWER':
				$ids[] = $token->getViewerId();
				break;
			case 'OWNER_FRIENDS':
				$ids = PartuzaDbFetcher::get()->getFriendIds($token->getOwnerId());
				break;
			case 'VIEWER_FRIENDS':
				$ids = PartuzaDbFetcher::get()->getFriendIds($token->getViewerId());
				break;
			case 'USER_IDS':
				$ids = $idSpec->fetchUserIds();
				break;
		}
		return $ids;
	}

	/* Data */
	private function getPersonData($ids, $keys, $token)
	{
		$app_id = $token->getAppId();
		$mod_id = $token->getModuleId();
		return new ResponseItem(null, null, PartuzaDbFetcher::get()->getAppData($ids, $keys, $app_id, $mod_id));
	}

	private function updatePersonData($id, $key, $value, $token)
	{
		if (! $this->isValidKey($key)) {
			return new ResponseItem(BAD_REQUEST, "The person data key had invalid characters", null);
		}
		if ($id != $token->getViewerId()) {
			return new ResponseItem(BAD_REQUEST, "Person ID invalid", null);
		}
		$app_id = $token->getAppId();
		$mod_id = $token->getModuleId();
		if (PartuzaDbFetcher::get()->setAppData($id, $key, $value, $app_id, $mod_id)) {
			return new ResponseItem(null, null, array());
		} else {
			return new ResponseItem(BAD_REQUEST, "Error storing app preference", null);
		}
	}

	/* activities */
	private function getActivities($ids, $token)
	{
		return new ResponseItem(null, null, PartuzaDbFetcher::get()->getActivities($ids));
	}

	private function createActivity($personIds, $activity, $token)
	{
		$requestId = $personIds[0];
		if ($requestId != $token->getViewerId()) {
			return new ResponseItem(BAD_REQUEST, "Invalid person id for createActivity, person id can only be viewer", null);
		}
		if (PartuzaDbFetcher::get()->createActivity($token->getViewerId(), $activity, $token->getAppId())) {
			return new ResponseItem(null, null, array());
		} else {
			return new ResponseItem(BAD_REQUEST, "Error storing activity item", null);
		}
	}

	/**
	 * Determines whether the input is a valid key. Valid keys match the regular
	 * expression [\w\-\.]+.
	 * 
	 * @param key the key to validate.
	 * @return true if the key is a valid appdata key, false otherwise.
	 */
	private function isValidKey($key)
	{
		if (empty($key)) {
			return false;
		}
		for ($i = 0; $i < strlen($key); ++ $i) {
			$c = substr($key, $i, 1);
			if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || ($c == '-') || ($c == '_') || ($c == '.')) {
				continue;
			}
			return false;
		}
		return true;
	}
}
