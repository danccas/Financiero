<?php
require_once(__DIR__ . '/conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');


Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

$db = Doris::init('financiero');

$fecha_actual = date('Y-m-d');

function extraer_parte($desde, $hasta = NULL, $html, $all = false) {
  $parte = explode($desde, $html);
  unset($html);
  if(count($parte) > 1){
    if(!empty($hasta)) {
      if($all) {
        $rt = array();
        foreach ($parte as $p) {
          $p = explode($hasta, $p);
          $p = trim($p[0]);
          $rt[] = $p;
        }
        $retorno = $rt;
      } else {
        $parte = explode($hasta, $parte[1]);
        $parte = trim($parte[0]);
        $retorno = $parte;
      }
    } else {
      if(!$all) {
        $retorno = trim($parte[1]);
      } else {
        $retorno = NULL;
      }
    }
  } else {
    $retorno = NULL;
  }
  return $retorno;
}


$url = 'https://cuantoestaeldolar.pe/';
$web = Curly(CURLY_GET, $url);
$web = extraer_parte('<h3 class="ico-cambista">Cambistas (Paralelo)</h3>', '<div class="clear-fix list-p-d mb-b">', $web);
$compra = extraer_parte("tb_dollar_compra\">", '</div>', $web);
$venta  = extraer_parte("tb_dollar_venta\">", '</div>', $web);

$data = array(
  '*fecha'       => $fecha_actual,
  '*desde_id'    => 1,
  '*hasta_id'    => 2,
  'dividir'     => $compra,
  'multiplicar' => 1,
);
$db->insert_update('tipo_cambio', $data);
print_r($data);

$data = array(
  '*fecha'       => $fecha_actual,
  '*desde_id'    => 2,
  '*hasta_id'    => 1,
  'dividir'     => 1,
  'multiplicar' => $venta,
);
$db->insert_update('tipo_cambio', $data);
print_r($data);
