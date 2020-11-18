<?php
require_once(__DIR__ . '/conf.php');
require_once(ABS_LIBRERIAS . 'formity2.php');
require_once(ABS_LIBRERIAS . 'chartjs.php');


Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$db = Doris::init('financiero');

$time = time();
#$time = strtotime('2020-02-27');

$fecha_actual = date('Y-m-d', $time);
$mes_actual   = date('Y-m', $time);
$mes_cierre   = date('Y-m', strtotime('last day of previous month', $time));
$fecha_cierre = date('Y-m-d', strtotime('last day of previous month', $time));



#$mes_cierre   = "2019-02";
$mes_cierre   = date('Y-m', strtotime('last day of -2 month', $time));

#$fecha_cierre = "2019-02-28";
$fecha_cierre   = date('Y-m-d', strtotime('last day of -2 month', $time));

#$mes_actual   = "2019-03";
$mes_actual   = date('Y-m', strtotime('last day of -1 month', $time));

#$fecha_actual = "2019-03-31";
$fecha_actual = date('Y-m-d', strtotime('last day of -1 month', $time));

#$mes_siguiente = "2019-04";
$mes_siguiente = date('Y-m', $time);
#$fecha_siguiente = "2019-04-01";
$fecha_siguiente = date('Y-m-d', $time);


$cuentas = $db->get("
  SELECT
    C.usuario_id,
    C.id as cuenta_id,
    M.id as moneda_id,
    C.nombre,
    C.credito,
    C.numero,
    C.tipo_id,
    C.fecha_cierre,
    C.fecha_facturacion,
    M.nombre as moneda,
    T.nombre as tipo,
    B.nombre as banco,
    COALESCE((
      SELECT
        SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END)
      FROM movimiento MOV
      WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_actual . "' AND MOV.monto < 0
    ), 0) as gasto,
    COALESCE((
      SELECT
        SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END)
      FROM movimiento MOV
      WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_actual . "' AND MOV.monto > 0
    ), 0) as ingreso,
    (
      (
        SELECT
          COALESCE(SUM(CI.contable), 0)
        FROM cierre CI
        WHERE date_format(CI.fecha, '%Y-%m') = '" . $mes_cierre . "' AND CI.moneda_id = M.id AND CI.cuenta_id = C.id
      ) + (
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE 0 END), 0)
        FROM movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_actual . "'
      )
    ) as contable,
    (
      (
        SELECT
          COALESCE(SUM(CI.disponible), 0)
        FROM cierre CI
        WHERE date_format(CI.fecha, '%Y-%m') = '" . $mes_cierre . "' AND CI.cuenta_id = C.id AND CI.moneda_id = M.id
      )
      +
      (
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM movimiento MOV
	WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND C.tipo_id = 1 AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_actual . "'
      )
    ) as disponible
  FROM moneda M
  JOIN cuenta C ON C.tipo_id IN (1, 3)
  JOIN tipo T ON T.id = C.tipo_id
  LEFT JOIN banco B ON B.id = C.banco_id
  ORDER BY B.id, M.id, C.id");
echo "<pre>";print_r($cuentas);echo "</pre>";
foreach($cuentas as $c) {
  $db->update('cierre', array('estado' => 0), "cuenta_id = {$c['cuenta_id']} AND moneda_id = {$c['moneda_id']} AND DATE(fecha) < '{$fecha_actual}'");
  $db->insert_update('cierre', array(
    'nombre'     => strtoupper($MESES[date('m', strtotime($fecha_actual)) - 1]) . ' ' . date('Y', strtotime($fecha_actual)),
    'fecha'      => $fecha_actual,
    'usuario_id' => $c['usuario_id'],
    'cuenta_id'  => $c['cuenta_id'],
    'moneda_id'  => $c['moneda_id'],
    'ingreso'    => $c['ingreso'],
    'gasto'      => $c['gasto'],
    'contable'   => $c['contable'],
    'disponible' => $c['disponible'],
    'estado'     => 1,
  ));
}

/* Quitamos los no efectuados de meses anteriores */
$db->update('movimiento', array('fecha' => Doris::time($fecha_siguiente)), "DATE(fecha) <= '" . $fecha_actual . "' AND efectuado = 0");

/* Cierres */
$ls = $db->get("SELECT * FROM recurrente WHERE procesado IS NULL");
foreach($ls as $n) {
  $fecha_inicio_unix = strtotime($n['fecha_inicio']);
  $fecha_fin_unix    = !empty($n['fecha_fin']) ? strtotime($n['fecha_fin']) : strtotime('last day of december this year');

  $db->transaction();
  $fecha_unix = $fecha_inicio_unix;
  while($fecha_unix <= $fecha_fin_unix) {
    if($n['tipo'] == 'MENSUAL') {
      $fecha_unix = strtotime('+1 month', $fecha_unix);
    } else {
      break;
    }
    if(empty($fecha_unix)) {
      break;
    }
    echo "Insertamos: #" . $n['id'] . " en " . Doris::time($fecha_unix) . "\n";
    $db->insert('movimiento', array(
      'recurrente_id' => $n['id'],
      'cuenta_id'     => $n['cuenta_id'],
      'usuario_id'    => $n['usuario_id'],
      'categoria_id'  => $n['categoria_id'],
      'sujeto_id'     => $n['sujeto_id'],
      'descripcion'   => 'RECURRENTE: ' . $n['descripcion'],
      'monto'         => $n['monto'],
      'moneda_id'     => $n['moneda_id'],
      'fecha'         => Doris::time($fecha_unix),
      'efectuado'     => 0,
    ));
  }
  $db->update('recurrente', array(
    'procesado' => Doris::time(),
  ), 'id = ' . $n['id']);
  $db->commit();
}
