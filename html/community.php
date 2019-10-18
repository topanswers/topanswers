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
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/highlightjs/default.css">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #4d7ebb; padding: 0.5em; }
    .question { margin-bottom: 0.5em; padding: 0.5em; border: 1px solid black; }
    .message { flex: 0 0 auto; max-width: calc(100% - 1.7em); max-height: 8em; overflow: auto; padding: 0.2em; border: 1px solid darkgrey; border-radius: 0.3em; background-color: white; }
    .message-wrapper { width: 100%; display: flex; margin-top: 0.2em; align-items: center; }
    .message-wrapper>img { flex: 0 0 1.2em; height: 1.2em; margin-right: 0.2em; xborder: 1px solid black; xborder-radius: 2px; }
    .markdown>:first-child { margin-top: 0; }
    .markdown>:last-child { margin-bottom: 0; }
    .markdown ul { padding-left: 1em; }
    .markdown img { max-height: 7em; }
  </style>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/js.cookie.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }}).use(window.markdownitSup).use(window.markdownitSub);
      var chatChangeId = <?=ccdb("select coalesce(max(chat_change_id),0) from chat where room_id=$1",$room)?>;
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
      setInterval(function(){ 
	$.get('/change?room=<?=$room?>',function(r){ 
          console.log(chatChangeId + ' - ' + JSON.parse(r).chat_change_id);
          if(chatChangeId!==JSON.parse(r).chat_change_id){
            $.get(window.location.href,function(r){ $('#chat > div').html($(r).find('#chat > div').html()); },'html');
          }
        });
      }, 10000);
    });
  </script>
  <title><?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex; background-color: red;">
  <main style="background-color: lightgreen; display: flex; flex-direction: column; flex: 0 0 60%;">
    <header style="border-bottom: 2px solid black;">
      <span>TopAnswers: </span>
      <select id="community">
        <?foreach(db("select community_name from community order by community_name desc") as $r){ extract($r);?>
          <option<?=($community===$community_name)?' selected':''?>><?=ucfirst($community_name)?></option>
        <?}?>
      </select>
      <?if(!$uuid){?><input id="register" type="button" value="register"><?}?>
    </header>
    <div id="qa" style="background-color: white; overflow: auto; padding: 0.5em;">
      <?for($x = 1; $x<100; $x++){?>
        <div class="question">Question <?=$x?></div>
      <?}?>
    </div>
  </main>
  <div id="chat" style="background-color: #d4dfec; flex: 0 0 40%; display: flex; flex-direction: column-reverse; justify-content: flex-start; min-width: 0; xoverflow-x: auto; border-left: 2px solid black;">
    <header style="flex: 0 0 auto; border-top: 2px solid black;">
      <select id="room">
        <?foreach(db("select room_id, coalesce(room_name,initcap(community_name)||' Chat') room_name from room where community_name=$1 order by room_name desc",$community) as $r){ extract($r);?>
          <option<?=($room_id===$room)?' selected':''?> value="<?=$room_id?>"><?=$room_name?></option>
        <?}?>
      </select>
    </header>
    <textarea id="chatbox" style="flex: 0 0 auto; width: 100%; resize: none; outline: none; border: none; padding: 0.3em;" rows="1" placeholder="type message here" autofocus></textarea>
    <div style="display: flex; flex: 1 1 auto; min-height: 0;">
      <div style="flex: 1 1 auto; display: flex; align-items: flex-start; flex-direction: column-reverse; padding: 0.5em; overflow: scroll;">
        <?foreach(db("select account_id, chat_markdown from chat where room_id=$1 order by chat_at desc",$room) as $r){ extract($r);?>
          <div class="message-wrapper">
            <img src="/identicon.php?id=<?=crc32($account_id)?>">
            <div class="message markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
          </div>
        <?}?>
      </div>
      <div style="flex: 0 0 2em; background-color: pink;">
      </div>
    </div>
  </div>
</body>   
</html>   
