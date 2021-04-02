<?php

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

function getMeses() {
  global $MESES;
  return $MESES;
}
function getMesesActual() {
  $meses = getMeses();
  $rp = [];
  foreach($meses as $k => $v) {
    if($k + 1>= ((int) date('m')) - 3 && $k +1 <= ((int) date('m'))) {
      $rp[date('Y-') . ($k+1)] = date('Y-') . strtoupper($v);
    }
  }
  return $rp;
}
function regresar() {
  header('location: /alquileres/');
  exit;
}

$db = Doris::init('alquileres');

$ls = $db->get("SELECT * FROM alquileres.departamento ORDER BY rotulo ASC");
$departamentos = result_parse_to_options($ls, 'id', 'rotulo');

$form = Formity::getInstance('departamento');
$form->setTitle('Departamento');
$form->addField('piso', 'input:numeric');
$form->addField('rotulo', 'input:text');
$form->addField('monto', 'input:numeric')->setMin(50)->setMax(1500);

if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->insert('alquileres.departamento', $data + ['edificio_id' => 1]);
    regresar();
  }
}

$form = Formity::getInstance('inquilino');
$form->setTitle('Inquilino');
$form->addField('departamento_id', 'select')->setOptions($departamentos);
$form->addField('documento', 'input:text');
$form->addField('nombres', 'input:text');
$form->addField('telefono', 'input:text');
$form->addField('monto', 'input:numeric')->setMin(50)->setMax(1500);
$form->addField('monto_garantia', 'input:numeric')->setMin(50)->setMax(1500);
$form->addField('fecha_desde', 'input:date');


if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    $db->insert('alquileres.inquilino', $data);
    regresar();
  }
}

$ls = $db->get("
  SELECT I.id, I.nombres, D.rotulo
  FROM alquileres.inquilino I
  JOIN alquileres.departamento D ON D.id = I.departamento_id
  ORDER BY D.rotulo ASC");
$inquilinos = result_parse_to_options($ls, 'id', ['rotulo',' => ', 'nombres']);
$form = Formity::getInstance('mensualidad');
$form->setTitle('Registrar Mensualidad');
$form->addField('inquilino_id', 'select')->setOptions($inquilinos);
$form->addField('mes', 'select')->setOptions(getMesesActual());
$form->addField('fecha_pago', 'input:date');
$ls = $db->get("SELECT * FROM financiero.obtener_cuentas_debitos(NOW()::timestamp) WHERE moneda_id = 1");
$cuentas = result_parse_to_options($ls, 'id', ['banco',' / ', 'cuenta']);

$form->addField('cuenta_id', 'select')->setOptions($cuentas);
$form->addField('monto', 'input:numeric')->setMin(50)->setMax(1500);


if($form->byRequest()) {
  if($form->isValid($error)) {
    $data = $form->getData();
    list($anho, $mes) = explode('-', $data['mes']);
    $data['mes'] = $mes;
    $data['anho'] = $anho;
    $db->insert('alquileres.mensualidad', $data);
    regresar();
  }
}

$departamentos = $db->get("
SELECT d.id, d.rotulo departamento,
  i.nombres inquilino,
  d.monto monto_referencial,
  i.monto monto_inquilino,
  i.id as inquilino_id
FROM alquileres.departamento d
LEFT JOIN alquileres.inquilino i on i.departamento_id = d.id AND i.fecha_hasta IS NULL
ORDER BY d.rotulo ASC");

$formularios = array('departamento','inquilino','mensualidad');
?>
<title>Alquileres</title>
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
.tabla01 th {
    padding: 10px;
    text-align: center;
    background: #d4d4d4;
}
.tabla01 td {
    padding: 10px;
    text-align: center;
}
.tabla01 tr:hover {
  background: #d4d4d4;
}
.tabla02 {
  width: fit-content;
}
.tabla02 td {
  padding: 10px;
}
.tabla02 h4 {
    font-size: 1rem;
    font-weight: bold;
    text-align: center;
}
.tabla02 td > div {
    background: #9ac7ff;
    padding: 10px 20px;
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
<?php foreach($formularios as $f) { $form = Formity::getInstance($f); ?>
    <div data-page="<?= $f ?>"><div class="formulario"><?= $form->render(); ?></div></div>
<?php } ?>
</div>
<div>
<table border="1" class="tabla01">
<thead>
<th>DEPARTAMENTO</th>
<th>DEBERÍA PAGAR</th>
<th>INQUILINO</th>
<th>PAGA</th>
<?php foreach(getMesesActual() as $k => $v) {?>
<th><?= strtoupper($v) ?></th>
<?php } ?>
</thead>
<tbody>
<?php foreach($departamentos as $dep) { ?>
<?php if(!empty($dep['inquilino_id'])) { ?>
  <tr>
<?php } else { ?>
  <tr style="background: #fff3a7;">
<?php } ?>
    <th style="font-size: 20px;padding: 5px;"><?= $dep['departamento'] ?></th>
    <td><?= $dep['monto_referencial'] ?></td>
    <td><?= $dep['inquilino'] ?></td>
    <td><?= $dep['monto_inquilino'] ?></td>
<?php foreach(getMesesActual() as $k => $v) { ?>
<td>
<?php foreach($db->get("SELECT * FROM inquilinos_obtener_mensualidad(" . $dep['id'] . ", " . implode(',', explode('-', $k)) . ")") as $n) { ?>
<div>
  <span><?= fecha($n['fecha_pago']) ?></span>
  =>
  <span style="font-weight:bold;"><?= $n['monto'] ?></span>
</div>
<?php } ?>
</td>
<?php } ?>
  </tr>
<?php } ?>
</tbody>
</table>
</div>
<div style="margin:25px 0;">
<table class="tabla02">
<tr>
<?php foreach($db->get("SELECT * FROM alquileres.obtener_reporte_mensualidades(" . date('Y') . ")") as $n) { ?>
<td><div>
<h4><?= strtoupper(getMeses()[$n['mes'] - 1]) ?></h4>
<div>
  <b>Monto:</b> <?= $n['monto'] ?>
</div>
<div>
  <b>Cantidad:</b> <?= $n['cantidad'] ?>
</div></div>
</td>
<?php } ?>
</tr>
</table>
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
