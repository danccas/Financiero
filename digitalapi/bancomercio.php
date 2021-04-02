<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');
require_once(ABS_LIBRERIAS . 'financiero.php');

function curly2($url, $headers) {
$ch = curl_init($url);
curl_setopt($ch,CURLOPT_ENCODING , "");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$output = curl_exec($ch);
curl_close($ch);
return $output;
}

function html_to_json($html) {
  $html = explode('<table id="tabla" class="WEB_formTRalter">', $html);
  $html = explode('</table>', $html[1]);
  $html = array_map(function($html) {
    preg_match_all("/\"\>(?<contenido>[^(\<\/)]+)\<\//", $html, $out, PREG_SET_ORDER);
    return $out;
  }, explode('</tr>', $html[0]));
  $html = array_filter($html, function($n) {
    return count($n) == 7;
  });
  $html = array_map(function ($n){
    $datos = array_map(function($n) {
      return trim(str_replace('&nbsp;', '', $n['contenido']));
    }, $n);
    $datos[7] = trim(str_replace(',', '', $datos[6]));
    return array(
      'fecha' => $datos[0],
      'fecha_proceso' => $datos[1],
      'transaccion' => $datos[2],
      'operacion' => $datos[3],
      'descripcion' => preg_replace("/[\s]+/", ' ', $datos[4]),
      't/a' => $datos[5],
      'monto_original' => $datos[6],
      'monto' => -1 * (strpos($datos[7], '-') !== false ? -1 * (float) (trim($datos[7], '-')) : (float) $datos[7]),
    );
  }, $html);
  return $html;
}

$MONEDAS = array('604' => 1, '840' => 2);

$db = Doris::init('financiero');

$ses = $db->get("SELECT * FROM sesion WHERE banco_id = 8");
foreach($ses as $s) {
  $cuentas = $db->get("SELECT * FROM cuenta WHERE usuario_id = " . $s['usuario_id'] . " AND banco_id = " . $s['banco_id'] . " AND referencia_id IS NOT NULL");
  foreach($cuentas as $c) {
    echo "\n### Cuenta: " . $c['nombre'] . "\n";
    foreach($MONEDAS as $moneda_banco => $moneda_interno) {
	    $url = 'https://enlinea.bancomercio.com/hb/18400401.jsf?_init_=1&NUMEROTARJETA=' . $c['referencia_id'] . '&CMONEDA=' . $moneda_banco . '&MESFILTRO=' . date('Ym') . '&_init_=1&_=1606610996033';
#      $url = 'https://enlinea.bancomercio.com/hb/18400401.jsf?_init_=1&NUMEROTARJETA=' . $c['referencia_id'] . '&CMONEDA=' . $moneda_banco . '&MESFILTRO=202010&_init_=1&_=1606610996033';
      $rp = curly2($url, [
	      'Cookie: JSESSIONID=' . $s['token'],
      ]);
      echo "Respuesta!\n";
      if(empty($rp)) {
        echo "-- Sin respuesta --\n";
	break;
      }
      $rp = html_to_json($rp);
#      debug($rp);
      if(empty($rp)) {
        echo "-- Sin respuesta json --\n";
        break;
      }
      $db->update('sesion', array(
        'ultima_actualizacion' => $db->time(),
      ), 'id = ' . $s['id']);
      foreach($rp as $t) {
#	      var_dump($t);exit;
        $t['id'] = 'AU/' . md5(json_encode($t));
        if(strpos($t['descripcion'], 'por procesar') !== false) {
          echo ":: Omitiendo en proceso\n";
          continue;
        }
        $ex = $db->get("SELECT * FROM movimiento WHERE cuenta_id = '" . $c['id'] . "' AND referencia_id = '" . $t['id'] . "'", true);
	if(empty($ex)) {
	  $esperado = encontrar_movimiento_registrado($db, $c['id'], $moneda_interno, null, $t['monto']);
	  if(!empty($esperado)) {
          echo ":: Nuevo movimiento [ESPERADO]: " . $t['id'] . " [" . $t['monto'] . "]\n";
		  $db->update('movimiento', [
			  'descripcion' => $esperado['descripcion'] . ' => ' . $t['descripcion'],
			  'referencia_id'   => $t['id'],
			  'monto'           => $t['monto'],
			  'fecha'           => $db->time($t['fecha']),
			  'procesado'       => !empty($t['fecha_proceso']) ? $db->time($t['fecha_proceso']) : null,
		  ], 'id = ' . $esperado['id']);
	  } else {
          echo ":: Nuevo movimiento: " . $t['id'] . " [" . $t['monto'] . "]\n";
          $tiempo = $db->time($t['fecha']);
          $tiempo = explode(' ', $tiempo);
          $tiempo = $tiempo[0] . ' ' . date('H:i:s');
          $db->insert('movimiento', array(
            'usuario_id'      => $c['usuario_id'],
            'cuenta_id'       => $c['id'],
            'moneda_id'       => $moneda_interno,
            'referencia_id'   => $t['id'],
            'descripcion'     => $t['descripcion'],
            'categoria_id'    => encontrarle_una_categoria($db, $t['descripcion']),
            'categoria_confirmacion' => 0,
            'monto'           => $t['monto'],
            'fecha'           => $tiempo,
            'procesado'       => !empty($t['fecha_proceso']) ? $db->time($t['fecha_proceso']) : null,
    ));
	  }
        } else {
          echo ":: Ya registrado: " . $t['operacion'] . " [" . $t['monto'] . "]\n";
        }
      }
    }
  }
}
