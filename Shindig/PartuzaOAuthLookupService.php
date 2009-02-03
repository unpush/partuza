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

/**
 * Basic implementation of OAuthLookupService based on BasicOAuthDataStore.
 */
class PartuzaOAuthLookupService extends OAuthLookupService {

  public function getSecurityToken($oauthRequest, $appUrl, $userId) {
    try {
      $ds = new PartuzaOAuthDataStore();
      $server = new OAuthServer($ds);
      $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
      $server->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
      // Include the postBody in the signature check, conforming to spec
      if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        $oauthRequest->set_parameter($GLOBALS['HTTP_RAW_POST_DATA'], '');
      }
      if (! isset($oauthRequest->parameters['oauth_token'])) {
        // 2 legged OAuth request, do our own magic instead of counting OAuth.php since it doesn't support 2 legged OAuth very well (read: not at all)
        $consumerToken = $ds->lookup_consumer($oauthRequest->parameters['oauth_consumer_key']);
        $signature_method = new OAuthSignatureMethod_HMAC_SHA1();
        $signature_valid = $signature_method->check_signature($oauthRequest, $consumerToken, null, $_GET["oauth_signature"]);
        if (! $signature_valid) {
          // signature did not check out, abort
          return null;
        }
        return new OAuthSecurityToken($userId, $appUrl, $ds->get_app_id($consumerToken), "partuza");
      } else {
        // 'Regular' 3 legged OAuth
        list($consumer, $token) = $server->verify_request($oauthRequest);
      }
      if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
        unset($oauthRequest->parameters[$GLOBALS['HTTP_RAW_POST_DATA']]);
      }
      $oauthUserId = $ds->get_user_id($token);
      if ($userId && $oauthUserId && $oauthUserId != $userId) {
        return null; // xoauth_requestor_id was provided, but does not match oauth token -> fail
      } else {
        $userId = $oauthUserId; // use userId from oauth token
      }
      return new OAuthSecurityToken($userId, $appUrl, 0, "partuza");
    } catch (OAuthException $e) {
      //echo "OAuthException: ".$e->getMessage();
      return null;
    }
  }
}
