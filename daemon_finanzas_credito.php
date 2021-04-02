<?php
require_once(__DIR__ . '/conf.php');
require_once(ABS_LIBRERIAS . 'formity2.php');
require_once(ABS_LIBRERIAS . 'chartjs.php');


$db = Doris::init('financiero');

$fechas = dateRange('2020-01-01', date('Y-m-d'), '+1 day');

foreach($fechas as $fecha) {
  echo "=> {$fecha} \n";
//$now = strtotime('2021-01-28');
//$now = strototime($fecha);
//$now = strtotime('2021-02-28');

#$unix_actual   = time();
$unix_actual   = strtotime($fecha);
#echo $i . " => " . date("Y-m-d", $unix_actual) . "\n";continue;
$unix_anterior = strtotime('previous month', $unix_actual);

$query = "
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
      WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND MOV.monto < 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '" . date('Y-m-d', $unix_actual) . "' ORDER BY fecha DESC LIMIT 1), DATE_SUB('" . date('Y-m-d', $unix_anterior) . "', INTERVAL 1 MONTH)))
        AND DATE(MOV.fecha) < '" . date('Y-m-d', $unix_actual) . "'
    ), 0) as gasto,
    COALESCE((
      SELECT
        SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END)
      FROM movimiento MOV
      WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND MOV.monto > 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '" . date('Y-m-d', $unix_actual) . "' ORDER BY fecha DESC LIMIT 1), DATE_SUB('" . date('Y-m-d', $unix_anterior) . "', INTERVAL 1 MONTH)))
        AND DATE(MOV.fecha) < '" . date('Y-m-d', $unix_actual) . "'
    ), 0) as ingreso,
    (
      COALESCE((
        SELECT
          CI.contable
        FROM cierre CI
        WHERE CI.cuenta_id = C.id AND CI.moneda_id = M.id AND DATE(CI.fecha) < '" . date('Y-m-d', $unix_actual) . "'
        ORDER BY CI.fecha DESC
        LIMIT 1), 0) +
      COALESCE((
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE 0 END), 0)
        FROM movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL
          AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '" . date('Y-m-d', $unix_actual) . "' ORDER BY fecha DESC LIMIT 1), DATE_SUB('" . date('Y-m-d', $unix_anterior) . "', INTERVAL 1 MONTH)))
          AND DATE(MOV.fecha) < '" . date('Y-m-d', $unix_actual) . "'), 0)
    ) as contable,
    (
      COALESCE((
        SELECT
          CI.contable
        FROM cierre CI
        WHERE CI.cuenta_id = C.id AND CI.moneda_id = M.id AND DATE(CI.fecha) < '" . date('Y-m-d', $unix_actual) . "'
        ORDER BY CI.fecha DESC
        LIMIT 1), 0) +
      COALESCE((
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM movimiento MOV
	WHERE MOV.cuenta_id = C.id AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND C.tipo_id = 2
          AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '" . date('Y-m-d', $unix_actual) . "' ORDER BY fecha DESC LIMIT 1), DATE_SUB('" . date('Y-m-d', $unix_anterior) . "', INTERVAL 1 MONTH)))
          AND DATE(MOV.fecha) < '" . date('Y-m-d', $unix_actual) . "'), 0)
    ) as disponible
  FROM moneda M
  JOIN cuenta C ON C.tipo_id = 2 AND DATE_FORMAT(C.fecha_cierre, '%e') = " . date('j', $unix_actual) . "
    AND DATE(C.created_on) <= '" . date('Y-m-d', $unix_actual) . "' AND (C.eliminado IS NULL OR C.eliminado >= '" . date('Y-m-d', $unix_actual) . "')
  JOIN tipo T ON T.id = C.tipo_id
  LEFT JOIN banco B ON B.id = C.banco_id
  ORDER BY B.id, M.id, C.id";
$cuentas = $db->get($query);
echo "Fecha: " . date('Y-m-d', $unix_actual) . "\n";
echo "<pre>";print_r($cuentas);echo "</pre>";
foreach($cuentas as $c) {
  $db->update('cierre', array('estado' => 0), "cuenta_id = {$c['cuenta_id']} AND moneda_id = {$c['moneda_id']} AND DATE(fecha) < '" . date('Y-m-d', $unix_actual) . "'");
  print_r($db->insert_update('cierre', array(
    '*fecha'      => date('Y-m-d', $unix_actual),
    '*usuario_id' => $c['usuario_id'],
    '*cuenta_id'  => $c['cuenta_id'],
    '*moneda_id'  => $c['moneda_id'],
    'nombre'     => date('Y', $unix_anterior) . ': ' . date('d', strtotime('next day', $unix_actual)) . ' ' . substr(strtoupper($MESES[date('m', $unix_anterior) - 1]), 0, 3) . ' - ' . date('d', $unix_actual) . ' ' . substr(strtoupper($MESES[date('m', $unix_actual) - 1]), 0, 3),
    'ingreso'    => $c['ingreso'],
    'gasto'      => $c['gasto'],
    'contable'   => $c['contable'],
    'disponible' => $c['disponible'],
    'estado'     => 1,
  )));
}
}
