<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');
require_once(ABS_LIBRERIAS . 'financiero.php');

Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$MONEDAS = array('S/' => 1, '$' => 2);

$db = Doris::init('financiero');

$usuario_id = 1;
$cuenta_id  = 24;

$cmd = "curl 'https://apis.interbank.pe/eureca/api/debt?PageNumber=1&ColumnName=&InputSearch=&Asc=true&Service=&Status=&DateForFilter=&DateFrom=&DateTo=&_=" . time() . "345' \
  -H 'Connection: keep-alive' \
  -H 'Pragma: no-cache' \
  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36' \
  -H 'Ocp-Apim-Trace: true' \
  -H 'Accept: application/json, text/plain, */*' \
  -H 'Cache-Control: no-cache' \
  -H 'Authorization: bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJjaWQiOiI4OCIsInJ1YyI6IjIwNjAyNDk3NTE5IiwicHJmbCI6IjAiLCJleHAiOjE1OTA3MzE5NjgsImlzcyI6IkV1cmVjYSIsImF1ZCI6IkV1cmVjYSJ9.I0uEhCTqKhJ6UOXhJxjOc6tWLKU-3quNhsXQVBHI7nA' \
  -H 'Ocp-Apim-Subscription-Key: c9664a560c184e8cb857e5d2a7efabc4' \
  -H 'Expires: Sat, 01 Jan 2000 00:00:00 GMT' \
  -H 'Origin: https://cobrosimple.interbank.pe' \
  -H 'Sec-Fetch-Site: same-site' \
  -H 'Sec-Fetch-Mode: cors' \
  -H 'Sec-Fetch-Dest: empty' \
  -H 'Referer: https://cobrosimple.interbank.pe/home' \
  -H 'Accept-Language: en,es;q=0.9,la;q=0.8,ha;q=0.7,fr;q=0.6,gl;q=0.5,und;q=0.4,ja;q=0.3' \
  --compressed";
$data = exec($cmd);
$data = json_decode($data, true);

if(empty($data)) {
  echo "sin-data";
  exit;
}

if(empty($data['data'])) {
  echo "no-hay-data";
  exit;
}

print_r($data);

foreach($data['data'] as $t) {

        $ex = $db->get("SELECT * FROM movimiento WHERE cuenta_id = '" . $cuenta_id . "' AND referencia_id = '" . $t['id'] . "'", true);
        if(empty($ex)) {
          echo ":: Nuevo movimiento: " . $t['id'] . " [" . $t['amount'] . "]\n";
          $tiempo = Doris::time($t['dueDate']);
          $t['description'] = $t['service'] . '#' . $t['code'] . ' => ' . $t['concept'] . ' | ' . implode(' ', array($t['firstName'], $t['lastName']));
          $db->insert('movimiento', array(
            'usuario_id'      => $usuario_id,
            'cuenta_id'       => $cuenta_id,
            'moneda_id'       => $MONEDAS[$t['currency']],
            'referencia_id'   => $t['id'],
            'descripcion'     => $t['description'],
            'categoria_id'    => encontrarle_una_categoria($db, $t['description']),
            'categoria_confirmacion' => 0,
            'monto'           => $t['amount'],
            'fecha'           => $tiempo,
            'efectuado'       => $t['totalAmount'] == $t['totalAmountPayed'],
          ));
        } else {
          echo ":: Ya registrado: " . $t['id'] . " [" . $t['amount'] . "]\n";
        }

}
print_r($data);
