<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');

$cookie = __DIR__ . '/cookie.txt';

Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$db = Doris::init('financiero');


$url = 'https://apis.interbank.pe/eureca/api/login?_=1584755784193';
$header = array(
  'Accept' => '*/*',
  'Accept-Encoding' => 'gzip, deflate, br',
  'Accept-Language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'Access-Control-Request-Headers' => 'cache-control,expires,ocp-apim-subscription-key,ocp-apim-trace,pragma',
  'Access-Control-Request-Method' => 'POST',
  'Connection' => 'keep-alive',
  'Host' => 'apis.interbank.pe',
  'Origin' => 'https://cobrosimple.interbank.pe',
  'Referer' => 'https://cobrosimple.interbank.pe/login',
  'Sec-Fetch-Mode' => 'cors',
  'Sec-Fetch-Site' => 'same-site',
  'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
);
$rp  = Curly(CURLY_OPTIONS, $url, $header, null, $cookie);
echo $rp;

$url = 'https://apis.interbank.pe/eureca/api/login?_=1584755784193';//' . time() . '000';
$header = array(
  'Accept' => 'application/json, text/plain, */*',
  'Accept-Encoding' => 'gzip, deflate, br',
  'Accept-Language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'Cache-Control' => 'no-cache',
  'Connection' => 'keep-alive',
  'Content-Length' => '42',
  'Content-Type' => 'application/x-www-form-urlencoded',
  'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
  'Host' => 'apis.interbank.pe',
//  'Ocp-Apim-Subscription-Key' => 'c9664a560c184e8cb857e5d2a7efabc4',
  'Ocp-Apim-Trace' => 'true',
  'Origin' => 'https://cobrosimple.interbank.pe',
  'Pragma' => 'no-cache',
  'Referer' => 'https://cobrosimple.interbank.pe/login',
  'Sec-Fetch-Mode' => 'cors',
  'Sec-Fetch-Site' => 'same-site',
  'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
);
$data = array(
  'username' => '20602497519',
  'password' => 'perra132tmr@',
);
$rp  = Curly(CURLY_POST, $url, $header, $data, $cookie);
echo $rp;
