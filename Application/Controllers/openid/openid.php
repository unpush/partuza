<?php
/**
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

// The OpenID library is full of warnings and notices, so to suppress
// those we force E_ERROR only for our OpenID event
error_reporting(E_ERROR);

require_once "Auth/OpenId/Server.php";

class openidController extends baseController {

  public function __construct() {
    parent::__construct();
    $this->openid = $this->model('openid');
  }

  public function auth() {
    $server = &$this->openid->getOpenIdServer();
    $request = $server->decodeRequest();
    $this->openid->setRequestInfo($request);
    if (in_array($request->mode, array('checkid_immediate', 'checkid_setup'))) {
      if ($request->idSelect()) {
        // Perform IDP-driven identifier selection
        if ($request->mode == 'checkid_immediate') {
          $response = & $request->answer(false);
        } else {
          return $this->trust_render($request);
        }
      } else if ((! $request->identity) && (! $request->idSelect())) {
        // No identifier used or desired; display a page saying
        // so.
        return noIdentifier_render();
      } else if ($request->immediate) {
        $response = &$request->answer(false, buildURL());
      } else {
        if (! isset($_SESSION['id'])) {
          $this->login_render();
          return;
        }
        return $this->trust_render($request);
      }
    } else {
      $response = &$server->handleRequest($request);
    }
    $webresponse = &$server->encodeResponse($response);
    if ($webresponse->code != AUTH_OPENID_HTTP_OK) {
      header(sprintf("HTTP/1.1 %d ", $webresponse->code), true, $webresponse->code);
    }
    foreach ($webresponse->headers as $k => $v) {
      header("$k: $v");
    }
    header(header_connection_close);
    print $webresponse->body;
    exit(0);
  }

  public function login() {
    if (! isset($_SESSION['id'])) {
      $this->login_render();
      return;
    }
    $info = $this->openid->getRequestInfo();
    return $this->doAuth($info);
  }

  public function trust() {
    $info = $this->openid->getRequestInfo();
    $trusted = isset($_POST['trust']);
    return $this->doAuth($info, $trusted, true, @$_POST['idSelect']);
  }

  private function doAuth($info, $trusted = null, $fail_cancels = false, $idpSelect = null) {
    if (! $info) {
      // There is no authentication information, so bail
      return $this->openid->authCancel(null);
    }
    if ($info->idSelect()) {
      if ($idpSelect) {
        $req_url = $this->openid->idURL($idpSelect);
      } else {
        $trusted = false;
      }
    } else {
      $req_url = $info->identity;
    }
    $id_url = $this->openid->idUrl($_SESSION['id']);
    $this->openid->setRequestInfo($info);
    
    if ((! $info->idSelect()) && ($req_url != $id_url)) {
      return $this->openid->authCancel($info);
    }
    if ($trusted) {
      $this->openid->setRequestInfo();
      $server = &$this->openid->getOpenIdServer();
      $response = &$info->answer(true, null, $req_url); 
      // Generate a response to send to the user agent.
      $webresponse = &$server->encodeResponse($response);
      header('Location: ' . $webresponse->headers['location']);
    } elseif ($fail_cancels) {
      return $this->openid->authCancel($info);
    } else {
      return $this->trust_render($info);
    }
  }

  private function trust_render($info) {
    $GLOBALS['render'] = array('info' => serialize($info));
    $this->template('openid/trust.php');
  }

  private function login_render() {
    $GLOBALS['render'] = array('openid' => 'login');
    $this->template('home/home.php');
  }
}
