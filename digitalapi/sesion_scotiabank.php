<?php
require_once(__DIR__ . '/../conf.php');
require_once(ABS_LIBRERIAS . 'curly.php');

Doris::registerDSN('financiero', 'mysql://root@localhost:3306/financiero');

while(true) {
  $db = Doris::init('financiero');
  $ses = $db->get("SELECT * FROM sesion");
  foreach($ses as $s) {
    echo "=>" . date('d/m/Y H:i:s A') . ":\n";

    $url = 'https://mi.scotiabank.com.pe/rb_5d6af957-02db-404c-a979-eede7a5e51fd?type=js&session=v_4_srv_4_sn_897E5E3E760467C4A1CB6ECF89A9D9F6_perc_100000_ol_0_mul_1_app-3A10ba0fe2f7c21f4d_1_app-3A4d5cc9b2bf52d19e_1_app-3A1f6d5c5f26efa88f_1&svrid=4&flavor=post&referer=https%3A%2F%2Fmi.scotiabank.com.pe%2Fu%2Fdashboard&visitID=JMPOBJFKOHJEDGOMKFPMBUKXIMIMUKKC-0&modifiedSince=1583299832085&app=10ba0fe2f7c21f4d&end=1';
    $rp  = Curly(CURLY_POST, $url, array(
      'Cookie' => 'JOY=' . $s['token'] . ';',
    ));
    echo $rp . "\n";
    $url = 'https://mi.scotiabank.com.pe/digital-api/gates?context=OPERATIONS';
    $url = 'https://mi.scotiabank.com.pe/digital-api/user/profile';
    $rp  = Curly(CURLY_GET, $url, array(
      'Cookie' => 'JOY=' . $s['token'] . ';',
    ));
    echo $rp . "\n";
    $url = 'https://mi.scotiabank.com.pe/digital-api/products/home?offline=true&tx=false&hidden=false';
    $rp  = Curly(CURLY_GET, $url, array(
      'Cookie' => 'JOY=' . $s['token'] . ';',
    ));
    echo $rp . "\n";
    echo "\n\n\n";
  }
  sleep(20);
}
