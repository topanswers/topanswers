<?
$connection = pg_connect('dbname=postgres user=world') or die(header('HTTP/1.0 500 Internal Server Error'));
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){ header('HTTP/1.0 500 Internal Server Error'); exit(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).htmlspecialchars(pg_result_error($res))); }
  ($rows = pg_fetch_all($res)) || ($rows = []);
  return $rows;
}
function cdb($query,...$params){ return current(db($query,...$params)); }
function ccdb($query,...$params){ return current(cdb($query,...$params)); }
isset($_COOKIE['uuid']) || exit('no account cookie set');
db("select login($1)",$_COOKIE['uuid']);
isset($_FILES['image']) || exit('no file uploaded');
$hash = hash_file('sha256',$_FILES['image']['tmp_name']);
$path = '/srv/uploads/'.substr($hash,0,2).'/'.substr($hash,2,2).'/'.substr($hash,4,2);
$fname = $path.'/'.$hash;
is_dir($path) || mkdir($path,0777,true);
if(!file_exists($fname)) move_uploaded_file($_FILES['image']['tmp_name'],$fname);
exit($hash);
