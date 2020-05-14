<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
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
<html style="--rgb-dark:80,80,80;
             --rgb-mid:211,211,211;
             --rgb-light:236,236,236;
             --rgb-white:255,255,255;
             --rgb-black:0,0,0;
             --font-regular:source-sans-pro;
             --font-monospace:hack;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="icon" href="/image?hash=b42ff24d293d4c4e56fa76a7b7f4766ec971bfb63e257c080bfd59a2696aafc2" type="image/png">
  <link rel="stylesheet" href="<?=h("/fonts/source-sans-pro.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/charis.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/hack.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/lightbox2/css/lightbox.min.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/post.css")?>">
  <link rel="stylesheet" href="<?=h("/page/index/index.css")?>">
  <link rel="stylesheet" href="<?=h("/markdown.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/codemirror/codemirror.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/qp/qp.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/katex/katex.min.css")?>">
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/index/index.js")?>" src="<?=h("/lib/require.js")?>"></script>
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
        <span class="element"><input id="link" data-test="loginBtn" type="button" value="log in"><input id="join" type="button" value="join (sets cookie)"></span>
      <?}?>
    </div>
  </header>
  <main>
    <div>
      <div id="logo">
        <img src="/image?hash=3e6863b36a9859bb2ac05968cc2328a1406353babed2a1f70f897e3eb3e2e331">
        <div><span>Top</span><span>Answers</span><span>Knowledge Communities</span></div>
      </div>
    </div>
    <div>
      <div id="communities">
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
    </div>
    <div>
      <div id="qa">
        <?$ch = curl_init('http://127.0.0.1/questions?community=meta&search='.urlencode('@+ {}{code golf}{blog}')); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
      </div>
    </div>
    <div id="info-wrapper">
      <div id="info">
        <h1>Join TopAnswers, building a library of knowledge.</h1>
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
      </div>
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
