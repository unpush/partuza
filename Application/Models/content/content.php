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

class contentModel extends Model {

	public function person_content($arguments)
	{
		global $db;
		if (empty($arguments[0]) || !is_numeric($arguments[0])) {
			return false;
		}
		$ret = array();
		$res = $db->query("select id, title, content, created from content where owner = ".intval($arguments[0]));
		while ($row = $db->fetch_array($res, MYSQL_ASSOC)) {
			$ret[] = $row;
		}
		return $ret;
	}

	public function content_count($arguments)
	{
		global $db;
		if (empty($arguments[0]) || !is_numeric($arguments[0])) {
			return false;
		}
		$res       = $db->query("select count(*) from content where owner = ".intval($arguments[0]));
		list($cnt) = $db->fetch_row($res);
		return $cnt;
	}
}