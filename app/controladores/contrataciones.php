<?php
function esta_en_rango($desde, $hasta, $fecha = null) {
  $fecha = is_null($fecha) ? 'now' : $fecha;
  if(is_null($hasta)) {
    return strtotime($desde) <= strtotime($fecha);
  }
  return strtotime($desde) <= strtotime($fecha) && strtotime($hasta) >= strtotime($fecha);
}
function proxima_fecha($listado, &$rotulo, &$fecha) {
  foreach($listado as $r => $f) {
    if(strtotime($f) > time()) {
      $rotulo = $r;
      $fecha = $f;
      return true;
    }
  }
  return false;
}
function es_pasado($date, &$class = '')
{
  list($day, $month, $year) = explode('/', $date);
  $unix = strtotime(implode('-', [$year, $month, $day]));
  $rp = $unix < strtotime(date('Y-m-d'));
  $class = '';
  if ($rp) {
  } else {
    if ($unix < time() + 60 * 60 * 24) {
      $class = 'color: red';
    } elseif ($unix < time() + 60 * 60 * 24 * 3) {
      $class = 'color: #e27301';
    } else {
      $class = 'color: blue';
    }
  }
  return $rp;
}
$db = Doris::init('osce');


$empresas = $db->get("SELECT * FROM osce.empresa");

if(!empty($_GET['empresa_id'])) {
	$empresa_id = $_GET['empresa_id'];
} else {
	$empresa_id = $empresas[0]['id'];
}

$oportunidades = $db->get("
SELECT O.*, E.nombre as etiqueta, C.id, C.interes, C.participacion, C.identificador, C.observacion, EXTRACT(DAY FROM (O.fecha_buena_hasta - O.fecha_participacion_desde)) as duracion
FROM osce.candidato C
JOIN osce.oportunidad O ON O.id = C.oportunidad_id
JOIN osce.etiqueta E ON E.id = C.etiqueta_id
WHERE C.empresa_id = {$empresa_id} AND C.eliminado IS NULL AND O.fecha_buena_hasta >= NOW() - INTERVAL '10' DAY
ORDER BY C.identificador DESC, O.fecha_participacion_hasta ASC");
$cantidad_de_oportundiades = count($oportunidades);
$oportunidades = array_group_by($oportunidades, [
  'key' => 'identificador', 'only' => ['identificador']
]);


$atenciones = $db->get("
SELECT O.*, E.nombre as etiqueta, C.id, C.interes, C.participacion, C.propuesta, C.identificador, C.observacion, EXTRACT(DAY FROM (O.fecha_buena_hasta - O.fecha_participacion_desde)) as duracion
FROM osce.candidato C
JOIN osce.oportunidad O ON O.id = C.oportunidad_id
JOIN osce.etiqueta E ON E.id = C.etiqueta_id
WHERE C.empresa_id = {$empresa_id} AND C.eliminado IS NULL AND C.interes IS NOT NULL AND O.fecha_buena_hasta >= NOW() - INTERVAL '10' DAY
ORDER BY O.fecha_participacion_hasta ASC");

$etiquetas_incluidas = $db->get("
  SELECT string_agg(E.nombre, ',') texto
  FROM osce.empresa_etiqueta EE
  JOIN osce.etiqueta E ON E.id = EE.etiqueta_id
  WHERE EE.tipo = 1 AND EE.empresa_id = " . $empresa_id, true);
$etiquetas_incluidas = $etiquetas_incluidas['texto'];

$etiquetas_excluidas = $db->get("
  SELECT string_agg(E.nombre, ',') texto
  FROM osce.empresa_etiqueta EE
  JOIN osce.etiqueta E ON E.id = EE.etiqueta_id
  WHERE EE.tipo = 2 AND EE.empresa_id = " . $empresa_id, true);
$etiquetas_excluidas = $etiquetas_excluidas['texto'];
?>
<html>
<head>
<title>Convocatorias</title>
<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<meta http-equiv=”refresh” content=”100″>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.3.0/js/jquery.tooltipster.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.4/css/bulma.css" type="text/css" media="all" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.3.0/css/tooltipster.min.css" type="text/css" media="all" />

<link rel="stylesheet" type="text/css" href="http://xoxco.com/examples/jquery.tagsinput.css" />
<script type="text/javascript" src="http://xoxco.com/examples/jquery.tagsinput.js"></script>
<style>
  .oportunidades {
    margin: 0;
    padding: 0;
    list-style-type: none;
  }

  .oportunidades li {
    position: relative;
    background: #e8e8e8;
    padding: 10px 20px;
    border-radius: 5px;
    margin-bottom: 10px;
  }

  .oportunidades li:hover {
    background: #d2d2d2;
  }

  .item_id {
    font-size: 15px;
  }

  .item_calendario {
    position: absolute;
    top: 30px;
    right: 20px;
  }

  .item_calendario * {
    font-size: 11.5px;
    text-align: right;
  }

  .tachado {
    text-decoration: line-through;
    color: #b3b3b3;
  }

  .item_detalle {
    width: calc(100% - 500px);
  }

  .item_archivos li {
    display: inline-block;
    background: #fbcaca;
  }

  .item_eliminar {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: red;
    color: #fff;
    padding: 7px 10px;
    border-radius: 5px;
  }
  .botones {
    position: absolute;
    left: 350px;
    top: 25px;
}
  .item_postular {
    background: green;
    color: #fff;
    padding: 7px 10px;
    border-radius: 5px;
  }

  .item_interes {
    background: blue;
    color: #fff;
    padding: 7px 10px;
    border-radius: 5px;
  }

  li.interes {
    background: #c1dcc1 !important;
  }

  li.interes .item_interes {
    /*  display: none; */
  }
.listado_atenciones {
  position: relative;
  max-height: 800px;
  overflow: auto;
}
.listado_atenciones li {
    background: #00b9ff;
    padding: 5px 10px;
    color: #fff;
    border-radius: 5px;
    margin-bottom: 5px;
}
.item_observacion {
  max-width: 90%;
}
.observacion {
  min-height: 10px;
  background: #ccc8c8;
  border-radius: 3px;
  color: #000;
  padding: 5px 10px;
}
</style>
</head>
<body>
<div>
  <?php mostrar_navegacion(); ?>

  <?php foreach($empresas as $e) { ?>
  <a class="button" href="/contrataciones/?empresa_id=<?= $e['id'] ?>"><?= $e['razon_social'] ?></a>
  <?php } ?>
  <br />
  <div>
   <b>Etiquetas:</b> <input type="text" class="tags" id="etiquetas_incluidas" data-empresa="<?= $empresa_id ?>" data-tipo="1" value="<?= $etiquetas_incluidas ?>" />
   <b>Excluidas: </b> <input type="text" class="tags" id="etiquetas_excluidas" data-empresa="<?= $empresa_id ?>" data-tipo="2" value="<?= $etiquetas_excluidas ?>" />
  </div>
<script>
$('input.tags').tagsInput({interactive:true,width:'auto',onAddTag:onAddTag,onRemoveTag:onRemoveTag});
function onAddTag(tag) {
  var empresa_id = $(this).attr('data-empresa');
  var tipo = $(this).attr('data-tipo');
  if(tag == '') return;
  $.ajax({
        url: '/contrataciones/accion.php',
        type: 'POST',
        data: {
	accion: 'tag-add',
        tag: tag,
	empresa_id: empresa_id,
	tipo: tipo,
  }
});
}
function onRemoveTag(tag) {
  var empresa_id = $(this).attr('data-empresa');
  var tipo = $(this).attr('data-tipo');
  if(tag == '') return;
  $.ajax({
        url: '/contrataciones/accion.php',
        type: 'POST',
        data: {
  accion: 'tag-del',
        tag: tag,
  empresa_id: empresa_id,
  tipo: tipo,
  }
});
}
</script>
  <p style="padding:5px 10px;"><?= $cantidad_de_oportundiades ?> en <?= count($oportunidades) ?> resultados</p>
</div>
<div class="columns">
<div class="column is-8">
<?php foreach ($oportunidades as $fecha => $relacion) { ?>
  <div style="background: #ff8d00;color: #fff;padding: 5px 20px;margin-top: 10px;"><?= tiempo_transcurrido($fecha) ?></div>
  <ul class="oportunidades">
    <?php foreach ($relacion as $op) {
      $datos = json_decode($op['datos'], true); ?>
      <?php if (!empty($op['interes'])) { ?>
        <li data-id="<?= $op['id'] ?>" class="interes" id="pp<?= $op['id'] ?>">
        <?php } else { ?>
        <li data-id="<?= $op['id'] ?>">
        <?php } ?>
        <div class="item_id">#<?= $op['procedimiento_id'] ?></div>
        <div class="botones">
        <a data-accion="interes" class="item_interes">Interes: <?= $op['etiqueta'] ?></a>
        <a data-accion="participacion" class="item_postular">Participar!</a>
        <a data-accion="propuesta" class="item_postular">Enviar Propuesta!</a>
        </div>
        <div class="item_nomenclatura"><?= $op['nomenclatura'] ?></div>
        <div class="item_detalle">
          <?= $op['tipo_proceso'] ?> | <?= $op['entidad'] ?><br />
          <b><?= $op['tipo_objeto'] ?> | <?= $op['rotulo'] ?></b>
          <p><?= $op['descripcion'] ?></p>
          <p><?= $op['moneda'] ?> <?= $op['monto'] ?></p>
        </div>
        <div class="item_archivos">
          <ul>
            <?php foreach ($datos['listaDocumentos'] as $d) { ?>
              <li><a target="_blank" href="http://prodapp.seace.gob.pe/SeaceWeb-PRO/SdescargarArchivoAlfresco?fileCode=<?= $d['codigoAlfresco'] ?>"><?= $d['tipoDocumento'] ?></a></li>
            <?php } ?>
          </ul>
        </div>
        <div class="item_observacion">
        <div class="observacion" data-observacion="<?= $op['id'] ?>" contenteditable><?= $op['observacion'] ?></div>
        </div>
        <div class="item_calendario">
          <div style="text-align:right"><b>Duración:</b> <?= $op['duracion'] ?> días</div>
          <table>
            <tr>
              <th>Etapa</th>
              <th style="padding-right:10px;">Inicio</th>
              <th>Fin</th>
            </tr>
            <?php foreach ($datos['listaCronograma'] as $cro) { ?>
              <tr>
                <th><?= $cro['descripcionEtapa'] ?></th>
                <?php if (!es_pasado($cro['fechaInicio'], $class)) { ?>
                  <td style="padding-right: 10px;<?= $class ?>;"><?= $cro['fechaInicio'] ?> <?= $cro['horaInicio'] ?></td>
                <?php } else { ?>
                  <td class="tachado" style="padding-right: 10px;<?= $class ?>;"><?= $cro['fechaInicio'] ?> <?= $cro['horaInicio'] ?></td>
                <?php } ?>
                <?php if (!es_pasado($cro['fechaFin'], $class)) { ?>
                  <td style="<?= $class ?>;"><?= $cro['fechaFin'] ?> <?= $cro['horaFin'] ?></td>
                <?php } else { ?>
                  <td class="tachado" style="<?= $class ?>;"><?= $cro['fechaFin'] ?> <?= $cro['horaFin'] ?></td>
                <?php } ?>
              </tr>
            <?php } ?>
          </table>
        </div>
        <a data-accion="eliminar" class="item_eliminar">ELIMINAR</a>
        </li>
      <?php } ?>
  </ul>
  <?php } ?>
</div>
<div class="column is-4">
<div class="listado_atenciones">
<ul>
<?php foreach($atenciones as $a) { ?>
  <li data-id="<?= $a['id'] ?>">
  <div><a href="#pp<?= $a['id'] ?>" style="color:#fff;"><?= $a['nomenclatura'] ?></a> (Duración: <?= $a['duracion'] ?> días)</div>
<?php if(esta_en_rango($a['fecha_participacion_desde'], $a['fecha_participacion_hasta'])) { ?>
    <div><b title="desde <?= $a['fecha_participacion_desde'] ?> - <?= $a['fecha_participacion_hasta'] ?>">PARTICIPACION: </b>
      <?php if(!empty($a['participacion'])) { ?>
      REGISTRADO <?= tiempo_transcurrido($a['participacion']) ?>
      <?php } else { ?>
      <span style="color:#9ae3ff">termina en <?= tiempo_transcurrido($a['fecha_participacion_hasta']) ?></span>
      <?php } ?>
    </div>
<?php } elseif(esta_en_rango($a['fecha_propuesta_desde'], $a['fecha_propuesta_hasta'])) { ?>
    <div><b title="desde <?= $a['fecha_propuesta_desde'] ?> - <?= $a['fecha_propuesta_hasta'] ?>">ENVIAR PROPUESTA: </b>
      <?php if(!empty($a['propuesta'])) { ?>
      REGISTRADO <?= tiempo_transcurrido($a['propuesta']) ?>
      <?php } else { ?>
      <span style="color:#9ae3ff">termina en <?= tiempo_transcurrido($a['fecha_propuesta_hasta']) ?></span>
      <?php } ?>
    </div>
<?php } elseif(esta_en_rango($a['fecha_buena_desde'], $a['fecha_buena_hasta'])) { ?>
    <div><b>CONCURSO FINALIZADO </b></div>
<?php } else { ?>
<?php if(proxima_fecha([
  'PARTICIPACIÓN' => $a['fecha_participacion_desde'],
  'PROPUESTA'     => $a['fecha_propuesta_desde'],
  'BUENA PRO'     => $a['fecha_buena_desde'],
], $rotulo, $fecha)) { ?>
<div>Esperando a <b><?= $rotulo ?></b>: <b title="<?= $fecha ?>"><?= tiempo_transcurrido($fecha) ?></b></div>
<?= $a['fecha_participacion_desde'] ?> - <?= $a['fecha_participacion_hasta'] ?> <?= var_dump(esta_en_rango($a['fecha_participacion_desde'], $a['fecha_participacion_hasta'])) ?>
<?php } else { ?>
-- REVISAR --
<?php } } ?>
  <div class="observacion" data-observacion="<?= $a['id'] ?>" contenteditable><?= $a['observacion'] ?></div>
  </li>
<?php } ?>
</ul>
</div>
</div>
<script>
  $(document).ready(function() {
    $('[data-accion]').on('click', function() {
      var accion = $(this).attr('data-accion');
      var box = $(this).closest('[data-id]');
      var id = box.attr('data-id');
      $.ajax({
        url: '/contrataciones/accion.php',
        type: 'POST',
        data: {
          accion: accion,
          id: id
        },
        success: function(data) {

        }
      });
      if (accion == 'eliminar') {
        box.slideUp();
      } else if (accion == 'interes') {
        box.addClass('interes');
      } else {}

    });
    $('.observacion').blur(function(){
      var myTxt = $(this).html();
      var id    = $(this).closest('li').attr('data-id');
      $("[data-observacion=" + id + "]").html(myTxt);
      $.ajax({
        url: '/contrataciones/accion.php',
        type: 'POST',
        data: {
          accion: 'observacion',
          id: id,
          text: myTxt,
        },
        success: function(data) {

        }
      });
    });
  });
$(window).scroll(function (event) {
    var scroll = $(window).scrollTop();
    // Do something
    var box = $('.listado_atenciones');
    if(scroll > 300){
      box.css({ top: scroll - 310 } );
    } else {
      box.css({ top: 0} );
    }
});
</script>
</body>
</html>
