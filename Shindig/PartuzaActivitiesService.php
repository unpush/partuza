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

class PartuzaActivitiesService extends ActivitiesService {

	public function getActivity(UserId $userId, GroupId $groupId, $activityId, SecurityToken $token)
	{
		$activities = $this->getActivities($userId, $groupId, $token);
		$activities = $activities->getResponse();
		if ($activities instanceof RestFulCollection) {
			$activities = $activities->getEntry();
			foreach ($activities as $activity) {
				if ($activity->getId() == $activityId) {
					return new ResponseItem(null, null, $activity);
				}
			}
		}
		return new ResponseItem(NOT_FOUND, "Activity not found", null);
	}
	
	public function getActivities(UserId $userId, GroupId $groupId, SecurityToken $token)
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
        		$ids[] = $userId->getUserId($token);
        		break;
    	}
		$activities = PartuzaDbFetcher::get()->getActivities($ids);
		// TODO: Sort them
		return new ResponseItem(null, null, RestfulCollection::createFromEntry($activities));
	}

	public function createActivity(UserId $userId, $activity, SecurityToken $token)
	{
		// TODO: Validate the activity and do any template expanding
		PartuzaDbFetcher::get()->createActivity($userId->getUserId($token), $activity, $token->getAppId());
		return new ResponseItem(null, null, array());
	}
}
