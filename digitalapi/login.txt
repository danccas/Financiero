<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');

Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$db = Doris::init('financiero');

$cookie = '/var/www/financiero.anccas.org/digitalapi/cookie.txt';

$header001 = array(
  ':authority' => 'uas5.cams.scotiabank.com.pe',
  ':method' => 'OPTIONS',
  ':path' => '/aos/rest/user/logout',
  ':scheme' => 'https',
  'accept' => '*/*',
  'accept-encoding' => 'gzip, deflate, br',
  'accept-language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'access-control-request-headers' => 'content-type,x-aos-serviceaccount',
  'access-control-request-method' => 'POST',
  'origin' => 'https://mi.scotiabank.com.pe',
  'referer' => 'https://mi.scotiabank.com.pe/login',
  'sec-fetch-mode' => 'cors',
  'sec-fetch-site' => 'same-site',
  'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
);

$header002 = array(
  'content-type' => 'application/json',
  ':authority' => 'uas5.cams.scotiabank.com.pe',
  ':method' => 'POST',
  ':path' => '/aos/rest/user/logout',
  ':scheme' => 'https',
  'accept' => 'application/json, text/plain, */*',
  'accept-encoding' => 'gzip, deflate, br',
  'accept-language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'origin' => 'https://mi.scotiabank.com.pe',
  'referer' => 'https://mi.scotiabank.com.pe/login',
  'sec-fetch-mode' => 'cors',
  'sec-fetch-site' => 'same-site',
  'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
  'x-aos-serviceaccount' => 'SEL',
);

$header01 = array(
  'content-type' => 'application/json',
  'Host' => 'mi.scotiabank.com.pe',
  'accept' => 'application/json, text/plain, */*',
  'accept-encoding' => 'gzip, deflate, br',
  'accept-language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'origin' => 'https://mi.scotiabank.com.pe',
  'referer' => 'https://mi.scotiabank.com.pe/login',
  'sec-fetch-mode' => 'cors',
  'sec-fetch-site' => 'same-site',
  'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
  'udid' => 'd33364566fbbf712f328b9a4e5a8e575',
);

$header02 = array(
  'content-type' => 'application/json',
  'Host' => 'mi.scotiabank.com.pe',
  'accept' => 'application/json, text/plain, */*',
  'accept-encoding' => 'gzip, deflate, br',
  'accept-language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'origin' => 'https://mi.scotiabank.com.pe',
  'referer' => 'https://mi.scotiabank.com.pe/login/password',
  'sec-fetch-mode' => 'cors',
  'sec-fetch-site' => 'same-site',
  'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
  'udid' => 'd33364566fbbf712f328b9a4e5a8e575',
);

$header03 = array(
  'content-type' => 'application/json',
  ':authority' => 'uas5.cams.scotiabank.com.pe',
  ':method' => 'GET',
  ':scheme' => 'https',
  'accept' => 'application/json, text/plain, */*',
  'accept-encoding' => 'gzip, deflate, br',
  'accept-language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'origin' => 'https://mi.scotiabank.com.pe',
  'referer' => 'https://mi.scotiabank.com.pe/login/password',
  'sec-fetch-mode' => 'cors',
  'sec-fetch-site' => 'same-site',
  'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
);



$header04 = array(
  ':authority' => 'uas5.cams.scotiabank.com.pe',
  ':method' => 'OPTIONS',
  ':path' => '/aos/rest/user/authenticate/v1',
  ':scheme' => 'https',
  'accept' => '*/*',
  'accept-encoding' => 'gzip, deflate, br',
  'accept-language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'access-control-request-headers' => 'content-type,x-aos-serviceaccount',
  'access-control-request-method' => 'POST',
  'origin' => 'https://mi.scotiabank.com.pe',
  'referer' => 'https://mi.scotiabank.com.pe/login/password',
  'sec-fetch-mode' => 'cors',
  'sec-fetch-site' => 'same-site',
  'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
);

$header05 = array(
  'content-type' => 'application/json',
  ':authority' => 'uas5.cams.scotiabank.com.pe',
  ':method' => 'POST',
  ':path' => '/aos/rest/user/authenticate/v1',
  ':scheme' => 'https',
  'accept' => 'application/json, text/plain, */*',
  'accept-encoding' => 'gzip, deflate, br',
  'accept-language' => 'en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5',
  'content-length' => '358',
  'origin' => 'https://mi.scotiabank.com.pe',
  'referer' => 'https://mi.scotiabank.com.pe/login/password',
  'sec-fetch-mode' => 'cors',
  'sec-fetch-site' => 'same-site',
  'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36',
  'x-aos-serviceaccount' => 'SEL',
);

#goto salto;

echo "\nPASO00";
$url = 'https://mi.scotiabank.com.pe/login';
$data = null;
$paso00 = Curly(CURLY_GET, $url, null, $data, $cookie);
print_r($paso00);



echo "\nPASO001";
$url = 'https://uas5.cams.scotiabank.com.pe/aos/rest/user/logout';
$data = null;
$paso001 = Curly(CURLY_OPTIONS, $url, $header001, null, $cookie);
print_r($paso001);


echo "\nPASO002";
$url = 'https://uas5.cams.scotiabank.com.pe/aos/rest/user/logout';
$data = null;
$paso002 = Curly(CURLY_POST, $url, $header002, null, $cookie);
print_r($paso002);


echo "\nPASO01";
$url = 'https://mi.scotiabank.com.pe/digital-api/user/flow';
$data = '{"document_type":"DNI","document_number":"49008351"}';
$paso01 = Curly(CURLY_POST, $url, $header01, $data, $cookie);
#$paso01 = '{"user_id":"7fec6347-23d0-4a58-ba6d-fc9d9d57a2aa","flag_user_state":"L"}';
print_r($paso01);
$paso01 = json_decode($paso01, true);

echo "\nPASO02";
$url = 'https://mi.scotiabank.com.pe/digital-api/login/wrequest?redirectTo=';
$data = '{"state":"1327016769","relative":"1234567891234567891234567890123456789123456","platform":0}';
$paso02 = Curly(CURLY_POST, $url, $header02, $data, $cookie);
#$paso02 = '{"location":"https://uas5.cams.scotiabank.com/aos/oauth2/authorize?scope=openid+profile&response_type=code&redirect_uri=https%3A%2F%2Fmi.scotiabank.com.pe%2Fdigital-api%2Flogin%2Fvalidateauthcode&state=ZGYxMmYwODctYTJhMS00Y2Q2LTgzNGEtYWU4Y2I3M2M4ZDk1&code_challenge_method=S256&client_id=JOYPROD&code_challenge=wx_gxQUrAmG3OM5DSe9lgupb9neQIruglWnhFBHhCrY&request=eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJKT1lQUk9EIiwic2NvcGUiOiJvcGVuaWQgcHJvZmlsZSIsImlzcyI6IkpPWVBST0QiLCJyZXNwb25zZV90eXBlIjoiY29kZSIsInJlZGlyZWN0X3VyaSI6Imh0dHBzOlwvXC9taS5zY290aWFiYW5rLmNvbS5wZVwvZGlnaXRhbC1hcGlcL2xvZ2luXC92YWxpZGF0ZWF1dGhjb2RlIiwic3RhdGUiOiJaR1l4TW1Zd09EY3RZVEpoTVMwMFkyUTJMVGd6TkdFdFlXVTRZMkkzTTJNNFpEazEiLCJleHAiOjE1ODMzNTcwNzUsImNsaWVudF9pZCI6IkpPWVBST0QifQ.YYz8gmN9aA7YhM2UkI8atuFPl9vhagvRIRa3dzXXOOK9zKb7S7jkmm2GwmkbcUQ0m2X6XbchiJ64M1_01FN0kvpCPezMVwB2QwfdY-WecC-e89k0xjrF9ghBBuiHl7Oa40Q_DINHTsLA3LDliv1PwJRT7ydLrC4yJZa5n2wxwedmkib35hTPZra_EcB0CFqV01aQ_AfzqS2vYOsd1E4zQyOqjzSq9r5QOQR3iNoY8N96dfEOMTcH909-NcqqQuxZLzjIgsDkjNOiWsTKKy4AuQIAqLlfvktrm2NAmxIw5EmwXJH3o4Zs_VsTckYXh2oKQucf22K9-aw-72EB-t2y0A&user_agent_type=spa&service_account=sel"}';
print_r($paso02);
$paso02 = json_decode($paso02, true);


echo "\nPASO03";
$url = $paso02['location'];
$paso03 = Curly(CURLY_GET, $url, $header03, null, $cookie);
print_r($paso03);

#salto:
echo "\nPASO04";
$url = 'https://uas5.cams.scotiabank.com.pe/aos/rest/user/authenticate/v1';
$paso04 = Curly(CURLY_OPTIONS, $url, $header04, null, $cookie);
print_r($paso04);


echo "\nPASO05";
$url = 'https://uas5.cams.scotiabank.com.pe/aos/rest/user/authenticate/v1';
$data = '{"callbackUrl":"https://uas5.cams.scotiabank.com/aos/rest/user/authenticate/v1","callbacks":[{"input":{"name":"uuid","value":"' . $paso01['user_id'] . '"},"type":"UUIDCallback","header":"UUID"},{"input":{"name":"password","value":"123456789"},"type":"PasswordCallback","header":"PWD"}],"stepHeader":"1st Factor Authentication","trustedDevice":true}';
$paso05 = Curly(CURLY_POST, $url, $header05, $data, $cookie);
print_r($paso05);


