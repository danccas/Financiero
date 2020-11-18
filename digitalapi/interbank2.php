<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');

$cookie = __DIR__ . '/cookie_interbank.txt';

Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$db = Doris::init('financiero');



$cmd = "curl 'https://apis.interbank.pe/eureca/api/debt?PageNumber=1&ColumnName=&InputSearch=&Asc=true&Service=&Status=&DateForFilter=&DateFrom=&DateTo=&_=1590083315165' \
  -H 'Connection: keep-alive' \
  -H 'Pragma: no-cache' \
  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36' \
  -H 'Ocp-Apim-Subscription-Key: c9664a560c184e8cb857e5d2a7efabc4' \
  -H 'Accept: application/json, text/plain, */*' \
  -H 'Cache-Control: no-cache' \
  -H 'Ocp-Apim-Trace: true' \
  -H 'Authorization: bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjaWQiOiI4OCIsInJ1YyI6IjIwNjAyNDk3NTE5IiwicHJmbCI6IjAiLCJleHAiOjE1OTAwODM5ODMsImlzcyI6IkV1cmVjYSIsImF1ZCI6IkV1cmVjYSJ9.PvrAUCghfYzFc-LG1CBXb41D901g4yuDRfMhyGWwtrg' \
  -H 'Expires: Sat, 01 Jan 2000 00:00:00 GMT' \
  -H 'Origin: https://cobrosimple.interbank.pe' \
  -H 'Sec-Fetch-Site: same-site' \
  -H 'Sec-Fetch-Mode: cors' \
  -H 'Sec-Fetch-Dest: empty' \
  -H 'Referer: https://cobrosimple.interbank.pe/home' \
  -H 'Accept-Language: en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5,und;q=0.4,ja;q=0.3' \
  --compressed";
$rp = exec($cmd);
echo ">>>" . $rp . "<<<";
?>

