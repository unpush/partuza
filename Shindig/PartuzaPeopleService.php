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

class PartuzaPeopleService implements PeopleService {

	private function comparator($person, $person1)
	{
		$name = ($person instanceof Person ? $person->getDisplayName() : $person['displayName']);
		$name1 = ($person1 instanceof Person ? $person1->getDisplayName() : $person1['displayName']);
		return strnatcasecmp($name, $name1);
	}

	public function getPerson($userId, $groupId, $fields, SecurityToken $token)
	{
		if (! is_object($groupId)) {
			// request is for an optionalPersonId, so fetch that person and ignore the group
			// FIXME: cleaner way to do this (this seems to be how the data is passed in java)
			$userId = new UserId('userId', $groupId);
			$groupId = new GroupId(null, $groupId);
		}
		$person = $this->getPeople($userId, $groupId, new CollectionOptions(), $fields, $token);
		// return of getPeople is a ResponseItem(RestfulCollection(ArrayOfPeople)), disassemble to return just one person
		if (is_object($person->getResponse())) {
			$person = $person->getResponse()->getEntry();
			if (is_array($person) && count($person) == 1) {
				return new ResponseItem(null, null, array("entry" => array_pop($person)));
			}
		}
		return new ResponseItem(NOT_FOUND, "Person not found", null);
	}

	public function getPeople($userId, $groupId, CollectionOptions $options, $fields, SecurityToken $token)
	{
		$ids = array();
		switch ($groupId->getType()) {
			case 'all':
			case 'friends':
				$friendIds = PartuzaDbFetcher::get()->getFriendIds($userId->getUserId($token));
				if (is_array($friendIds) && count($friendIds)) {
					$ids = $friendIds;
				}
				break;
			case 'self':
			default:
				$ids[] = $userId->getUserId($token);
				break;
		}
		$allPeople = PartuzaDbFetcher::get()->getPeople($ids, $fields, $options);
		$totalSize = $allPeople['totalSize'];
		$people = array();
		foreach ($ids as $id) {
			$person = null;
			if (is_array($allPeople) && isset($allPeople[$id])) {
				$person = $allPeople[$id];
				if (! $token->isAnonymous() && $id == $token->getViewerId()) {
					$person->setIsViewer(true);
				}
				if (! $token->isAnonymous() && $id == $token->getOwnerId()) {
					$person->setIsOwner(true);
				}
				if (! isset($fields['@all'])) {
					$newPerson = array();
					$newPerson['isOwner'] = $person->isOwner;
					$newPerson['isViewer'] = $person->isViewer;
					$newPerson['displayName'] = $person->displayName;
					// force these fields to be present, without it the results are useless
					$fields['id'] = 1;
					$fields['displayName'] = 1;
					$fields['thumbnailUrl'] = 1;
					$fields['profileUrl'] = 1;
					foreach ($fields as $field => $present) {
						if (isset($person->$field) && ! isset($newPerson[$field])) {
							$newPerson[$field] = $person->$field;
						}
					}
					$person = $newPerson;
				}
				array_push($people, $person);
			}
		}
		$sorted = $this->sortResults($people, $options);
		$collection = new RestfulCollection($people, $options->getStartIndex(), $totalSize);
		if (! $sorted) {
			$collection->setSorted(false); // record that we couldn't sort as requested
		}
		if ($options->getUpdatedSince()) {
			$collection->setUpdatedSince(false); // we can never process an updatedSince request
		}
		return new ResponseItem(null, null, $collection);
	}

	private function sortResults(&$people, $options)
	{
		if (! $options->getSortBy())
			return true; // trivially sorted
		

		// for now, partuza can only sort by displayName, which also demonstrates returning sorted: false
		if ($options->getSortBy() != 'displayName')
			return false;
		
		usort($people, array($this, 'comparator'));
		if ($options->getSortOrder() != CollectionOptions::SORT_ORDER_ASCENDING) {
			$people = array_reverse($people);
		}
		return true;
	}
}
