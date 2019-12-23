<?
function fail($code = 500,$msg = ''){
  switch($code){
    case 400: header('HTTP/1.1 400 Bad Request'); break;
    case 403: header('HTTP/1.1 403 Forbidden'); break;
    case 405: header('HTTP/1.1 405 Method Not Allowed'); break;
    case 413: header('HTTP/1.1 413 Payload Too Large'); break;
    case 429: header('HTTP/1.1 429 Too Many Requests'); break;
    case 500: header('HTTP/1.1 500 Internal Server Error'); break;
    default: error_log('invalid http status code: '.$code); header('HTTP/1.0 500 Internal Server Error');
  }
  exit($msg);
}
$user = '';
if($_SERVER['SERVER_NAME']==='topanswers.xyz') $user = 'get';
if($_SERVER['SERVER_NAME']==='post.topanswers.xyz') $user = 'post';
$connection = pg_connect('dbname=postgres user='.$user) or fail(403);
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){
    error_log(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).': '.pg_result_error($res));
    $err = pg_result_error_field($res,PGSQL_DIAG_SQLSTATE);
    if(preg_match('/^H0[0-9]{3}$/',$err)){
      fail(intval(substr($err,2)),htmlspecialchars(pg_result_error($res)));
    }else{
      fail(500,$err.': '.htmlspecialchars(pg_result_error($res)));
    }
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
