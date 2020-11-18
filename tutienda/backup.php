<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');
require_once(ABS_LIBRERIAS . 'financiero.php');

Doris::registerDSN('tienda', 'mysql://root@localhost:3306/futuro');

$db = Doris::init('tienda');

$zoom = 700;

$lat_max  = '-18.85431036';
$lat_min  = '-11.974414';#-3.234414';
$long_min = '-81.58447266';
$long_max = '-69.89501953';

#$lat_max  = '-11.482499299161212';
#$lat_min  = '-11.462858268789034';
#$long_min = '-77.36602834314547';
#$long_max = '-77.28436044306002';

$area = abs($lat_min - $lat_max) * abs($long_min - $long_max);
$size_find = 0.092;

echo "Busquedas: " . ($area / $size_find) . "\n";

for($y = $lat_min; $y >= $lat_max; $y = $y - $size_find) {
  for($x = $long_min; $x <= $long_max; $x = $x + $size_find) {
    $to_x = $x + $size_find;
    $to_y = $y - $size_find;
    $center_x = $x + ($to_x - $x)/2;
    $center_y = $y + ($to_y - $y)/2;

echo "Busqueda en:\n";
echo "ini => " . $y . "," . $x . "\n";
echo "end => " . $to_y . "," . $to_x . "\n";
echo "center => " . $center_y . "," . $center_x . "\n";
$cmd = "curl -s 'https://1fzqk3npw4.execute-api.us-east-1.amazonaws.com/nearby_store_stage/pe' \
  -H 'authority: 1fzqk3npw4.execute-api.us-east-1.amazonaws.com' \
  -H 'accept: */*' \
  -H 'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36' \
  -H 'content-type: application/json' \
  -H 'origin: https://www.tiendacerca.pe' \
  -H 'sec-fetch-site: cross-site' \
  -H 'sec-fetch-mode: cors' \
  -H 'sec-fetch-dest: empty' \
  -H 'referer: https://www.tiendacerca.pe/' \
  -H 'accept-language: en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5,und;q=0.4,ja;q=0.3' \
  --data-binary '{\"south\":" . $to_y . ",\"west\":" . $x . ",\"north\":" . $y . ",\"east\":" . $to_x. ",\"center\":{\"lat\":" . $center_y . ",\"lng\":" . $center_x . "},\"zoom\":" . $zoom . ",\"country_code\":\"pe\"}' \
  --compressed";
$data = exec($cmd);
$data = json_decode($data, true);
echo "Encontramos => " . count($data) . "\n";
if(!empty($data) && count($data) > 1) {
  foreach($data as $d)  {
    $db->insert('tienda', $d, true); 
  }
}

}
}

