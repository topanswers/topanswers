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
  db("select new_chat($1,$2,$3)",$uuid,$_POST['room'],$_POST['msg']);
  exit;
}
if(!isset($_GET['community'])) die('Community not set');
$community = $_GET['community'];
$room = $_GET['room'] ?? ccdb("select community_room_id from community where community_name=$1",$community);
?>
<!doctype html>
<html style="box-sizing: border-box;">
<head>
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    .question { margin-bottom: 0.5em; padding: 0.5em; border: 1px solid black; }
    .message { margin-bottom: 0.5em; padding: 0.5em; border: 1px solid black; background-color: lightgrey; }
    .markdown > p:first-child { margin-top: 0; }
    .markdown > p:last-child { margin-bottom: 0; }
  </style>
  <script src="jquery.js"></script>
  <script src="markdown-it.js"></script>
  <script src="markdown-it-sup.js"></script>
  <script src="js.cookie.js"></script>
  <script>
    $(function(){
      var md = window.markdownit().use(window.markdownitSup);
      $('#register').click(function(){ if(confirm('This will set a cookie')) { $.ajax({ type: "GET", url: '/uuid', async: false }); location.reload(true); } });
      $('.markdown').each(function(){ $(this).html(md.render($(this).data('markdown'))); });
      $('#community').change(function(){ window.location = '/'+$(this).val().toLowerCase(); });
      $('#room').change(function(){ window.location = '/<?=$community?>?room='+$(this).val(); });
      $('#chatbox').on('input', function(){ $(this).css('height', '0'); $(this).css('height', this.scrollHeight + 'px'); });
      $('#chatbox').keydown(function(e){
        if((e.keyCode || e.which) == 13) {
          if(!e.shiftKey) {
            $.ajax({ type: "POST", url: '/community', data: { room: <?=$room?>, msg: $('textarea').val() }, async: false }); location.reload(true);
            return false;
          }
        }
      });
    });
  </script>
  <title><?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex; background-color: red;">
  <main style="background-color: lightgreen; display: flex; flex-direction: column; flex: 0 0 70%;">
    <header style="background-color: brown; padding: 0.5em; border-bottom: 2px solid black;">
      <span>TopAnswers: </span>
      <select id="community">
        <?foreach(db("select community_name from community order by community_name desc") as $r){ extract($r);?>
          <option<?=($community===$community_name)?' selected':''?>><?=ucfirst($community_name)?></option>
        <?}?>
      </select>
      <?if(!$uuid){?><input id="register" type="button" value="register"><?}?>
    </header>
    <div id="qa" style="background-color: goldenrod; overflow-y: scroll; padding: 0.5em;">
      <?for($x = 0; $x<100; $x++){?>
        <div class="question"><?=$x?>) Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tristique placerat felis vitae cursus.</div>
      <?}?>
    </div>
  </main>
  <div id="chat" style="background-color: darkslateblue; flex: 0 0 30%; display: flex; flex-direction: column-reverse; justify-content: flex-start; border-left: 2px solid black;">
    <header style="background-color: seagreen; padding: 0.5em; border-top: 2px solid black;">
      <select id="room">
        <?foreach(db("select room_id, coalesce(room_name,initcap(community_name)||' Chat') room_name from room where community_name=$1 order by room_name desc",$community) as $r){ extract($r);?>
          <option<?=($room_id===$room)?' selected':''?> value="<?=$room_id?>"><?=$room_name?></option>
        <?}?>
      </select>
    </header>
    <div>
      <textarea id="chatbox" style="width: 100%; resize: none; outline: none; border: none; padding: 0.3em;" rows="1" placeholder="type message here" autofocus></textarea>
    </div>
    <div style="display: flex; flex-direction: column-reverse; overflow-y: scroll; padding: 0.5em;">
      <?foreach(db("select chat_markdown from chat where room_id=$1 order by chat_at desc",$room) as $r){ extract($r);?>
        <div class="message markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
      <?}?>
    </div>
  </div>
</body>   
</html>   
