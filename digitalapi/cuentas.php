<?php
require_once(__DIR__ . '/conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');

Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$db = Doris::init('financiero');



$ses = $db->get("SELECT * FROM sesion");
foreach($ses as $s) {
  $cuentas = $db->get("SELECT * FROM cuenta WHERE usuario_id = " . $s['usuario_id'] . " AND banco_id = " . $s['banco_id'] . " AND referencia_id IS NOT NULL");
  foreach($cuentas as $c) {
    $url = 'https://mi.scotiabank.com.pe/digital-api/products/home?offline=true&tx=false&hidden=false';
    $rp  = Curly(CURLY_GET, $url, array(
      'Cookie' => 'JOY=1ed24525-102a-462e-9fdb-f8cb8c181329;',
    ));
    var_dump($rp);
  }
}
https://mi.scotiabank.com.pe/digital-api/products/home?offline=true&tx=false&hidden=false
