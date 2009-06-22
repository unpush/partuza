<?php
/**
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

class PartuzaDbFetcher {
  private $db;
  private $url_prefix;
  // private $cache;
  
  // Singleton
  private static $fetcher;

  private function connectDb() {
    // one of the class paths should point to partuza's document root, abuse that fact to find our config
    $extension_class_paths = Config::get('extension_class_paths');
    foreach (explode(',', $extension_class_paths) as $path) {
      if (file_exists($path . "/PartuzaDbFetcher.php")) {
        $configFile = $path . '/../html/config.php';
        if (file_exists($configFile)) {
          include $configFile;
          break;
        }
      }
    }
    if (! isset($config)) {
      throw new Exception("Could not locate partuza's configuration file while scanning extension_class_paths ({$extension_class_paths})");
    }
    $this->db = mysqli_connect($config['db_host'], $config['db_user'], $config['db_passwd'], $config['db_database']);
    mysqli_select_db($this->db, $config['db_database']);
    $this->url_prefix = $config['partuza_url'];
    if (substr($this->url_prefix, strlen($this->url_prefix) - 1, 1) == '/') {
      // prevent double //'s in the profile and thumbnail urls by forcing the prefix to end without a tailing /
      $this->url_prefix = substr($this->url_prefix, 0, strlen($this->url_prefix) - 1);
    }
  }

  private function __construct() {  // Not currently used
  //$this->cache = Cache::createCache(Config::get('data_cache'), 'PartuzaDbFetcher');
  }

  private function checkDb() {
    if (! is_object($this->db)) {
      $this->connectDb();
    }
  }

  private function __clone() {// private, don't allow cloning of a singleton
  }

  static function get() {
    // This object is a singleton
    if (! isset(PartuzaDbFetcher::$fetcher)) {
      PartuzaDbFetcher::$fetcher = new PartuzaDbFetcher();
    }
    return PartuzaDbFetcher::$fetcher;
  }

  public function createMessage($userId, $appId, $msgCollId, $message) {
    /* A $message looks like:
    * [id] => {msgid}
    * [title] => You have an invitation from Joe
    * [body] => Click <a href="http://app.example.org/invites/{msgid}">here</a> to review your invitation.
    * [recipients] => Array
    *      (
    *          [0] => UserId1
    *          [1] => UserId2
    *      )
    */
    $this->checkDb();
    $from = intval($userId);
    $title = mysqli_real_escape_string($this->db, trim($message['title']));
    if (strlen($title) == 0) {
      throw new SocialSpiException("Can't send a message with an empty title");
    }
    $body = mysqli_real_escape_string($this->db, trim($message['body']));
    $bodyId = mysqli_real_escape_string($this->db, trim($message['bodyId']));
    $titleId = mysqli_real_escape_string($this->db, trim($message['titleId']));
    
    // People can only send message to their friends.
    if (!isset($message['recipients'])) {
      throw new SocialSpiException("Invalid recipients");
    }
    if (!is_array($message['recipients'])) {
      $message['recipients'] = array($message['recipients']);
    }
    $friends = $this->getFriendIds($from);
    foreach ($message['recipients'] as $to) {
      if (!in_array($to, $friends)) {
        throw new SocialSpiException("Can't send message to none friend: $to", ResponseError::$BAD_REQUEST);
      }
    }
    $jsonRecipients = mysqli_real_escape_string($this->db, json_encode($message['recipients']));
    
    // Checks whether the specified message collections are in the repository.  
    $collectionIds = array();      
    if ($msgCollId != MessageCollection::$OUTBOX && $msgCollId != MessageCollection::$ALL) {
      $collectionIds[] = $msgCollId;
    }
    if (isset($message['collectionIds'])) {
      if (!is_array($message['collectionIds'])) {
        $message['collectionIds'] = array($message['collectionIds']);
      }
      $collectionIds = array_merge($collectionIds, $message['collectionIds']);
      $collectionIds = array_unique($collectionIds);
    }
    $collectionIds = array_map('intval', $collectionIds);
    $jsonCollectionIds = ''; 
    $appId = intval($appId);
    if (count($collectionIds) > 0) {
      $query = "select id from message_collections where person_id = $from and app_id = $appId and id in (" . implode(',', $collectionIds) . ")";
      $res = mysqli_query($this->db, $query);
      if (!$res || @mysqli_num_rows($res) != count($collectionIds)) {
        throw new SocialSpiException("Can't find message collections.", ResponseError::$BAD_REQUEST);
      }
      $jsonCollectionIds = mysqli_real_escape_string($this->db, json_encode($collectionIds));
    }
    $urls = '';
    if (isset($messageCollection['urls'])) {
      $urls = mysqli_real_escape_string($this->db, json_encode($messageCollection['urls']));  
    }
    $created = time();
    // The 'from_deleted' field of the first message is set to 'no' all the remaining
    // 'from_deleted' is set to 'yes'. It indicates that the first message is actually two messages
    // one for the sender(from) another for the receiver(to).
    $fromDeleted = 'no';
    foreach ($message['recipients'] as $to) {
      $to = intval($to);
      $query = "insert into messages (`from`, `to`, app_id, title, body, title_id, body_id, urls, recipients, collection_ids, from_deleted, updated, created)" .
          " values ($from, $to, $appId, '$title', '$body', '$titleId', '$bodyId', '$urls', '$jsonRecipients', '$jsonCollectionIds', '$fromDeleted', $created, $created)";
      $fromDeleted = 'yes';
      mysqli_query($this->db, $query);
      $messageId = mysqli_insert_id($this->db);
      if (!$messageId) {
        return false;
      } else {
        foreach ($collectionIds as $collectionId) {
          mysqli_query($this->db, "insert into message_groups (message_id, message_collection_id) values ($messageId, $collectionId)");
        }
      }
    }
    return true;
  }
  
  public function getMessages($userId, $msgCollId, $fields, $msgIds, $options) {
    // TODO: Supports fields and options. Currently deleted messages couldn't be retrieved.
    $this->checkDb();

    $userId = intval($userId);
    $fromQuery = " (messages.from = $userId and messages.from_deleted = 'no')";
    $toQuery = " (messages.to = $userId and messages.to_deleted = 'no')";
    $basicQuery = '';
    $groupTable = '';
    if ($msgCollId == '@inbox') {
      $basicQuery = $toQuery;
    } else if ($msgCollId == '@outbox') {
      $basicQuery = $fromQuery;
    } else if ($msgCollId == '@all') {
      $basicQuery = " ( " . $fromQuery . " or " . $toQuery . ")";
    } else {
      $msgCollId = intval($msgCollId);
      $groupTable = ', message_groups'; 
      $basicQuery = " messages.id = message_groups.message_id and " . " ( " . $fromQuery . " or " . $toQuery . ")" . " and message_groups.message_collection_id = $msgCollId";
    }
    
    $messageIdQuery = '';
    if (isset($msgIds) && is_array($msgIds) && count($msgIds) > 0) {
      $msgIds = array_map('intval', $msgIds);
      $messageIdQuery = " and messages.id in (" . implode(',', $msgIds) . ")";
    }
    
    $countQuery = "select count(*) from messages $groupTable where $basicQuery $messageIdQuery";
    $res = mysqli_query($this->db, $countQuery);
    if ($res !== false) {
      list($totalResults) = mysqli_fetch_row($res);
    } else {
      $totalResults = '0';
    }
    $startIndex = $options->getStartIndex();
    $count = $options->getCount();
    $messages = array();
    $messages['totalResults'] = $totalResults;
    $messages['startIndex'] = $startIndex;
    $messages['count'] = $count;
    
    $query = "select messages.from as `from`,
                     messages.to as `to`,
                     messages.id as id,
                     messages.title as title,
                     messages.body as body,
                     messages.updated as updated,
                     messages.collection_ids as collection_ids,
                     messages.recipients as recipients
                from messages $groupTable
                where $basicQuery $messageIdQuery
                order by messages.created desc
                limit $startIndex, $count;";
    $res = mysqli_query($this->db, $query);
    if ($res) {
      if (@mysqli_num_rows($res)) {
        while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
          $message = new Message($row['id'], $row['title']);
          $message->setBody($row['body']);
          $message->setUpdated($row['updated']);
          $message->setRecipients(json_decode($row['recipients']));
          if ($row['collection_ids']) {
            $message->setCollectionIds(json_decode($row['collection_ids']));
          }
          $messages[] = $message;
        }
      } else if($messageIdQuery) {
        throw new SocialSpiException("Message not found", ResponseError::$NOT_FOUND);
      }
      return $messages;
    }
  }
  
  /**
   * Only the 'status' and the 'collectionids' can be updated. A new message should be created
   * instead of updating fields like title, body and etc. 
   */
  public function updateMessage($userId, $appId, $msgCollId, $message) {
    $this->checkDb();
    $id = intval($message['id']);
    $appId = intval($appId);
    $userId = intval($userId);
    // Checks the ownership of the message.
    $personQuery = " ((`from` = $userId and from_deleted = 'no') or (`to` = $userId and to_deleted = 'no'))";
    $query = "select * from messages where id = $id and app_id = $appId and " . $personQuery;
    $res = mysqli_query($this->db, $query);
    if (!$res || @mysqli_num_rows($res) != 1) {
      throw new SocialSpiException("Message not found.", ResponseError::$NOT_FOUND);
    }
    // Checks whether the specified message collections are valid.
    $newIds = array();
    if ($msgCollId != MessageCollection::$OUTBOX && $msgCollId != MessageCollection::$ALL
        && $msgCollId != MessageCollection::$INBOX) {
      $newIds[] = $msgCollId;
    }
    if (isset($message['collectionIds'])) {
      if (!is_array($message['collectionIds'])) {
        $newIds = array($message['collectionIds']);  
      }
      $newIds = array_merge($newIds, array($message['collectionIds']));
      $newIds = array_unique($newIds);
    }
    $newIds = array_map('intval', $newIds);
    if (count($newIds) > 0) {
      $query = "select id from message_collections where person_id = $userId and app_id = $appId and id in (" . implode(',', $newIds) . ")";
      $res = mysqli_query($this->db, $query);
      if (!$res || @mysqli_num_rows($res) != count($newIds)) {
        throw new SocialSpiException("Can't find message collections.", ResponseError::$BAD_REQUEST);
      }
    }
    
    $collectionIds = '';
    if (!empty($newIds)) {
      $collectionIds = json_encode($newIds);  
    }
    $status = '';
    if (isset($message['status'])) {
      // Update the status to "DELETED" is not support.
      if ($message['status'] == 'NEW') {
        $status = 'new';
      } else if ($message['status'] == 'READ') {
        $status = 'read';
      } else {
        throw new SocialSpiException("Invalid status field.", ResponseError::$BAD_REQUEST);
      }
    }
    $query = "update messages set collection_ids = '$collectionIds'";
    if ($status) {
      $query .= ", status = '$status' where id = $id";
    }
    if (!mysqli_query($this->db, $query)) {
      throw new SocialSpiException("Update failed.", ResponseError::$INTERNAL_ERROR);
    }
    $oldIds = array();
    $res = mysqli_query($this->db, "select message_collection_id from message_groups where message_id = $id");
    if ($res && @mysqli_num_rows($res) > 0) {
      while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
        $oldIds[] = $row['message_collection_id'];
      }
    }
    $addIds = array_diff($newIds, $oldIds);
    $deleteIds = array_diff($oldIds, $newIds);
    // Removes and/or adds the message collection relationship.
    foreach ($addIds as $msgId) {
      mysqli_query($this->db, "insert into message_groups (message_id, message_collection_id) values ($id, $msgId)");
    }
    if (count($deleteIds) > 0) {
      $query = "delete from message_groups where message_id = $id and message_collection_id in (" . implode(',', $deleteIds) . ")";
      mysqli_query($this->db, $query);
    }
    return true;
  }
  
  private function deleteMessageGroups($msgCollId, $messageIds) {
    $collectionQuery = '';
    if ($msgCollId != MessageCollection::$INBOX && $msgCollId != MessageCollection::$OUTBOX
        && $msgCollId != MessageCollection::$ALL) {
      $msgCollId = intval($msgCollId);
      $collectionQuery = " and message_collection_id = $msgCollId"; 
    }
    $query = "delete from message_groups where message_id in (" . implode(',', $messageIds) . ") $collectionQuery";    
    mysqli_query($this->db, $query);
    return mysqli_affected_rows($this->db) > 0;
  }
  
  public function deleteMessages($userId, $appId, $msgCollId, $messageIds) {
    $this->checkDb();
    $userId = intval($userId);
    $messageIds = array_map('intval', $messageIds);
    $appId = intval($appId);
    // Only the sender requests to delete the messages.
    $fromDeleteIds = array();
    // Only the receiver requests to delete the messages.
    $toDeleteIds = array();
    $query = "select * from messages where id in (" . implode(',', $messageIds) . ") and app_id = $appId and (`from` = $userId or `to` = $userId)";
    $res = mysqli_query($this->db, $query);
    $filteredIds = array();
    if ($res && @mysqli_num_rows($res) > 0) {
      while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
        $filteredIds[] = $row['id'];
        if ($row['from'] == $userId) {
          if ($row['from_deleted'] == 'yes') {
            throw new SocialSpiException("Message not found.", ResponseError::$NOT_FOUND);
          } else {
            $fromDeleteIds[] = $row['id'];
          }  
        } else if ($row['to'] == $userId) {
          if ($row['to_deleted'] == 'yes') {
            throw new SocialSpiException("Message not found.", ResponseError::$NOT_FOUND);
          } else {
            $toDeleteIds[] = $row['id'];
          }
        }
      }
    } else {
      throw new SocialSpiException("Messages not found.", ResponseError::$BAD_REQUEST);
    }
    
    if ($msgCollId == '@inbox' || $msgCollId == '@outbox' || $msgCollId == '@all') {
      $cnt = 0;
      if (count($fromDeleteIds) > 0) {
        $query = "update messages set from_deleted = 'yes' where id in (" . implode(',', $fromDeleteIds) . ")";
        mysqli_query($this->db, $query);
        $cnt += mysqli_affected_rows($this->db);
      }
      if (count($toDeleteIds) > 0) {
        $query = "update messages set to_deleted = 'yes' where id in (" . implode(',', $toDeleteIds) . ")";
        mysqli_query($this->db, $query);
        $cnt += mysqli_affected_rows($this->db);
      }
      if (count($deleteIds) > 0) {
        $query = "delete from messages where id in (" . implode(',', $deleteIds) . ")";
        mysqli_query($this->db, $query);
        $cnt += mysqli_affected_rows($this->db);
      }
      if ($cnt == 0) {
        throw new SocialSpiException("Deletes failed.", ResponseError::$INTERNAL_ERROR);
      }
    }
    // Deletes the relations 
    return $this->deleteMessageGroups($msgCollId, $filteredIds);
  }
  
  public function createMessageCollection($userId, $appId, $messageCollection) {
    $this->checkDb();
    $userId = intval($userId);
    $appId = intval($appId);
    $title = mysqli_real_escape_string($this->db, trim($messageCollection['title']));
    if (strlen($title) == 0) {
      throw new SocialSpiException("Can't create a message collection with an empty title");
    }
    // Stores urls as a json string.
    $urls = '';
    if (isset($messageCollection['urls'])) {
      $urls = mysqli_real_escape_string($this->db, json_encode($messageCollection['urls']));  
    }
    $created = time();
    mysqli_query($this->db, "insert into message_collections (person_id, app_id, title, updated, urls, created) values ($userId, $appId, '$title', $created, '$urls', $created)");
    if (! ($messageCollectionId = mysqli_insert_id($this->db))) {
      throw new SocialSpiException("Insertion failed.", ResponseError::$INTERNAL_ERROR);
    } else {
      // The message collection created is returned so the client code can get the id of the created message collection.
      // Otherwise it's difficult for the client code to reference the created message collection.
      $collection = new MessageCollection($messageCollectionId, $messageCollection['title']);
      $collection->setUpdated($created);
      $collection->setTotal(0);
      $collection->setUnread(0);
      $collection->setUrls($messageCollection['urls']);
      return $collection;
    }
  }

  public function updateMessageCollection($userId, $appId, $messageCollection) {
    $this->checkDb();
    $id = intval($messageCollection['id']);
    $userId = intval($userId);
    $appId = intval($appId);
    $title = mysqli_real_escape_string($this->db, trim($messageCollection['title']));
    if (strlen($title) == 0) {
      throw new SocialSpiException("Can't set the title to empty.");
    }
    $urls = null;
    if (isset($messageCollection['urls'])) {
      $urls = mysqli_real_escape_string($this->db, json_encode($messageCollection['urls']));
    }
    $updated = time();
    $query = "update message_collections set title = '$title', updated = $updated, urls = '$urls' where id = $id and app_id = $appId and person_id = $userId";
    $ret = mysqli_query($this->db, $query);
    if (mysqli_affected_rows($this->db) != 1) {
      throw new SocialSpiException("Can't update the message collection. Please check the ownership.", ResponseError::$BAD_REQUEST);
    }
    return $ret;
  }
  
  public function deleteMessageCollection($userId, $appId, $msgCollId) {
    $this->checkDb();
    $msgCollId = intval($msgCollId);
    $appId = intval($appId);
    $userId = intval($userId);
    mysqli_query($this->db, "delete from message_collections where id = $msgCollId and app_id = $appId and person_id = $userId");
    if (mysqli_affected_rows($this->db) != 1) {
      throw new SocialSpiException("Can't delete the message collection. Please check the ownership.", ResponseError::$BAD_REQUEST);
    }
    return mysqli_query($this->db, "delete from message_groups where message_collection_id = $msgCollId");
  }
  
  public function getMessageCollections($userId, $appId, $fields, $options) {
    // TODO: Supports filtered fields, options. Supports unread and total.
    $this->checkDb();
    $appId = intval($appId);
    $userId = intval($userId);
    
    $countQuery = "select count(*) from message_collections where person_id = $userId and app_id = $appId";
    $res = mysqli_query($this->db, $countQuery);
    if ($res !== false) {
      list($totalResults) = mysqli_fetch_row($res);
    } else {
      $totalResults = '0';
    }
    $startIndex = $options->getStartIndex();
    $count = $options->getCount();
    $collections = array();
    $collections['totalResults'] = $totalResults;
    $collections['startIndex'] = $startIndex;
    $collections['count'] = $count;
    
    $query = "select id, title, updated, urls from message_collections where person_id = $userId and app_id = $appId limit $startIndex, $count";
    $res = mysqli_query($this->db, $query);
    if ($res) {
      if (@mysqli_num_rows($res)) {
        while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
          $collection = new MessageCollection($row['id'], $row['title']);
          $collection->setUpdated($row['updated']);
          $collection->setTitle($row['title']);
          if (isset($row['urls'])) {
            $collection->setUrls(json_decode($row['urls']));
          }
          $collections[] = $collection;
        }
      }
      return $collections;
    } else {
      throw new SocialSpiException("Can't retrieve message collections.", ResponseError::$INTERNAL_ERROR);
    }
  }
  
  public function createActivity($person_id, $activity, $app_id = '0') {
    $this->checkDb();
    $app_id = intval($app_id);
    $person_id = intval($person_id);
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

  public function getActivities($ids, $appId, $sortBy, $filterBy, $filterOp, $filterValue, $startIndex, $count, $fields, $activityIds) {
    //TODO add support for filterBy, filterOp and filterValue
    $this->checkDb();
    $activities = array();
    $ids = array_map('intval', $ids);
    $ids = implode(',', $ids);
    if (isset($activityIds) && is_array($activityIds)) {
      $activityIds = array_map('intval', $activityIds);
      $activityIdQuery = " and activities.id in (" . implode(',', $activityIds);
    } else {
      $activityIdQuery = '';
    }
    $appIdQuery = $appId ? " and activities.app_id = " . intval($appId) : '';

    // return a proper totalResults count
    $res = mysqli_query($this->db, "select count(id) from activities where activities.person_id in ($ids) $activityIdQuery $appIdQuery");
    if ($res !== false) {
      list($totalResults) = mysqli_fetch_row($res);
    } else {
      $totalResults = '0';
    }
    $startIndex = (! is_null($startIndex) && $startIndex !== false && is_numeric($startIndex)) ? intval($startIndex) : '0';
    $count = (! is_null($count) && $count !== false && is_numeric($count)) ? intval($count) : '20';
    $activities['totalResults'] = $totalResults;
    $activities['startIndex'] = $startIndex;
    $activities['count'] = $count;
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
				activities.person_id in ($ids)
				$activityIdQuery
				$appIdQuery
			order by
				created desc
			limit
				$startIndex, $count
			";
    $res = mysqli_query($this->db, $query);
    if ($res) {
      if (@mysqli_num_rows($res)) {
        while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
          $activity = new Activity($row['activity_id'], $row['person_id']);
          $activity->setStreamTitle('activities');
          $activity->setTitle($row['activity_title']);
          $activity->setBody($row['activity_body']);
          $activity->setPostedTime($row['created']);
          $activity->setMediaItems($this->getMediaItems($row['activity_id']));
          $activities[] = $activity;
        }
      } elseif (isset($activityIds) && is_array($activityIds)) {
        // specific activity id was specified, return a not found flag
        return false;
      }
      return $activities;
    } else {
      return false;
    }
  }

  public function deleteActivities($userId, $appId, $activityIds) {
    $this->checkDb();
    $activityIds = array_map('intval', $activityIds);
    $activityIds = implode(',', $activityIds);
    $userId = intval($userId);
    $appId = intval($appId);
    mysqli_query($this->db, "delete from activities where person_id = $userId and app_id = $appId and id in ($activityIds)");
    return (mysqli_affected_rows($this->db) != 0);
  }

  private function getMediaItems($activity_id) {
    $media = array();
    $activity_id = intval($activity_id);
    $res = mysqli_query($this->db, "select mime_type, media_type, url from activity_media_items where activity_id = $activity_id");
    while (list($mime_type, $type, $url) = @mysqli_fetch_row($res)) {
      $media[] = new MediaItem($mime_type, $type, $url);
    }
    return $media;
  }

  public function getFriendIds($person_id) {
    $this->checkDb();
    $ret = array();
    $person_id = intval($person_id);
    $res = mysqli_query($this->db, "select person_id, friend_id from friends where person_id = $person_id or friend_id = $person_id");
    while (list($pid, $fid) = @mysqli_fetch_row($res)) {
      $id = ($pid == $person_id) ? $fid : $pid;
      $ret[] = $id;
    }
    return $ret;
  }

  public function setAppData($person_id, $key, $value, $app_id) {
    $this->checkDb();
    $person_id = intval($person_id);
    $key = mysqli_real_escape_string($this->db, $key);
    $value = mysqli_real_escape_string($this->db, $value);
    $app_id = intval($app_id);
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

  public function deleteAppData($person_id, $key, $app_id) {
    $this->checkDb();
    $person_id = intval($person_id);
    $app_id = intval($app_id);
    if ($key == '*') {
      if (! @mysqli_query($this->db, "delete from application_settings where application_id = $app_id and person_id = $person_id")) {
        return false;
      }
    } else {
      $key = mysqli_real_escape_string($this->db, $key);
      if (! @mysqli_query($this->db, "delete from application_settings where application_id = $app_id and person_id = $person_id and name = '$key'")) {
        return false;
      }
    }
    return true;
  }

  public function getAppData($ids, $keys, $app_id) {
    $this->checkDb();
    $data = array();
    $ids = array_map('intval', $ids);
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

  public function getPeople($ids, $fields, $options, $token) {
    $first = $options->getStartIndex();
    $max = $options->getCount();
    $this->checkDb();
    $ret = array();
    $filterQuery = '';
    if ($options->getFilterBy() == 'hasApp') {
      // remove the filterBy field, it's taken care of in the query already, otherwise filterResults will disqualify all results
      $options->setFilterBy(null);
      $appId = $token->getAppId();
      $filterQuery = " and id in (select person_id from person_applications where application_id = $appId)";
    } elseif ($options->getFilterBy() == 'all') {
      $options->setFilterBy(null);
    }
    $query = "select * from persons where id in (" . implode(',', $ids) . ") $filterQuery order by id ";
    $res = mysqli_query($this->db, $query);
    if ($res) {
      while ($row = @mysqli_fetch_array($res, MYSQLI_ASSOC)) {
        $person_id = $row['id'];
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
          $person->setPhotos(array(new Photo($this->url_prefix . $row['thumbnail_url'], 'thumbnail', true)));
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
        if (isset($fields['activities']) || in_array('@all', $fields)) {
          $activities = array();
          $res2 = mysqli_query($this->db, "select activity from person_activities where person_id = " . $person_id);
          while (list($activity) = @mysqli_fetch_row($res2)) {
            $activities[] = $activity;
          }
          $person->setActivities($activities);
        }
        if (isset($fields['addresses']) || in_array('@all', $fields)) {
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
        if (isset($fields['bodyType']) || in_array('@all', $fields)) {
          $res2 = mysqli_query($this->db, "select * from person_body_type where person_id = " . $person_id);
          if (@mysqli_num_rows($res2)) {
            $row = @mysqli_fetch_array($res2, MYSQLI_ASSOC);
            $bodyType = new BodyType();
            $bodyType->setBuild($row['build']);
            $bodyType->setEyeColor($row['eye_color']);
            $bodyType->setHairColor($row['hair_color']);
            $bodyType->setHeight($row['height']);
            $bodyType->setWeight($row['weight']);
            $person->setBodyType($bodyType);
          }
        }
        if (isset($fields['books']) || in_array('@all', $fields)) {
          $books = array();
          $res2 = mysqli_query($this->db, "select book from person_books where person_id = " . $person_id);
          while (list($book) = @mysqli_fetch_row($res2)) {
            $books[] = $book;
          }
          $person->setBooks($books);
        }
        if (isset($fields['cars']) || in_array('@all', $fields)) {
          $cars = array();
          $res2 = mysqli_query($this->db, "select car from person_cars where person_id = " . $person_id);
          while (list($car) = @mysqli_fetch_row($res2)) {
            $cars[] = $car;
          }
          $person->setCars($cars);
        }
        if (isset($fields['currentLocation']) || in_array('@all', $fields)) {
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
        if (isset($fields['emails']) || in_array('@all', $fields)) {
          $emails = array();
          $res2 = mysqli_query($this->db, "select address, email_type from person_emails where person_id = " . $person_id);
          while (list($address, $type) = @mysqli_fetch_row($res2)) {
            $emails[] = new Email(strtolower($address), $type); // TODO: better email canonicalization; remove dups
          }
          $person->setEmails($emails);
        }
        if (isset($fields['food']) || in_array('@all', $fields)) {
          $foods = array();
          $res2 = mysqli_query($this->db, "select food from person_foods where person_id = " . $person_id);
          while (list($food) = @mysqli_fetch_row($res2)) {
            $foods[] = $food;
          }
          $person->setFood($foods);
        }
        if (isset($fields['heroes']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select hero from person_heroes where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setHeroes($strings);
        }
        if (isset($fields['interests']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select interest from person_interests where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setInterests($strings);
        }
        $organizations = array();
        $fetchedOrg = false;
        if (isset($fields['jobs']) || in_array('@all', $fields)) {
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
              $res3 = mysqli_query($this->db, "select * from addresses where id = " . $row['address_id']);
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
        if (isset($fields['schools']) || in_array('@all', $fields)) {
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
              $res3 = mysqli_query($this->db, "select * from addresses where id = " . $row['address_id']);
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
        if ($fetchedOrg) {
          $person->setOrganizations($organizations);
        }
        //TODO languagesSpoken, currently missing the languages / countries tables so can't do this yet
        if (isset($fields['movies']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select movie from person_movies where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setMovies($strings);
        }
        if (isset($fields['music']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select music from person_music where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setMusic($strings);
        }
        if (isset($fields['phoneNumbers']) || in_array('@all', $fields)) {
          $numbers = array();
          $res2 = mysqli_query($this->db, "select number, number_type from person_phone_numbers where person_id = " . $person_id);
          while (list($number, $type) = @mysqli_fetch_row($res2)) {
            $numbers[] = new Phone($number, $type);
          }
          $person->setPhoneNumbers($numbers);
        }
        if (isset($fields['ims']) || in_array('@all', $fields)) {
          $ims = array();
          $res2 = mysqli_query($this->db, "select value, value_type from person_ims where person_id = " . $person_id);
          while (list($value, $type) = @mysqli_fetch_row($res2)) {
            $ims[] = new Im($value, $type);
          }
          $person->setIms($ims);
        }
        if (isset($fields['accounts']) || in_array('@all', $fields)) {
          $accounts = array();
          $res2 = mysqli_query($this->db, "select domain, userid, username from person_accounts where person_id = " . $person_id);
          while (list($domain, $userid, $username) = @mysqli_fetch_row($res2)) {
            $accounts[] = new Account($domain, $userid, $username);
          }
          $person->setAccounts($accounts);
        }
        if (isset($fields['quotes']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select quote from person_quotes where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setQuotes($strings);
        }
        if (isset($fields['sports']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select sport from person_sports where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setSports($strings);
        }
        if (isset($fields['tags']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select tag from person_tags where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setTags($strings);
        }

        if (isset($fields['turnOns']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select turn_on from person_turn_ons where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setTurnOns($strings);
        }
        if (isset($fields['turnOffs']) || in_array('@all', $fields)) {
          $strings = array();
          $res2 = mysqli_query($this->db, "select turn_off from person_turn_offs where person_id = " . $person_id);
          while (list($data) = @mysqli_fetch_row($res2)) {
            $strings[] = $data;
          }
          $person->setTurnOffs($strings);
        }
        if (isset($fields['urls']) || in_array('@all', $fields)) {
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
    try {
      $ret = $this->filterResults($ret, $options);
      $ret['totalSize'] = count($ret);
    } catch (Exception $e) {
      $ret['totalSize'] = count($ret) - 1;
      $ret['filtered'] = 'false';
    }
    if ($first !== false && $max !== false && is_numeric($first) && is_numeric($max) && $first >= 0 && $max > 0) {
      $count = 0;
      $result = array();
      foreach ($ret as $id => $person) {
        if ($id == 'totalSize' || $id == 'filtered') {
          $result[$id] = $person;
          continue;
        }
        if ($count >= $first && $count < $first + $max) {
          $result[$id] = $person;
        }
        ++ $count;
      }
      return $result;
    } else {
      return $ret;
    }
  }

  private function filterResults($peopleById, $options) {
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
        if ($this->passesFilter($person, $filterBy, $op, $value)) {
          $filteredResults[$id] = $person;
          $numFilteredResults ++;
        }
      } else {
        $filteredResults[$id] = $person; // copy extra metadata verbatim
      }
    }
    if (! isset($filteredResults['totalSize'])) {
      $filteredResults['totalSize'] = $numFilteredResults;
    }
    return $filteredResults;
  }

  private function passesFilter($person, $filterBy, $op, $value) {
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

  private function passesStringFilter($fieldValue, $op, $filterValue) {
    switch ($op) {
      case CollectionOptions::FILTER_OP_EQUALS:
        return $fieldValue == $filterValue;
      case CollectionOptions::FILTER_OP_CONTAINS:
        return stripos($fieldValue, $filterValue) !== false;
      case CollectionOptions::FILTER_OP_STARTSWITH:
        return stripos($fieldValue, $filterValue) === 0;
      default:
        throw new Exception('unrecognized filterOp');
    }
  }
}
