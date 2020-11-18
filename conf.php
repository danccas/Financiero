<?php
define("ABS_RAIZ", dirname(__FILE__) . '/');
define("ABS_LIBRERIAS", ABS_RAIZ . 'app/librerias/');
define("ABS_HELPERS", ABS_RAIZ . 'app/helpers/');
define("ABS_PLANTILLAS", ABS_RAIZ . 'app/plantillas/');
define("ABS_CONTROLADORES", ABS_RAIZ . 'app/controladores/');
define("ABS_MAILS", ABS_RAIZ . 'app/plantillas/mails/');
define("ABS_CARDS", ABS_RAIZ . 'app/cards/');
define("ABS_LOGS", ABS_RAIZ . 'logs/');
define("ABS_TEMPORALES", ABS_RAIZ . 'temp/');
define("ABS_DOMINIOS", ABS_RAIZ . 'dominios/');
define("ABS_PUBLIC", realpath(ABS_RAIZ . '../public') . '/');
define("DEVEL_MODE", true);


require_once(ABS_LIBRERIAS . "misc.php");

/* Proceso Identificación de Dominio y SubDominio */
 if(!empty($_SERVER['HTTP_HOST'])) {
  $DOMINIO_ACTUAL = $_SERVER['HTTP_HOST'];
  $DOMINIO_ACTUAL = parse_domain($DOMINIO_ACTUAL);
  define('DOMINIO_COMPLETO_ACTUAL',  $DOMINIO_ACTUAL['completo']);
  define('DOMINIO_ACTUAL',  $DOMINIO_ACTUAL['dominio']);
  define('DOMINIO_ES_IP', $DOMINIO_ACTUAL['es_ip']);
  define('SUBDOMINIO_ACTUAL', $DOMINIO_ACTUAL['subdominio']);
  unset($DOMINIO_ACTUAL);
  define('ES_AJAX', !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
  define('ES_POPY', ES_AJAX && !empty($_SERVER['HTTP_X_POPY']) && strtolower($_SERVER['HTTP_X_POPY']) == '9435');
}

require_once(ABS_LIBRERIAS . 'theme.php');
require_once(ABS_LIBRERIAS . 'route.php');
require_once(ABS_LIBRERIAS . 'doris.pdo.php');
require_once(ABS_LIBRERIAS . 'pagination.php');
require_once(ABS_LIBRERIAS . "tablefy.php");

Doris::registerDSN('financiero', 'mysql://desarrollo@localhost:3306/financiero');

$DIAS  = array('domingo','lunes','martes','miercoles','jueves','viernes','sabado','domingo');
$MESES = array('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre');

$FECHA_COMPLETA = $DIAS[date('N')] . ', ' . date('d') . ' de ' . $MESES[date('m') - 1] . ' de ' . date('Y');
$HORA_COMPLETA  = date("h:i:s A");

