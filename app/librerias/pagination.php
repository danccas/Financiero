<?php
class Pagination {
  public $id;
  public $page     = 1;
  public $npages   = 0;
  public $cantidad = 10;
  public $number_pages   = 0;
  public $number_results = 0;
  public $next;
  public $previous;
  public $has_filter = false;
  public $filter     = null;
  private $cb_filter = null;
  private $vkey = null;

  function __construct($id) {
    $this->id = $id;
    $this->vkey = Pagination::hash('_p4' . $id);
  }
  public static function hash($st) {
    return substr(md5($st), 0, 4);
  }
  public function setFilter($f) {
    if($f instanceof Formity) {
      $this->cb_filter = $f;
      $f->setMethod('GET');
      $f->button = 'FILTRAR';
      $this->has_filter = true;
      return true;
    } elseif(is_string($f)) {
      if(Formity::exists($f)) {
        $this->cb_filter = Formity::getInstance($f);
        $this->cb_filter->setMethod('GET');
        $this->cb_filter->button = 'FILTRAR';
        $this->has_filter = true;
        return true;
      }
    }
    return false;
  }
  function analyzeFilter() {
    $re = $this->cb_filter;
    if($re->byRequest()) {
      if($re->isValid($err)) {
        $this->filter = $re->getData();
      }
    }
  }
  function setRequest() {
    if(isset($_GET[$this->vkey])) {
      $this->page = !empty($_GET[$this->vkey]) ? (int) $_GET[$this->vkey] : 1;
      if(!is_int($this->page) || $this->page < 1) {
        return false;
      }
    }
    return true;
  }
  function renderFilter() {
    $rp = '<div class="FormityFilter">';
    $rp .= $this->cb_filter->render();
    $rp .= '</div>';
    return $rp;
  }
  function renderPagination() {
    return $this->render();
  }
  function setNumResults($n) {
    $this->number_results = $n;
    $this->number_pages   = (int) ceil($n /  $this->cantidad);
    if(!is_int($this->page) || $this->page > $this->number_pages) {
      return false;
    }
    $this->previous = $this->page > 1;
    $this->next     = $this->page < $this->number_pages;
    $this->offset = ($this->page - 1) * $this->cantidad;
    return true;
  }
  static function mostrar_filtro_pagination($link = null) {
    if(empty($this['cantidad']) || empty($this['npages'])) {
      return "";
    }
    $link = is_null($link) ? "?page=$1&cant=$2" : $link;
    $link = str_replace("$1", 1, $this->link);//$this->['pag'], $link);
    $html = "";
    $html .= "<div class=\"pagination\">";
    $html .= "<div class=\"inlineBlock\">";
    $html .= "<div class=\"w-all-12 text-right\">";
    $html .= "<select onchange=\"if (this.value) window.location.href=this.value\">";
    $html .= "<option value=\"\">Cantidad de Resultados</option>";
    foreach($this['npages'] as $i) {
      $url = str_replace("$2", $i, $link);
      $html .= "<option value=\"" . $url . "\">" . $i . "</option>";
    }
    $html .= "</select>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
    return $html;
  }
  public function getArray() {
    $link = $this->vkey . '=$p1';
    $link = Route::link(null, DOMINIO_ACTUAL, SUBDOMINIO_ACTUAL, $link);
    $link_anterior  = str_replace("\$p1", ($this->page - 1), $link);
    $link_siguiente = str_replace("\$p1", ($this->page + 1), $link);
    return array(
      'id'             => $this->id,
      'page'           => $this->page,
      'number_pages'   => $this->number_pages,
      'number_results' => $this->number_results,
      'previous'       => $this->previous,
      'next'           => $this->next,
      'link_previous'  => $link_anterior,
      'link_next'      => $link_siguiente,
    );
  }
  public function render() {
    if(empty($this->number_results) || $this->number_pages == 1) {
      return '';
    }
    $link = $this->vkey . '=$p1';
    $link = Route::link(null, DOMINIO_ACTUAL, SUBDOMINIO_ACTUAL, $link);
    $link_anterior  = str_replace("\$p1", ($this->page - 1), $link);
    $link_siguiente = str_replace("\$p1", ($this->page + 1), $link);
    $html = "";
    $html .= "<div class=\"pagination\" data-id=\"" . $this->id . "\">";
    $html .= "<div class=\"inlineBlock\">";
    $html .= "<div class=\"w-all-3 text-left btns\">";
    if(!empty($this->previous)) {
      $html .= "<a class=\"button bg-azul\" data-in-popy href=\"" . $link_anterior . "\">Anterior</a>";
    }
    $html .= "</div>";
    $html .= "<div class=\"w-all-6 text-center info\">";
//    $html .= "<div><b>Pagina " . $this->page . " de " . $this->number_pages . "</b></div>";
//    $html .= "<small>Se ha encontrado " . $this->number_results . " resultado" . ($this->number_results == 1 ? '' : 's') . "</small>";
    $html .= "<small>" . $this->number_results . " resultado" . ($this->number_results == 1 ? '' : 's') . "</small>";
    $html .= "</div>";
    $html .= "<div class=\"w-all-3 text-right btns\">";
    if(!empty($this->next)) {
      $html .= "<a class=\"button bg-azul\" data-in-popy href=\"" . $link_siguiente . "\">Siguiente</a>";
    }
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
    return $html;
  }
}

