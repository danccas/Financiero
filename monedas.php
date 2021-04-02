<?php
require_once(__DIR__ . '/conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');


$db = Doris::init('financiero');

$fecha_actual = date('Y-m-d');

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
$web = curly2($url, []);
$web = extraer_parte('<h3 class="ico-cambista">Cambista</h3>', '<div id="blockPPreci" class="clear-fix block-p-preci">', $web);
$web = str_replace('<small>$</small>', '', $web);
$web = str_replace('<small>S/.</small>', '', $web);
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
