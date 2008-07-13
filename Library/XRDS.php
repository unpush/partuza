<?php
$xrds = 
'<XRDS xmlns="xri://$xrds">
    <XRD xmlns:simple="http://xrds-simple.net/core/1.0" xmlns="xri://$XRD*($v*2.0)" xmlns:os="http://ns.opensocial.org/" version="2.0">
        <Type>xri://$xrds*simple</Type>
        <Service>
          <Type>http://ns.opensocial.org/people/0.8</Type>
          <os:URI-Template>{url}/social/rest/people/{guid}/{selector}{-prefix|/|pid}?format=atom</URI-Template>
        </Service>
        <Service>
          <Type>http://ns.opensocial.org/activities/0.8</Type>
          <os:URI-Template>{url}/social/rest/activities/{guid}/{selector}?format=atom</URI-Template>
        </Service>
        <Service>
          <Type>http://ns.opensocial.org/appdata/0.8</Type>
          <os:URI-Template>{url}/social/rest/appdata/{guid}/{selector}?format=atom</URI-Template>
        </Service>
    </XRD>
</XRDS>';

// output the XRDS document with the correct URL to our gadget server filled in
echo str_replace('{url}', Config::get('gadget_server'), $xrds);
