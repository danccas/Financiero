<?php
session_start(); 

require_once(__DIR__ . '/conf.php');
require_once(ABS_LIBRERIAS . 'formity2.php');
require_once(ABS_LIBRERIAS . 'chartjs.php');
require_once(ABS_LIBRERIAS . 'financiero.php');



function money($monto, $moneda = '', $titulo = '') {
  $r = '<div class="monto" title="' . $titulo . '">';
  if($moneda == 'SOLES') {
    $r .= '<span class="moneda">S/.</span>';
  }
  $r .= '<span class="cantidad">' . $monto . '</span>';
  if($moneda == 'DOLARES') {
    $r .= '<span class="moneda">$</span>';
  }
  $r .= '</div>';
  return $r;
}

$db = Doris::init('financiero');

$sId = 'user23';

$form = Formity::getInstance('usuario');
$form->setUniqueId('login');
$form->setTitle('Identificación');
$form->addField('usuario', 'input:text');
$form->addField('clave', 'input:password');
$form->addField('tipo', 'select')->setOptions(array(1 => 'YA REGISTRADO', 2 => 'NUEVO'));

if(isset($_GET['salir'])) {
  $_SESSION[$sId] = null;
}
if(empty($_SESSION[$sId])) {
  if($form->byRequest()) {
    if($form->isValid($error)) {
      $data = $form->getData();
      $usuario = $db->get("SELECT * FROM usuario WHERE usuario = :usuario", true, false, array(
        'usuario' => $data['usuario'],
      ));
      if($data['tipo'] == 2) {
        if(empty($usuario)) {
          unset($data['tipo']);
          $usuario_id = $db->insert('usuario', $data);
          $db->insert('sujeto', array(
            'nombre'     => strtoupper($data['usuario']),
            'usuario_id' => $usuario_id,
          ));
          $_SESSION[$sId] = $data + array('id' => $usuario_id);
          header("location: .");
          exit;
        } else {
          $form->setDescription('Usuario ya existe');
        }
      } else {
        if(!empty($usuario) && $usuario['clave'] == $data['clave']) {
          $_SESSION[$sId] = $usuario;
          header("location: .");
          exit;
        } else {
          $form->setDescription('Datos incorrectos');
        }
      }
    }
  }
  echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">';
  echo $form->render();
  exit;
}

$usuario_id = $_SESSION[$sId]['id'];

if(empty($usuario_id)) {
  echo "sin-usuario";
  exit;
}

if(!empty($_GET['editar'])) {
  if($_GET['editar'] == 'categoria') {
    $db->update('movimiento', array(
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
  FROM tipo_cambio TC
  JOIN moneda M1 ON M1.id = TC.desde_id
  JOIN moneda M2 ON M2.id = TC.hasta_id
  WHERE TC.fecha = '{$fecha_visto}'");

$ls = $db->get("SELECT * FROM moneda ORDER BY nombre");
$monedas = result_parse_to_options($ls, 'id', 'nombre');

$ls = $db->get("SELECT * FROM tipo ORDER BY nombre");
$tipos = result_parse_to_options($ls, 'id', 'nombre');

$ls = $db->get("SELECT * FROM banco ORDER BY nombre");
$ls = result_parse_to_options($ls, 'id', 'nombre');


$form = Formity::getInstance('categoria');
$form->setTitle('Categoria');
$form->addField('nombre', 'input:text');
$form->addField('color', 'input:color');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->insert('categoria', $data);
  }
}

$form = Formity::getInstance('sujeto');
$form->setTitle('Sujeto');
$form->addField('nombre', 'input:text');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $data['usuario_id'] = $usuario_id;
    $db->insert('sujeto', $data);
  }
}

$form = Formity::getInstance('grupo');
$form->setTitle('Crear Grupo');
$form->addField('nombre', 'input:text');

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->transaction();
    $grupo_id = $db->insert('grupo', $data);
    $db->insert('grupo_usuario', array(
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
    $gru = $db->get("SELECT * FROM grupo WHERE id = " . (int) $data['codigo'], true);
    if(empty($gru)) {
      $form->setError('El código es inválido');
    } else {
      $db->insert('grupo_usuario', array(
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
    $db->insert('cuenta', $data);
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
  FROM cuenta C
  JOIN moneda M ON M.id = C.moneda_id
  JOIN tipo T ON T.id = C.tipo_id
  LEFT JOIN banco B ON B.id = C.banco_id
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

$categorias_sql = $db->get("SELECT * FROM categoria ORDER BY nombre ASC");
$categorias = result_parse_to_options($categorias_sql, 'id', 'nombre');

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

$sujetos = $db->get("SELECT * FROM sujeto WHERE usuario_id = {$usuario_id} ORDER BY id ASC");
$sujetos = result_parse_to_options($sujetos, 'id', 'nombre');

$bloqueados = $db->get("SELECT id, descripcion FROM movimiento M1 WHERE M1.bloqueado = 1 AND M1.id NOT IN (SELECT id FROM movimiento M2 WHERE M2.bloqueado_id = M1.id)");
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
    FROM movimiento WHERE LOWER(descripcion) LIKE ?
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
      $cuenta = $db->get("SELECT * FROM cuenta WHERE id = " . $data['cuenta_id'], true);
      $data['moneda_id'] = $cuenta['moneda_id'];
    }
    $db->transaction();
    $db->update('cuenta', array('ultimo' => Doris::time()), 'id = ' . (int) $data['cuenta_id']);
    $db->update('categoria', array('ultimo' => Doris::time()), 'id = ' . (int) $data['categoria_id']);
    $data['usuario_id'] = $usuario_id;
    $data['procesado']  = $data['fecha'];
    $db->insert('movimiento', $data);
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
    $recurrente_id = $db->insert('recurrente', $data);
    $db->insert('movimiento', array(
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
        $cu = $db->get("SELECT * FROM cuenta WHERE id = " . (int) $data['desde_id'], true);
        if(!empty($cu) && $cu['tipo_id'] == 2) {
          $contable = 1;
        }
      }
      $db->insert('movimiento', array(
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
      $db->insert('movimiento', array(
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
    $prestamo_id = $db->insert('prestamo', $data);
    $db->insert('movimiento', array(
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
      $db->insert('movimiento', array(
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
    $db->insert('cierre', $data);
    #regresar();
  }
}
$debitos = $db->get("
  SELECT
    x.*,
    (x.cierre + x.flujo_ingreso + x.flujo_gasto) as disponible
  FROM (
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
    B.nombre as banco,
    B.color as banco_color,
    (
      COALESCE((
        SELECT COALESCE(CASE WHEN C.tipo_id = 1 THEN disponible ELSE contable END, 0)
        FROM cierre
        WHERE cuenta_id = C.id AND moneda_id = C.moneda_id AND DATE(fecha) < '" . $fecha_visto . "'
        ORDER BY fecha DESC
        LIMIT 1
      ), 0)
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? "
      +
      (
        SELECT
           COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.eliminado IS NULL AND MOV.moneda_id = C.moneda_id
          AND DATE(MOV.fecha) <= '" . $fecha_cierre . "'
          AND ((DATE(MOV.fecha) <= '" . $fecha_actual . "' AND MOV.efectuado = 1) OR (DATE(MOV.fecha) > '" . $fecha_actual . "'))
      )" : "") . "
    ) as cierre,
    (
      COALESCE((
        SELECT CASE WHEN C.tipo_id = 1 THEN disponible ELSE contable END
        FROM cierre
        WHERE cuenta_id = C.id AND moneda_id <> C.moneda_id AND DATE(fecha) < '" . $fecha_visto . "'
        ORDER BY fecha DESC
        LIMIT 1
      ), 0)
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? "
      +
      (
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.eliminado IS NULL AND MOV.moneda_id <> C.moneda_id
          AND DATE(MOV.fecha) > '" . $fecha_cierre . "' AND DATE(MOV.fecha) <= '" . $fecha_visto . "'
          AND ((DATE(MOV.fecha) <= '" . $fecha_actual . "' AND MOV.efectuado = 1) OR (DATE(MOV.fecha) > '" . $fecha_actual . "'))
      )" : "") . "
    ) as cierre_extra,
    COALESCE((
      SELECT SUM(CASE WHEN bloqueado = 0 THEN monto ELSE (monto * -1) END)
      FROM movimiento
      WHERE monto > 0 AND cuenta_id = C.id AND moneda_id = C.moneda_id AND eliminado IS NULL AND date_format(fecha, '%Y-%m') = '" . $mes_visto . "' AND DATE(fecha) <= '" . $fecha_visto . "'
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? " AND ((DATE(fecha) <= '" . $fecha_actual . "' AND efectuado = 1) OR (DATE(fecha) > '" . $fecha_actual . "')) " : " AND efectuado = 1 ") . "
    ), 0) as flujo_ingreso,
    COALESCE((
      SELECT SUM(CASE WHEN bloqueado = 0 THEN monto ELSE (monto * -1) END)
      FROM movimiento
      WHERE monto < 0 AND cuenta_id = C.id AND moneda_id = C.moneda_id AND eliminado IS NULL AND date_format(fecha, '%Y-%m') = '" . $mes_visto . "' AND DATE(fecha) <= '" . $fecha_visto . "'
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? " AND ((DATE(fecha) <= '" . $fecha_actual . "' AND efectuado = 1) OR (DATE(fecha) > '" . $fecha_actual . "')) " : " AND efectuado = 1 ") . "
    ), 0) as flujo_gasto
  FROM cuenta C
  JOIN moneda M ON M.id = C.moneda_id
  JOIN tipo T ON T.id = C.tipo_id
  LEFT JOIN banco B ON B.id = C.banco_id
  WHERE C.usuario_id = {$usuario_id} AND C.tipo_id IN (1, 3) AND C.created_on <= '" . $fecha_visto . "' AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "'))x
  ORDER BY banco, tipo, moneda DESC, nombre");

$table = Tablefy::getInstance('cuentas');
$table->setTitle('Cuentas Debito');
$table->setHeader(array('BANCO-CUENTA', 'CIERRE', 'FLUJO', 'DISP.'));
$table->setData($debitos, function($n) {
  return array(
    '<div>#' . $n['id'] . ':' . (!empty($n['banco']) ? '<span style="color:' . $n['banco_color'] .';">' . $n['banco'] . '</span>: ' : '') . $n['moneda'] . ':' . $n['nombre'] . '</div><small>' . $n['numero'] . '</small>',
    money($n['cierre'], $n['moneda'], 'CIERRE'),
    money($n['flujo_ingreso'], $n['moneda'], 'INGRESO DEL PERIODO') . money($n['flujo_gasto'], $n['moneda'], 'GASTO DEL PERIODO'),
    money($n['disponible'], $n['moneda'], 'DISPONIBLE ACTUAL'),
  );
});
$table->setOption('Editar&', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM cuenta WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
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

$creditos = $db->get("
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
    DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '" . date('Y-m-d', strtotime($fecha_visto)) . "' ORDER BY fecha DESC LIMIT 1), '" . date('Y-m-d', strtotime($fecha_cierre)) . "')) as periodo_anterior,
    (
      COALESCE((
        SELECT contable
        FROM cierre
        WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) <= '" . $fecha_visto . "'
        ORDER BY fecha DESC
        LIMIT 1
      ), 0)
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? "
      +
      (
        SELECT
           COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.eliminado IS NULL AND MOV.moneda_id = M.id
          AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH)))
          AND DATE(MOV.fecha) <= '{$fecha_visto}'
      )" : "") . "
    ) as cierre,
    (
      COALESCE((
        SELECT SUM(contable * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END))
        FROM cierre
        WHERE cuenta_id = C.id AND DATE(fecha) = (SELECT DATE(fecha) FROM cierre WHERE cuenta_id = C.id AND DATE(fecha) <= '{$fecha_visto}' ORDER BY id DESC LIMIT 1)
      ), 0)
      " . (strtotime($fecha_actual) < strtotime($fecha_visto) ? "
      +
      (
        SELECT
           COALESCE(SUM((CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END) * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END)), 0)
        FROM movimiento MOV
        WHERE MOV.cuenta_id = C.id AND MOV.eliminado IS NULL
          AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH)))
          AND DATE(MOV.fecha) <= '{$fecha_visto}'
      )" : "") . "
    ) as cierre_convertido,
    COALESCE((
      SELECT SUM(CASE WHEN bloqueado = 0 THEN monto ELSE (monto * -1) END)
      FROM movimiento MOV
      WHERE efectuado = 1 AND cuenta_id = C.id AND moneda_id = M.id AND eliminado IS NULL AND MOV.monto > 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH)))
        AND DATE(MOV.fecha) <= '" . $fecha_visto . "'
    ), 0) as ingreso,
    COALESCE((
      SELECT SUM((CASE WHEN bloqueado = 0 THEN monto ELSE (monto * -1) END) * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END))
      FROM movimiento MOV
      WHERE efectuado = 1 AND cuenta_id = C.id AND eliminado IS NULL AND MOV.monto > 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH)))
        AND DATE(MOV.fecha) <= '" . $fecha_visto . "'
    ), 0) as ingreso_convertido,

    COALESCE((
      SELECT SUM(CASE WHEN bloqueado = 0 THEN monto ELSE (monto * -1) END)
      FROM movimiento MOV
      WHERE efectuado = 1 AND cuenta_id = C.id AND moneda_id = M.id AND eliminado IS NULL AND MOV.monto < 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH)))
        AND DATE(MOV.fecha) <= '{$fecha_visto}'
    ), 0) as gasto,
    COALESCE((
      SELECT SUM((CASE WHEN bloqueado = 0 THEN monto ELSE (monto * -1) END) * (CASE WHEN moneda_id = C.moneda_id THEN 1 ELSE (SELECT TC.multiplicar/TC.dividir FROM tipo_cambio TC WHERE TC.fecha = DATE(fecha) AND desde_id = moneda_id AND hasta_id = C.moneda_id LIMIT 1) END))
      FROM movimiento MOV
      WHERE efectuado = 1 AND cuenta_id = C.id AND eliminado IS NULL AND MOV.monto < 0
        AND DATE(MOV.fecha) >= DATE(COALESCE((SELECT fecha FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND DATE(fecha) < '{$fecha_visto}' ORDER BY fecha DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH)))
        AND DATE(MOV.fecha) <= '{$fecha_visto}'
    ), 0) as gasto_convertido

  FROM cuenta C
    JOIN moneda MC ON MC.id = C.moneda_id
    JOIN moneda M
    JOIN tipo T ON T.id = C.tipo_id
    LEFT JOIN banco B ON B.id = C.banco_id
  WHERE C.usuario_id = {$usuario_id} AND C.tipo_id = 2 AND DATE(C.created_on) <= '" . $fecha_visto . "' AND (C.eliminado IS NULL OR DATE(C.eliminado) >= '" . $fecha_visto . "')
  HAVING moneda = moneda2 OR (moneda <> moneda2 AND (cierre <> 0 OR gasto <> 0 OR ingreso <> 0)))x
  ORDER BY banco, tipo, moneda, id, moneda <> moneda2, nombre");
$table = Tablefy::getInstance('creditos');
$table->setTitle('Cuentas de Créditos');
$table->setHeader(array('CUENTA','CIERRE','PERIODO','TOTAL'));
$table->setData($creditos, function($n) {
  return array(
    '#' . $n['id'] . ':' . (!empty($n['banco']) ? '<span style="color:' . $n['banco_color'] .';">' . $n['banco'] . '</span>: ' : '') . $n['moneda'] . ':' . $n['nombre'] . 
    ' [' . date('d', strtotime($n['fecha_cierre'])) . ':' . date('d', strtotime($n['fecha_facturacion'])) . ']' . 
    ($n['moneda'] == $n['moneda2'] ? '<progress max="' . $n['credito'] . '" value="' . ($n['credito']  - $n['disponible_convertido']) . '" title="CONSUMIDO: ' . (int) ($n['credito']  - $n['disponible_convertido']) . ' ' . $n['moneda'] . ' DE ' . $n['credito'] . ' ' . $n['moneda'] . '"></progress>' : ''),
    money($n['cierre'], $n['moneda2'], 'CIERRE DEL PERIODO ANTERIOR: ' . $n['periodo_anterior']),
    money($n['gasto'], $n['moneda2'], 'GASTOS DEL PERIODO ACTUAL') . money($n['ingreso'], $n['moneda2'], 'PAGOS DEL PERIODO ACTUAL'),
    money($n['total'], $n['moneda2'], 'MONTO TOTAL'),
  );
});
$table->setOption('Editar&', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM cuenta WHERE usuario_id = {$usuario_id} AND eliminado IS NULL AND id = " . (int) $n['id'], true);
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


$cierres = $db->get("
SELECT
   GROUP_CONCAT(DISTINCT CI.nombre) as nombre,
    CI.fecha,
    M.nombre as moneda,
    SUM(CI.gasto) as gasto,
    SUM(CI.ingreso) as ingreso,
    SUM(CI.contable) as contable,
    SUM(CI.disponible) as disponible
FROM (
  SELECT
    C.id,
    C.tipo_id,
    (SELECT id FROM cierre WHERE usuario_id = C.usuario_id AND cuenta_id = C.id AND moneda_id = M.id AND fecha < '{$fecha_visto}' ORDER BY id DESC LIMIT 1) as cierre_id
  FROM moneda M
  JOIN cuenta C ON C.usuario_id = {$usuario_id} 
  HAVING cierre_id IS NOT NULL) x
	JOIN cierre CI ON CI.id = x.cierre_id
	JOIN moneda M ON M.id = CI.moneda_id
	GROUP BY x.tipo_id, CI.moneda_id, CI.nombre
  HAVING gasto <> 0 OR ingreso <> 0 OR contable <> 0
  ORDER BY nombre;");
$table = Tablefy::getInstance('cierres');
$table->setTitle('Cierre: ' . $mes_cierre);
$table->setHeader(array('PERIODO','INGRE','GAST','CONT.','DISP.'));
$table->setData($cierres, function($n) use($mes_cierre) {
  return array(
    $n['nombre'],
    money($n['ingreso'], $n['moneda'], 'INGRESO DEL ' . $mes_cierre),
    money($n['gasto'], $n['moneda'], 'GASTOS DEL ' . $mes_cierre),
    money($n['contable'], $n['moneda'], 'CONTRABLE EN ' . $mes_cierre),
    money($n['disponible'], $n['moneda'], 'DISPONIBLE EL ' . $mes_cierre),
  );
});

$mgrupos = $db->get("
  SELECT
    G.id,
    G.nombre as grupo,
    M.nombre as moneda,
    COUNT(DISTINCT GU.usuario_id) as integrantes,
    GROUP_CONCAT(DISTINCT U.usuario) as usuarios,
    SUM((
      (
        SELECT
          COALESCE(SUM(CI.disponible), 0)
        FROM cierre CI
        JOIN cuenta C ON C.id = CI.cuenta_id AND C.tipo_id = 1
        WHERE CI.usuario_id = GU.usuario_id AND date_format(CI.fecha, '%Y-%m') = '" . $mes_cierre . "' AND CI.moneda_id = M.id
      )
      +
      (
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM cuenta C
        JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL
        WHERE C.usuario_id = GU.usuario_id AND MOV.moneda_id = M.id AND C.tipo_id = 1 AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND DATE(MOV.fecha) <= '{$fecha_visto}'
          AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
      )
    )) as disponible,
    SUM((
      SELECT
        SUM((SELECT COALESCE(contable, 0) FROM cierre WHERE usuario_id = C.usuario_id AND cuenta_id = C.id AND moneda_id = M2.id AND fecha < '{$fecha_visto}' ORDER BY id DESC LIMIT 1))
      FROM cuenta C
        JOIN moneda M2 ON 1 = 1
      WHERE C.usuario_id = GU.usuario_id AND C.tipo_id = 2 AND M2.id = M.id AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
    )) as credito_cierre,
    SUM((
      SELECT
        COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
      FROM cuenta C
      JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL
      WHERE C.usuario_id = GU.usuario_id AND MOV.moneda_id = M.id AND C.tipo_id = 2 AND DATE(MOV.fecha) <= '{$fecha_visto}' AND DATE(MOV.fecha) >= (SELECT DATE(fecha) FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id ORDER BY id DESC LIMIT 1)
        AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
    )) as credito_consumido,
    SUM((
      SELECT
        COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
      FROM cuenta C
      JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND MOV.monto > 0
      WHERE C.usuario_id = GU.usuario_id AND MOV.moneda_id = M.id AND C.tipo_id = 2 AND DATE(MOV.fecha) <= '{$fecha_visto}' AND DATE(MOV.fecha) >= (SELECT DATE(fecha) FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id ORDER BY id DESC LIMIT 1)
        AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
    )) as credito_pago,
    SUM((
      SELECT
       SUM(credito)
      FROM cuenta C
      WHERE C.usuario_id = GU.usuario_id AND C.moneda_id = M.id AND C.tipo_id = 2 AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
    )) as creditos
  FROM grupo_usuario GU2
    JOIN grupo G ON G.id = GU2.grupo_id
    JOIN grupo_usuario GU ON GU.grupo_id = G.id
    JOIN usuario U ON U.id = GU.usuario_id
    JOIN moneda M ON 1 = 1
  WHERE GU2.usuario_id = {$usuario_id} AND DATE(GU2.created_on) <= '" . $fecha_visto . "'
  GROUP BY G.id, M.id
  ORDER BY G.nombre ASC, M.id ASC");
$mgrupos = array_group_by($mgrupos, array(
  array('key' => 'id', 'only' => array('id','grupo','integrantes','usuarios')),
));
$table = Tablefy::getInstance('grupos');
$table->setTitle('Grupos');
$table->setHeader(array('GRUPO','DISPONIBLE','CRÉDITO','CONSUMOS'));
$table->setData($mgrupos, function($n) {
  return array(
    '#' . $n['id'] . ': ' . $n['grupo'] . '<br /><small title="' . $n['usuarios'] . '">' . $n['integrantes'] . ' integrantes</small>',
    money($n['children'][0]['disponible'], $n['children'][0]['moneda'], 'MONTO DISPONIBLE DEL GRUPO') . money($n['children'][1]['disponible'], $n['children'][1]['moneda'], 'MONTO DISPONIBLE DEL GRUPO'),
    money($n['children'][0]['creditos'], $n['children'][0]['moneda'], 'CRÉDITOS OTORGADOS AL GRUPO') . money($n['children'][1]['creditos'], $n['children'][1]['moneda'], 'CRÉDITOS OTORGADOS AL GRUPO'),
    money($n['children'][0]['credito_cierre'] + $n['children'][0]['credito_pago'], $n['children'][0]['moneda'], 'CRÉDITOS CONSUMIDOS DEL GRUPO')
  . money($n['children'][1]['credito_cierre'] + $n['children'][1]['credito_pago'], $n['children'][1]['moneda'], 'CRÉDITOS CONSUMIDOS DEL GRUPO'),
  );
});

$prestamos = $db->get("
  SELECT
    P.*,
    M.nombre as moneda,
    (P.monto * (100 + P.interes) / 100) as pagar,
    (SELECT SUM(monto) FROM movimiento WHERE prestamo_id = P.id AND eliminado IS NULL AND efectuado = 1 AND monto < 0 AND DATE(fecha) <= '{$fecha_visto}') as pagado,
    (SELECT SUM(monto) FROM movimiento WHERE prestamo_id = P.id AND eliminado IS NULL AND efectuado = 0 AND monto < 0 AND DATE(fecha) >= '{$fecha_visto}') as pendiente,
    (SELECT COUNT(monto) FROM movimiento WHERE prestamo_id = P.id AND eliminado IS NULL AND efectuado = 0 AND monto < 0 AND DATE(fecha) >= '{$fecha_visto}') as pendiente_cuotas,
    (SELECT fecha FROM movimiento WHERE prestamo_id = P.id AND eliminado IS NULL AND efectuado = 0 AND DATE(fecha) >= '{$fecha_visto}' ORDER BY fecha ASC LIMIT 1) as proxima_fecha,
    (SELECT monto FROM movimiento WHERE prestamo_id = P.id AND eliminado IS NULL AND efectuado = 0 AND DATE(fecha) >= '{$fecha_visto}' ORDER BY fecha ASC LIMIT 1) as proximo_pago
  FROM prestamo P
  JOIN moneda M ON M.id = P.moneda_id
  WHERE P.usuario_id = {$usuario_id} AND P.fecha_deposito <= '" . $fecha_visto . "' AND (P.fecha_cancelacion IS NULL OR P.fecha_cancelacion >= '" . $fecha_visto . "')
  GROUP BY 1");
$table = Tablefy::getInstance('prestamos');
$table->setTitle('Prestamos');
$table->setHeader(array('NOMBRE','MONTO','PENDIENT.','PROXIMO'));
$table->setData($prestamos, function($n) {
  return array(
    $n['descripcion'] .
    '<progress max="' . $n['pagar'] . '" value="' . -1 * $n['pagado'] . '"></progress>',
    money($n['monto'], $n['moneda'], 'MONTO PRESTADO') . money($n['pagar'] * -1, $n['moneda'], 'MONTO A PAGAR'),
    money($n['pendiente'], $n['moneda'], 'MONTO TOTAL PENDIENTE') . '<div style="text-align:center;" title="CUOTAS PENDIENTES">' . $n['pendiente_cuotas'] . ' meses</div>',
    money($n['proximo_pago'], $n['moneda'], 'PAGO MENSUAL PENDIENTE') . fecha($n['proxima_fecha']),
  );
});


$table = Tablefy::getInstance('categorias');
$table->setTitle('Categorias: ' . $mes_visto);
$table->setHeader(array('CATEGORIA','GASTO','INGRESO'));
$table->setData(function() use($db, $mes_visto, $usuario_id) {
  return $db->get("
    SELECT
      C.nombre as categoria,
      M.nombre as moneda,
      SUM(CASE WHEN MOV.monto < 0 THEN MOV.monto ELSE 0 END) as monto_gasto,
      SUM(CASE WHEN MOV.monto > 0 THEN MOV.monto ELSE 0 END) as monto_ingreso
    FROM movimiento MOV
      JOIN categoria C ON C.id = MOV.categoria_id
      JOIN moneda M ON M.id = MOV.moneda_id
    WHERE MOV.usuario_id = {$usuario_id} AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND MOV.eliminado IS NULL AND MOV.efectuado = 1 AND MOV.contable = 1
    GROUP BY C.id, M.id
    ORDER BY monto_gasto ASC");
}, function($n) {
  return array(
    $n['categoria'],
    money($n['monto_gasto'], $n['moneda'], 'MONTO GASTADO EN ' . $n['categoria'] . ': ' . $n['monto_gasto'] . ' ' . $n['moneda']),
    money($n['monto_ingreso'], $n['moneda'], 'MONTO INGRESADO POR ' . $n['categoria'] . ': ' . $n['monto_ingreso'] . ' ' . $n['moneda']),
  );
});


$table = Tablefy::getInstance('resumen_por_dias');
$table->setTitle('GASTO PROMEDIO: ' . $mes_visto);
$table->setHeader(array('DIA','PROMEDIO'));
$table->setData(function() use($db, $mes_visto, $usuario_id) {
  return $db->get("
  SELECT
    dia,
    moneda,
    COUNT(*) as vez,
    AVG(monto) as monto
  FROM (SELECT
      date_format(MOV.fecha, '%w') as dia,
      M.nombre as moneda,
      SUM(CASE WHEN MOV.monto < 0 THEN MOV.monto ELSE 0 END) as monto
    FROM movimiento MOV
      JOIN moneda M ON M.id = MOV.moneda_id
    WHERE MOV.usuario_id = {$usuario_id} AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND MOV.efectuado = 1
      AND MOV.bloqueado = 0 AND MOV.contable = 1 AND MOV.eliminado IS NULL
      AND MOV.categoria_id IN (2,3,22,24)
    GROUP BY DATE(MOV.fecha), MOV.moneda_id)x
  GROUP BY dia, moneda
  ORDER BY dia = 0, dia ASC, moneda");
}, function($n) use($DIAS) {
  return array(
    strtoupper($DIAS[$n['dia']]) . ' x' . $n['vez'],
    money($n['monto'], $n['moneda'], 'MONTO PROMEDIO DE GASTO LOS ' . strtoupper($DIAS[$n['dia']]))
  );
});



$table = Tablefy::getInstance('movimientos_pendientes');
$table->setTitle('Pendientes: ' . $mes_visto);
$table->setHeader(array('FECHA','MOVIMIENTO','MONTO'));
$table->setRow(function($id) use($db, $usuario_id) {
  return $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $id, true);
});
$table->setData(function() use($db, $mes_visto, $usuario_id) {
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
  FROM movimiento MOV
  JOIN cuenta C ON C.id = MOV.cuenta_id AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
  LEFT JOIN sujeto S ON S.id = MOV.sujeto_id
  LEFT JOIN moneda M2 ON M2.id = MOV.moneda_id
  JOIN moneda M ON M.id = C.moneda_id
  LEFT JOIN tipo T ON T.id = C.tipo_id
  LEFT JOIN categoria CAT ON CAT.id = MOV.categoria_id
  LEFT JOIN banco B ON B.id = C.banco_id
  WHERE MOV.usuario_id = {$usuario_id} AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND MOV.eliminado IS NULL " . (!empty($_GET['cuenta']) ? ' AND C.id = ' . $_GET['cuenta'] : '')  . " AND MOV.efectuado = 0
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
  $ficha = $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $db->update('movimiento', array(
    'efectuado' => 1,
    'fecha'     => Doris::time(),
    'procesado' => Doris::time(),
  ), 'id = ' . (int) $n['id']);
  regresar();
});
$table->setOption('editar&', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $form = Formity::getInstance('movimiento');
  $form->setUniqueId('editar');
  $form->setPreData($ficha);
  if($form->byRequest()) {
    if($form->isValid($err)) {
      $data = $form->getData();
      $db->update('movimiento', $data, 'id = ' . (int) $n['id']);
    }
  }
  echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">';
  echo $form->render();
});
$table->setOption('eliminar', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $db->update('movimiento', array(
    'eliminado' => Doris::time(),
  ), 'id = ' . (int) $n['id']);
  regresar();
});


$table = Tablefy::getInstance('movimientos');
$table->setTitle('Movimientos: ' . $mes_visto);
$table->setHeader(array('FECHA','MOVIMIENTO','MONTO'));
$table->setRow(function($id) use($db, $usuario_id) {
  return $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $id, true);
});
$ls = array();
$grupos = array();
$table->setData(function() use($db, $mes_visto, $usuario_id, &$ls) {
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
  FROM movimiento MOV
  JOIN cuenta C ON C.id = MOV.cuenta_id AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
  LEFT JOIN sujeto S ON S.id = MOV.sujeto_id
  LEFT JOIN moneda M2 ON M2.id = MOV.moneda_id
  JOIN moneda M ON M.id = C.moneda_id
  JOIN tipo T ON T.id = C.tipo_id
  LEFT JOIN categoria CAT ON CAT.id = MOV.categoria_id
  LEFT JOIN banco B ON B.id = C.banco_id
  WHERE MOV.usuario_id = {$usuario_id} AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND MOV.eliminado IS NULL " . (!empty($_GET['cuenta']) ? ' AND C.id = ' . $_GET['cuenta'] : '')  . " AND MOV.efectuado = 1
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
  $ficha = $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $form = Formity::getInstance('movimiento');
  $form->setUniqueId('editar');
  $form->setPreData($ficha);
  if($form->byRequest()) {
    if($form->isValid($err)) {
      $data = $form->getData();
      $db->update('movimiento', $data, 'id = ' . (int) $n['id']);
    }
  }
  echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">';
  echo $form->render();
});
$table->setOption('eliminar', function($n) use($db, $usuario_id) {
  $ficha = $db->get("SELECT * FROM movimiento WHERE usuario_id = {$usuario_id} AND id = " . (int) $n['id'], true);
  if(empty($ficha)) {
    _404();
  }
  $db->update('movimiento', array(
    'eliminado' => Doris::time(),
  ), 'id = ' . (int) $n['id']);
  regresar();
});


$table = Tablefy::getInstance('sugeridos');
$table->setTitle('Sugeridos para Hoy');
$table->setHeader(array('HORA','MOVIMIENTO','MONTO'));
$table->setData(function() use($db) {
return $db->get("
SELECT
x.*,
CAT.nombre as categoria,
B.nombre as banco,
C.nombre as cuenta,
M.nombre as moneda,
M2.nombre as moneda2,
T.nombre as tipo
FROM (SELECT
  M.sujeto_id,
  M.cuenta_id,
  M.categoria_id,
  M.moneda_id,
  M.descripcion,
  TIME_FORMAT(SEC_TO_TIME(AVG(HOUR(M.fecha) * 3600 + (MINUTE(M.fecha) * 60) + SECOND(M.fecha))),'%h:%i %p') as hora,
  AVG(HOUR(M.fecha) * 3600 + (MINUTE(M.fecha) * 60) + SECOND(M.fecha)) as minutos,
  M.monto,
  COUNT(*) as cantidad
FROM movimiento M
WHERE M.eliminado IS NULL AND M.efectuado = 1 AND ((DAYOFMONTH(M.fecha) = DAYOFMONTH(NOW()) AND M.categoria_id NOT IN (15)) OR (DAYOFWEEK(M.fecha) = DAYOFWEEK(NOW()) AND M.categoria_id NOT IN (15)))
GROUP BY M.descripcion, HOUR(M.fecha)
ORDER BY cantidad DESC
LIMIT 10)x
JOIN cuenta C ON C.id = x.cuenta_id AND C.eliminado IS NULL
JOIN tipo T ON T.id = C.tipo_id
LEFT JOIN banco B ON B.id = C.banco_id
JOIN moneda M ON M.id = C.moneda_id
JOIN moneda M2 ON M2.id = x.moneda_id
JOIN categoria CAT ON CAT.id = x.categoria_id
ORDER BY x.minutos ASC");
}, function($n) {
  global $DIAS;
  $clases = array();
  if($n['monto'] > 0) {
    $clases[] = 'ingreso';
  } elseif($n['monto'] < 0) {
    $clases[] = 'gasto';
  }

  $tr = array();
  $tr[] = date('h:iA', strtotime($n['hora']));
  $tr[] = '<div>' . $n['categoria'] . ' x ' . $n['cantidad'] . ' vece(s)</div>' .
  '<b>#' . $n['cuenta_id'] . ':' .  (!empty($n['banco']) ? '<span style="color:' . $n['banco_color'] .';">' . $n['banco'] . '</span>: ' .  $n['tipo'] . ':' : '') . $n['moneda'] . ':' . $n['cuenta'] . ': </b> ' . $n['descripcion'];
  $tr[] = money($n['monto'], $n['moneda2'], 'EN ' . $n['categoria']);
  $tr['tablefy_tr'] = array(
    'class' => implode(' ', $clases),
  );
  return $tr;
});
$table->setOption('Efectuar', function($n) use($db, $usuario_id) {
  $db->insert('movimiento', array(
    'usuario_id'   => $usuario_id,
    'cuenta_id'    => $n['cuenta_id'],
    'categoria_id' => $n['categoria_id'],
    'sujeto_id'    => $n['sujeto_id'],
    'contable'     => 1,
    'descripcion'  => $n['descripcion'],
    'monto'        => $n['monto'],
    'moneda_id'    => $n['moneda_id'],
    'fecha'        => Doris::time(date('Y-m-d') . ' ' . $n['hora']),
    'efectuado'    => 1,
  ));
  regresar();
});


$cuentas_resumen = $db->get("
  SELECT
    M.nombre as moneda,
    (
      (
        SELECT
          COALESCE(SUM(CI.contable), 0)
        FROM cierre CI
        JOIN cuenta C ON C.id = CI.cuenta_id AND C.tipo_id IN (1, 3)
        WHERE CI.usuario_id = {$usuario_id} AND date_format(CI.fecha, '%Y-%m') = '" . $mes_cierre . "' AND CI.moneda_id = M.id
      )
      +
      (
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado_id IS NULL THEN MOV.monto ELSE 0 END), 0)
        FROM movimiento MOV
        JOIN cuenta C ON C.id = MOV.cuenta_id AND C.tipo_id IN (1, 3)
        WHERE MOV.usuario_id = {$usuario_id} AND MOV.moneda_id = M.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND DATE(MOV.fecha) <= '{$fecha_visto}'
      )
    ) as contable,
    (
      (
        SELECT
          COALESCE(SUM(CI.disponible), 0)
        FROM cierre CI
        JOIN cuenta C ON C.id = CI.cuenta_id AND C.tipo_id = 1
        WHERE CI.usuario_id = {$usuario_id} AND date_format(CI.fecha, '%Y-%m') = '" . $mes_cierre . "' AND CI.moneda_id = M.id
      )
      +
      (
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM cuenta C
        JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL
        WHERE C.usuario_id = {$usuario_id} AND MOV.moneda_id = M.id AND C.tipo_id = 1 AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND DATE(MOV.fecha) <= '{$fecha_visto}'
      )
    ) as disponible,
    (
      SELECT
        SUM((SELECT COALESCE(contable, 0) FROM cierre WHERE usuario_id = C.usuario_id AND cuenta_id = C.id AND moneda_id = M.id AND fecha < '{$fecha_visto}' ORDER BY fecha DESC, id DESC LIMIT 1))
      FROM cuenta C
      WHERE C.usuario_id = {$usuario_id} AND C.tipo_id = 2 AND (C.eliminado IS NULL OR DATE(C.eliminado) > '{$fecha_visto}')
    ) as credito_cierre,
    (
      SELECT
        COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
      FROM cuenta C
        JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL 
      WHERE C.usuario_id = {$usuario_id} AND C.tipo_id = 2
        AND MOV.moneda_id = M.id
        AND DATE(MOV.fecha) <= '{$fecha_visto}'
        AND DATE(MOV.fecha) >= COALESCE((SELECT DATE(fecha) FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id AND fecha < '{$fecha_visto}' ORDER BY fecha DESC, id DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH))
    ) as credito_consumido,
    (
      SELECT
        COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
      FROM cuenta C
      JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL AND MOV.monto > 0
      WHERE C.usuario_id = {$usuario_id} AND MOV.moneda_id = M.id AND C.tipo_id = 2 AND DATE(MOV.fecha) <= '{$fecha_visto}'
       AND DATE(MOV.fecha) >= COALESCE((SELECT DATE(fecha) FROM cierre WHERE cuenta_id = C.id AND moneda_id = M.id ORDER BY id DESC LIMIT 1), DATE_SUB('{$fecha_cierre}', INTERVAL 1 MONTH))
    ) as credito_pago,
    (
      SELECT
       SUM(credito)
      FROM cuenta C
      WHERE C.usuario_id = {$usuario_id} AND C.moneda_id = M.id AND C.tipo_id = 2 AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
    ) as credito_total,
    (
      (
        SELECT
          COALESCE(SUM(CI.disponible), 0)
        FROM cierre CI
        JOIN cuenta C ON C.id = CI.cuenta_id AND C.tipo_id = 1
        WHERE CI.usuario_id = {$usuario_id} AND date_format(CI.fecha, '%Y-%m') = '" . $mes_cierre . "' AND CI.moneda_id = M.id
      )
      +
      (
        SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM cuenta C
        JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.eliminado IS NULL
        WHERE C.usuario_id = {$usuario_id} AND MOV.moneda_id = M.id AND C.tipo_id = 1 AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND (C.eliminado IS NULL OR DATE(C.eliminado) > '" . $fecha_visto . "')
      )
    ) as proyectado
  FROM moneda M");
$mes_resumen = $db->get("
  SELECT
    M.id as moneda_id,
    M.nombre as moneda,
    SUM(CASE WHEN MOV.monto > 0 AND MOV.efectuado = 1 AND MOV.contable = 1 AND C.tipo_id = 1 AND DATE(MOV.fecha) <= '{$fecha_visto}' THEN MOV.monto ELSE 0 END) as ingreso,
    SUM(CASE WHEN MOV.monto < 0 AND MOV.efectuado = 1 AND MOV.contable = 1 AND DATE(MOV.fecha) <= '{$fecha_visto}' THEN MOV.monto ELSE 0 END) as gasto,
    SUM(CASE WHEN MOV.monto > 0 AND MOV.efectuado = 0 AND MOV.contable = 1 AND C.tipo_id = 1 THEN MOV.monto ELSE 0 END) as ingreso_pendiente,
    SUM(CASE WHEN MOV.monto < 0 AND MOV.efectuado = 0 AND MOV.contable = 1 THEN MOV.monto ELSE 0 END) as gasto_pendiente,
    SUM(CASE WHEN MOV.monto > 0 AND C.tipo_id = 1 AND MOV.contable = 1 THEN MOV.monto ELSE 0 END) as ingreso_proyectado,
    SUM(CASE WHEN MOV.monto < 0 AND MOV.contable = 1 THEN MOV.monto ELSE 0 END) as gasto_proyectado
  FROM moneda M
  JOIN movimiento MOV ON MOV.moneda_id = M.id
  JOIN cuenta C ON C.id = MOV.cuenta_id
  WHERE MOV.usuario_id = {$usuario_id} AND MOV.bloqueado = 0 AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND MOV.eliminado IS NULL
  GROUP BY MOV.moneda_id
  ORDER BY M.nombre DESC");


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
$estadistica_01 = $db->get("
SELECT
  mes,
  moneda_id,
  moneda,
  SUM(ingreso) as ingreso,
  GROUP_CONCAT(gasto) as gasto,
  SUM(disponible) as disponible
FROM (
  (
SELECT
  mes,
  moneda_id,
  moneda,
  ingreso,
  gasto,
  (
    (
      SELECT
        COALESCE(SUM(CI.disponible), 0)
      FROM cierre CI
      WHERE CI.usuario_id = x.usuario_id AND DATE(CI.fecha) = (SELECT MAX(fecha) FROM cierre WHERE usuario_id = x.usuario_id) AND CI.moneda_id = x.moneda_id
    ) + (
      SELECT
        COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
      FROM cuenta C
      JOIN movimiento MOV ON MOV.contable = 1 AND MOV.cuenta_id = C.id AND MOV.eliminado IS NULL AND (MOV.efectuado = 1 OR (MOV.efectuado = 0 AND DATE(MOV.fecha) > LAST_DAY(NOW())))
      WHERE C.usuario_id = x.usuario_id AND MOV.moneda_id = x.moneda_id AND C.tipo_id = 1 AND DATE(MOV.fecha) > (SELECT MAX(fecha) FROM cierre WHERE usuario_id = x.usuario_id)
        AND DATE(MOV.fecha) < DATE(CONCAT(x.mes, '-01'))
    ) + (
      SELECT
        COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
      FROM cuenta C
      JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.efectuado = 1 AND MOV.eliminado IS NULL
      WHERE C.usuario_id = x.usuario_id AND MOV.moneda_id = x.moneda_id AND C.tipo_id = 1 AND date_format(MOV.fecha, '%Y-%m') = x.mes
    )
  ) as disponible
FROM (SELECT
    C.usuario_id,
    date_format(MOV.fecha, '%Y-%m') as mes,
    MOV.moneda_id,
    M.nombre as moneda,
    COALESCE(SUM(
      CASE WHEN MOV.contable = 1 AND MOV.monto > 0 AND MOV.eliminado IS NULL AND MOV.bloqueado = 0 AND (MOV.efectuado = 1 OR (MOV.efectuado = 0 AND DATE(MOV.fecha) > LAST_DAY(NOW()))) THEN MOV.monto ELSE 0 END
    ), 0) as ingreso,
    COALESCE(SUM(
      CASE WHEN MOV.contable = 1 AND MOV.monto < 0 AND MOV.eliminado IS NULL AND MOV.bloqueado = 0 AND (MOV.efectuado = 1 OR (MOV.efectuado = 0 AND DATE(MOV.fecha) > LAST_DAY(NOW()))) THEN MOV.monto ELSE 0 END
    ), 0) as gasto
  FROM cuenta C
  JOIN movimiento MOV ON MOV.cuenta_id = C.id AND DATE(MOV.fecha) > '{$fecha_cierre}'
  JOIN moneda M ON M.id = MOV.moneda_id
  WHERE C.usuario_id = {$usuario_id}
  GROUP BY date_format(MOV.fecha, '%Y-%m'), MOV.moneda_id) x
  )
  UNION
  (
    SELECT
      date_format(C.fecha, '%Y-%m') as mes,
      C.moneda_id,
      M.nombre as moneda,
      COALESCE(SUM(CASE WHEN CU.tipo_id = 1 THEN C.ingreso ELSE 0 END), 0) as ingreso,
      COALESCE(SUM(C.gasto), 0) as gasto,
      COALESCE(SUM(C.disponible), 0) as disponible
    FROM cierre C
    JOIN cuenta CU ON CU.id = C.cuenta_id
    JOIN moneda M ON M.id = C.moneda_id
    WHERE C.usuario_id = {$usuario_id} AND CU.tipo_id = 1
    GROUP BY mes, C.moneda_id
    LIMIT 11
  )
  ) x
  GROUP BY mes, moneda_id
  ORDER BY mes ASC, moneda_id ASC
  LIMIT 13");
if(!empty($estadistica_01)) {
  $_est = array();
  foreach($estadistica_01 as $e) {
    foreach(array('ingreso','gasto','disponible') as $d) {
      $_est[] = array(
        'fecha' => $e['mes'],
        'tipo'  => $e['moneda_id'] . '-' . $d,
        'cantidad' => $d == 'gasto' ? -1 * (double) $e[$d] : (double) $e[$d],
      );
    }
  }
  $estadistica_01 = $_est;
  unset($_est);
  $estadistica_01 = Chartjs::line($estadistica_01, $labels_monedas);
}
$reporte_anual  = array();
foreach($MESES as $k => $m) {
  $f_c = $k == 0 ? (date('Y') - 1) . '-12' : date('Y') . '-' . str_pad($k, 2, '0', STR_PAD_LEFT);
  $f = date('Y') . '-' . str_pad(($k + 1), 2, '0', STR_PAD_LEFT);
  $last = date('Y-m-d 11:59:59', strtotime('last day of this month', strtotime($f)));
  if($k + 1 < date('m')) {
    $rp = $db->get("
      SELECT
        *,
        (disponible + deuda) as saldo
      FROM (
      SELECT
        '$f' as mes,
        M.id as moneda_id,
        M.nombre as moneda,
        (
          SELECT
            COALESCE(SUM(CI.disponible), 0)
          FROM cierre CI
            JOIN cuenta C ON C.id = CI.cuenta_id AND C.tipo_id = 1
          WHERE CI.usuario_id = {$usuario_id} AND date_format(CI.fecha, '%Y-%m') = '" . $f . "' AND CI.moneda_id = M.id
        ) as disponible,
        (
          (
            SELECT
              COALESCE(SUM(CI.contable), 0)
            FROM cierre CI
              JOIN cuenta C ON C.id = CI.cuenta_id AND C.tipo_id = 2
            WHERE CI.usuario_id = {$usuario_id} AND CI.moneda_id = M.id
              AND DATE(CI.fecha) = (SELECT MAX(fecha) FROM cierre WHERE cuenta_id = C.id AND CI.fecha < '" . $last . "' LIMIT 1)
          ) + (
            SELECT
              COALESCE(SUM((SELECT SUM(monto) FROM movimiento WHERE prestamo_id = P.id AND eliminado IS NULL AND efectuado = 0 AND monto < 0)), 0)
            FROM prestamo P
            WHERE P.fecha_deposito <= '" . $last . "' AND P.moneda_id = M.id AND P.usuario_id = {$usuario_id}
          )
        ) as deuda
      FROM moneda M)x");
  } else {
    $rp = $db->get("
    SELECT
      *,
      (disponible1 + disponible2 + deuda_cierre + deuda_mes + deuda_prestamo) as saldo
    FROM (
      SELECT
        '$f' as mes,
        M.id as moneda_id,
        M.nombre as moneda,
        (
          SELECT
            COALESCE(SUM(CI.disponible), 0)
          FROM cierre CI
            JOIN cuenta C ON C.id = CI.cuenta_id AND C.tipo_id = 1
          WHERE CI.usuario_id = {$usuario_id} AND date_format(CI.fecha, '%Y-%m') = '" . $mes_actual_cierre . "' AND CI.moneda_id = M.id
        ) as disponible1,
        (
          SELECT
            COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
          FROM cuenta C
          JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.eliminado IS NULL
            AND DATE(MOV.fecha) > '" . $fecha_actual_cierre . "' AND date_format(MOV.fecha, '%m') <= " . ($k + 1) . " AND date_format(MOV.fecha, '%Y') = " . date('Y') . "
            " . (date('m') == ($k + 1) ? 'AND MOV.efectuado = 1' : "AND ((date_format(MOV.fecha, '%Y-%m') = '" . $mes_actual . "' AND MOV.efectuado = 1) OR (date_format(MOV.fecha, '%Y-%m') <> '" . $mes_actual . "'))") . "
          WHERE C.usuario_id = {$usuario_id} AND MOV.moneda_id = M.id AND C.tipo_id = 1
        ) as disponible2,
        '{$last}' as deuda_fecha,
        (
          SELECT
            SUM((SELECT COALESCE(contable, 0) FROM cierre WHERE usuario_id = C.usuario_id AND cuenta_id = C.id AND moneda_id = M.id AND fecha < '{$last}' ORDER BY fecha DESC, id DESC LIMIT 1))
          FROM cuenta C
          WHERE C.usuario_id = {$usuario_id} AND C.tipo_id = 2 AND (C.eliminado IS NULL OR DATE(C.eliminado) > '{$last}')
        ) as deuda_cierre,
        (SELECT
          COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
        FROM cuenta C
        JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.eliminado IS NULL
          AND DATE(MOV.fecha) > (SELECT fecha FROM cierre WHERE usuario_id = C.usuario_id AND cuenta_id = C.id AND moneda_id = M.id AND fecha < '{$last}' ORDER BY fecha DESC, id DESC LIMIT 1) AND date_format(MOV.fecha, '%m') <= " . ($k + 1) . " AND date_format(MOV.fecha, '%Y') = " . date('Y') . "
          " . (date('m') == ($k + 1) ? 'AND MOV.efectuado = 1' : "AND ((date_format(MOV.fecha, '%Y-%m') = '" . $mes_actual . "' AND MOV.efectuado = 1) OR (date_format(MOV.fecha, '%Y-%m') <> '" . $mes_actual . "'))") . "
        WHERE C.usuario_id = {$usuario_id} AND MOV.moneda_id = M.id AND C.tipo_id = 2
        ) as deuda_mes,
        (SELECT
          COALESCE(SUM((SELECT SUM(monto) FROM movimiento WHERE prestamo_id = P.id AND eliminado IS NULL AND efectuado = 0 AND monto < 0)), 0)
        FROM prestamo P
        WHERE P.fecha_deposito <= '" . $last . "' AND P.moneda_id = M.id AND P.usuario_id = {$usuario_id} AND (P.fecha_cancelacion IS NULL OR P.fecha_cancelacion >= '" . $last . "')
        ) as deuda_prestamo
      FROM moneda M)x");
  }
  $reporte_anual[$m] = $rp;
}

$estadistica_02 = $db->get("
SELECT
  x.*,
  DAY(x.fecha) as fecha,
  (
    (
      SELECT
        COALESCE(SUM(CI.disponible), 0)
      FROM cierre CI
      WHERE CI.usuario_id = {$usuario_id} AND date_format(CI.fecha, '%Y-%m') = '" . $mes_cierre . "' AND CI.moneda_id = x.moneda_id
    )
    +
    (
      SELECT
        COALESCE(SUM(CASE WHEN MOV.bloqueado = 0 THEN MOV.monto ELSE (MOV.monto * -1) END), 0)
      FROM cuenta C
      JOIN movimiento MOV ON MOV.cuenta_id = C.id AND MOV.eliminado IS NULL AND ((DATE(MOV.fecha) <= DATE(NOW()) AND MOV.efectuado = 1) OR DATE(MOV.fecha) > DATE(NOW()))
      WHERE C.usuario_id = {$usuario_id} AND MOV.moneda_id = x.moneda_id AND C.tipo_id = 1 AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND DATE(MOV.fecha) <= x.fecha
    )
  ) as disponible
FROM
  (SELECT
    DATE(MOV.fecha) as fecha,
    M.id as moneda_id,
    M.nombre as moneda,
    COALESCE(SUM(CASE WHEN MOV.monto < 0 AND MOV.bloqueado = 0 AND ((DATE(MOV.fecha) <= DATE(NOW()) AND MOV.efectuado = 1) OR DATE(MOV.fecha) > DATE(NOW())) THEN MOV.monto ELSE 0 END), 0) as gasto,
    COALESCE(SUM(CASE WHEN MOV.monto > 0 AND MOV.bloqueado = 0 AND ((DATE(MOV.fecha) <= DATE(NOW()) AND MOV.efectuado = 1) OR DATE(MOV.fecha) > DATE(NOW())) THEN MOV.monto ELSE 0 END), 0) as ingreso
  FROM moneda M
  JOIN movimiento MOV ON MOV.usuario_id = {$usuario_id} AND MOV.moneda_id = M.id AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "' AND MOV.eliminado IS NULL
  GROUP BY DATE(MOV.fecha), MOV.moneda_id) x
  ORDER BY x.fecha ASC, x.moneda_id ASC");


if(!empty($estadistica_02)) {
  $_est = array();
  foreach($estadistica_02 as $e) {
    foreach(array('ingreso','gasto','disponible') as $d) {
      $_est[] = array(
        'fecha' => $e['fecha'],
        'tipo'  => $e['moneda_id'] . '-' . $d,
        'cantidad' => $d == 'gasto' ? -1 * (double) $e[$d] : (double) $e[$d],
      );
    }
  }
  $estadistica_02 = $_est;
  unset($_est);
  $estadistica_02 = Chartjs::line($estadistica_02, $labels_monedas);
}

/*
$estadistica_03x = $db->get("
  SELECT
    C.id,
    C.nombre as tipo,
    COALESCE(SUM(CASE WHEN MOV.monto < 0 THEN -1 * MOV.monto ELSE 0 END), 0) as cantidad
  FROM categoria C
  JOIN movimiento MOV ON MOV.categoria_id = C.id AND MOV.usuario_id = {$usuario_id} AND MOV.moneda_id = 1 AND date_format(MOV.fecha, '%Y-%m') = '" . $mes_visto . "'
    AND MOV.eliminado IS NULL AND MOV.bloqueado = 0
  GROUP BY C.id
  ORDER BY cantidad DESC");

$categorias_sql = array_map(function($n) { $n['tipo'] = $n['nombre']; return $n; }, $categorias_sql);
$estadistica_03 = Chartjs::pie($estadistica_03x, $categorias_sql);*/
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
  background: #c8ecd0;
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
  <div class="column is-12">
    <b>Situación Financiera: </b>
    <div class="tabs is-centered">
    <table><tr>
<?php
$i = 1;
foreach($reporte_anual as $k => $m) {
  $i++;
  $es_futuro = $i > date('m') + 1;
  $es_visto  = $i == date('m', strtotime($fecha_visto)) + 1;
  $es_actual = $i == date('m') + 1;
?>
  <td data-k="<?= $k ?>">
        <a href="?fecha=<?= $es_actual ? $fecha_actual : date('Y-m-d', strtotime('last day of this month', strtotime($m[0]['mes'] . '-01'))); ?>" style="padding: 0;display: block;">
        <div style="text-align: center;padding: 0 10px;margin:0 3px;border: 1px solid #d4d4d4;<?= ($es_actual ? 'background: #fff0ba;' : ($es_futuro ? 'background: #e4e4e4;' : '')) . ($es_visto ? 'border-bottom: 5px solid #fb8686;' : '') ?>">
          <b><?= strtoupper($k) ?></b>
<?php foreach($m as $d) { ?>
<?= money($d['disponible'], $d['moneda'], $d['mes'] . ' | LIQUIDO ' . $d['moneda']); ?>
<?= money($d['saldo'], $d['moneda'], $d['mes'] . ' | SITUACIÓN ' . $d['moneda'] . ' = LIQUIDO - CRÉDITOS - PRESTAMOS (' . $d['disponible'] . '|' . $d['deuda_cierre'] . '|' . $d['deuda_mes'] . '|' . $d['deuda_prestamo'] . '|' . $d['deuda_fecha'] . ')'); ?>
<?php } ?>
        </div>
        </a>
      </td>
<?php } ?>
      </tr></table>
    </div>
  </div>
  <div class="column is-5">
    <div class="tablefy"><?= Tablefy::getInstance('cierres')->render(); ?></div>
    <div class="tablefy"><?= Tablefy::getInstance('cuentas')->render(); ?></div>
    <div class="tablefy"><?= Tablefy::getInstance('creditos')->render(); ?></div>
  </div>
  <div class="column">
    <div class="columns">
      <div class="column">
<?php if(!empty($mgrupos)) { ?>
        <div class="tablefy"><?= Tablefy::getInstance('grupos')->render(); ?></div>
<?php } ?>
        <div class="tablefy"><?= Tablefy::getInstance('prestamos')->render(); ?></div>
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
      <div class="column">
        <div class="tablefy"><?= Tablefy::getInstance('sugeridos')->render(); ?></div>
      </div>
    </div>
  </div>
  <div class="column is-12">
      <div>
        <canvas id="canvas01" style="height: 200px"></canvas>
      </div>
  </div>
  <div class="column is-12">
      <div>
        <canvas id="canvas02" style="height: 200px"></canvas>
      </div>
  </div>
<?php /*
  <div class="column is-12">
    <div class="columns">
      <div class="column">
        <canvas id="canvas03" style="max-height:300px;"></canvas>
      </div>
      <div class="column">
        <ul>
<?php foreach($estadistica_03x as $n) { ?>
          <li  style="text-align:center;"><?= $n['tipo'] ?> (<b><?= $n['cantidad'] ?></b>)</li>
<?php } ?>
        </ul>
      </div>
    </div>
  </div> */ ?>
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
var config01 = {
  type: 'line',
  data: <?= json_encode($estadistica_01); ?>,
  options: {
        maintainAspectRatio: false,
        responsive: true,
        tooltips: {
          mode: 'index',
          intersect: false,
        },
        hover: {
          mode: 'nearest',
          intersect: true
        },
        scales: {
          xAxes: [{
            display: true,
          }],
        }
      }
};
var config02 = {
  type: 'line',
  data: <?= json_encode($estadistica_02); ?>,
  options: {
        maintainAspectRatio: false,
        responsive: true,
        tooltips: {
          mode: 'index',
          intersect: false,
        },
        hover: {
          mode: 'nearest',
          intersect: true
        },
        scales: {
          xAxes: [{
            display: true,
          }],
        }
      }
};
/*var config03 = {
  type: 'pie',
  data: 
  options: { responsive: true,maintainAspectRatio:false,transparencyEffects:true,dataSetBorderWidth: 2, legend:{ display: false } }
};*/
window.onload = function() {
  var ctx = document.getElementById('canvas01').getContext('2d');
  window.myLine01 = new Chart(ctx, config01);
  var ctx = document.getElementById('canvas02').getContext('2d');
  window.myLine02 = new Chart(ctx, config02);
/*  var ctx = document.getElementById('canvas03').getContext('2d');
  window.myLine03 = new Chart(ctx, config03);*/
};
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
