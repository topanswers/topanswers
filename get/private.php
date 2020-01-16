<?
include '../db.php';
include '../locache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to private,pg_temp");
isset($_GET['community']) || fail(400,'community must be set');
ccdb("select exists(select 1 from community where community_name=$1)",$_GET['community']) || fail(400,'private community '.$_GET['community'].' does not exist');
$auth = ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'');
extract(cdb("select account_id, community_display_name from one cross join community where community_name=$1",$_GET['community']));
?>
<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="/fonts/source-sans-pro.css">
  <link rel="stylesheet" href="/fonts/source-code-pro.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html { box-sizing: border-box; font-family: source-sans-pro, serif; font-size: 16px; }
    body { display: flex; flex-direction: column; background: lightgrey; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header, header>div { display: flex; min-width: 0; overflow: hidden; align-items: center; }
    footer, footer>div { display: flex; min-width: 0; overflow: hidden; align-items: center; }
    header { min-height: 30px; flex: 0 0 auto; flex-wrap: wrap; justify-content: space-between; font-size: 14px; background: lightgrey; border-bottom: 2px solid black; }
    footer { min-height: 30px; flex: 0 0 auto; flex-wrap: wrap; justify-content: space-between; font-size: 14px; background: lightgrey; border-top: 2px solid black; }
    main { flex: 1 1 auto; overflow: auto; scroll-behavior: smooth; }
    main>div { background: white; flex: 1 1 auto; margin: 5vh 20vw; padding: 1px 24px; border-radius: 5px; }
    .frame { display: inline-block; border: 1px solid black; margin: 2px; outline: 1px solid #00000040; background-color: white; }
    .icon { width: 20px; height: 20px; display: block; margin: 1px; }
    .element { margin: 0 4px; }
    h3 { font-size: 20px; }
    h2 { font-size: 24px; }
    h1 { font-size: 28px; font-weight: normal; }
    @media (max-width: 576px){
      main>div { margin: 16px 16px; }
    }
  </style>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script>
    $(function(){
      $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); })).trigger('resize');
      $('#join').click(function(){
        if(confirm('This will set a cookie to identify your account. You must be 16 or over to join TopAnswers.')){
          $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).done(function(r){
            alert('This login key should be kept confidential, just like a password.\nTo ensure continued access to your account, please record your key somewhere safe:\n\n'+r);
            location.reload(true);
          }).fail(function(r){
            alert((r.status)===429?'Rate limit hit, please try again later':responseText);
            location.reload(true);
          });
        }
      });
      $('#community').change(function(){ window.location = '/'+$(this).find(':selected').attr('data-name'); });
    });
  </script>
  <title>TopAnswers — Building a Library of Knowledge for the Internet</title>
</head>
<body>
  <header>
    <div>
      <span class='element'>TopAnswers</span>
    </div>
    <div>
      <?if($auth){?>
        <a class="frame" href="/profile?community=meta"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
      <?}else{?>
        <span class="element"><input id="join" type="button" value="join"> or <input id="link" type="button" value="log in"></span>
        <a class="frame" href="/meta"><img class="icon" src="/image?hash=bf9d945e0263481d82dfe42837c31a19bfdadd03f120665be23a3f09c34c0cc4"></a>
      <?}?>
    </div>
  </header>
  <main>
    <div>
    <h1>The <?=$community_display_name?> community on TopAnswers is currently in private beta.</h1>
    <p>Requesting access is currently a manual process — please ask in the main chat room in the <a href="/meta">'Meta' community</a>.</p>
    <p>If you are new to TopAnswers, please visit <a href="/">our home page</a> for more information about the site.</p>
  </main>
</body>
</html>
