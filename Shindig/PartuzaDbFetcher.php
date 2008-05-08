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

class PartuzaDbFetcher {
	private $db;
	
	// Singleton
	private static $fetcher;

	private function __construct()
	{
		$this->db = mysqli_connect('localhost', 'root', '', 'partuza');
		mysqli_select_db($this->db, 'partuza');
	}

	private function __clone()
	{
		// private, don't allow cloning of a singleton
	}

	static function get()
	{
		// This object is a singleton
		if (! isset(PartuzaDbFetcher::$fetcher)) {
			PartuzaDbFetcher::$fetcher = new PartuzaDbFetcher();
		}
		return PartuzaDbFetcher::$fetcher;
	}
	
	public function createActivity($person_id, $activity, $app_id = '0')
	{
		$app_id = mysqli_real_escape_string($this->db, $app_id);
		$person_id = mysqli_real_escape_string($this->db, $person_id);
		$title = mysqli_real_escape_string($this->db, !empty($activity['fields_']['title']) ? trim($activity['fields_']['title']) : '');
		$body = mysqli_real_escape_string($this->db, !empty($activity['fields_']['body']) ? trim($activity['fields_']['body']) : '');
		$time = time();
		mysqli_query($this->db, "insert into activities (id, person_id, app_id, title, body, created) values (0, $person_id, $app_id, '$title', '$body', $time)");
		echo mysqli_error($this->db);
		if (!($activityId = mysqli_insert_id($this->db))) {
			return false;
		}
		if (isset($activity['fields_']['mediaItems']) && count($activity['fields_']['mediaItems'])) {
			foreach ($activity['fields_']['mediaItems'] as $mediaItem) {
				$type = mysqli_real_escape_string($this->db, $mediaItem['fields_']['type']);
				$mimeType = mysqli_real_escape_string($this->db, $mediaItem['fields_']['mimeType']);
				$url = mysqli_real_escape_string($this->db, $mediaItem['fields_']['url']);
				mysqli_query($this->db, "insert into activity_media_items (id, activity_id, mime_type, media_type, url) values (0, $activityId, '$mimeType', '$type', '$url')");
				if (!mysqli_insert_id($this->db)) {
					return false;
				}
			}
		}
		return true;
	}

	public function getActivities($ids)
	{
		$activities = array();
		foreach ($ids as $key => $val) {
			$ids[$key] = mysqli_real_escape_string($this->db, $val);
		}
		$res = mysqli_query($this->db, "
			select 
				activities.person_id as person_id,
				activities.id as activity_id,
				activities.title as activity_title,
				activities.body as activity_body,
				activities.created as created
			from 
				activities
			where
				activities.person_id in (" . implode(',', $ids) . ")
			order by 
				created desc
			");
		while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
			$activity = new Activity($row['activity_id'], $row['person_id']);
			$activity->setStreamTitle('activities');
			$activity->setTitle($row['activity_title']);
			$activity->setBody($row['activity_body']);
			$activity->setPostedTime($row['created']);
			$activity->setMediaItems($this->getMediaItems($row['activity_id']));
			$activities[] = $activity;
		}
		return $activities;
	}

	private function getMediaItems($activity_id)
	{
		$media = array();
		$activity_id = mysqli_real_escape_string($this->db, $activity_id);
		$res = mysqli_query($this->db, "select mime_type, media_type, url from activity_media_items where activity_id = $activity_id");
		while (list($mime_type, $type, $url) = @mysqli_fetch_row($res)) {
			$media[] = new MediaItem($mime_type, $type, $url);
		}
		return $media;
	}

	public function getFriendIds($person_id)
	{
		$ret = array();
		$person_id = mysqli_real_escape_string($this->db, $person_id);
		$res = mysqli_query($this->db, "select person_id, friend_id from friends where person_id = $person_id or friend_id = $person_id");
		while (list($pid, $fid) = @mysqli_fetch_row($res)) {
			$ret[] = ($pid == $person_id) ? $fid : $pid;
		}
		return $ret;
	}

	public function setAppData($person_id, $key, $value, $app_id, $mod_id)
	{
		$person_id = mysqli_real_escape_string($this->db, $person_id);
		$key = mysqli_real_escape_string($this->db, $key);
		$value = mysqli_real_escape_string($this->db, $value);
		$app_id = mysqli_real_escape_string($this->db, $app_id);
		$mod_id = mysqli_real_escape_string($this->db, $mod_id);
		if (empty($value)) {
			// orkut specific type feature, empty string = delete value
			if (! @mysqli_query($this->db, "delete from application_settings where application_id = $app_id and person_id = $person_id and module_id = $mod_id and name = $key")) {
				return false;
			}
		} else {
			if (! @mysqli_query($this->db, "insert into application_settings (application_id, person_id, module_id, name, value) values ($app_id, $person_id, $mod_id, '$key', '$value') on duplicate key update value = '$value'")) {
				return false;
			}
		}
		return true;
	}

	public function getAppData($ids, $keys, $app_id, $mod_id)
	{
		$data = array();
		foreach ($ids as $key => $val) {
			$ids[$key] = mysqli_real_escape_string($this->db, $val);
		}
		if ($keys[0] == '*') {
			$keys = '';
		} else {
			foreach ($keys as $key => $val) {
				$keys[$key] = "'" . mysqli_real_escape_string($this->db, $val) . "'";
			}
			$keys = "and name in (" . implode(',', $keys) . ")";
		}
		$res = mysqli_query($this->db, "select person_id, name, value from application_settings where application_id = $app_id and module_id = $mod_id and person_id in (" . implode(',', $ids) . ") $keys");
		while (list($person_id, $key, $value) = @mysqli_fetch_row($res)) {
			if (! isset($data[$person_id])) {
				$data[$person_id] = array();
			}
			$data[$person_id][$key] = $value;
		}
		return $data;
	}

	public function getPeople($ids, $profileDetails)
	{
		$ret = array();
		//TODO select * is damn expensive considering most of the time we don't
		// need all fields, so strains the DB and IO way to much.
		// Add a more subtle select where it only selects the requested profileDetails
		

		// ps don't pay attention to the -funroll-loops style coding, it's meant to be quick and dirty :)
		$query = "select * from persons where id in (" . implode(',', $ids) . ")";
		$res = mysqli_query($this->db, $query);
		if ($res)
			while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
				$person_id = mysqli_real_escape_string($this->db, $row['id']);
				$name = new Name($row['first_name'] . ' ' . $row['last_name']);
				$name->setGivenName($row['first_name']);
				$name->setFamilyName($row['last_name']);
				$person = new Person($row['id'], $name);
				$person->setAboutMe($row['about_me']);
				$person->setAge($row['age']);
				$person->setChildren($row['children']);
				$person->setDateOfBirth($row['date_of_birth']);
				$person->setEthnicity($row['ethnicity']);
				$person->setFashion($row['fashion']);
				$person->setHappiestWhen($row['happiest_when']);
				$person->setHumor($row['humor']);
				$person->setJobInterests($row['job_interests']);
				$person->setLivingArrangement($row['living_arrangement']);
				$person->setLookingFor($row['looking_for']);
				$person->setNickname($row['nickname']);
				$person->setPets($row['pets']);
				$person->setPoliticalViews($row['political_views']);
				$person->setProfileSong($row['profile_song']);
				$person->setProfileUrl($row['profile_url']);
				$person->setProfileVideo($row['profile_video']);
				$person->setRelationshipStatus($row['relationship_status']);
				$person->setReligion($row['religion']);
				$person->setRomance($row['romance']);
				$person->setScaredOf($row['scared_of']);
				$person->setSexualOrientation($row['sexual_orientation']);
				$person->setStatus($row['status']);
				$person->setThumbnailUrl(!empty($row['thumbnail_url']) ? $row['thumbnail_url'] : Config::get('gadget_server')."/gadgets/files/samplecontainer/examples/nophoto.gif");
				$person->setTimeZone($row['time_zone']);
				if (! empty($row['drinker'])) {
					$person->setDrinker(new EnumDrinker($row['drinker']));
				}
				if (! empty($row['gender'])) {
					$person->setGender(new EnumGender($row['gender']));
				}
				if (! empty($row['smoker'])) {
					$person->setSmoker(new EnumSmoker($row['smoker']));
				}
				/* the following fields require additional queries so are only executed if requested */
				if (isset($profileDetails['activities'])) {
					$activities = array();
					$res2 = mysqli_query($this->db, "select activity from person_activities where person_id = " . $person_id);
					while (list($activity) = @mysqli_fetch_row($res2)) {
						$activities[] = $activity;
					}
					$person->setActivities($activities);
				}
				if (isset($profileDetails['addresses'])) {
					$addresses = array();
					$res2 = mysqli_query($this->db, "select address.* from person_addresses, addresses where address.id = person_addresses.address_id and person_addresses.person_id = " . $person_id);
					while ($row = mysqli_fetch_array($res2, MYSQLI_ASSOC)) {
						if (empty($row['unstructured_address'])) {
							$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
						}
						$addres = new Address($row['unstructured_address']);
						$addres->setCountry($row['country']);
						$addres->setExtendedAddress($row['extended_address']);
						$addres->setLatitude($row['latitude']);
						$addres->setLongitude($row['longitude']);
						$addres->setLocality($row['locality']);
						$addres->setPoBox($row['po_box']);
						$addres->setPostalCode($row['postal_code']);
						$addres->setRegion($row['region']);
						$addres->setStreetAddress($row['street_address']);
						$addres->setType($row['address_type']);
						$addresses[] = $addres;
					}
					$person->setAddresses($addresses);
				}
				if (isset($profileDetails['bodyType'])) {
					$res2 = mysqli_query($this->db, "select * from person_body_type where person_id = " . $person_id);
					if (mysqli_num_rows($res2)) {
						$row = mysql_fetch_array($res2, MYSQLI_ASSOC);
						$bodyType = new BodyType();
						$bodyType->setBuild($row['build']);
						$bodyType->setEyeColor($row['eye_color']);
						$bodyType->setHairColor($row['hair_color']);
						$bodyType->setHeight($row['height']);
						$bodyType->setWeight($row['weight']);
						$person->setBodyType($bodyType);
					}
				}
				if (isset($profileDetails['books'])) {
					$books = array();
					$res2 = mysqli_query($this->db, "select book from person_books where person_id = " . $person_id);
					while (list($book) = @mysqli_fetch_row($res2)) {
						$books[] = $book;
					}
					$person->setBooks($books);
				}
				if (isset($profileDetails['cars'])) {
					$cars = array();
					$res2 = mysqli_query($this->db, "select car from person_cars where person_id = " . $person_id);
					while (list($car) = @mysqli_fetch_row($res2)) {
						$cars[] = $car;
					}
					$person->setCars($cars);
				}
				if (isset($profileDetails['currentLocation'])) {
					$addresses = array();
					$res2 = mysqli_query($this->db, "select address.* from person_current_location, addresses where address.id = person_current_location.address_id and person_addresses.person_id = " . $person_id);
					if (mysqli_num_rows($res2)) {
						$row = mysqli_fetch_array($res2, MYSQLI_ASSOC);
						if (empty($row['unstructured_address'])) {
							$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
						}
						$addres = new Address($row['unstructured_address']);
						$addres->setCountry($row['country']);
						$addres->setExtendedAddress($row['extended_address']);
						$addres->setLatitude($row['latitude']);
						$addres->setLongitude($row['longitude']);
						$addres->setLocality($row['locality']);
						$addres->setPoBox($row['po_box']);
						$addres->setPostalCode($row['postal_code']);
						$addres->setRegion($row['region']);
						$addres->setStreetAddress($row['street_address']);
						$addres->setType($row['address_type']);
						$person->setCurrentLocation($addres);
					}
				}
				if (isset($profileDetails['emails'])) {
					$emails = array();
					$res2 = mysqli_query($this->db, "select address, email_type from person_emails where person_id = " . $person_id);
					while (list($address, $type) = @mysqli_fetch_row($res2)) {
						$emails[] = new Email($address, $type);
					}
					$person->setEmails($emails);
				}
				if (isset($profileDetails['food'])) {
					$foods = array();
					$res2 = mysqli_query($this->db, "select food from person_foods where person_id = " . $person_id);
					while (list($food) = @mysqli_fetch_row($res2)) {
						$foods[] = $food;
					}
					$person->setFood($foods);
				}
				
				if (isset($profileDetails['heroes'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select hero from person_heroes where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setHeroes($strings);
				}
				
				if (isset($profileDetails['interests'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select interest from person_interests where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setInterests($strings);
				}
				if (isset($profileDetails['jobs'])) {
					$organizations = array();
					$res2 = mysqli_query($this->db, "select organizations.* from person_jobs, organizations where organizations.id = person_jobs.organization_id and person_jobs.person_id = " . $person_id);
					while ($row = mysqli_fetch_array($res2, MYSQLI_ASSOC)) {
						$organization = new Organization();
						$organization->setDescription($row['description']);
						$organization->setEndDate($row['end_date']);
						$organization->setField($row['field']);
						$organization->setName($row['name']);
						$organization->setSalary($row['salary']);
						$organization->setStartDate($row['start_date']);
						$organization->setSubField($row['sub_field']);
						$organization->setTitle($row['title']);
						$organization->setWebpage($row['webpage']);
						if ($row['address_id']) {
							$res3 = mysqli_query($this->db, "select * from addresses where id = " . mysqli_real_escape_string($this->db, $row['address_id']));
							if (mysqli_num_rows($res3)) {
								$row = mysqli_fetch_array($res3, MYSQLI_ASSOC);
								if (empty($row['unstructured_address'])) {
									$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
								}
								$addres = new Address($row['unstructured_address']);
								$addres->setCountry($row['country']);
								$addres->setExtendedAddress($row['extended_address']);
								$addres->setLatitude($row['latitude']);
								$addres->setLongitude($row['longitude']);
								$addres->setLocality($row['locality']);
								$addres->setPoBox($row['po_box']);
								$addres->setPostalCode($row['postal_code']);
								$addres->setRegion($row['region']);
								$addres->setStreetAddress($row['street_address']);
								$addres->setType($row['address_type']);
								$organization->setAddress($address);
							}
						}
						$organizations[] = $organization;
					}
					$person->setJobs($organizations);
				}
				
				//TODO languagesSpoken, currently missing the languages / countries tables so can't do this yet
				

				if (isset($profileDetails['movies'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select movie from person_movies where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setMovies($strings);
				}
				if (isset($profileDetails['music'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select music from person_music where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setMusic($strings);
				}
				if (isset($profileDetails['phoneNumbers'])) {
					$numbers = array();
					$res2 = mysqli_query($this->db, "select number, number_type from person_phone_numbers where person_id = " . $person_id);
					while (list($number, $type) = @mysqli_fetch_row($res2)) {
						$numbers[] = new Phone($number, $type);
					}
					$person->setPhoneNumbers($numbers);
				}
				if (isset($profileDetails['quotes'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select quote from person_quotes where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setQuotes($strings);
				}
				if (isset($profileDetails['schools'])) {
					$organizations = array();
					$res2 = mysqli_query($this->db, "select organizations.* from person_schools, organizations where organizations.id = person_schools.organization_id and person_schools.person_id = " . $person_id);
					while ($row = mysqli_fetch_array($res2, MYSQLI_ASSOC)) {
						$organization = new Organization();
						$organization->setDescription($row['description']);
						$organization->setEndDate($row['end_date']);
						$organization->setField($row['field']);
						$organization->setName($row['name']);
						$organization->setSalary($row['salary']);
						$organization->setStartDate($row['start_date']);
						$organization->setSubField($row['sub_field']);
						$organization->setTitle($row['title']);
						$organization->setWebpage($row['webpage']);
						if ($row['address_id']) {
							$res3 = mysqli_query($this->db, "select * from addresses where id = " . mysqli_real_escape_string($this->db, $row['address_id']));
							if (mysqli_num_rows($res3)) {
								$row = mysqli_fetch_array($res3, MYSQLI_ASSOC);
								if (empty($row['unstructured_address'])) {
									$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
								}
								$addres = new Address($row['unstructured_address']);
								$addres->setCountry($row['country']);
								$addres->setExtendedAddress($row['extended_address']);
								$addres->setLatitude($row['latitude']);
								$addres->setLongitude($row['longitude']);
								$addres->setLocality($row['locality']);
								$addres->setPoBox($row['po_box']);
								$addres->setPostalCode($row['postal_code']);
								$addres->setRegion($row['region']);
								$addres->setStreetAddress($row['street_address']);
								$addres->setType($row['address_type']);
								$organization->setAddress($address);
							}
						}
						$organizations[] = $organization;
					}
					$person->setSchools($organizations);
				}
				if (isset($profileDetails['sports'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select sport from person_sports where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setSports($strings);
				}
				if (isset($profileDetails['tags'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select tag from person_tags where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setTags($strings);
				}
				
				if (isset($profileDetails['turnOns'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select turn_on from person_turn_ons where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setTurnOns($strings);
				}
				if (isset($profileDetails['turnOffs'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select turn_off from person_turn_offs where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setTurnOffs($strings);
				}
				if (isset($profileDetails['urls'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select url from person_urls where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setUrls($strings);
				}
				$ret[$person_id] = $person;
			}
		return $ret;
	}

}