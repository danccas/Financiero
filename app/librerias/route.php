<?php
final Class Route {
  private $permissions = null;
  private $http = '';
  private $data = array();
  public  $analyze = null;
  public  $route = null;
  private $errores = array();
  public $web = array();
  public $debug = false;

  private static $instance;

  public static function g() {
    return static::getInstance();
  }
  public static function getInstance() {
    if (null === static::$instance) {
      static::$instance = new static();
    }
    return static::$instance;
  }
  function __construct() {
    if (null === static::$instance) {
      static::$instance = $this;
    }
    return $this->_init();
  }
  public function __clone() {
    trigger_error('La clonación de este objeto no está permitida', E_USER_ERROR);
  }
  private function analyze_route() {
    $regx = '((sy-:empresa/?)?(:controlador(/:metodo)?)?)';
    $r = routexl(null, $regx, array(
      'empresa'     => '[\w\-]{3,25}',
      'controlador' => '[\w\_\-]{3,15}',
      'metodo'      => '[^\/]+',
    ), true, false);
    $this->analyze = array(
      'empresa_slug'     => !empty($r['empresa']) ? $r['empresa'] : null,
      'controlador_link' => !empty($r['controlador']) ?  $r['controlador'] : null,
      'metodo_link'      => !empty($r['controlador']) ? (!empty($r['metodo']) ? $r['metodo'] : 'index') : null,
    );
    $this->web = array(
      'raiz_web'         => '/',
      'raiz_area'        => null,
      'raiz_controlador' => null,
      'raiz_metodo'      => $this->route['main'],
    );
  }
  static function setArea($a) {
    return Route::getInstance()->web['raiz_area'] = '/sy-' . $a . '/';
  }
  static function web($x) {
    return Route::getInstance()->web[$x];
  }
  static function init() {
    return Route::getInstance();
  }
  static function setUser($x) {
    return Route::getInstance()->permissions = $x;
  }
  static function setURL($x) {
    return Route::getInstance()->http = $x;
  }
  public static function library($file) {
    $file = ABS_LIBRERIAS . $file . '.php';
#    $file = strpos($file, '/') === false ? Route::getInstance()->web['dir'] . 'libs/' . $file : $file;
    if(!file_exists($file))  {
      echo $file;
#      _404('libreria-no-existe: ' . $file);
    }
    require_once($file);
  }
  public static function libraryOwn($file) {
    $file = Route::getInstance()->web['dir'] . 'libs/' . $file . '.php';
    if(!file_exists($file))  {
      echo $file;
    }
    require_once($file);
  }
  private function _init() {
    $url = isset($_GET['route_url']) ? $_GET['route_url'] : $_SERVER['REQUEST_URI'];
    $url = $this->clear($url);
    $this->route = array(
      'main'  => '/' . $url,
      'path'  => $url,
      'old'   => null,
      'query' => array(),
      'back'  => null,
      'tree'  => array(),
    );    
    $this->analyze_route();
  }
  static function back() {
    return Route::getInstance()->route['back'];
  }
  static function addQuery($q) {
    $ce = Route::getInstance();
    $ce->route['old'] = $ce->route['main'];
    $ce->route['query'][] = $q;
    $ce->route['back'] = $ce->route['old'] . (!empty($ce->route['query']) ? '?' . implode('&', $ce->route['query']) : '');
  }
  static function getData() {
    return Route::getInstance()->data;
  }
  static function data($n, $v = -1) {
    $ce = Route::getInstance();
    if($v !== -1) {
      return $ce->data[$n] = $v;
    }
    if(is_callable($ce->data[$n])) {
      return $ce->data[$n]($ce);
    }
    if(!array_key_exists($n, $ce->data)) {
      return false;
    }
    return $ce->data[$n];
  }
  private function clear($query) {
    if(empty($query)) {
      return '';
    }
    $query = parse_url($query); // Solo queremos la URL sin parámetros GET
    $query = !empty($query['path']) ? $query['path'] : '';
    $query = preg_replace("/\/+/", '/', $query); // Quitamos todos los slashes repetidos (e.g. "politica/:codigo")
    $query = trim($query, '/') . '/';
    return $query;
  }
  public function who() {
    print_r($this->route);
    exit;
  }
  public function route($route, $regexps = false, $started = false, $delete = true) {
    $query = $this->route['path'];

    if($this->debug) {
      echo "=========================================<br />\n";
      $e = $this->routexl($query, $route, $regexps, $started, $delete, $cantidad);
      $deb = debug_backtrace();
      echo "File:  " . $deb[1]['file'] . ":" . $deb[1]['line'] . "<br />\n";
      echo "Query: " . json_encode($query) . "<br />\n";
      echo "Route: " . json_encode($route) . "<br />\n";
      echo "Regex: " . json_encode($regexps) . "<br />\n";
      echo "Start: " . json_encode($started) . "<br />\n";
      echo "Delet: " . json_encode($delete) . "<br />\n";
      echo "Resul: " . (!empty($e) ? '<span style="color:green">TRUE</span>' : '<span style="color:red">FALSE</span>') . "<br />\n";
      echo "Data:  " . json_encode($e) . "<br />\n";
      echo "Canti: " . json_encode($cantidad) . "<br />\n";
      echo "========================================<br /><br />\n";
    } else {
      $e = $this->routexl($query, $route, $regexps, $started, $delete, $cantidad);
    }
#    var_dump(array($route, $query));
#    echo $route;
#    var_dump($e);
    if(!empty($e)) {
#      var_dump(array($route, $query));
      $sub = $cantidad != 0 ? $e[0] : $route;
      $this->route['old'] = '/' . implode('/', $this->route['tree']);
      $this->route['back'] = $this->route['old'] . (!empty($this->route['query']) ? '?' . implode('&', $this->route['query']) : '');
      $this->route['tree'][] = trim($sub, '/');
      $query =  $delete ? substr($query, strlen($sub)) : $query;
      $query = $this->clear($query);
      $this->route['path'] = $query;
    }
    return $e;
  }
  public function routexl($query, $route, $regexps = false, $started = false, $delete = true, &$cantidad = 0) {
    if(is_null($query)) { /* TODO: le cambie a NOT NOT */
      $query = $this->route['main'];
      $query = $this->clear($query);
    }
    $route = str_replace('/', '\/', $route);
    $expresion_regular = preg_replace_callback("/\:(?<id>[\w_]+)\;?/", function($n) use($regexps) {
      $regexp = !empty($regexps[$n['id']]) ? $regexps[$n['id']] : '[^\/]+';
      $regexp = "(?P<" . $n['id'] . ">" . $regexp . ")";
      return $regexp;
    }, $route, -1, $cantidad);
    if($cantidad != 0) {
      $expresion_regular = '/^' . $expresion_regular . ($started ? '\//' : '$/');
      $e = preg_match($expresion_regular, ($started ? $query : trim($query, '/')), $r) ? array_merge(array('route' => $query), $r) : FALSE;
    } else {
      $route = str_replace('\/', '/', $route);
      //$route = trim($route, '/') . '/';
      $e = $started ? strpos($query, $route) === 0 : $route == trim($query, '/');
    }
    return $e;
  }
  public static function __callStatic($method, $params) {
    if(count($params) < 1 || count($params) > 3) {
      throw new Exception("ROUTE method invalid params");
    }
    if($method == 'else') {
      if(is_callable($params[0])) {
        $params[0]('Route:else');
        exit;
        return true;
      }
    }
    $regex    = $params[0];
    $eq       = null;
    $callback = null;
    if(isset($params[1])) {
      if(is_callable($params[1])) {
        $callback = $params[1];
      } elseif(is_array($params[1])) {
        $eq = $params[1];
        $callback = isset($params[2]) ? $params[2] : null;
      }
    }
    if(in_array($method, ['post','get','put','delete'])) {
      if(strtolower($_SERVER['REQUEST_METHOD']) != $method) {
        return false;
      }
    } elseif(in_array($method, ['any','path', 'tool'])) {
    } else {
      throw new Exception("ROUTE method invalid:" . $method);
    }
    $r = Route::getInstance()->route($regex, $eq, $method == 'path', $method != 'tool');
    if($r && !is_null($callback) && is_callable($callback)) {
      $callback($r, Route::getInstance()->route);
      exit;
    }
    return $r;
  }
  public static function setError($x) {
    Route::getInstance()->errores[] = array(
      'type'    => 'error',
      'message' => $x,
    );
  }
  public static function setAlert($x) {
    Route::getInstance()->errores[] = array(
      'type'    => 'warning',
      'message' => $x,
    );
  }
  public static function renderErrors() {
    $ls =& Route::getInstance()->errores;
    $se = array();
    if(!empty($ls)) {
      foreach($ls as $e) {
        if(isset($se[$e['type']])) {
          $se[$e['type']] = array();
        }
        $se[$e['type']][] = $e['message'];
      }
      foreach($se as $type => $messages) {
        echo "<div class=\"alert " . $type . "\" style=\"margin:0px 25px 20px 25px;\">";
        if(count($messages) == 1) {
          echo $messages[0];
        } else {
          echo "<ol style=\"padding-left:20px;\">";
          foreach($messages as $m) {
            echo "<li>" . $m . "</li>";
          }
          echo "</ol>";
        }
        echo "</div>";
      }
    }
  }
  public static function link($path, $domain = null, $sub = null, $get = null) {
    $r = '';
    if(is_null($sub)) {
      if(is_null($domain)) {
        $r .= '';
      } else {
        if($sub != SUBDOMINIO_ACTUAL || $domain != DOMINIO_ACTUAL) {
          $r .= '//' . $domain;
        }
      }
    } else {
      if($sub != SUBDOMINIO_ACTUAL || $domain != DOMINIO_ACTUAL) {
        $r .= '//' . $sub . '.' . $domain;
      }
    }
    $r .= $path;
    if(!empty($get)) {
      $clear = strpos($get, '?') === 0;
      if($clear || empty($_GET)) {
        $r .= ($clear ? '' : '?') . $get;
      } else {
        $oldGet = $_GET;
        parse_str($get, $out);
        if(!empty($out)) {
          foreach($out as $k => $v) {
            unset($oldGet[$k]);
          }
        }
        $r .= '?' . http_build_query($oldGet) . '&' . $get;
      }
    }
    return $r;
  }
  public static function controller($file, $params = null) {
    return Theme::controller($file, $params);
  }
}
function routexl($query, $route, $regexps = false, $started = false, $delete = true) {
  return Route::getInstance()->routexl($query, $route, $regexps, $started, $delete);
}
function route($route, $regexps = false, $started = false, $delete = true) {
  return Route::getInstance()->route($route, $regexps, $started, $delete);
}
function route_path($route, $regexps = false, $delete = true) {
  return Route::getInstance()->route($route, $regexps, true, $delete);
}


function route_init() { }
function route_sub_base($b) { }
function set_route_url($query) { }

function generar_url_corta($session, $data = null) {
  $token = get_token(5);
  $link  = ABS_TEMPORALES . 'links/' . $token . '.json';
  $session['link'] = $token;
  $session['data'] = $data;
  file_put_contents($link, json_encode($session));
  return $token;
}
