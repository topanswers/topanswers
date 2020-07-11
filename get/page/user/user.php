<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to usr,pg_temp");

ccdb("select login_communityuser(nullif($1,'')::uuid,$2,$3)",$_COOKIE['uuid']??'',$_GET['community']??'meta',$_GET['id']);
extract(cdb("select account_id,account_image_url
                   ,user_account_id,user_account_name,user_account_image_url,user_account_name_is_derived
                   ,community_id,community_name,community_display_name,community_image_url
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
                   ,my_community_regular_font_name,my_community_monospace_font_name
             from one"));

$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<!doctype html>
<html style="--community:<?=$community_name?>;
             <?foreach(['dark','mid','light','highlight','warning','black','white'] as $c){?>--rgb-<?=$c?>: <?=${'community_rgb_'.$c}?>;<?}?>
             --font-regular:<?=$my_community_regular_font_name?>;
             --font-monospace:<?=$my_community_monospace_font_name?>;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_monospace_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/datatables/datatables.min.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/fouc.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/page/user/user.css")?>">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <title><?=$user_account_name?> - TopAnswers</title>
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/user/user.js")?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div><?if($account_id){?><a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="<?=$account_image_url?>"></a><?}?></div>
  </header>
  <main>
    <fieldset>
      <div style="display: flex; align-items: center;">
        <div class="frame"><img class="icon" src="<?=$user_account_image_url?>"></div>
        <div style="margin-left: 4px;<?=$user_account_name_is_derived?' font-style: italic;':''?>"><?=$user_account_name?></div>
      </div>
    </fieldset>
    <fieldset>
      <legend><a class="panel" data-panel="answers">answers</a> / <a class="panel" data-panel="questions" href=".">questions</a> / <a class="panel" data-panel="communities" href=".">communities</a></legend>
      <div id="answers" class="panel">
        <table data-order='[[0,"desc"]]' data-page-length='10'>
          <thead>
            <tr><th>answer date/time</th><th>question type</th><th>title</th><th>answer stars</th><th>question date/time</th></tr>
          </thead>
          <tbody>
            <?foreach(db("select question_id,question_title,question_votes,answer_id,answer_votes,sanction_description
                               , to_char(answer_at,'YYYY-MM-DD HH24:MI') answer_at_desc
                               , to_char(question_at,'YYYY-MM-DD HH24:MI') question_at_desc
                          from answer
                          order by question_at desc") as $r){extract($r);?>
              <tr>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$answer_at_desc?></td>
                <td><?=$sanction_description?></td>
                <td><a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>"><?=$question_title?></a></td>
                <td><?=$answer_votes?></td>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$question_at_desc?></td>
              </tr>
            <?}?>
          </tbody>
        </table>
      </div>
      <div id="questions" class="panel">
        <table data-order='[[0,"desc"]]' data-page-length="10">
          <thead>
            <tr><th>date/time</th><th>type</th><th>title</th><th>stars</th></tr>
          </thead>
          <tbody>
            <?foreach(db("select question_id,question_title,question_votes,sanction_description,kind_has_question_votes
                               , to_char(question_at,'YYYY-MM-DD HH24:MI') question_at_desc
                          from question
                          order by question_at desc") as $r){extract($r);?>
              <tr>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$question_at_desc?></td>
                <td><?=$sanction_description?></td>
                <td><a href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></td>
                <td><?=$kind_has_question_votes?$question_votes:''?></td>
              </tr>
            <?}?>
          </tbody>
        </table>
      </div>
      <div id="communities" class="panel">
        <table data-order='[[3,"desc"]]' data-page-length="10" data-dom="rtip">
          <thead>
            <tr><th>community</th><th>questions</th><th>answers</th><th>stars</th></tr>
          </thead>
          <tbody>
            <?foreach(db("select community_id,community_name,community_display_name,community_question_count,community_answer_count,community_votes
                          from community
                          order by community_votes desc, community_answer_count desc, community_question_count desc, community_id") as $r){extract($r,EXTR_PREFIX_ALL,'c');?>
              <tr>
                <td><?=($community_name!==$c_community_name)?('<a href="?id='.$_GET['id'].'&community='.$c_community_name.'">'.$c_community_display_name.'</a>'):$c_community_display_name?></td>
                <td><?=$c_community_question_count?></td>
                <td><?=$c_community_answer_count?></td>
                <td><?=$c_community_votes?></td>
              </tr>
            <?}?>
          </tbody>
        </table>
      </div>
    </fieldset>
  </main>
</body>
</html>
<?ob_end_flush();
