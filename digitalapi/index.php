<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');

Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$db = Doris::init('financiero');

$ses = $db->get("SELECT * FROM sesion");
foreach($ses as $s) {
  $cuentas = $db->get("SELECT * FROM cuenta WHERE usuario_id = " . $s['usuario_id'] . " AND banco_id = " . $s['banco_id'] . " AND referencia_id IS NOT NULL");
  foreach($cuentas as $c) {
    echo "\n### Cuenta: " . $c['nombre'] . "\n";
    $page = 0;
    $salir = 0;
    do {
      echo "Consultando Page " . $page . "...\n";
      $url = 'https://mi.scotiabank.com.pe/digital-api/products/' . $c['referencia_id'] . '/transactions?page=' . $page;
      $rp  = Curly(CURLY_GET, $url, array(
        'Cookie' => 'JOY=' . $s['token'] . ';',
      ));
      echo "Respuesta!\n";
      if(empty($rp)) {
        echo "-- Sin respuesta --\n";
        break;
      }
      $rp = json_decode($rp, true);
      if(empty($rp)) {
        echo "-- Sin respuesta json --\n";
        break;
      }
      if(empty($rp['transactions'])) {
        echo "-- Sin transaciones --\n";
        break;
      }
      foreach($rp['transactions'] as $t) {
        if($c['tipo_id'] == 2 && empty($t['id'])) {
          $t['id'] = 'AU/' . md5(json_encode($t));
        }
        if(empty($t['id'])) {
          echo ":: Omitiendo sin ID\n";
          continue;
        }
        if(strpos($t['description'], 'por procesar') !== false) {
          echo ":: Omitiendo en proceso\n";
          continue;
        }
        $ex = $db->get("SELECT * FROM movimiento WHERE cuenta_id = '" . $c['id'] . "' AND referencia_id = '" . $t['id'] . "'", true);
        if(empty($ex)) {
          $salir = 0;
          echo ":: Nuevo movimiento: " . $t['id'] . "\n";
          $tiempo = Doris::time($t['date_formatted']);
          if($c['tipo_id'] == 2) {
            $tiempo = explode(' ', $tiempo);
            $tiempo = $tiempo[0] . ' ' . date('H:i:s');
          }
          $db->insert('movimiento', array(
            'usuario_id'      => $c['usuario_id'],
            'cuenta_id'       => $c['id'],
            'moneda_id'       => $MONEDAS[$t['currency']],
            'referencia_id'   => $t['id'],
            'referencia_tipo' => $t['type'],
            'descripcion'     => $t['description'],
            'monto'           => $t['amount'],
            'fecha'           => $tiempo,
            'procesado'       => $tiempo,
          ));
        } else {
          echo ":: Ya registrado: " . $t['id'] . "\n";
          $salir++;
        }
      }
      echo "[Página: " . $page . "/" . $rp['page']['total_pages'] . "]\n";
      sleep(1);
      $page++;
    } while($rp['page']['total_pages'] >= $page && $salir <= 3);
  }
}
