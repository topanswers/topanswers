<?
function fail($code = 500,$msg = ''){
  switch($code){
    case 403: header('HTTP/1.1 403 Forbidden'); break;
    case 413: header('HTTP/1.1 413 Payload Too Large'); break;
    case 429: header('HTTP/1.1 429 Too Many Requests'); break;
    case 500: header('HTTP/1.1 500 Internal Server Error'); break;
    default: error_log('invalid http status code'); header('HTTP/1.0 500 Internal Server Error');
  }
  exit($msg);
}
$connection = pg_connect('dbname=postgres user=world') or fail(423);
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)) fail(intval(substr(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE),2)),htmlspecialchars(pg_result_error($res)));
  ($rows = pg_fetch_all($res)) || ($rows = []);
  return $rows;
}
function cdb($query,...$params){ return current(db($query,...$params)); }
function ccdb($query,...$params){ return current(cdb($query,...$params)); }
