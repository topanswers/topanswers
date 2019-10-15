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
header('X-Powered-By: ');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
$uuid = $_COOKIE['uuid'] ?? false;
if($_SERVER['REQUEST_METHOD']==='POST'){
  db("select new_chat($1,$2,$3)",$uuid,1,$_POST['msg']);
  exit;
}
?>
<!doctype html>
<html style="box-sizing: border-box;">
<head>
  <style>
    *:not(hr) { box-sizing: inherit; }
  </style>
  <script src="jquery.js"></script>
  <script src="markdown-it.js"></script>
  <script src="markdown-it-sup.js"></script>
  <script src="js.cookie.js"></script>
  <script>
    $(function(){
      var md = window.markdownit().use(window.markdownitSup);
      $('#register').click(function(){ if(confirm('This will set a cookie')) { $.ajax({ type: "GET", url: '/uuid', async: false }); location.reload(true); } });
      $('#send').click(function(){ $.ajax({ type: "POST", url: '/', data: { msg: $('textarea').val() }, async: false }); location.reload(true); });
      $('.markdown').each(function(){ $(this).html(md.render($(this).data('markdown'))); });
    });
  </script>
</head>
<body>
  <?if(!$uuid){?><input id="register" type="button" value="register"><?}?>
  <textarea></textarea>
  <input id="send" type="button" value="send">
  <?foreach(db("select chat_markdown from chat order by chat_at desc") as $r){ extract($r);?>
    <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
  <?}?>
</body>   
</html>   
