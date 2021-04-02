<?php
session_start(); 

require_once(__DIR__ . '/conf.php');
require_once(ABS_LIBRERIAS . 'formity2.php');
require_once(ABS_LIBRERIAS . 'chartjs.php');
require_once(ABS_LIBRERIAS . 'financiero.php');



function money($monto, $moneda = '', $titulo = '') {
  $r = '<div class="monto" title="' . $titulo . '">';
  $r .= '<span class="cantidad">' . $monto . '</span>';
  if($moneda == 'SOLES') {
    $r .= '<span class="moneda">PEN</span>';
  }
  if($moneda == 'DOLARES') {
    $r .= '<span class="moneda">USD</span>';
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
      $db = Doris::init('financiero');
      $usuario = $db->get("SELECT * FROM financiero.usuario WHERE usuario = :usuario", true, false, array(
        'usuario' => $data['usuario'],
      ));
      if($data['tipo'] == 2) {
        if(empty($usuario)) {
          unset($data['tipo']);
          $usuario_id = $db->insert('financiero.usuario', $data);
          $db->insert('financiero.sujeto', array(
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
function mostrar_navegacion() {
  require_once(__DIR__ . '/app/controladores/inicio.php');
}
Route::init();
Route::any('financiero', function() use($usuario_id) {
  require_once(__DIR__ . '/app/controladores/financiero.php');
});
Route::any('contrataciones', function() use($usuario_id) {
  require_once(__DIR__ . '/app/controladores/contrataciones.php');
});
Route::any('alquileres', function() use($usuario_id) {
  require_once(__DIR__ . '/app/controladores/alquileres.php');
});
Route::else(function() {
  mostrar_navegacion();
  exit;
});
