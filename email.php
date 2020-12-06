<?php
include 'config.php';
$connection = pg_connect("host='" . config("DB_HOST") ."' dbname=postgres user=email") or die;
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){
    error_log(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).': '.pg_result_error($res));
    $err = pg_result_error_field($res,PGSQL_DIAG_SQLSTATE);
    die;
  }
  ($rows = pg_fetch_all($res)) || ($rows = []);
  for($i = 0; $i<pg_num_fields($res); $i++){
    for($j = 0; $j<pg_num_rows($res); $j++){
      if(pg_field_type($res,$i)==='bool') $rows[$j][pg_field_name($res,$i)] = $rows[$j][pg_field_name($res,$i)]==='t';
      if(in_array(pg_field_type($res,$i),['int4','int8'],TRUE)) $rows[$j][pg_field_name($res,$i)] = intval($rows[$j][pg_field_name($res,$i)]);
    }
  }
  return $rows;
}
function cdb($query,...$params){ $c = db($query,...$params); if(!$c) error_log($query); return current($c); }
function ccdb($query,...$params){ $c = cdb($query,...$params); if(!$c) error_log($query); return current($c); }

foreach(db("select notification_id,account_email,notification_subject,notification_message from notification") as $r){
  extract($r);
  mail($account_email,$notification_subject,$notification_message,array('From' => 'noreply@topanswers.xyz'),'-f noreply@topanswers.xyz');
  db("select process($1)",$notification_id);
}
