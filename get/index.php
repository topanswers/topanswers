<?
include '../config.php';
include '../db.php';
include '../nocache.php';

$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');

db("set search_path to indx,pg_temp");
$auth = ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'');
extract(cdb("select account_id from one"));

function getPublicCommunities() {
    $rows = db("select community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light from community where community_type='public' order by random()");
    return $rows;
}

function hasPrivateCommunities() {
    return ccdb("select exists(select community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light from community where community_type='private')");
}

function getPrivateCommunities() {
    $rows = db("select community_name,community_display_name,community_rgb_dark,community_rgb_mid,community_rgb_light from community where community_type='private' order by random()");
    return $rows;
}

?>

<!doctype html>
<html style="--rgb-light: 211,211,211;
             --rgb-mid: 211,211,211;
             --rgb-dark: 80,80,80;
             --regular-font-family: 'source-sans-pro', serif;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="/fonts/source-sans-pro.css">
  <link rel="stylesheet" href="/fonts/source-code-pro.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <link rel="stylesheet" href="/cssjs/index/style.css">
  
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/cssjs/index/appbar.js"></script>
  <title>TopAnswers — Building a Library of Knowledge for the Internet</title>
</head>
<body>
  <header>
    <div class="container">
      <a class="frame" style="background: white;" href="/" title="home"><img class="icon" style="background: white;" src="/image?hash=cb8fe8c88f6b7326bcca667501eaf8b1f1e2ef46af1bc0c37eeb71daa477e1be"></a>
      <span class='element'>TopAnswers</span>
    </div>
    <div>
      <?if($auth){?>
        <a class="frame" href="/profile?community=meta"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
      <?}else{?>
        <span class="element"><input id="join" type="button" value="join"> or <input id="link" type="button" value="log in"></span>
      <?}?>
    </div>
  </header>
  <main>
    <div>
      <h1>TopAnswers Communities:</h1>
      <div class="communities">
        <? foreach(getPublicCommunities() as $r) { extract($r); ?>
          <a href="/<?=$community_name?>" style="--rgb-dark: <?=$community_rgb_dark?>; --rgb-mid: <?=$community_rgb_mid?>; --rgb-light: <?=$community_rgb_light?>;"><?=$community_display_name?></a>
        <?}?>
      </div>
      <?if (hasPrivateCommunities()) { ?>
        <h1>Coming Soon:</h1>
        <div class="communities">
          <? foreach(getPrivateCommunities() as $r) { extract($r); ?>
            <a href="/<?=$community_name?>" style="--rgb-dark: <?=$community_rgb_dark?>; --rgb-mid: <?=$community_rgb_mid?>; --rgb-light: <?=$community_rgb_light?>;"><?=$community_display_name?></a>
          <?}?>
        </div>
      <?}?>
    </div>
    <div style="--rgb-dark: 0,0,240;">
    <h1>Join TopAnswers, and help build a lasting library of knowledge.</h1>
    <p>TopAnswers is what Stack Overflow should be: focused on communities and knowledge sharing, not profit. We share some of the same aims:</p>
    <ul>
      <li>Focus on questions and answers. Everything else we do is to <em>help</em> us produce useful answers to good questions.</li>
      <li>Keep the signal:noise ratio high with a voting system that helps good answers float to the top.</li>
      <li>Build communities of experts across a diverse range of subjects.</li>
    </ul>
    <p>We aren't a clone though; we diverge in important areas:</p>
    <ul>
      <li>We have invested more in the community aspects of the platform, that encourage like-minded people to coalesce around the production and curation of the library of Q&A, but…</li>
      <li>…conversely, we've improved the focus on Q&A by moving comments to the side.</li>
      <li>We are not for-profit, so contributors will never be the 'product', and our core aims will not evolve over time.</li>
      <li>As much as possible of our platform is published <a href="/meta?q=28">as open source</a> <a href="/meta?q=221#a580">on GitHub</a>.</li>
      <li>You are <a href="/meta?q=18#a8">free to decide how to license your contributions</a>.</li>
    </ul>
    <p>We are growing steadily, and starting to register <a href="https://www.google.com/search?q=bmktopage">on search engines</a>. We launched <a href="/databases">Databases</a> in October 2019, <a href="/tex">TeX</a> in January 2020, and an experimental <a href="/cplusplus">C++</a> community and <a href="/codegolf">Code Golf</a> in February. We also have a <a href="/meta?q=530">*nix</a> community in private beta. If you would like to help build a community here, <a href="/meta?q=211">you can</a>. If you are coming from an existing Stack Exchange community you will be able to <a href="/meta?q=236#a176">import your content</a>.</p>
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
