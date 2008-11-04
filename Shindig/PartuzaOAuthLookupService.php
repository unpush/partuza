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
 * Basic implementation of OAuthLookupService using BasicOAuthDataStore.
 */
class PartuzaOAuthLookupService extends OAuthLookupService {
  public function thirdPartyHasAccessToUser($oauthRequest, $appUrl, $userId) {
    $appId = $this->getAppId($appUrl);
    return $this->hasValidSignature($oauthRequest, $appUrl, $appId) 
           && $this->userHasAppInstalled($userId, $appId);
  }

  private function hasValidSignature($oauthRequest, $appUrl, $appId) {
    try {
      $server = new OAuthServer(new PartuzaOAuthDataStore());
      $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
      $server->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
      list($consumer, $token) = $server->verify_request($oauthRequest);
      return true;
    } catch (OAuthException $e) {
      //echo "OAuthException: ".$e->getMessage();
    }
    return false;
  }

  private function userHasAppInstalled($userId, $appId) {
    //TODO: SQL: select count(1) from person_applications where person_id = $userId and application_id $appId
    return true; // a real implementation would look this up
  }

  public function getSecurityToken($appUrl, $userId) {
    return new OAuthSecurityToken($userId, $appUrl, $this->getAppId($appUrl), "partuza");
  }

  private function getAppId($appUrl) { 
    // TODO: SQL: select id from applications where url = $appUrl
    return 0; // a real implementation would look this up
  }
}
