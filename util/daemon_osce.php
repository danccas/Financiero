<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'formity2.php');
require_once(ABS_LIBRERIAS . 'curly.php');


$db = Doris::init('osce');

$identificador = $db->time();
#debug($identificador);

function to_date($txt) {
  list($date, $time) = explode(' ', $txt);
  list($day, $month, $year) = explode('/', $date);
  if(empty($year)) {
     return null;
  }
  return implode('-', [$year,$month,$day]) . ' ' . $time . ':00';
}
$etiquetas = $db->get("SELECT id, nombre FROM osce.etiqueta");
foreach ($etiquetas as $tag) {
        $url = 'http://190.216.169.243:8081/api/oportunidades/codObjeto/codDepartamento/sintesisProceso/codTipoProceso/%20%20%20%200/0/' . $tag['nombre'] . '/0';
        $data = Curly('GET', $url);

        if (empty($data)) {
                echo "no-llega-data\n";
                continue;
        }
        $data = json_decode($data, true);
        if (empty($data)) {
                echo "no-es-json\n";
                continue;
        }

        foreach ($data as $n) {
          if(empty($n['idProcedimiento'])) {
            continue;
          }
                        $url = 'http://190.216.169.243:8081/api/oportunidades/fichaProceso/idProceso/' . $n['idProcedimiento'];
                        $info  = Curly('GET', $url);
                        if (empty($info)) {
                                echo "no-llega-data\n";
                        }
                        $info = json_decode($info, true);
                        if (empty($info)) {
                                echo "no-es-json\n";
                        }
                        $fechas = $info['listaCronograma'];
                        $propuesta = end(array_filter($fechas, function($n) { return strpos($n['descripcionEtapa'],'Presentación de propuestas') !== false; }));
                        $buena_pro = end(array_filter($fechas, function($n) { return strpos($n['descripcionEtapa'], 'Buena Pro') !== false; }));

                        echo "[REGISTRANDO: " . $n['idProcedimiento'] . "]\n";
                        if(empty($n['idProcedimiento'])) {
                           continue;
                        }
#                        debug(to_date($propuesta['fechaInicio'] . ' ' . $propuesta['horaInicio']));
                $existe00 = $db->get("SELECT id FROM osce.oportunidad WHERE procedimiento_id = '" . $n['idProcedimiento'] . "'", true);
                if (empty($existe00)) {
                        $oportunidad_id = $db->insert('osce.oportunidad', [
                                'etiqueta_id' => $tag['id'],
                                'procedimiento_id' => $n['idProcedimiento'],
                                'entidad' => $n['detEntidad'],
                                'nomenclatura'   => $n['nomenclatura'],
                                'tipo_proceso' => $n['detTipoProceso'],
                                'tipo_objeto' => $n['detObjeto'],
                                'moneda' => $n['monedaProceso'],
                                'monto' => $n['valorReferencial'],
                                'documento_base' => $n['documentoBase'],
                                'rotulo' => $n['detCubso'],
                                'descripcion' => $n['sintesisProceso'] . ' ' . $n['detItem'],
                                'fecha_participacion_desde' => $db->time($n['fecInicioParticipantes']),
                                'fecha_participacion_hasta' => $db->time($n['fecFinParticipantes']),
                                'fecha_propuesta_desde' => to_date($propuesta['fechaInicio'] . ' ' . $propuesta['horaInicio']),
                                'fecha_propuesta_hasta' => to_date($propuesta['fechaFin'] . ' ' . $propuesta['horaFin']),
                                'fecha_buena_desde' => to_date($buena_pro['fechaInicio'] . ' ' . $buena_pro['horaInicio']),
                                'fecha_buena_hasta' => to_date($buena_pro['fechaFin'] . ' ' . $buena_pro['horaFin']),
                                'datos' => json_encode($info),
                        ]);
                } else {
                  $db->update('osce.oportunidad', [
                                                   'fecha_participacion_desde' => $db->time($n['fecInicioParticipantes']),
                                'fecha_participacion_hasta' => $db->time($n['fecFinParticipantes']),
                                'fecha_propuesta_desde' => to_date($propuesta['fechaInicio'] . ' ' . $propuesta['horaInicio']),
                                'fecha_propuesta_hasta' => to_date($propuesta['fechaFin'] . ' ' . $propuesta['horaFin']),
                                'fecha_buena_desde' => to_date($buena_pro['fechaInicio'] . ' ' . $buena_pro['horaInicio']),
                                'fecha_buena_hasta' => to_date($buena_pro['fechaFin'] . ' ' . $buena_pro['horaFin']),
                                'datos' => json_encode($info),
                      ], 'id = ' . $existe00['id']);
                        $oportunidad_id = $existe00['id'];
                }
                $empresas = $db->get("SELECT * FROM osce.empresa_etiqueta WHERE etiqueta_id = " . $tag['id'] . " AND tipo = 1");
                foreach ($empresas as $e) {
                        $existe = $db->get("SELECT * FROM osce.candidato WHERE oportunidad_id = " . $oportunidad_id . " AND empresa_id = " . $e['empresa_id'], true);
                        if(empty($existe)) {
                                $exclusiones = $db->get("
                                        SELECT string_agg(E.nombre, '|') as texto
                                        FROM osce.empresa_etiqueta EE
                                        JOIN osce.etiqueta E ON E.id = EE.etiqueta_id
                                        WHERE EE.empresa_id = " . $e['empresa_id'] . " AND EE.tipo = 2", true);
                                if(!empty($exclusiones) && !empty($exclusiones['texto'])) {
                                        $texto = strtolower($n['detCubso'] . ' ' . $n['detItem'] . ' ' . $n['sintesisProceso']);
                                        if (preg_match('/' . $exclusiones['texto'] . '/i', $texto, $out)) {
                                                echo "excluido: " . $exclusiones['texto'] . "\n";
                                                print_r($out);
                                                continue;
                                        }
                                }
                                $db->insert('osce.candidato', [
                                        'identificador'  => $identificador,
                                        'oportunidad_id' => $oportunidad_id,
                                        'empresa_id'     => $e['empresa_id'],
                                        'etiqueta_id'    => $tag['id'],
                                ]);
                        } else {

                        }
                }
        }
}
echo "FIN";
