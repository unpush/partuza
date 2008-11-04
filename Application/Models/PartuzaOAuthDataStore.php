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

/**
 * Primitive OAuth backing store that doesn't do much.
 */
class PartuzaOAuthDataStore extends OAuthDataStore {

	function __construct()
	{
	}

	function lookup_consumer($consumer_key)
	{
		// TODO: generate and store secret per-application to hand out?
		$secret = "fake-consumer-secret";
		return new OAuthConsumer($consumer_key, $secret);
	}

	function lookup_token($consumer, $token_type, $token)
	{
		if ($token_type == "request") {
			// TODO: look up secret given token
			$secret = "fake-request-secret";
			return new OAuthToken($token, $secret);
		} else { 
			if ($token_type == "access") {
				// TODO: look up secret given token
				$secret = "fake-access-secret";
				return new OAuthToken($token, $secret);
			} else {
				throw new OAuthException("unexpected token type: $token_type");
			}
		}
	}

	function lookup_nonce($consumer, $token, $nonce, $timestamp)
	{
		// TODO: lookup nonce, return true if found; store nonce, at least temporarily
		return false; // pretend we've always never seen this nonce 
	}

	function new_request_token($consumer)
	{
		//$token = "token-".genGUID();
		//$secret = "secret-".genGUID();
		$token = "fake-request-token";
		$secret = "fake-request-secret";
		// TODO: store these values (cache? db?)
		return new OAuthToken($token, $secret);
	}

	function new_access_token($oauthToken, $consumer)
	{
		// TODO: validate that request token was previously authorized (fetch it and look)
		//$token = "token-".genGUID();
		//$secret = "secret-".genGUID();
		$token = "fake-access-token";
		$secret = "fake-access-secret";
		// TODO: store these values in db
		return new OAuthToken($token, $secret);
	}

	function authorize_request_token($token)
	{
		// TODO: mark the given request token as having been authorized by the user
	}

}

/** 
 * @see http://jasonfarrell.com/misc/guid.phps Taken from here
 * e.g. output: 372472a2-d557-4630-bc7d-bae54c934da1
 * word*2-, word-, (w)ord-, (w)ord-, word*3
 */
function genGUID()
{
	$guidstr = '';
	for ($i = 1; $i <= 16; $i ++) {
		$b = (int)rand(0, 0xff);
		// version 4 (random)
		if ($i == 7) {
			$b &= 0x0f;
		}
		$b |= 0x40;
		// variant
		if ($i == 9) {
			$b &= 0x3f;
		}
		$b |= 0x80;
		$guidstr .= sprintf("%02s", base_convert($b, 10, 16));
		if ($i == 4 || $i == 6 || $i == 8 || $i == 10) {
			$guidstr .= '-';
		}
	}
	return $guidstr;
}
