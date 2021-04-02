<?php
function encontrarle_una_categoria($db, $rotulo) {
  $rotulo = preg_replace("/[^\w]/i", '', $rotulo);
  $rp = $db->get("
    SELECT categoria_id, descripcion
    FROM financiero.movimiento
    WHERE categoria_id IS NOT NULL AND categoria_confirmacion IS TRUE AND descripcion LIKE '%" . $rotulo . "%'
    LIMIT 1", true);
  if(!empty($rp)) {
    return $rp['categoria_id'];
  }
  return null;
}

function encontrar_movimiento_registrado($db, $cuenta_id, $moneda_id, $fecha, $monto) {
  $error_monto = 5;
  return $db->get("
    SELECT *
    FROM financiero.movimiento
    WHERE cuenta_id = {$cuenta_id} AND moneda_id = {$moneda_id} AND efectuado IS FALSE AND eliminado IS NULL AND monto >= " . ($monto - $error_monto) . " AND monto <= " . ($monto + $error_monto) . "
    ORDER BY fecha DESC
    LIMIT 1", true);
}
