<?php

$db = Doris::init('financiero');

if(!empty($_GET['editar'])) {
  if($_GET['editar'] == 'categoria') {
    $db->update('financiero.movimiento', array(
      'categoria_id' => $_POST['categoria_id'],
      'categoria_confirmacion' => 1,
    ), 'id = ' . $_POST['id']);
    echo "editado-" . $_GET['id'];
  }
  exit;
}


function regresar() {
  header('location: .');
  exit;
}
function moneda($id) {
  if($id == 1) {
    return 'SOLES';
  } else {
    return 'DOLARES';
  }
}
$mes_actual   = date('Y-m');
$fecha_actual = date('Y-m-d');#, strtotime('last day of this month'));


if(!empty($_GET['fecha'])) {
  if(strtotime($_GET['fecha']) == false) {
    _404();
  }
  $mes_visto   = date('Y-m', strtotime($_GET['fecha']));
  $fecha_visto = date('Y-m-d', strtotime($_GET['fecha']));
} else {
  $mes_visto    = $mes_actual;
  $fecha_visto  = $fecha_actual;
}
$fecha_visto_last = date('Y-m-d', strtotime('last day of this month', strtotime($fecha_visto)));
$mes_actual_cierre   = date('Y-m', strtotime('last day of previous month', strtotime($fecha_actual)));
$fecha_actual_cierre = date('Y-m-d', strtotime('last day of previous month', strtotime($fecha_actual)));
$mes_cierre   = date('Y-m', strtotime('last day of previous month', strtotime($fecha_visto)));
$fecha_cierre = date('Y-m-d', strtotime('last day of previous month', strtotime($fecha_visto)));


$tipo_cambio = $db->get("
  SELECT
    TC.*,
    M1.nombre as desde,
    M2.nombre as hasta
  FROM financiero.tipo_cambio TC
  JOIN financiero.moneda M1 ON M1.id = TC.desde_id
  JOIN financiero.moneda M2 ON M2.id = TC.hasta_id
  WHERE TC.fecha = '{$fecha_visto}'");

$ls = $db->get("SELECT * FROM financiero.moneda ORDER BY nombre");
$monedas = result_parse_to_options($ls, 'id', 'nombre');

$ls = $db->get("SELECT * FROM financiero.tipo ORDER BY nombre");
$tipos = result_parse_to_options($ls, 'id', 'nombre');

$ls = $db->get("SELECT * FROM financiero.banco ORDER BY nombre");
$ls = result_parse_to_options($ls, 'id', 'nombre');


$form = Formity::getInstance('categoria');
$form->setTitle('Categoria');
$form->addField('nombre', 'input:text');
$form->addField('color', 'input:color');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->insert('financiero.categoria', $data);
  }
}

$form = Formity::getInstance('sujeto');
$form->setTitle('Sujeto');
$form->addField('nombre', 'input:text');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $data['usuario_id'] = $usuario_id;
    $db->insert('financiero.sujeto', $data);
  }
}

$form = Formity::getInstance('grupo');
$form->setTitle('Crear Grupo');
$form->addField('nombre', 'input:text');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->transaction();
    $grupo_id = $db->insert('financiero.grupo', $data);
    $db->insert('financiero.grupo_usuario', array(
      'grupo_id'   => $grupo_id,
      'usuario_id' => $usuario_id,
    ));
    $db->commit();
  }
}

$form = Formity::getInstance('unirte_grupo');
$form->setTitle('Unirte a un Grupo');
$form->addField('codigo', 'input:text');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $gru = $db->get("SELECT * FROM financiero.grupo WHERE id = " . (int) $data['codigo'], true);
    if(empty($gru)) {
      $form->setError('El código es inválido');
    } else {
      $db->insert('financiero.grupo_usuario', array(
        'grupo_id'   => $data['codigo'],
        'usuario_id' => $usuario_id,
      ), true);
    }
  }
}

$form = Formity::getInstance('cuenta');
$form->setUniqueId('nuevo');
$form->setTitle('Cuenta');
$form->addField('nombre', 'input:text');
$form->addField('banco_id?:Banco', 'select')->setOptions(['' => 'Ninguno'] + $ls);
$form->addField('moneda_id:Moneda', 'select')->setOptions($monedas);
$form->addField('tipo_id:Tipo', 'select')->setOptions($tipos);
$form->addField('numero?', 'input:text');
$form->addField('credito?:Monto', 'decimal')->setMin(-9999999)->setMax(9999999)->setStep(0.0000000001);
$form->addField('fecha_cierre?', 'input:date');
$form->addField('fecha_facturacion?', 'input:date');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $data['usuario_id'] = $usuario_id;
    $db->insert('financiero.cuenta', $data);
#    regresar();
  }
}

$cuentas = $db->get("
  SELECT
    C.id,
    C.nombre,
    C.credito,
    C.numero,
    C.tipo_id,
    C.fecha_cierre,
    C.fecha_facturacion,
    M.nombre as moneda,
    T.nombre as tipo,
    B.nombre as banco
  FROM financiero.cuenta C
  JOIN financiero.moneda M ON M.id = C.moneda_id
  JOIN financiero.tipo T ON T.id = C.tipo_id
  LEFT JOIN financiero.banco B ON B.id = C.banco_id
  WHERE C.usuario_id = {$usuario_id} AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
  ORDER BY C.ultimo IS NULL, C.ultimo DESC, banco, tipo, moneda, nombre");

$_c = array();
foreach($cuentas as $c) {
  $_c[$c['id']] = $c;
}
$cuentas = $_c;
unset($_c);
unset($c);

$cuentas = array_map(function($n) {
  $lp = array();
  if(!empty($n['banco'])) {
    $lp[] = $n['banco'];
    $lp[] = $n['tipo'];
  }
  $lp[] = $n['moneda'];
  $lp[] = $n['nombre'];
  return implode(':', $lp);
}, $cuentas);

$categorias_sql = $db->get("SELECT * FROM financiero.categoria ORDER BY nombre ASC");
$categorias = result_parse_to_options($categorias_sql, 'id', ['[', id, '] ', 'nombre']);

function select_categoria($n) {
  global $categorias, $db;
//  return $n['categoria_id'] . '---' . encontrarle_una_categoria($db, $n['descripcion']);
  $rp = '';
  $n['categoria_id'] = !empty($n['categoria_id']) ? $n['categoria_id'] : encontrarle_una_categoria($db, $n['descripcion']);
  if(empty($n['categoria_confirmacion'])) {
    $rp .= '<select data-id="' . $n['id'] . '" class="selectCategoria">';
    foreach($categorias as $k => $v) {
      if($k == $n['categoria_id']) {
        $rp .= '<option value="' . $k . '" selected>' . $v . '</option>';
      } else {
        $rp .= '<option value="' . $k . '">' . $v . '</option>';
      }
    }
    $rp .= '</select>';
  } else {
    $rp = $n['categoria'];
  }
  return $rp;
}

$sujetos = $db->get("SELECT * FROM financiero.sujeto WHERE usuario_id = {$usuario_id} ORDER BY id ASC");
$sujetos = result_parse_to_options($sujetos, 'id', 'nombre');

$bloqueados = $db->get("SELECT id, descripcion FROM financiero.movimiento M1 WHERE M1.bloqueado IS TRUE AND M1.id NOT IN (SELECT id FROM financiero.movimiento M2 WHERE M2.bloqueado_id = M1.id)");
$bloqueados = result_parse_to_options($bloqueados, 'id', array('descripcion', ': ', 'monto'));

$form = Formity::getInstance('movimiento');
$form->setUniqueId('nuevo');
$form->setTitle('Transacción');
$form->addField('cuenta_id:Cuenta', 'select')->setOptions($cuentas);
$form->addField('categoria_id:Categoria', 'select')->setOptions($categorias);
$form->addField('sujeto_id:Sujeto', 'select')->setOptions($sujetos);
$form->addField('fecha', 'input:datetime-local')->setValue(date('Y-m-d H:i:s'));
$form->addField('procesado?', 'input:datetime-local')->setValue(date('Y-m-d H:i:s'));
$form->addField('descripcion', 'textarea:autocomplete')->setOptions(function($form, $field, $term) use($db) {
  $term = '%' . $term . '%';
  return $db->get("
    SELECT
      DISTINCT CONCAT(descripcion, ' x ', monto) as label,
      descripcion as id,
      monto,
      categoria_id,
      moneda_id,
      cuenta_id
    FROM financiero.movimiento
    WHERE LOWER(descripcion) LIKE ?
    AND categoria_id <> 29
    ORDER BY fecha DESC
    LIMIT 10", false, false, array(
    $term
  ));
});
$form->addField('monto', 'decimal')->setMin(-9999999)->setMax(9999999)->setStep(0.0000000001);
$form->addField('moneda_id?:Moneda', 'select')->setOptions(['' => 'Auto'] + $monedas);
$form->addField('efectuado', 'boolean')->setValue(1);
$form->addField('bloqueado', 'boolean')->setValue(0);
$form->addField('bloqueado_id?:Desbloqueo', 'select')->setOptions(['' => 'No desbloqueado'] + $bloqueados);
$form->addField('contable', 'boolean')->setValue(1);

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    if(empty($data['moneda_id'])) {
      $cuenta = $db->get("SELECT * FROM financiero.cuenta WHERE id = " . $data['cuenta_id'], true);
      $data['moneda_id'] = $cuenta['moneda_id'];
    }
    $db->transaction();
    $db->update('financiero.cuenta', array('ultimo' => Doris::time()), 'id = ' . (int) $data['cuenta_id']);
    $db->update('financiero.categoria', array('ultimo' => Doris::time()), 'id = ' . (int) $data['categoria_id']);
    $data['usuario_id'] = $usuario_id;
    $data['procesado']  = $data['fecha'];
    $db->insert('financiero.movimiento', $data);
    $db->commit();
    #regresar();
  }
}

$form = Formity::getInstance('recurrente');
$form->setUniqueId('nuevo');
$form->setTitle('Movimientos Recurrentes');
$form->addField('cuenta_id:Cuenta', 'select')->setOptions($cuentas);
$form->addField('categoria_id:Categoria', 'select')->setOptions($categorias);
$form->addField('sujeto_id:Sujeto', 'select')->setOptions($sujetos);
$form->addField('descripcion', 'textarea');
$form->addField('monto', 'decimal')->setMin(-9999999)->setMax(9999999)->setStep(0.0000000001);
$form->addField('moneda_id?:Moneda', 'select')->setOptions($monedas);
$form->addField('tipo', 'select')->setOptions(array('DIARIO' => 'DIARIO', 'LABORAL' => 'LABORAL', 'MENSUAL' => 'MENSUAL'));
$form->addField('fecha_inicio', 'input:datetime-local');
$form->addField('fecha_fin?', 'input:datetime-local');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->transaction();
    $data['usuario_id'] = $usuario_id;
    $recurrente_id = $db->insert('financiero.recurrente', $data);
    $db->insert('financiero.movimiento', array(
      'recurrente_id' => $recurrente_id,
      'usuario_id'    => $usuario_id,
      'cuenta_id'     => $data['cuenta_id'],
      'categoria_id'  => $data['categoria_id'],
      'sujeto_id'     => $data['sujeto_id'],
      'descripcion'   => 'RECURRENTE: ' . $data['descripcion'],
      'monto'         => $data['monto'],
      'moneda_id'     => $data['moneda_id'],
      'fecha'         => $data['fecha_inicio'],
      'efectuado'     => 0,
    ));
    $db->commit();
    #regresar();
  }
}


$form = Formity::getInstance('transferencia');
$form->setUniqueId('nuevo');
$form->setTitle('Transferencia');
$form->addField('desde_id?:Desde', 'select')->setOptions(['' => 'Cuenta de Tercero'] + $cuentas);
$form->addField('hasta_id?:Hasta', 'select')->setOptions(['' => 'Cuenta de Tercero'] + $cuentas);
$form->addField('sujeto_id:Sujeto', 'select')->setOptions($sujetos);
$form->addField('descripcion', 'textarea');
$form->addField('monto', 'decimal')->setMin(1)->setMax(9999999);
$form->addField('moneda_id?:Moneda', 'select')->setOptions($monedas);
$form->addField('fecha', 'input:datetime-local')->setValue(date('Y-m-d H:i:s'));

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->transaction();
    $transaccion_id = time();
    if(!empty($data['desde_id'])) {
      $contable = empty($data['hasta_id']) ? 1 : 0;
      if(!empty($data['hasta_id'])) {
        $cu = $db->get("SELECT * FROM financiero.cuenta WHERE id = " . (int) $data['desde_id'], true);
        if(!empty($cu) && $cu['tipo_id'] == 2) {
          $contable = 1;
        }
      }
      $db->insert('financiero.movimiento', array(
        'contable'     => $contable,
        'usuario_id'   => $usuario_id,
        'transaccion'  => $transaccion_id,
        'cuenta_id'    => $data['desde_id'],
        'sujeto_id'    => $data['sujeto_id'],
        'categoria_id' => 15,
        'descripcion'  => 'TRANSFERENCIA: ' . $data['descripcion'],
        'monto'        => $data['monto'] * -1,
        'fecha'        => $data['fecha'],
        'moneda_id'    => $data['moneda_id'],
        'efectuado'    => 1,
      ));
    }
    if(!empty($data['hasta_id'])) {
      $contable = empty($data['desde_id']) ? 1 : 0;
      $db->insert('financiero.movimiento', array(
        'contable'     => $contable,
        'usuario_id'   => $usuario_id,
        'transaccion'  => $transaccion_id,
        'cuenta_id'    => $data['hasta_id'],
        'sujeto_id'    => $data['sujeto_id'],
        'categoria_id' => 15,
        'descripcion'  => 'TRANSFERENCIA: ' . $data['descripcion'],
        'monto'        => $data['monto'],
        'fecha'        => $data['fecha'],
        'moneda_id'    => $data['moneda_id'],
        'efectuado'    => 1,
      ));
    }
    $db->commit();
    #regresar();
  }
}


$form = Formity::getInstance('prestamo');
$form->setUniqueId('nuevo');
$form->setTitle('Prestamo');
$form->addField('cuenta_id:Cuenta', 'select')->setOptions($cuentas);
$form->addField('sujeto_id:Sujeto', 'select')->setOptions($sujetos);
$form->addField('descripcion', 'textarea');
$form->addField('monto', 'decimal')->setMin(-9999999)->setMax(9999999);
$form->addField('moneda_id:Moneda', 'select')->setOptions($monedas);
$form->addField('interes', 'decimal')->setMin(-9999999)->setMax(9999999)->setStep(0.00000001);
$form->addField('meses', 'integer')->setMin(1)->setMax(64);
$form->addField('fecha_deposito', 'input:date');
$form->addField('fecha_pago', 'input:date');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->transaction();
    $data['usuario_id'] = $usuario_id;
    $prestamo_id = $db->insert('financiero.prestamo', $data);
    $db->insert('financiero.movimiento', array(
      'usuario_id'   => $usuario_id,
      'prestamo_id'  => $prestamo_id,
      'cuenta_id'    => $data['cuenta_id'],
      'sujeto_id'    => $data['sujeto_id'],
      'categoria_id' => 14,
      'moneda_id'    => $data['moneda_id'],
      'descripcion'  => 'DEPOSITO DE PRESTAMO: ' . $data['descripcion'],
      'monto'        => $data['monto'],
      'fecha'        => $data['fecha_deposito'],
      'efectuado'    => 1,
    ));
    $fecha_pago = $data['fecha_pago'];
    for($i = 1; $i <= $data['meses']; $i ++) {
      $db->insert('financiero.movimiento', array(
        'usuario_id'   => $usuario_id,
        'prestamo_id'  => $prestamo_id,
        'cuenta_id'    => $data['cuenta_id'],
        'sujeto_id'    => $data['sujeto_id'],
        'categoria_id' => 14,
        'moneda_id'    => $data['moneda_id'],
        'descripcion'  => 'PAGO DE CUOTA ' . $i . ': ' . $data['descripcion'],
        'monto'        => -1 * (($data['monto'] * ((100 + $data['interes'])/100)) / $data['meses']),
        'fecha'        => $fecha_pago,
        'efectuado'    => 0,
      ));
      $fecha_pago = date('Y-m-d', strtotime("+1 months", strtotime($fecha_pago)));
    }
    $db->commit();
    #regresar();
  }
}

$form = Formity::getInstance('cierre');
$form->setUniqueId('nuevo');
$form->setTitle('Cierre');
$form->addField('cuenta_id:Cuenta', 'select')->setOptions($cuentas);
$form->addField('moneda_id:Moneda', 'select')->setOptions($monedas);
$form->addField('fecha', 'input:date');
$form->addField('ingreso?', 'decimal')->setMin(-9999999)->setMax(9999999);
$form->addField('ingreso_acumulado?', 'decimal')->setMin(-9999999)->setMax(9999999);
$form->addField('gasto?', 'decimal')->setMin(-9999999)->setMax(9999999);
$form->addField('gasto_acumulado?', 'decimal')->setMin(-9999999)->setMax(9999999);
$form->addField('contable?', 'decimal')->setMin(-9999999)->setMax(9999999);
$form->addField('disponible', 'decimal')->setMin(-9999999)->setMax(9999999);

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $data['usuario_id'] = $usuario_id;
    $db->insert('financiero.cierre', $data);
    #regresar();
  }
}

$table = Tablefy::getInstance('cuentas');
$table->setTitle('Cuentas Debito');
$table->setHeader(array('BANCO-CUENTA', 'CONTABLE','DISPONIBLE'));
$table->setData(function() use($db) {
  return $db->get("SELECT * FROM financiero.obtener_cuentas_debitos(NOW()::timestamp)");
}, function($n) {
  return array(
    '<div>#' . $n['id'] . ': ' . $n['banco'] . ' ' . moneda($n['moneda_id']) . ':' . $n['cuenta'] . '</div><small>' . $n['numero'] . '</small>',
    money($n['monto_contable'], $n['moneda'], 'CONTABLE ACTUAL'),
    money($n['monto_disponible'], $n['moneda'], 'DISPONIBLE ACTUAL'),
  );
});
$table->setOption('Editar&', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM financiero.cuenta WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $form = Formity::getInstance('cuenta');
  $form->setUniqueId('editar');
  $form->setPreData($ficha);
  if($form->byRequest()) {
    if($form->isValid($err)) {
      $data = $form->getData();
      $db->update('cuenta', $data, 'id = ' . (int) $n['id']);
    }
  }
  echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">';
  echo $form->render();
});

$creditos = null; /*$db->get("
SELECT * FROM (
  SELECT
    x.*,
    (x.cierre + x.ingreso + x.gasto + x.credito) as disponible,
    (x.cierre_convertido + x.ingreso_convertido + x.gasto_convertido + x.credito) as disponible_convertido,
    (x.cierre + x.ingreso + x.gasto) as total,
    (x.cierre_convertido + x.ingreso_convertido + x.gasto_convertido) as total_convertido
  FROM (
  SELECT
    C.id,
    C.nombre,
    (CASE WHEN MC.nombre = M.nombre THEN C.credito ELSE 0 END) as credito,
    C.numero,
    C.tipo_id,
    C.fecha_cierre,
    C.fecha_facturacion,
    MC.nombre as moneda,
    M.nombre as moneda2,
    T.nombre as tipo,
    B.nombre as banco,
    B.color as banco_color,
    DATE(COALESCE((SELECT fecha FROM financiero.cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '" . date('Y-m-d', strtotime($fecha_visto)) . "' ORDER BY fecha DESC LIMIT 1), '" . date('Y-m-d', strtotime($fecha_cierre)) . "')) as periodo_anterior,
    (
      COALESCE((
        SELECT contable
        FROM financiero.cierre
        WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) <= '" . $fecha_visto . "'
        ORDER BY fecha DESC
        LIMIT 1
      ), 0)
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? "
      +
      (
        SELECT
           COALESCE(SUM(CASE WHEN MOV.bloqueado IS FALSE THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM financiero.movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.eliminado IS NULL AND MOV.moneda_id = M.id
          AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM financiero.cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), ('{$fecha_cierre}'::date - INTERVAL '1' MONTH)))
          AND DATE(MOV.fecha) <= '{$fecha_visto}'
      )" : "") . "
    ) as cierre,
    (
      COALESCE((
        SELECT SUM(contable * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM financiero.tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END))
        FROM financiero.cierre
        WHERE cuenta_id = C.id AND DATE(fecha) = (SELECT DATE(fecha) FROM financiero.cierre WHERE cuenta_id = C.id AND DATE(fecha) <= '{$fecha_visto}' ORDER BY id DESC LIMIT 1)
      ), 0)
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? "
      +
      (
        SELECT
           COALESCE(SUM((CASE WHEN MOV.bloqueado IS FALSE THEN MOV.monto ELSE (MOV.monto * -1) END) * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM financiero.tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END)), 0)
        FROM financiero.movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.eliminado IS NULL
          AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM financiero.cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), ('{$fecha_cierre}'::date - INTERVAL '1' MONTH)))
          AND DATE(MOV.fecha) <= '{$fecha_visto}'
      )" : "") . "
    ) as cierre_convertido,
    COALESCE((
      SELECT SUM(CASE WHEN bloqueado IS FALSE THEN monto ELSE (monto * -1) END)
      FROM financiero.movimiento MOV
      WHERE efectuado IS TRUE AND cuenta_id = C.id AND moneda_id = M.id AND eliminado IS NULL AND MOV.monto > 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM financiero.cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), ('{$fecha_cierre}'::date - INTERVAL '1' MONTH)))
        AND DATE(MOV.fecha) <= '" . $fecha_visto . "'
    ), 0) as ingreso,
    COALESCE((
      SELECT SUM((CASE WHEN bloqueado IS FALSE THEN monto ELSE (monto * -1) END) * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM financiero.tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END))
      FROM financiero.movimiento MOV
      WHERE efectuado IS TRUE AND cuenta_id = C.id AND eliminado IS NULL AND MOV.monto > 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM financiero.cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), ('{$fecha_cierre}'::date - INTERVAL '1' MONTH)))
        AND DATE(MOV.fecha) <= '" . $fecha_visto . "'
    ), 0) as ingreso_convertido,

    COALESCE((
      SELECT SUM(CASE WHEN bloqueado IS FALSE THEN monto ELSE (monto * -1) END)
      FROM financiero.movimiento MOV
      WHERE efectuado IS TRUE AND cuenta_id = C.id AND moneda_id = M.id AND eliminado IS NULL AND MOV.monto < 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM financiero.cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), ('{$fecha_cierre}'::date - INTERVAL '1' MONTH)))
        AND DATE(MOV.fecha) <= '{$fecha_visto}'
    ), 0) as gasto,
    COALESCE((
      SELECT SUM((CASE WHEN bloqueado IS FALSE THEN monto ELSE (monto * -1) END) * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM financiero.tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END))
      FROM financiero.movimiento MOV
      WHERE efectuado IS TRUE AND cuenta_id = C.id AND eliminado IS NULL AND MOV.monto < 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM financiero.cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), ('{$fecha_cierre}'::date - INTERVAL '1' MONTH)))
        AND DATE(MOV.fecha) <= '{$fecha_visto}'
    ), 0) as gasto_convertido
  FROM financiero.cuenta C
    JOIN financiero.moneda MC ON MC.id = C.moneda_id
    JOIN financiero.moneda M ON TRUE
    JOIN financiero.tipo T ON T.id = C.tipo_id
    LEFT JOIN financiero.banco B ON B.id = C.banco_id
  WHERE C.usuario_id = {$usuario_id} AND C.tipo_id = 2 AND DATE(C.created_on) <= '" . $fecha_visto . "' AND (C.eliminado IS NULL OR DATE(C.eliminado) >= '" . $fecha_visto . "')
)x
  ORDER BY banco, tipo, moneda, id, moneda <> moneda2, nombre) z
  WHERE moneda = moneda2 OR (moneda <> moneda2 AND (cierre <> 0 OR gasto <> 0 OR ingreso <> 0))
  "); */
$table = Tablefy::getInstance('creditos');
$table->setTitle('Cuentas de Créditos');
$table->setHeader(array('CUENTA','DEUDA'));
$table->setData(function() use($db) {
  return $db->get("SELECT * FROM financiero.obtener_cuentas_creditos(NOW()::timestamp)");
}, function($n) {
  return array(
    '#' . $n['id'] . ':' . $n['banco'] . ' ' . moneda($n['moneda_id']) . ':' . $n['cuenta'],
    money($n['monto_contable'], $n['moneda_id'], 'GASTOS DEL PERIODO ACTUAL'),
  );
});
$table->setOption('Editar&', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM financiero.cuenta WHERE usuario_id = {$usuario_id} AND eliminado IS NULL AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $form = Formity::getInstance('cuenta');
  $form->setUniqueId('editar');
  $form->setPreData($ficha);
  if($form->byRequest()) {
    if($form->isValid($err)) {
      $data = $form->getData();
      $db->update('financiero.cuenta', $data, 'id = ' . (int) $n['id']);
    }
  }
  echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">';
  echo $form->render();
});


$table = Tablefy::getInstance('movimientos_pendientes');
$table->setTitle('Pendientes: ' . $mes_visto);
$table->setHeader(array('FECHA','MOVIMIENTO','MONTO'));
$table->setRow(function($id) use($db, $usuario_id) {
  return $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $id, true);
});
$table->setData(function() use($db, $mes_visto, $fecha_visto, $usuario_id) {
  $ls = $db->get("
  SELECT
    DATE(MOV.fecha) as fecha_corta,
    MOV.*,
    C.nombre as cuenta,
    M.nombre as moneda,
    T.nombre as tipo,
    B.nombre as banco,
    S.nombre as sujeto,
    B.color as banco_color,
    CAT.nombre as categoria,
    M2.nombre as moneda2
  FROM financiero.movimiento MOV
  JOIN financiero.cuenta C ON C.id = MOV.cuenta_id AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
  LEFT JOIN financiero.sujeto S ON S.id = MOV.sujeto_id
  LEFT JOIN financiero.moneda M2 ON M2.id = MOV.moneda_id
  JOIN financiero.moneda M ON M.id = C.moneda_id
  LEFT JOIN financiero.tipo T ON T.id = C.tipo_id
  LEFT JOIN financiero.categoria CAT ON CAT.id = MOV.categoria_id
  LEFT JOIN financiero.banco B ON B.id = C.banco_id
  WHERE MOV.usuario_id = {$usuario_id} AND to_char(MOV.fecha, 'YYYY-MM') = '" . $mes_visto . "' AND MOV.eliminado IS NULL " . (!empty($_GET['cuenta']) ? ' AND C.id = ' . $_GET['cuenta'] : '')  . " AND MOV.efectuado IS FALSE
  ORDER BY MOV.fecha ASC, MOV.id ASC");
  return $ls;
}, function($n) {
  global $DIAS;
  $clases = array();
  if(!empty($n['bloqueado'])) {
    $clases[] = 'bloqueado';
  }
  if(empty($n['efectuado'])) {
    $clases[] = 'no-efectuado';
  }
  if(!empty($n['recurrente_id'])) {
    $clases[] = 'recurrente';
  }
  if($n['monto'] > 0) {
    $clases[] = 'ingreso';
  } elseif($n['monto'] < 0) {
    $clases[] = 'gasto';
  }
  
  $tr = array();
#  $tr[] = $n['fecha_corta'];
  $tr[] = substr(strtoupper($DIAS[date('w', strtotime($n['fecha_corta']))]), 0, 3) . ' ' . date('d', strtotime($n['fecha_corta']));
  $tr[] = '<div>#' . $n['id'] . ': ' . date('h:i A', strtotime($n['fecha'])) . ' | ' . $n['sujeto'] . ' | ' . $n['categoria'] . '</div>' .
  '<b>#' . $n['cuenta_id'] . ':' .  (!empty($n['banco']) ? '<span style="color:' . $n['banco_color'] .';">' . $n['banco'] . '</span>: ' .  $n['tipo'] . ':' : '') . $n['moneda'] . ':' . $n['cuenta'] . ': </b> ' . $n['descripcion'];
  $tr[] = money($n['monto'], $n['moneda2'], 'EN ' . $n['categoria']);
  $tr['tablefy_tr'] = array(
    'class' => implode(' ', $clases),
  );
  return $tr;
});
$table->setOption('efectuar', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM financiero.movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $db->update('financiero.movimiento', array(
    'efectuado' => 1,
    'fecha'     => Doris::time(),
    'procesado' => Doris::time(),
  ), 'id = ' . (int) $n['id']);
  regresar();
});
$table->setOption('editar&', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM financiero.movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $form = Formity::getInstance('movimiento');
  $form->setUniqueId('editar');
  $form->setPreData($ficha);
  if($form->byRequest()) {
    if($form->isValid($err)) {
      $data = $form->getData();
      $db->update('financiero.movimiento', $data, 'id = ' . (int) $n['id']);
    }
  }
  echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">';
  echo $form->render();
});
$table->setOption('eliminar', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM financiero.movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $db->update('financiero.movimiento', array(
    'eliminado' => Doris::time(),
  ), 'id = ' . (int) $n['id']);
  regresar();
});


$table = Tablefy::getInstance('movimientos');
$table->setTitle('Movimientos: ' . $mes_visto);
$table->setHeader(array('FECHA','MOVIMIENTO','MONTO'));
$table->setRow(function($id) use($db, $usuario_id) {
  return $db->get("SELECT * FROM financiero.movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $id, true);
});
$ls = array();
$grupos = array();
$table->setData(function() use($db, $mes_visto, $fecha_visto, $usuario_id, &$ls) {
  $ls = $db->get("
  SELECT
    DATE(MOV.fecha) as fecha_corta,
    MOV.*,
    C.nombre as cuenta,
    M.nombre as moneda,
    T.nombre as tipo,
    B.nombre as banco,
    S.nombre as sujeto,
    B.color as banco_color,
    CAT.nombre as categoria,
    M2.nombre as moneda2
  FROM financiero.movimiento MOV
  JOIN financiero.cuenta C ON C.id = MOV.cuenta_id AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
  LEFT JOIN financiero.sujeto S ON S.id = MOV.sujeto_id
  LEFT JOIN financiero.moneda M2 ON M2.id = MOV.moneda_id
  JOIN financiero.moneda M ON M.id = C.moneda_id
  JOIN financiero.tipo T ON T.id = C.tipo_id
  LEFT JOIN financiero.categoria CAT ON CAT.id = MOV.categoria_id
  LEFT JOIN financiero.banco B ON B.id = C.banco_id
  WHERE MOV.usuario_id = {$usuario_id} AND to_char(MOV.fecha, 'YYYY-MM') = '" . $mes_visto . "' AND MOV.eliminado IS NULL " . (!empty($_GET['cuenta']) ? ' AND C.id = ' . $_GET['cuenta'] : '')  . "
  AND MOV.efectuado IS TRUE AND (MOV.categoria_id IS NULL OR MOV.categoria_id <> 29) " . (!empty($_GET['cuenta']) ? ' AND C.id = ' . $_GET['cuenta'] : '')  . "
  ORDER BY MOV.fecha DESC, MOV.id DESC");
  return $ls;
}, function($n) use(&$ls, &$grupos) {
  global $DIAS;
  $cantidad = 0;
  if(!isset($grupos[$n['fecha_corta']])) {
    $cantidad = array_filter($ls, function($m) use($n) {
      return $m['fecha_corta'] == $n['fecha_corta'];
    });
    $cantidad = count($cantidad);
    $grupos[$n['fecha_corta']] = $cantidad;
  }
  $clases = array();
  $clases[] = 'cantidad-' . $cantidad;
  if(!empty($n['bloqueado'])) {
    $clases[] = 'bloqueado';
  }
  if(empty($n['efectuado'])) {
    $clases[] = 'no-efectuado';
  }
  if(!empty($n['recurrente_id'])) {
    $clases[] = 'recurrente';
  }
  if($n['monto'] > 0) {
    $clases[] = 'ingreso';
  } elseif($n['monto'] < 0) {
    $clases[] = 'gasto';
  }
  
  $tr = array();
  if($cantidad > 1) {
    $tr[] = array(substr(strtoupper($DIAS[date('w', strtotime($n['fecha_corta']))]), 0, 3) . ' ' . date('d', strtotime($n['fecha_corta'])), array(
      'rowspan'    => $cantidad,
      'text-align' => 'center',
    ));
  } elseif($cantidad == 1) {
    $tr[] = substr(strtoupper($DIAS[date('w', strtotime($n['fecha_corta']))]), 0, 3) . ' ' . date('d', strtotime($n['fecha_corta']));
  }
  $tr[] = '<div>#' . $n['id'] . ': ' . date('h:i A', strtotime($n['fecha'])) . ' | ' . $n['sujeto'] . ' | ' . select_categoria($n) . '</div>' .
  '<b>#' . $n['cuenta_id'] . ':' .  (!empty($n['banco']) ? '<span style="color:' . $n['banco_color'] .';">' . $n['banco'] . '</span>: ' .  $n['tipo'] . ':' : '') . $n['moneda'] . ':' . $n['cuenta'] . ': </b> ' . $n['descripcion'];
  $tr[] = money($n['monto'], $n['moneda2'], 'EN ' . $n['categoria']);
  $tr['tablefy_tr'] = array(
    'class' => implode(' ', $clases),
  );
  return $tr;
});
$table->setOption('editar&', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM financiero.movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $form = Formity::getInstance('movimiento');
  $form->setUniqueId('editar');
  $form->setPreData($ficha);
  if($form->byRequest()) {
    if($form->isValid($err)) {
      $data = $form->getData();
      $db->update('financiero.movimiento', $data, 'id = ' . (int) $n['id']);
    }
  }
  echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">';
  echo $form->render();
});
$table->setOption('eliminar', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM financiero.movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $db->update('financiero.movimiento', array(
    'eliminado' => Doris::time(),
  ), 'id = ' . (int) $n['id']);
  regresar();
});

$labels_monedas = array(
    '1-ingreso' => array(
      'tipo' => '1-ingreso',
      'nombre' => 'INGRESO SOLES',
      'color'  => '#c18335',
    ),
    '1-gasto' => array(
      'tipo' => '1-gasto',
      'nombre' => 'GASTO SOLES',
      'color'  => '#ff3939',
    ),
    '1-disponible' => array(
      'tipo' => '1-disponible',
      'nombre' => 'DISPONIBLE SOLES',
      'color'  => '#eef136',
    ),
    '2-ingreso' => array(
      'tipo' => '2-ingreso',
      'nombre' => 'INGRESO DOLARES',
      'color'  => '#b0f39b',
    ),
    '2-gasto' => array(
      'tipo' => '2-gasto',
      'nombre' => 'GASTO DOLARES',
      'color'  => '#164001',
    ),
    '2-disponible' => array(
      'tipo' => '2-disponible',
      'nombre' => 'DISPONIBLE DOLARES',
      'color'  => '#26c313',
   )
);

$formularios = array('movimiento','recurrente','transferencia','prestamo','cierre','cuenta','categoria','sujeto', 'grupo');
?>
<title>Finanzas Personales</title>
<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.3.0/js/jquery.tooltipster.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.4/css/bulma.css" type="text/css" media="all" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.3.0/css/tooltipster.min.css" type="text/css" media="all" />
<style>
* {
  vertical-align: top;
  text-align: left;
  padding: 0;
  margin: 0;
}
body {
  overflow: unset!important;
  overflow-x: unset!important;
  font-size: 11px;
  font-family: sans-serif;
}
h1 {
  font-size: 15px;
  border-bottom: 1px solid #bbbbbb;
  margin-bottom: 5px;
}
table {
  font-size: inherit;
  width: 100%;
  max-width: 100%;
}
.tablefy {
  padding: 2px;
  border: 1px solid #d4d4d4;
  margin: 2px;
}
.tablefy td, .tablefy th {
  border: 1px solid #ececec;
  padding-left: 5px;
  padding-right: 5px;
}
th {
  font-size: 10.5px;
}
.formulario {
  padding: 5px 15px;
  border: 1px solid #d4d4d4; 
  max-width: 500px;
  margin: 0 auto;
  margin-bottom: 20px;
}
.monto {
  text-align: right;
  color: #d0d0d0;
/*  background: #ffffffc9; */
  margin: 0 -5px;
}
.monto .cantidad {
  padding: 0 3px;
}
/*.monto .moneda {
  font-size: 9px;
  vertical-align: bottom;
  color: #696969;
}*/
.no-efectuado {
  background: #eaeaea!important;
  color: #abaaaa!important;
}
.bloqueado {
  background: #a7a7a7!important;
  color: #fdfcfc!important;
}
.recurrente {
  background: #ffc645!important;
  color: #8a6000!important;
}
.gasto {
  background: #ffd9d9;
}
.ingreso {
  background: #c8ecd0!important;
}
[data-page] {
  display: none;
}
.titular {
  text-transform: uppercase;
  font-weight: bold;
}
.opciones {
  font-size: 9px;
  text-transform: uppercase;
  color: #444;
  padding-top: 3px;
}
a {
  color: inherit!important;
}
.movimientos {
  margin: 0 auto;
  max-width: 600px;
}
.movimientos tr {
  border-bottom: 2px solid #b5b5b5;
}
small {
  display: block;
  text-align: left;
  color: #8dc0ff;
}
progress {
  display: block;
  margin: 0 auto;
  width: 90%;
  height: 5px;
}
.selectCategoria {
  font-size: 9px;
}
</style>
<div class="container is-widescreen">
<?php mostrar_navegacion(); ?>
<div class="tabs is-centered">
  <ul>
<?php foreach($formularios as $f) { ?>
    <li data-open="<?= $f ?>"><a><?= strtoupper($f) ?></a></li>
<?php } ?>
    <li style="background: #fb8686;color: #fff;"><a href="?salir">SALIR</a></li>
  </ul>
</div>
<div class="pages">
<?php foreach($formularios as $f) {
  $form = Formity::getInstance($f);
  if($f == 'movimiento') {
    $form->removeField('procesado');
  }
  if($f == 'grupo') { ?>
    <div data-page="<?= $f ?>">
      <div class="formulario"><?= $form->render(); ?></div>
      <div class="formulario"><?= Formity::getInstance('unirte_grupo')->render(); ?></div>
    </div>
  <?php } else { ?>
    <div data-page="<?= $f ?>"><div class="formulario"><?= $form->render(); ?></div></div>
<?php } } ?>
</div>
<div>
  <b>Usuario:</b> <?= strtoupper($_SESSION[$sId]['usuario']) ?><br />
  <b>Fecha Actual:</b> <?= $fecha_visto ?><br />
  <b>Fecha Cierre:</b> <?= $fecha_cierre ?><br /><br />
<?php if(!empty($tipo_cambio)) { foreach($tipo_cambio as $tc) { ?>
  <b><?= $tc['desde'] ?> => <?= $tc['hasta'] ?>: <?= $tc['multiplicar'] ?>/<?= $tc['dividir'] ?></br>
<?php } } ?><br /> 
</div>

<div class="columns is-multiline">
  <div class="column">
    <div class="tablefy"><?= Tablefy::getInstance('cuentas')->render(); ?></div>
    <div class="tablefy"><?= Tablefy::getInstance('creditos')->render(); ?></div>
  </div>
  <div class="column">
    <div class="columns">
      <div class="column">
        <div class="tablefy">
          <h1 class="titular">LIQUIDO: <?= $mes_visto ?></h1>
          <table>
<?php foreach($cuentas_resumen as $c) { ?>
            <tr>
              <th colspan="4" style="text-align:center;vertical-align:middle;"><?= $c['moneda'] ?></th>
            </tr>
            <tr>
              <th style="text-align:right;">CONTABLE</th>
              <th style="text-align:right;">CREDITOS</th>
              <th style="text-align:right;">DISPONIBLE</th>
              <th style="text-align:right;">PROYECTADO</th>
            </tr>
            <tr>
              <td><?= money($c['contable'], $c['moneda'], 'CONTABLE ' . $c['moneda']) ?></td>
              <td>
                <?= money($c['credito_total'], $c['moneda'], 'CREDITO TOTAL ' . $c['moneda']) ?>
                <?= money($c['credito_cierre'] + $c['credito_consumido'], $c['moneda'], $c['credito_cierre'] . ' + ' . $c['credito_consumido'] . ' : ' . $c['moneda']) ?>
              </td>
              <td>
                <?= money($c['disponible'], $c['moneda'], 'DISPONIBLE ' . $c['moneda']) ?>
                <?= money($c['disponible'] + $c['credito_cierre'] + $c['credito_pago'], $c['moneda'], 'DISPONIBLE - CREDITO DEL CIERRE EN ' . $c['moneda']) ?>
              </td>
              <td>
                <?= money($c['proyectado'], $c['moneda'], 'PROYECTADO ' . $c['moneda']) ?>
                <?= money($c['proyectado'] + $c['credito_cierre'] + $c['credito_consumido'], $c['moneda'], 'PROYECTADO ' . $c['moneda'] . ' - TOTAL CREDITOS ' . $c['moneda']) ?>
              </td>
            </tr>
<?php } ?>
          </table>
        </div>
        <div class="tablefy">
          <h1 class="titular">RESUMÉN DEL MES: <?= $mes_visto ?></h1>
          <table>
<?php foreach($mes_resumen as $c) { ?>
            <tr>
              <th colspan="5" style="text-align:center;vertical-align:middle;"><?= $c['moneda'] ?></th>
            </tr>
            <tr>
              <th></th>
              <th style="text-align:right">INGRESOS</th>
              <th style="text-align:right">GASTOS</th>
              <th style="text-align:right">FLUJO</th>
            </tr>
            <tr>
              <th>EFECTUADO</th>
              <td><?= money($c['ingreso'], $c['moneda'], 'INGRESO EFECTUADO') ?></td>
              <td><?= money($c['gasto'], $c['moneda'], 'GASTO EFECTUADO') ?></td>
              <td><?= money($c['ingreso'] + $c['gasto'], $c['moneda'], 'FLUJO DEL MES EFECTUADO') ?></td>
            </tr>
            <tr>
              <th>PENDIENTE</th>
              <td><?= money($c['ingreso_pendiente'], $c['moneda'], 'INGRESO PENDIENTE') ?></td>
              <td><?= money($c['gasto_pendiente'], $c['moneda'], 'GASTO PENDIENTE') ?></td>
              <td><?= money($c['ingreso_pendiente'] + $c['gasto_pendiente'], $c['moneda'], 'FLUJO PENDIENTE') ?></td>
            </tr>
            <tr>
              <th>PROYECTADO</th>
              <td><?= money($c['ingreso_proyectado'], $c['moneda'], 'INGRESO PROYECTADO A FIN DE MES') ?></td>
              <td><?= money($c['gasto_proyectado'], $c['moneda'], 'GASTO PROYECTADO A FIN DE MES') ?></td>
              <td><?= money($c['ingreso_proyectado'] + $c['gasto_proyectado'], $c['moneda'], 'FLUJO PROYECTADO A FIN DE MES') ?></td>
            </tr>
<?php } ?>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="columns">
  <div class="column is-4">
    <div class="tablefy"><?= Tablefy::getInstance('categorias')->render(); ?></div>
    <div class="tablefy"><?= Tablefy::getInstance('resumen_por_dias')->render(); ?></div>
    <div class="tablefy movimientos"><?= Tablefy::getInstance('movimientos_pendientes')->render(); ?></div>
  </div>
  <div class="column is-8">
    <div class="tablefy movimientos"><?= Tablefy::getInstance('movimientos')->render(); ?></div>
  </div>
</div>
</div>
<script>
$(document).on('change', '.selectCategoria', function() {
  var id = $(this).attr('data-id');
  var categoria_id = $(this).val();
  $.ajax({
    url: '?editar=categoria',
    type: 'POST',
    data: { id: id, categoria_id: categoria_id },
  });
});
$("[data-open]").on("click", function() {
  var id = $(this).attr('data-open');
  if($("[data-page='" + id + "']").is(':visible')) {
    $("[data-page='" + id + "']").slideUp();
    return;
  }
  $(".pages > div").stop().slideUp();
  $("[data-page='" + id + "']").stop().slideDown();
});
$('a[data-popy]').attr('target','_blank');
function numberFormat(n, c, d, t) {
  var c = isNaN(c = Math.abs(c)) ? 2 : c,
    d = d == undefined ? "." : d,
    t = t == undefined ? "," : t,
    s = n < 0 ? "-" : "",
    i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
    j = (j = i.length) > 3 ? j % 3 : 0;
    decimal = '';
    if(c) {
      decimal = Math.abs(n - i).toFixed(c).slice(2);
      decimal = decimal == '00' ? '' : d + decimal;
    }
  return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + decimal;
};
$(".monto").each(function() {
  var num = parseFloat($(this).find('.cantidad').text());
  $(this).find('.cantidad').text(numberFormat(num));
  if(num > 0) {
    $(this).css({ color: '#008800' });
  } else if(num < 0) {
    $(this).css({ color: '#ff0000' });
  }
});
$(document).ready(function() {
  $('.monto').tooltipster();
});
</script>
