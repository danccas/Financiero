<?php
function encontrarle_una_categoria($db, $rotulo) {
  $rotulo = str_replace('-', '', $rotulo);
  $rotulo = str_replace('+', '', $rotulo);
  $rp = $db->get("SELECT categoria_id, descripcion, MATCH (descripcion) AGAINST ('" . $rotulo . "' IN BOOLEAN MODE) as score
    FROM movimiento
    WHERE categoria_id IS NOT NULL AND categoria_confirmacion = 1
    HAVING score > 0
    ORDER BY score DESC
    LIMIT 1;", true);
  if(!empty($rp)) {
    return $rp['categoria_id'];
  }
  return null;
}
