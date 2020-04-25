<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to indx,pg_temp");
$auth = ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'');
extract(cdb("select account_id from one"));
$community_name = 'meta';
$community_code_language = '';
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
$codidact = json_decode(file_get_contents('https://codidact.com/communities.json'),true);
?>
<!doctype html>
<html style="--rgb-light: 211,211,211;
             --rgb-mid: 211,211,211;
             --rgb-dark: 80,80,80;
             --rgb-white: 255, 255, 255;
             --rgb-black: 0, 0, 0;
             --regular-font-family: 'source-sans-pro', serif;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="/fonts/source-sans-pro.css">
  <link rel="stylesheet" href="/fonts/source-code-pro.css">
  <link rel="icon" href="/image?hash=b42ff24d293d4c4e56fa76a7b7f4766ec971bfb63e257c080bfd59a2696aafc2" type="image/png">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <link rel="stylesheet" href="/post.css">
  <style>
    html { box-sizing: border-box; font-family: source-sans-pro, serif; font-size: 16px; }
    body { display: flex; flex-direction: column; background: lightgrey; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    main { flex: 1 1 auto; overflow: auto; scroll-behavior: smooth; }
    main>div:first-child>* { text-align: center; }
    main>div:first-child>p { margin-bottom: 30px; }
    main>div { flex: 1 1 auto; margin: 5vh 20vw; }
    main>div:not(#qa) { background: rgb(var(--rgb-white)); border-radius: 3px; padding: 1px 24px; }
    .question { max-width: 800px; margin-left: auto; margin-right: auto; }

    footer { display: flex; align-items: center; justify-content: safe center; height: 30px; flex: 0 0 auto; font-size: 14px; background: rgb(var(--rgb-dark)); white-space: nowrap; }
    footer *, footer a[href] { color: rgb(var(--rgb-light)); }

    .hover { display: none; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; background: rgb(var(--rgb-light)); }
    .communities { display: flex; flex-wrap: wrap; justify-content: center; margin: 16px 0; }
    .communities>a { display: flex; align-items: center; border: 2px solid rgb(var(--rgb-dark)); border-radius: 6px; color: rgb(var(--rgb-black)); background: rgb(var(--rgb-light));
                     text-decoration: none; padding: 8px 16px; font-size: 24px; margin: 8px; line-height: 1; }
    .communities img { margin-right: 10px; }

    #colinks { display: flex; flex-wrap: wrap; justify-content: center; }
    #colinks a { min-width: 180px; margin: 20px; border: 1px solid rgb(var(--rgb-light)); padding: 5px; border-radius: 3px; }
    #colinks img { height: 40px; display: block; margin: auto; }

    @media (max-width: 576px){
      main>div { margin: 16px 16px; padding: 0; }
      h1 { font-size: 26px; }
      .communities>a { font-size: 16px; margin: 4px; }
    }
  </style>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/jquery.waitforimages.js"></script>
  <?require '../markdown.php';?>
  <script>
    $(function(){
      $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); })).trigger('resize');
      $('#join').click(function(){
        if(confirm('This will set a cookie to identify your account. You must be 16 or over to join TopAnswers.')){
          $.post({ url: '//post.<?=config("SITE_DOMAIN")?>/profile', data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).done(function(r){
            alert('This login key should be kept confidential, just like a password.\nTo ensure continued access to your account, please record your key somewhere safe:\n\n'+r);
            location.reload(true);
          }).fail(function(r){
            alert((r.status)===429?'Rate limit hit, please try again later':responseText);
            location.reload(true);
          });
        }
      });
      $('#link').click(function(){ var pin = prompt('Enter PIN (or login key) from account profile'); if(pin!==null) { $.post({ url: '//post.<?=config("SITE_DOMAIN")?>/profile', data: { action: 'link', link: pin }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){ alert(r.responseText); }).done(function(){ location.reload(true); }); } });
      $('#community').change(function(){ window.location = '/'+$(this).find(':selected').attr('data-name'); });
      function renderQuestion(){
        $(this).find('.summary span[data-markdown]').renderMarkdownSummary();
        $(this).find('.answers>.bar:first-child+.bar+.bar+.bar').each(function(){
          var t = $(this), h = t.nextAll('.bar').addBack();
          if(h.length>1){
            t.prev().addClass('premore');
            $('<div class="bar more"><span></span><a href=".">show '+h.length+' more</a><span></span></div>').appendTo(t.parent()).click(function(){
              t.prev().removeClass('premore');
              $(this).prevAll('.bar:hidden').slideDown().end().slideUp();
              return false;
            });
            h.hide();
          }
        });
      }
      (function(){
        var promises = [];
        $('#qa .post.deleted').remove();
        $('#qa .post:not(.processed)').find('.markdown[data-markdown]').renderMarkdown(promises);
        Promise.allSettled(promises).then(() => {
          $('#qa .post:not(.processed).question').each(renderQuestion);
          //$('#qa .post:not(.processed) .answers .summary span[data-markdown]').renderMarkdownSummary();
          $('#qa .post').addClass('processed');
          $('#qa .post').slice(7).remove();
        });
      })();
    });
  </script>
  <title>TopAnswers — Knowledge Communities</title>
</head>
<body>
  <header>
    <div class="container">
      <a class="frame" href="/" title="home"><img class="icon" src="/communityicon"></a>
      <span class='element'>TopAnswers</span>
    </div>
    <div>
      <?if($auth){?>
        <a class="frame" href="/profile?community=meta"><img data-test="userIcon" class="icon" src="/identicon?id=<?=$account_id?>"></a>
      <?}else{?>
        <span class="element"><input id="join" type="button" value="join"> or <input data-test="loginBtn" id="link" type="button" value="log in"></span>
      <?}?>
    </div>
  </header>
  <main>
    <div><h1>TopAnswers</h1><p>knowledge communities</p></div>
    <div>
      <div class="communities">
        <?foreach(db("select community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light
                      from community
                      where community_type='public'
                      order by random()") as $r){ extract($r);?>
          <a href="/<?=$community_name?>" style="--rgb-dark: <?=$community_rgb_dark?>; --rgb-mid: <?=$community_rgb_mid?>; --rgb-light: <?=$community_rgb_light?>;">
            <img class="icon" src="/communityicon?community=<?=$community_name?>">
            <?=$community_display_name?>
          </a>
        <?}?>
      </div>
      <?if(ccdb("select exists(select community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light from community where community_type='private')")){?>
        <h1>Coming Soon:</h1>
        <div class="communities">
          <?foreach(db("select community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light from community where community_type='private' order by random()") as $r){ extract($r);?>
            <a href="/<?=$community_name?>" style="--rgb-dark: <?=$community_rgb_dark?>; --rgb-mid: <?=$community_rgb_mid?>; --rgb-light: <?=$community_rgb_light?>;"><?=$community_display_name?></a>
          <?}?>
        </div>
      <?}?>
    </div>
    <div id="qa"><?$ch = curl_init('http://127.0.0.1/questions?community=meta&search='.urlencode('@+ {}{code golf}')); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?></div>
    <div style="--rgb-dark: 0,0,240;">
    <h1>Join TopAnswers, and help build a library of knowledge.</h1>
    <p>TopAnswers is what Stack Overflow should be: focused on communities and knowledge sharing, not profit. We share some of the same aims:</p>
    <ul>
      <li>Focus on questions and answers. Everything else we do is to <em>help</em> us produce useful answers to good questions.</li>
      <li>Keep the signal:noise ratio high with a voting system that helps good answers float to the top.</li>
      <li>Build communities of experts across a diverse range of subjects.</li>
    </ul>
    <p>We aren't a clone though:</p>
    <ul>
      <li>We have invested more in the community aspects of the platform, that encourage like-minded people to coalesce around the production and curation of the library of Q&amp;A, but…</li>
      <li>…conversely, we've improved the focus on Q&amp;A by moving comments to the side.</li>
      <li>We are not for-profit, so contributors will never be the 'product', and our core aims will not evolve over time.</li>
      <li>As much as possible of our platform is published <a href="/meta?q=28">as open source</a> <a href="/meta?q=221#a580">on GitHub</a>.</li>
      <li>You are <a href="/meta?q=18#a8">free to decide how to license your contributions</a>.</li>
    </ul>
    <p>We are growing steadily, and starting to register <a href="https://www.google.com/search?q=bmktopage">on search engines</a>. We launched <a href="/databases">Databases</a> in October 2019, <a href="/tex">TeX</a> in January 2020, and <a href="/codegolf">Code Golf</a> in February. We have since added a number of experimental communities like <a href="/cplusplus">C++</a> and <a href="/meta?q=530">*nix</a>. If you would like to help start another community here, <a href="/meta?q=211">you can</a>. If you are coming from an existing Stack Exchange community you will be able to <a href="/meta?q=236#a176">import your content</a>.</p>
    <p>There is a lot more detailed information on our <a href="/meta">meta</a> community (a place for questions and answers about TopAnswers itself), for example:</p>
    <ul>
      <li><a href="/meta?q=1">Why we are building TopAnswers</a></li>
      <li><a href="/meta?q=8">TopAnswers Code of Conduct</a></li>
      <li><a href="/meta?q=182">Who will moderate and what tools will they have access to?</a></li>
      <li><a href="/meta?q=72">What markdown options are available?</a></li>
    </ul>
    <p>Finally, we share many goals (and some contributors) with  <a href="https://codidact.org/">Codidact</a>. Please consider participating in their communities:</p>
    <div id="colinks">
      <?foreach($codidact as $s){?>
        <a href="<?=$s['canonical_url']?>" title="<?=$s['name']?>"?><img src="<?=$s['logo_url']?>"></a>
      <?}?>
    </div>
    </div>
  </main>
  <footer>
    <div>TopAnswers is based in the UK, with servers in a data centre in Reading. We are <a href="/meta?q=1">committed to</a> seeking <a href="https://en.wikipedia.org/wiki/Charitable_incorporated_organisation">Charitable Incorporated Organisation</a> status as soon as we can meet our modest hosting and accountancy costs.</div>
  </footer>
</body>
</html>
