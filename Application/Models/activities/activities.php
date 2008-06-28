<?php

class activitiesModel extends Model {
	public $cachable = array(
	'get_person_activities',
	'get_friend_activities'
	);
	
	public function load_get_person_activities($id, $limit)
	{
		global $db;
		$this->add_dependency('activities', $id);
		$id = $db->addslashes($id);
		$limit = $db->addslashes($limit);
		$ret = array();
		$res = $db->query("
		select
			activities.title as title,
			activities.body as body,
			activities.created as created,
			persons.id as person_id,
			concat(persons.first_name,' ',persons.last_name) as person_name, 
			applications.id as app_id, 
			applications.title as app_title,
			applications.directory_title as app_directory_title,
			applications.url as app_url
		from ( activities, persons )
		left join applications on applications.id = activities.app_id
		where 
			activities.person_id = $id and 
			persons.id = activities.person_id
		order by 
			created desc
		limit 
			$limit
		");
		while ($row = $db->fetch_array($res, MYSQLI_ASSOC)) {
			$this->add_dependency('activities', $row['person_id']);
			$ret[] = $row;
		}
		return $ret;		
	}
	
	public function load_get_friend_activities($id, $limit)
	{
		global $db;
		$this->add_dependency('activities', $id);
		$id = $db->addslashes($id);
		$limit = $db->addslashes($limit);
		$ret = array();
		$res = $db->query("
		select
			activities.title as title,
			activities.body as body,
			activities.created as created,
			persons.id as person_id,
			concat(persons.first_name,' ',persons.last_name) as person_name, 
			applications.id as app_id, 
			applications.title as app_title,
			applications.directory_title as app_directory_title,
			applications.url as app_url
		from ( activities, persons )
		left join applications on applications.id = activities.app_id
		where 
		(
			activities.person_id in (
				select friend_id from friends where person_id = $id
			) or 
			activities.person_id in (
				select person_id from friends where friend_id = $id
			)
		) and 
			persons.id = activities.person_id
		order by 
			created desc
		limit 
			$limit
		");

		while ($row = $db->fetch_array($res, MYSQLI_ASSOC)) {
			$this->add_dependency('activities', $row['person_id']);
			$ret[] = $row;
		}
		return $ret;
	}

}