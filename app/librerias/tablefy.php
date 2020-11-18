<?php
class Tablefy {
  const TABLEFY_NORMAL = 0;
  const TABLEFY_MINI   = 1;
  const TABLEFY_LIGHT   = 2;

  private $style = 0;

  private $passRequest = '_GET';
  private $passVarNam  = '_tyn';
  private $passVarVal  = '_tyv';
  private $passVarAjax = '_ajx';

  private $index   = null;
  private $headers = array();

  private $data    = array();
  private $row_cb  = null;

  private $options = array();

  public $pagination = array();
  private $fn_process = null;

  public $title = null;
  public $hash = null;
  
  public $ajax = false;
  
  private $is_process = false;
  private static $instances = array();

  public static function hash($st) {
    return substr(md5($st), 0, 4);
  }
  public static function getInstance($cdr = null) {
    if(!is_null($cdr) && !array_key_exists($cdr, static::$instances)) {
      return static::$instances[$cdr] = new static($cdr, count(static::$instances) + 1);
    }
    if(is_null($cdr)) {
      if(empty(static::$instances)) {
        trigger_error('Sin instancias');
      } else {
        return end(static::$instances);
      }
    }
    return static::$instances[$cdr];
  }

  public function __construct($t = null, $index) {
    //$this->title = $t;
    $this->index = $index;
    $this->passVarNam  = $this->passVarNam . $this->index;
    $this->passVarVal  = $this->passVarVal . $this->index;
    $this->passVarAjax = $this->passVarAjax . $this->index;
    $this->pagination = new Pagination($t);
  }
  public function setTitle($t) {
    $this->title = $t;
  }
  public function setStyle($t) {
    $this->style = (int) $t;
  }
  public static function link($ruta) {
    return array(
      'type' => 'link',
      'data' => $ruta,
    );
  }
  private static function match_link($row, $ruta = null, $get = null) {
    if(!is_null($get)) {
      $get = preg_replace_callback("/\:(?<id>[\w\_]+)\;?/", function($n) use($row) {
        return $row[$n['id']];
      }, $get, -1, $cantidad);
    }
    if(!is_null($ruta)) {
      $ruta = preg_replace_callback("/\:(?<id>[\w\_]+)\;?/", function($n) use($row) {
        return $row[$n['id']];
      }, $ruta, -1, $cantidad);
    }
    $rp = Route::link($ruta, DOMINIO_ACTUAL, SUBDOMINIO_ACTUAL, $get);
    return $rp;
  }
  public function setFilter($f) {
    if($f instanceof Formity) {
      return $this->pagination->setFilter($f);
    } elseif(is_string($f)) {
      if(Formity::exists($f)) {
        return $this->pagination->setFilter(Formity::getInstance($f));
      }
    }
    return false;
  }
  public function request($x) {
    return isset($_GET[$x]) ? $_GET[$x] : null;
  }
  public function setHeader($n) {
    $this->headers = $n;
  }
  public function setRow($cb) {
    if(is_callable($cb)) {
      $this->row_cb = $cb;
    }
  }
  public function setData($n, $map = null) {
    
    if($this->pagination->has_filter) {
      $this->pagination->analyzeFilter();
    }
    if(!(!is_null($this->row_cb) && !empty($_GET[$this->passVarNam]))) {
      if(is_callable($n)) {
        $this->data = $n($this->pagination);
      } else {
        $this->data = $n;
      }
    }
    if(!is_null($map)) {
      $this->fn_process = $map;
    }
    $this->detectAjax();
  }
  public function setPagination($pag) {
    $this->pagination = $pag;
  }
  public function setMap($cb) {
    $this->fn_process = $cb;
  }
  public function setAjax($t) {
    return $this->ajax = !!$t;
  }
  private function detectAjax() {
    if(!$this->ajax) {
      return;
    }
    if(!is_null($this->request($this->passVarAjax))) {
      echo json_encode($this->renderJson());
      exit;
    }
  }
  public function setOption($key, $call) {
    $div = ':';
    if(strpos($key, $div) === false) {
      $key .= $div;
    }
    list($key, $name) = explode($div, $key);
    $is_popy = substr($key, -1) == '&';
    $key = trim($key,'&');
    $name = !empty($name) ? $name : ucfirst(str_replace('_',' ', strtolower($key)));
    $key = Tablefy::hash($key);
    $call = is_array($call) ? $call : (is_callable($call) ? array('type' => 'callback', 'data' => $call) : array('type' => 'link', 'data' => $call));
    $this->options[$key] = array(
      'name'  => $name,
      'event' => $call,
      'popy'  => $is_popy,
      'link'  => $this->passVarNam . '=' . $key . '&' . $this->passVarVal . '=:xid');
    if(!empty($_GET[$this->passVarNam]) && $key == $_GET[$this->passVarNam]) {
      if(!is_null($this->row_cb)) {
        $rp = ($this->row_cb)($_GET[$this->passVarVal]);
        if(empty($rp)) {
          _404();
        } else {
          $id = $this->passVarVal;
          $error = null;
          if($this->options[$_GET[$this->passVarNam]]['event']['type'] == 'callback') {
            $route = Route::getInstance()->route;
            if(class_exists('Popy')) {
              Popy::g()->currentRoute = $route;
            }
            Route::addQuery($this->passVarNam . '=' . $key . '&' . $this->passVarVal . '=' . $_GET[$this->passVarVal]);
            Theme::data('submenu', array());
            $e['call'] = $this->options[$_GET[$this->passVarNam]]['event']['data']($rp, $route);
            exit; //TODO
          }
        }
      } elseif(is_numeric($_GET[$this->passVarVal]) && isset($this->data[$_GET[$this->passVarVal]])) {
        $id = $this->passVarVal;
        $error = null;
        if($this->options[$_GET[$this->passVarNam]]['event']['type'] == 'callback') {
          $route = Route::getInstance()->route;
          if(class_exists('Popy')) {
            Popy::g()->currentRoute = $route;
          }
          Route::addQuery($this->passVarNam . '=' . $key . '&' . $this->passVarVal . '=' . $_GET[$this->passVarVal]);
          Theme::data('submenu', array());
          $e['call'] = $this->options[$_GET[$this->passVarNam]]['event']['data']($this->data[$_GET[$this->passVarVal]], $route);
          exit; //TODO
        }
        if($e['call'] === false && is_null($error)) {
          $error = 'No se ha podido realizar la Acci&oacute;n';
        }
        $e['error']  = $error;
        $this->ls[$id] = $e;
        return true;
      }
    }
  }
  public function onOption(&$ls = null) {
    return;
    if(!empty($this->ls)) {
      $ls = $this->ls;
      return true;
    }
    return false;
    if(!is_null($this->request($this->passVarNam)) && !is_null($this->request($this->passVarVal))) {
      if(isset($this->options[$this->request($this->passVarNam)])) {
        if(is_numeric($this->request($this->passVarVal)) && isset($this->data[$this->request($this->passVarVal)])) {
          $error = null;
          $e['option'] = $this->request($this->passVarNam);
          if($this->options[$this->request($this->passVarNam)]['event']['type'] == 'callback') {
            $e['call']   = $this->options[$this->request($this->passVarNam)]['event']['data']($this->data[$this->request($this->passVarVal)], $error);;
          }
          if($e['call'] === false && is_null($error)) {
            $error = 'No se ha podido realizar la Acci&oacute;n';
          }
          $e['error']  = $error; 
          $objeto = $this->data[$this->request($this->passVarVal)];
          return true;
        }
      }
    }
    return false;
  }
  private function process_data() {
    if($this->is_process) {
      return;
    }
    $this->is_process = true;
    if(!empty($this->data) && is_array($this->data)) {
      $this->data = array_map(function($n) {
        $n['_id'] = isset($n['_id']) ? $n['_id'] : uniqid();
        return $n;
      }, $this->data);
      if(!is_null($this->fn_process)) {
        $cb = $this->fn_process;
        $this->data = array_map(function($n) use($cb) {
          $m = $cb($n);
          $m['_options'] = isset($m['_options']) ? array_map(function($n) { return Tablefy::hash($n); }, $m['_options']) : null;
          $m['_id']      = isset($m['_id'])      ? $m['_id']      : $n['id'];
          $m['id'] = $n['id'];//TODO
          return $m;
        }, $this->data);
      }
      $this->hash = md5(json_encode($this->data));
    } else {
      $this->hash = 'clean';
    }
  }
  public function renderJson() {
    $this->process_data();
    $tr_id = 0;
    $ce =& $this;
    $rp = array_map(function($n) use(&$tr_id, &$ce) {
      $tds = array();
      $ii = -1;
      foreach ($n as $k => $v) {
        $ii++;
        if($k !== 0 && in_array($k, array('_options','_id'))) {
          continue;
        }
        $tds[] = array(
          'label' => $ce->headers[$ii],
          'value' => $v,
        );
        if(!empty($ce->options)) {
          $ce->options = array_map(function($n) { return Tablefy::hash($n); }, $ce->options);
          $ops = array();
          foreach($ce->options as $key => $_n) {
            if(is_null($n['_options']) || in_array($key, $n['_options'])) {
              if($_n['event']['type'] == 'link') {
                $ops[] = array(
                  'link' => Tablefy::match_link($n, $_n['event']['data']),
                  'popy' => !empty($_n['popy']),
                  'key'  => $key,
                  'name' => $_n['name'],
                );
              } else {
                $ops[] = array(
                  'link' => Tablefy::match_link(array('xid' => $tr_id), null, $_n['link']),
                  'popy' => !empty($_n['popy']),
                  'key'  => $key,
                  'name' => $_n['name'],
                );
              }
            }
          }
          $tds[] = array(
            'label' => 'Opciones',
            'value' => $ops,
          );
        }
      }
      return $tds;
    }, $this->data);
    return json_encode(array(
      'id'         => $this->index,
      'headers'    => $this->headers,
      'data'       => $rp,
      'pagination' => $this->pagination->getArray(),
    ));
  }
  public function renderOnlyTable($style = null) {
    if($this->ajax) {
      echo $this->renderOnlyTableAjax($style);
      return;
    }
    $this->process_data();
    if(is_null($style)) {
      $style = $this->style;
    }
    if($this->pagination->has_filter) {
      echo $this->pagination->renderFilter();
    }
    echo "<table data-id=\"" . $this->index . "\" class=\"table is-fullwidth is-striped style00" . $style . "\" data-hash=\"" . $this->hash . "\">\n";
      echo "<thead>\n";
        echo "<tr>\n";
        #echo "<th class=\"text-center\">#</th>\n";
          foreach ($this->headers as $h) {
            echo "<th>" . $h . "</th>\n";
          }
          if(!empty($this->options)) {
            echo "<th>Op.</th>\n";
          }
        echo "</tr>\n";
      echo "</thead>\n";
/*      echo "<tfoot>";
        echo "<td colspan="6">";
          echo "<div class=\"pagination pull-right\">";
            echo "<ul>";
              echo "<li ng-class=\"{disabled: currentPage == 0}\">";
                echo "<a href ng-click=\"prevPage()\">« Prev</a>";
              echo "</li>";
              echo "<li ng-repeat=\"n in range(pagedItems.length)\" ng-class=\"{active: n == currentPage}\" ng-click=\"setPage:()">";
                echo "<a href ng-bind="n + 1">1</a>";
              echo "</li>";
              echo "<li ng-class="{disabled: currentPage == pagedItems.length - 1}">";
                echo "<a href ng-click="nextPage()">Next »</a>";
              echo "</li>";
            echo "</ul>";
          echo "</div>";
        echo "</td>";
      echo "</tfoot>";*/
      echo "<body>\n";
        if(empty($this->data)) {
          echo "<tr><td colspan=\"" . count($this->headers) . "\">No se ha encontrado coincidencias.</td></tr>\n";
        } else {
        foreach($this->data as $tr_id => $n) {
          if(!is_null($this->row_cb)) {
            $orden_id = $n['id'];
          } else {
            $orden_id = $tr_id;
          }
          $tr_attrs = ' ';
          if(!empty($n['tablefy_tr']) && is_array($n['tablefy_tr'])) {
            foreach($n['tablefy_tr'] as $trck => $trcv) {
              $tr_attrs .= $trck .'="' . $trcv . '" ';
            }
          }
          unset($n['tablefy_tr']);
          echo "<tr data-id=\"" . $orden_id . "\"" . $tr_attrs . ">\n";
            #echo "<th data-tr=\"" . $tr_id . "\" class=\"text-center\">" . ($tr_id + 1) . "</th>\n";
            $ii = -1;
            foreach ($n as $k => $v) {
              $ii++;
              if($k !== 0 && in_array($k, array('_options','_id'))) {
                continue;
              }
              if(!isset($this->headers[$ii])) {
                break;
              }
              if(!is_array($v)) {
                echo "<td data-label=\"" . $this->headers[$ii] . "\" data-tr=\"" . $orden_id . "\" data-id=\"" . $n['_id'] . "\">" . $v . "</td>\n";
              } else {
                $params = array();
                if(!empty($v[1])) {
                  foreach($v[1] as $a => $b) {
                    if(in_array($a, ['color','text-align'])) {
                      $params[] = 'style="' . $a . ':' . $b . ';"';
                    } else {
                      $params[] = $a . '="' . $b . '"';
                    }
                  }
                }
                echo "<td data-label=\"" . $this->headers[$ii] . "\" data-tr=\"" . $orden_id . "\" data-id=\"" . $n['_id'] . "\" " . implode(' ', $params) . ">" . $v[0] . "</td>\n";
              }
            }
            if(!empty($this->options)) {
              echo "<td data-label=\"Opciones\">\n";
                  echo "<div class=\"opciones\">\n";
                    foreach($this->options as $key => $_n) {
                      if(is_null($n['_options']) || in_array($key, $n['_options'])) {
                        if($_n['event']['type'] == 'link') {
                          echo "<a href=\"" . Tablefy::match_link($n, $_n['event']['data']) . "\" data-option=\"" . $key . "\">" . $_n['name'] . "</a>\n";
                        } else {
                          echo "<a " . (!empty($_n['popy']) ? 'data-popy' : '') . " href=\"" . Tablefy::match_link(array('xid' => $orden_id), null, $_n['link']) . "\" data-option=\"" . $key . "\">" . $_n['name'] . "</a>\n";
                        }
                      }
                    }
                echo "</div>\n";
              echo "</td>\n";
            }
          echo "</tr>\n";
        }}
      echo "</body>\n";
    echo "</table>\n";
    if(!is_null($this->pagination)) {
      echo $this->pagination->render();
      //echo mostrar_paginacion($this->pagination);
    }
  }
  public function renderOnlyTableAjax($style = null) {
    echo "<script>Tablefy(" . $this->renderJson() . ");</script>\n";
  }
  public function renderInPage($style = null) {
    require_once(ABS_PLANTILLAS . 'cabecera_cpanel.php');
    echo '<div class="card">';
    echo '<div class="card-table">';
    if(!is_null($this->title)) {
      echo '<h1 class="titular">' . $this->title . '</h1>';
    }
    //echo mostrar_submenu($SUBMENU);
    echo Route::renderErrors();
    echo '<div>';
    $this->renderOnlyTable($style);
    echo '</div>';
    echo '</div>';
    echo '</div>';
    require_once(ABS_PLANTILLAS . 'pie_cpanel.php');
    exit;
  }
  public function renderTablefy($style = null) {
    if(ES_POPY) {
      if(!empty(Theme::data('submenu'))) {
        echo '<div class="struct_site_body_menu">';
        echo '<ul class="inlineBlock nowrap no-full right">';
        mostrar_menu(Theme::data('submenu'), null, true);
        echo '</ul>';
        echo '</div>';
      }
    }
    //echo '<div class="cuerpo_principal">';
    if(!is_null($this->title)) {
      echo '<h1 class="titular">' . $this->title . '</h1>';
    }
    //echo mostrar_submenu($SUBMENU);
    echo Route::renderErrors();
    echo '<div>';// style="white-space: nowrap;overflow: overlay;padding-bottom: 18px;">';
    $this->renderOnlyTable($style);
    echo '</div>';
    //echo '</div>';
  }
  public function render($style = null) {
    return $this->renderTablefy($style);
  }
}
