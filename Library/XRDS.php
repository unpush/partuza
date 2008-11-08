<?php
$xrds = '<XRDS xmlns="xri://$xrds">
    <XRD xml:id="oauth" xmlns:simple="http://xrds-simple.net/core/1.0" xmlns="xri://$XRD*($v*2.0)" version="2.0">
      <Type>xri://$xrds*simple</Type>
      <Expires>2008-12-31T23:59:59Z</Expires>
      <Service priority="10">
        <Type>http://oauth.net/core/1.0/endpoint/request</Type>
        <Type>http://oauth.net/core/1.0/parameters/auth-header</Type>
        <Type>http://oauth.net/core/1.0/parameters/uri-query</Type>
        <Type>http://oauth.net/core/1.0/signature/PLAINTEXT</Type>
        <URI>{host}/oauth/request_token</URI>
      </Service>
      <Service priority="10">
        <Type>http://oauth.net/core/1.0/endpoint/authorize</Type>
        <Type>http://oauth.net/core/1.0/parameters/uri-query</Type>
        <URI>{host}/oauth/authorize</URI>
      </Service>
      <Service priority="10">
        <Type>http://oauth.net/core/1.0/endpoint/access</Type>
        <Type>http://oauth.net/core/1.0/parameters/auth-header</Type>
        <Type>http://oauth.net/core/1.0/parameters/uri-query</Type>
        <Type>http://oauth.net/core/1.0/signature/PLAINTEXT</Type>
        <URI>{host}/oauth/access_token</URI>
      </Service>
      <Service priority="10">
        <Type>http://oauth.net/core/1.0/endpoint/resource</Type>
        <Type>http://oauth.net/core/1.0/parameters/auth-header</Type>
        <Type>http://oauth.net/core/1.0/parameters/uri-query</Type>
        <Type>http://oauth.net/core/1.0/signature/HMAC-SHA1</Type>
      </Service>
      <Service priority="10">
        <Type>http://oauth.net/discovery/1.0/consumer-identity/static</Type>
        <LocalID>0685bd9184jfhq22</LocalID>
      </Service>
    </XRD>
    <XRD xmlns:simple="http://xrds-simple.net/core/1.0" xmlns="xri://$XRD*($v*2.0)" xmlns:os="http://ns.opensocial.org/" version="2.0">
        <Type>xri://$xrds*simple</Type>
        <Service>
          <Type>http://portablecontacts.net/spec/1.0</Type>
          <URI>{url}/social/rest/people</URI>
        </Service>
        <Service>
          <Type>http://ns.opensocial.org/people/0.8</Type>
          <os:URI-Template>{url}/social/rest/people/{guid}/{selector}{-prefix|/|pid}?format=atom</os:URI-Template>
        </Service>
        <Service>
          <Type>http://ns.opensocial.org/activities/0.8</Type>
          <os:URI-Template>{url}/social/rest/activities/{guid}/{selector}?format=atom</os:URI-Template>
        </Service>
        <Service>
          <Type>http://ns.opensocial.org/appdata/0.8</Type>
          <os:URI-Template>{url}/social/rest/appdata/{guid}/{selector}?format=atom</os:URI-Template>
        </Service>
        <Service>
          <Type>http://ns.opensocial.org/messages/0.8</Type>
          <os:URI-Template>{url}/social/rest/messages/{guid}/outbox/{msgid}</os:URI-Template>
        </Service>
        <Service priority="10">
          <Type>http://oauth.net/discovery/1.0</Type>
          <URI>#oauth</URI>
        </Service>
    </XRD>
</XRDS>';

header("Content-Type: application/xrds+xml");
//header("Content-Type: text/xml"); // turn this on and comment out the previous line to view it easily in a browser
// output the XRDS document with the correct URL to our gadget server filled in
$xrds = str_replace('{url}', PartuzaConfig::get('gadget_server'), $xrds);
$xrds = str_replace('{host}', 'http://'.$_SERVER['HTTP_HOST'], $xrds);
echo $xrds;
