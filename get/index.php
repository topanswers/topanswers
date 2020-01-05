<?
include '../db.php';
include '../locache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to indx,pg_temp");
$auth = ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'');
extract(cdb("select account_id from one"));
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
      <select id="community" class="element">
        <option selected>Home</option>
        <?foreach(db("select community_name,community_room_id,community_display_name from community order by community_name desc") as $r){ extract($r,EXTR_PREFIX_ALL,'s');?>
          <option value="<?=$s_community_room_id?>" data-name="<?=$s_community_name?>"><?=$s_community_display_name?></option>
        <?}?>
      </select>
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
    <h1>Join TopAnswers, and help build a lasting library of knowledge for the internet.</h1>
    <p>TopAnswers is what Stack Overflow should be: focused on communities and knowledge sharing, not profit. We share some of the same aims:</p>
    <ul>
      <li>Focus on questions and answers. Everything else we do is to <em>help</em> us produce useful questions and answers.</li>
      <li>Keep the signal:noise ratio high with a voting system that helps good answers float to the top.</li>
      <li>Build communities of experts across a diverse range of subjects.</li>
    </ul>
    <p>We aren't a clone though; we diverge in important areas:</p>
    <ul>
      <li>We are not for-profit, so contributors will never be the 'product', and our core aims will not evolve over time.</li>
      <li>As much as possible of our platform <a href="/meta?q=28">will be published as open source</a>.</li>
      <li>We've improved the focus on Q&A by moving comments to the side.</li>
      <li>You are <a href="/meta?q=18#a8">free to decide how to license your content<a>.</li>
    </ul>
    <p>We are small right now — we launched <a href="/databases">databases</a> in October 2019 and have a TeX community in private beta. If you would like to help build a community here, <a href="/meta?q=211">you can</a>. If you are coming from an existing Stack Exchange community you will be able to <a href="/meta?q=236#a176">import your content</a> from there.</p>
    <p>There is a lot more detailed information on our <a href="/meta">meta</a> community (a place for questions and answers about TopAnswers itself), for example:</p>
    <ul>
      <li><a href="/meta?q=1">Why we are building TopAnswers</a></li>
      <li><a href="/meta?q=8">TopAnswers Code of Conduct</a></li>
      <li><a href="/meta?q=182">Who will moderate and what tools will they have access to?</a></li>
      <li><a href="/meta?q=72">What markdown options are available?</a></li>
    </ul>
    <p>Finally, we share many goals (and some contributors) with another project you might like to know about: <a href="https://codidact.org/">Codidact</a>.</p>
    </div>
  </main>
  <footer>
    <div>
      <span class='element'>TopAnswers is based in the UK, with servers in a data centre in Reading. We are <a href="/meta?q=1">committed to</a> seeking <a href="https://en.wikipedia.org/wiki/Charitable_incorporated_organisation">Charitable Incorporated Organisation</a> status as soon as we can meet our modest hosting and accountancy costs.</span>
    </div>
  </footer>
</body>
</html>
