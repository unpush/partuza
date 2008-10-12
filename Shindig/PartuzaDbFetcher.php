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
	private $url_prefix;
	private $cache;
	
	// Singleton
	private static $fetcher;

	private function connectDb()
	{
		//TODO move these to PartuzaConfig.php, this is uglaaaaaayyyy!
		// enter your db config here
		$this->db = mysqli_connect('localhost', 'root', '', 'partuza');
		mysqli_select_db($this->db, 'partuza');
	}

	private function __construct()
	{
		$cache = Config::get('data_cache');
		$this->cache = new $cache();
		// change this to your site's location
		$this->url_prefix = 'http://partuza';
	}

	private function checkDb()
	{
		if (! is_resource($this->db)) {
			$this->connectDb();
		}
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
		$this->checkDb();
		$app_id = mysqli_real_escape_string($this->db, $app_id);
		$person_id = mysqli_real_escape_string($this->db, $person_id);
		$title = trim(isset($activity['title']) ? $activity['title'] : '');
		if (empty($title)) {
			throw new Exception("Invalid activity: empty title");
		}
		$body = isset($activity['body']) ? $activity['body'] : '';
		$title = mysqli_real_escape_string($this->db, $title);
		$body = mysqli_real_escape_string($this->db, $body);
		$time = time();
		mysqli_query($this->db, "insert into activities (id, person_id, app_id, title, body, created) values (0, $person_id, $app_id, '$title', '$body', $time)");
		if (! ($activityId = mysqli_insert_id($this->db))) {
			return false;
		}
		$mediaItems = isset($activity['mediaItems']) ? $activity['mediaItems'] : array();
		if (count($mediaItems)) {
			foreach ($mediaItems as $mediaItem) {
				$type = isset($mediaItem['type']) ? $mediaItem['type'] : '';
				$mimeType = isset($mediaItem['mimeType']) ? $mediaItem['mimeType'] : '';
				$url = isset($mediaItem['url']) ? $mediaItem['url'] : '';
				$type = mysqli_real_escape_string($this->db, trim($type));
				$mimeType = mysqli_real_escape_string($this->db, trim($mimeType));
				$url = mysqli_real_escape_string($this->db, trim($url));
				if (! empty($mimeType) && ! empty($type) && ! empty($url)) {
					mysqli_query($this->db, "insert into activity_media_items (id, activity_id, mime_type, media_type, url) values (0, $activityId, '$mimeType', '$type', '$url')");
					if (! mysqli_insert_id($this->db)) {
						return false;
					}
				} else {
					return false;
				}
			}
		}
		return true;
	}

	public function getActivities($ids, $first = false, $max = false)
	{
		$this->checkDb();
		$activities = array();
		foreach ($ids as $key => $val) {
			$ids[$key] = mysqli_real_escape_string($this->db, $val);
		}
		$query = "
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
			";
		if ($first !== false && $max !== false && is_numeric($first) && is_numeric($max) && $first >= 0 && $max > 0) {
			$query .= " limit $first, $max";
		}
		$res = mysqli_query($this->db, $query);
		if ($res && mysqli_num_rows($res)) {
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
		} else {
			return false;
		}
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
		$this->checkDb();
		$ret = array();
		$person_id = mysqli_real_escape_string($this->db, $person_id);
		$res = mysqli_query($this->db, "select person_id, friend_id from friends where person_id = $person_id or friend_id = $person_id");
		while (list($pid, $fid) = @mysqli_fetch_row($res)) {
			$id = ($pid == $person_id) ? $fid : $pid;
			$ret[] = $id;
		}
		return $ret;
	}

	public function setAppData($person_id, $key, $value, $app_id)
	{
		$this->checkDb();
		$person_id = mysqli_real_escape_string($this->db, $person_id);
		$key = mysqli_real_escape_string($this->db, $key);
		$value = mysqli_real_escape_string($this->db, $value);
		$app_id = mysqli_real_escape_string($this->db, $app_id);
		if (empty($value)) {
			// empty key kind of became to mean "delete data" (was an old orkut hack that became part of the spec spec)
			if (! @mysqli_query($this->db, "delete from application_settings where application_id = $app_id and person_id = $person_id and name = '$key'")) {
				return false;
			}
		} else {
			if (! @mysqli_query($this->db, "insert into application_settings (application_id, person_id, name, value) values ($app_id, $person_id, '$key', '$value') on duplicate key update value = '$value'")) {
				return false;
			}
		}
		return true;
	}

	public function deleteAppData($person_id, $key, $app_id)
	{
		$this->checkDb();
		$person_id = mysqli_real_escape_string($this->db, $person_id);
		$key = mysqli_real_escape_string($this->db, $key);
		$app_id = mysqli_real_escape_string($this->db, $app_id);
		if (! @mysqli_query($this->db, "delete from application_settings where application_id = $app_id and person_id = $person_id and name = $key")) {
			return false;
		}
		return true;
	}

	public function getAppData($ids, $keys, $app_id)
	{
		$this->checkDb();
		$data = array();
		foreach ($ids as $key => $val) {
			if (!empty($val)) {
				$ids[$key] = mysqli_real_escape_string($this->db, $val);
			}
		}
		if (! isset($keys[0])) {
			$keys[0] = '*';
		}
		if ($keys[0] == '*') {
			$keys = '';
		} elseif (is_array($keys)) {
			foreach ($keys as $key => $val) {
				$keys[$key] = "'" . mysqli_real_escape_string($this->db, $val) . "'";
			}
			$keys = "and name in (" . implode(',', $keys) . ")";
		} else {
			$keys = '';
		}
		$res = mysqli_query($this->db, "select person_id, name, value from application_settings where application_id = $app_id and person_id in (" . implode(',', $ids) . ") $keys");
		while (list($person_id, $key, $value) = @mysqli_fetch_row($res)) {
			if (! isset($data[$person_id])) {
				$data[$person_id] = array();
			}
			$data[$person_id][$key] = $value;
		}
		return $data;
	}

	public function getPeople($ids, $fields, $options)
	{
		$first = $options->getStartIndex();
		$max = $options->getCount();		
		$this->checkDb();
		$ret = array();
		$ret['totalSize'] = '0';
		if (($res = mysqli_query($this->db, "select count(*) from persons where id in (" . implode(',', $ids) . ")")) !== false) {
			list($count) = mysqli_fetch_row($res);
			$ret['totalSize'] = $count;
		}
		$query = "select * from persons where id in (" . implode(',', $ids) . ") order by id ";
		if ($first !== false && $max !== false && is_numeric($first) && is_numeric($max) && $first >= 0 && $max > 0) {
			$query .= " limit $first, $max";
		}
		$res = mysqli_query($this->db, $query);
		if ($res) {
			while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
				$person_id = mysqli_real_escape_string($this->db, $row['id']);
				$name = new Name($row['first_name'] . ' ' . $row['last_name']);
				$name->setGivenName($row['first_name']);
				$name->setFamilyName($row['last_name']);
				$person = new Person($row['id'], $name);
				$person->setDisplayName($name->getFormatted());
				$person->setAboutMe($row['about_me']);
				$person->setAge($row['age']);
				$person->setChildren($row['children']);
				$person->setBirthday(date('Y-m-d', $row['date_of_birth']));
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
				$person->setProfileUrl($this->url_prefix . '/profile/' . $row['id']);
				$person->setProfileVideo($row['profile_video']);
				$person->setRelationshipStatus($row['relationship_status']);
				$person->setReligion($row['religion']);
				$person->setRomance($row['romance']);
				$person->setScaredOf($row['scared_of']);
				$person->setSexualOrientation($row['sexual_orientation']);
				$person->setStatus($row['status']);
				$person->setThumbnailUrl(! empty($row['thumbnail_url']) ? $this->url_prefix . $row['thumbnail_url'] : '');
				if (! empty($row['thumbnail_url'])) {
					// also report thumbnail_url in standard photos field (this is the only photo supported by partuza)
					$person->setPhotos(array(
							new Photo($this->url_prefix . $row['thumbnail_url'], 'thumbnail', true)));
				}
				$person->setUtcOffset(sprintf('%+03d:00', $row['time_zone'])); // force "-00:00" utc-offset format
				if (! empty($row['drinker'])) {
					$person->setDrinker($row['drinker']);
				}
				if (! empty($row['gender'])) {
					$person->setGender(strtolower($row['gender']));
				}
				if (! empty($row['smoker'])) {
					$person->setSmoker($row['smoker']);
				}
				/* the following fields require additional queries so are only executed if requested */
				if (isset($fields['activities']) || isset($fields['@all'])) {
					$activities = array();
					$res2 = mysqli_query($this->db, "select activity from person_activities where person_id = " . $person_id);
					while (list($activity) = @mysqli_fetch_row($res2)) {
						$activities[] = $activity;
					}
					$person->setActivities($activities);
				}
				if (isset($fields['addresses']) || isset($fields['@all'])) {
					$addresses = array();
					$res2 = mysqli_query($this->db, "select addresses.* from person_addresses, addresses where addresses.id = person_addresses.address_id and person_addresses.person_id = " . $person_id);
					while ($row = @mysqli_fetch_array($res2, MYSQLI_ASSOC)) {
						if (empty($row['unstructured_address'])) {
							$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
						}
						$addres = new Address($row['unstructured_address']);
						$addres->setCountry($row['country']);
						$addres->setLatitude($row['latitude']);
						$addres->setLongitude($row['longitude']);
						$addres->setLocality($row['locality']);
						$addres->setPostalCode($row['postal_code']);
						$addres->setRegion($row['region']);
						$addres->setStreetAddress($row['street_address']);
						$addres->setType($row['address_type']);
						//FIXME quick and dirty hack to demo PC
						$addres->setPrimary(true);
						$addresses[] = $addres;
					}
					$person->setAddresses($addresses);
				}
				if (isset($fields['bodyType']) || isset($fields['@all'])) {
					$res2 = mysqli_query($this->db, "select * from person_body_type where person_id = " . $person_id);
					if (@mysqli_num_rows($res2)) {
						$row = @mysql_fetch_array($res2, MYSQLI_ASSOC);
						$bodyType = new BodyType();
						$bodyType->setBuild($row['build']);
						$bodyType->setEyeColor($row['eye_color']);
						$bodyType->setHairColor($row['hair_color']);
						$bodyType->setHeight($row['height']);
						$bodyType->setWeight($row['weight']);
						$person->setBodyType($bodyType);
					}
				}
				if (isset($fields['books']) || isset($fields['@all'])) {
					$books = array();
					$res2 = mysqli_query($this->db, "select book from person_books where person_id = " . $person_id);
					while (list($book) = @mysqli_fetch_row($res2)) {
						$books[] = $book;
					}
					$person->setBooks($books);
				}
				if (isset($fields['cars']) || isset($fields['@all'])) {
					$cars = array();
					$res2 = mysqli_query($this->db, "select car from person_cars where person_id = " . $person_id);
					while (list($car) = @mysqli_fetch_row($res2)) {
						$cars[] = $car;
					}
					$person->setCars($cars);
				}
				if (isset($fields['currentLocation']) || isset($fields['@all'])) {
					$addresses = array();
					$res2 = mysqli_query($this->db, "select addresses.* from person_current_location, person_addresses, addresses where addresses.id = person_current_location.address_id and person_addresses.person_id = " . $person_id);
					if (@mysqli_num_rows($res2)) {
						$row = mysqli_fetch_array($res2, MYSQLI_ASSOC);
						if (empty($row['unstructured_address'])) {
							$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
						}
						$addres = new Address($row['unstructured_address']);
						$addres->setCountry($row['country']);
						$addres->setLatitude($row['latitude']);
						$addres->setLongitude($row['longitude']);
						$addres->setLocality($row['locality']);
						$addres->setPostalCode($row['postal_code']);
						$addres->setRegion($row['region']);
						$addres->setStreetAddress($row['street_address']);
						$addres->setType($row['address_type']);
						$person->setCurrentLocation($addres);
					}
				}
				if (isset($fields['emails']) || isset($fields['@all'])) {
					$emails = array();
					$res2 = mysqli_query($this->db, "select address, email_type from person_emails where person_id = " . $person_id);
					while (list($address, $type) = @mysqli_fetch_row($res2)) {
						$emails[] = new Email(strtolower($address), $type); // TODO: better email canonicalization; remove dups
					}
					$person->setEmails($emails);
				}
				if (isset($fields['food']) || isset($fields['@all'])) {
					$foods = array();
					$res2 = mysqli_query($this->db, "select food from person_foods where person_id = " . $person_id);
					while (list($food) = @mysqli_fetch_row($res2)) {
						$foods[] = $food;
					}
					$person->setFood($foods);
				}
				
				if (isset($fields['heroes']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select hero from person_heroes where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setHeroes($strings);
				}
				
				if (isset($fields['interests']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select interest from person_interests where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setInterests($strings);
				}
				$organizations = array();
				$fetchedOrg = false;
				if (isset($fields['jobs']) || isset($fields['@all'])) {
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
						$organization->setType('job');
						if ($row['address_id']) {
							$res3 = mysqli_query($this->db, "select * from addresses where id = " . mysqli_real_escape_string($this->db, $row['address_id']));
							if (mysqli_num_rows($res3)) {
								$row = mysqli_fetch_array($res3, MYSQLI_ASSOC);
								if (empty($row['unstructured_address'])) {
									$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
								}
								$addres = new Address($row['unstructured_address']);
								$addres->setCountry($row['country']);
								$addres->setLatitude($row['latitude']);
								$addres->setLongitude($row['longitude']);
								$addres->setLocality($row['locality']);
								$addres->setPostalCode($row['postal_code']);
								$addres->setRegion($row['region']);
								$addres->setStreetAddress($row['street_address']);
								$addres->setType($row['address_type']);
								$organization->setAddress($address);
							}
						}
						$organizations[] = $organization;
					}
					$fetchedOrg = true;
				}
				if (isset($fields['schools']) || isset($fields['@all'])) {
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
						$organization->setType($row['school']);
						if ($row['address_id']) {
							$res3 = mysqli_query($this->db, "select * from addresses where id = " . mysqli_real_escape_string($this->db, $row['address_id']));
							if (mysqli_num_rows($res3)) {
								$row = mysqli_fetch_array($res3, MYSQLI_ASSOC);
								if (empty($row['unstructured_address'])) {
									$row['unstructured_address'] = trim($row['street_address'] . " " . $row['region'] . " " . $row['country']);
								}
								$addres = new Address($row['unstructured_address']);
								$addres->setCountry($row['country']);
								$addres->setLatitude($row['latitude']);
								$addres->setLongitude($row['longitude']);
								$addres->setLocality($row['locality']);
								$addres->setPostalCode($row['postal_code']);
								$addres->setRegion($row['region']);
								$addres->setStreetAddress($row['street_address']);
								$addres->setType($row['address_type']);
								$organization->setAddress($address);
							}
						}
						$organizations[] = $organization;
					}
				}
				if ($fetchedOrg) {
					$person->setOrganizations($organizations);
				}
				//TODO languagesSpoken, currently missing the languages / countries tables so can't do this yet

				if (isset($fields['movies']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select movie from person_movies where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setMovies($strings);
				}
				if (isset($fields['music']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select music from person_music where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setMusic($strings);
				}
				if (isset($fields['phoneNumbers']) || isset($fields['@all'])) {
					$numbers = array();
					$res2 = mysqli_query($this->db, "select number, number_type from person_phone_numbers where person_id = " . $person_id);
					while (list($number, $type) = @mysqli_fetch_row($res2)) {
						$numbers[] = new Phone($number, $type);
					}
					$person->setPhoneNumbers($numbers);
				}
				if (isset($fields['ims']) || isset($fields['@all'])) {
					$ims = array();
					$res2 = mysqli_query($this->db, "select value, value_type from person_ims where person_id = " . $person_id);
					while (list($value, $type) = @mysqli_fetch_row($res2)) {
						$ims[] = new Im($value, $type);
					}
					$person->setIms($ims);
				}
				if (isset($fields['accounts']) || isset($fields['@all'])) {
					$accounts = array();
					$res2 = mysqli_query($this->db, "select domain, userid, username from person_accounts where person_id = " . $person_id);
					while (list($domain, $userid, $username) = @mysqli_fetch_row($res2)) {
						$accounts[] = new Account($domain, $userid, $username);
					}
					$person->setAccounts($accounts);
				}
				if (isset($fields['quotes']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select quote from person_quotes where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setQuotes($strings);
				}
				if (isset($fields['sports']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select sport from person_sports where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setSports($strings);
				}
				if (isset($fields['tags']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select tag from person_tags where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setTags($strings);
				}
				
				if (isset($fields['turnOns']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select turn_on from person_turn_ons where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setTurnOns($strings);
				}
				if (isset($fields['turnOffs']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select turn_off from person_turn_offs where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = $data;
					}
					$person->setTurnOffs($strings);
				}
				if (isset($fields['urls']) || isset($fields['@all'])) {
					$strings = array();
					$res2 = mysqli_query($this->db, "select url from person_urls where person_id = " . $person_id);
					while (list($data) = @mysqli_fetch_row($res2)) {
						$strings[] = new Url($data, null, null);
					}
					$strings[] = new Url($this->url_prefix . '/profile/' . $person_id, null, 'profile'); // always include profile URL
					$person->setUrls($strings);
				}
				$ret[$person_id] = $person;
			}
		}
		$ret = $this->filterResults($ret, $options);
		return $ret;
	}

	private function filterResults($peopleById, $options)
	{
		if (! $options->getFilterBy()) {
			return $peopleById; // no filtering specified
		}		
		$filterBy = $options->getFilterBy();
		$op = $options->getFilterOperation();
		if (! $op) {
			$op = CollectionOptions::FILTER_OP_EQUALS; // use this container-specific default
		}
		$value = $options->getFilterValue();
		$filteredResults = array();
		$numFilteredResults = 0;
		foreach ($peopleById as $id => $person) {
			if ($person instanceof Person) {
				if (true || $this->passesFilter($person, $filterBy, $op, $value)) {
					$filteredResults[$id] = $person;
					$numFilteredResults ++;
				}
			} else {
				$filteredResults[$id] = $person; // copy extra metadata verbatim
			}
		}
		if (!isset($filteredResults['totalSize'])) {
			$filteredResults['totalSize'] = $numFilteredResults;
		}
		return $filteredResults;
	}

	private function passesFilter($person, $filterBy, $op, $value)
	{
		$fieldValue = $person->getFieldByName($filterBy);
		if ($fieldValue instanceof ComplexField) {
			$fieldValue = $fieldValue->getPrimarySubValue();
		}
		if (! $fieldValue || (is_array($fieldValue) && ! count($fieldValue))) {
			return false; // person is missing the field being filtered for
		}
		if ($op == CollectionOptions::FILTER_OP_PRESENT) {
			return true; // person has a non-empty value for the requested field
		}
		if (! $value) {
			return false; // can't do an equals/startswith/contains filter on an empty filter value
		}
		// grab string value for comparison
		if (is_array($fieldValue)) {
			// plural fields match if any instance of that field matches
			foreach ($fieldValue as $field) {
				if ($field instanceof ComplexField) {
					$field = $field->getPrimarySubValue();
				}
				if ($this->passesStringFilter($field, $op, $value)) {
					return true;
				}
			}
		} else {
			return $this->passesStringFilter($fieldValue, $op, $value);
		}
		
		return false;
	}

	private function passesStringFilter($fieldValue, $op, $filterValue)
	{
		switch ($op) {
			case CollectionOptions::FILTER_OP_EQUALS:
				return $fieldValue == $filterValue;
			case CollectionOptions::FILTER_OP_CONTAINS:
				return strpos($fieldValue, $filterValue) !== false;
			case CollectionOptions::FILTER_OP_STARTSWITH:
				return strpos($fieldValue, $filterValue) === 0;
			default:
				return false; // unrecognized filterOp (TODO: throw error here?)
		}
	}
}
