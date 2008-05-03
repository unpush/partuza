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

class ModelException extends Exception {};

class Model {
	// Placeholder for what will be some global data caching, example code of what it might do below
	/* 
	public  $cachable = array();

	public function __call($method, $arguments)
	{
		global $cache;
		$key = "$method:".md5(serialize($arguments));
		$tries = 0;
		if (isset($this->cachable[$method])) {
			do {
				$tries++;
				try {
					$data = $cache->get($key);
					return $data;
				} catch (CacheException $e) {
					// data wasn't in cache, try to lock (to prevent a cache update stampede), then if we get lock attempt to generate the data and populate the cache
					try {
						// add key with timeout of 5 seconds, so even if we crash the lock will go away again at some point
						$cache->add("lock:$key","", 5);
						// successfully added a lock, we must be the first then to fill this in. Generate data and store it!
						$function  = "load_{$method}";
						if (is_callable(array($this, $function))) {
							$data = $this->$function($arguments);
							$cache->set($key, $data);
							$cache->delete("lock:$key");
							return $data;
						} else {
							$cache->delete("lock:$key");
							throw new ModelException("Invalid method: load_{$method}");
						}
					} catch (CacheException $e) {
						// couldn't add lock, this (probably) means that another process is updating this cache
						// other explanation is that the cache server is down ...
						// So either way, ignore this exception and let it re-try the whole loop again until we reached our max tries
						usleep(500);
					}
				}
			} while ($tries <= 10);
		} else {
			// non cachable information, do a plain load
			$function  = "load_{$method}";
			if (is_callable(array($this, $function))) {
				return $this->$function($arguments);
			} else {
				throw new ModelException("Invalid method: load_{$method}");
			}
		}
		return false;
	}*/
}
