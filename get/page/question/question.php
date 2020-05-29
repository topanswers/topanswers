<?
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to question,pg_temp");
if(isset($_GET['id'])){
  $auth = ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']);
  $auth || fail(403,'access denied');
}else{
  $auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']??'');
  $auth||(($_GET['community']==='databases')&&isset($_GET['rdbms'])&&isset($_GET['fiddle'])) || fail(403,'need to be logged in to visit this page unless from a fiddle');
}
extract(cdb("select account_id,account_is_dev,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense,account_image_url
                  , account_license_name||(case when account_permit_later_license then ' or later' else '' end)
                       ||(case when account_has_codelicense then ' + '||account_codelicense_name||(case when account_permit_later_codelicense then ' or later' else '' end) else '' end) account_license
                   ,community_id,community_name,community_display_name,community_code_language,community_tables_are_monospace,community_language,community_image_url
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
                   ,communicant_keyboard,my_community_regular_font_name,my_community_monospace_font_name
                   ,question_id,question_title,question_markdown,question_se_question_id
                  , question_license_name||(case when question_permit_later_license then ' or later' else '' end)
                       ||(case when question_has_codelicense then ' + '||question_codelicense_name||(case when question_permit_later_codelicense then ' or later' else '' end) else '' end) license
                   ,question_is_deleted,question_answered_by_me
                   ,question_when
                   ,question_account_id,question_account_is_me,question_account_name,question_account_is_imported
                   ,question_license_href,question_has_codelicense,question_codelicense_name
             from one"));
$communicant_keyboard = htmlspecialchars_decode($communicant_keyboard);
include '../../../lang/question.'.$community_language.'.php';
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
//ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<!doctype html>
<html style="--community:<?=$community_name?>;
             --lang-code:<?=$community_code_language?>;
             --rgb-dark:<?=$community_rgb_dark?>;
             --rgb-mid:<?=$community_rgb_mid?>;
             --rgb-light:<?=$community_rgb_light?>;
             --rgb-highlight:<?=$community_rgb_highlight?>;
             --rgb-warning:<?=$community_rgb_warning?>;
             --rgb-white:255,255,255;
             --rgb-black:0,0,0;
             --font-regular:<?=$my_community_regular_font_name?>;
             --font-monospace:<?=$my_community_monospace_font_name?>;
             --font-table:<?=$community_tables_are_monospace?$my_community_monospace_font_name:$my_community_regular_font_name?>;
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_monospace_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/lightbox2/css/lightbox.min.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/post.css")?>">
  <link rel="stylesheet" href="<?=h("/page/question/question.css")?>">
  <link rel="stylesheet" href="<?=h("/markdown.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/codemirror/codemirror.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/qp/qp.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/katex/katex.min.css")?>">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <title><?=$question_id?'Edit':'Ask'?> Question - TopAnswers</title>
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/question/question.js").preg_replace('/^&/','?',(isset($_GET['fiddle'])?'&fiddle':'').($question_id?'':'&new'))?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <?if($question_id){?>
      <div>
        <span class="element">editing the question <a href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></span>
      </div>
    <?}?>
    <div>
      <?if($auth){?>
        <?if(!$question_id){?>
          <span id="license">
            <span class="element wideonly"><?=$account_license?> (<a href=".">change</a>)</span>
            <span class="element wideonly">
              <select name="license" form="form">
                <?foreach(db("select license_id,license_name,license_is_versioned from license order by license_name") as $r){ extract($r);?>
                  <option value="<?=$license_id?>" data-versioned="<?=$license_is_versioned?'true':'false'?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
                <?}?>
              </select>
              <label><input type="checkbox" name="license-orlater" form="form"<?=$account_permit_later_license?'checked':''?>>or later </label>
              <select name="codelicense" form="form">
                <?foreach(db("select codelicense_id,codelicense_name,codelicense_is_versioned from codelicense order by codelicense_id<>1, codelicense_name") as $r){ extract($r);?>
                  <option value="<?=$codelicense_id?>" data-versioned="<?=$codelicense_is_versioned?'true':'false'?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
                <?}?>
              </select>
              <label><input type="checkbox" name="codelicense-orlater" form="form"<?=$account_permit_later_license?'checked':''?>>or later</label>
            </span>
          </span>
          <select class="element" name="sanction" form="form" required>
            <option value="" disabled selected><?=$l_choose_post_type?></option>
            <?foreach(db("select sanction_id,sanction_description,sanction_is_default from sanction order by sanction_ordinal") as $r){ extract($r);?>
              <option value="<?=$sanction_id?>"<?=$sanction_is_default?' selected':''?>><?=$sanction_description?></option>
            <?}?>
          </select>
        <?}?>
        <button class="element" id="submit" type="submit" form="form"><?=$question_id?'update<span class="wideonly"> post under '.$license.'</span>':$l_submit?></button>
        <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="<?=$account_image_url?>"></a>
      <?}else{?>
        <input class="element" id="join" type="button" value="join">
      <?}?>
    </div>
  </header>
  <form id="form" method="POST" action="//post.topanswers.xyz/question">
    <?if($question_id){?>
      <input type="hidden" name="action" value="change">
      <input type="hidden" name="id" value="<?=$question_id?>">
    <?}else{?>
      <input type="hidden" name="action" value="new">
      <input type="hidden" name="community" value="<?=$community_name?>">
    <?}?>
    <main>
      <input id="title" name="title" placeholder="<?=$l_your_question_title?>" minlength="5" maxlength="200" autocomplete="off" autofocus required<?=$question_id?' value="'.$question_title.'"':''?>>
      <div id="editor-buttons">
        <div>
          <i title="Bold (Ctrl + B)" class="button fa fa-bold"></i>
          <i title="Italic (Ctrl + I)" class="button fa fa-italic"></i>
          <br>
          <i title="Hyperlink (Ctrl + L)" class="button fa fa-link"></i>
          <i title="Blockquote (Ctrl + Q)" class="button fa fa-quote-left"></i>
          <i title="Code (Ctrl + K)" class="button fa fa-code"></i>
          <i title="Upload Image (Ctrl + G)" class="button fa fa-picture-o"></i>
          <?if($community_name==='databases'||$community_name==='test'){?>
            <i title="DBFiddle (Ctrl + D)" class="button fa fa-database"></i>
          <?}?>
          <br>
          <i title="Ordered List (Ctrl + O)" class="button fa fa-list-ol"></i>
          <i title="Unordered List (Ctrl + U)" class="button fa fa-list-ul"></i>
          <br>
          <i title="Undo (Ctrl + Z)" class="button fa fa-undo"></i>
          <i title="Redo (Ctrl + Y)" class="button fa fa-repeat"></i>
        </div>
      </div>
      <div id="codemirror-container">
        <textarea name="markdown" autocomplete="off" required placeholder="<?=$l_your_question?>"><?=$question_id?$question_markdown:(isset($_GET['fiddle'])?('I have a question about this fiddle:&#13;&#10;&#13;&#10;<>https://dbfiddle.uk?rdbms='.$_GET['rdbms'].'&fiddle='.$_GET['fiddle']):'')?></textarea>
        <div id="keyboard">
          <?foreach(explode(' ',$communicant_keyboard) as $group){?>
            <span>
              <?foreach(preg_split('//u',$group,-1,PREG_SPLIT_NO_EMPTY) as $c){?><span><?=$c?></span><?}?>
            </span>
          <?}?>
        </div>
      </div>
      <div id="markdown" class="markdown noexpander"></div>
    </main>
  </form>
  <form id="imageupload" action="//post.topanswers.xyz/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*"></form>
</body>   
</html>   
<?//ob_end_flush();
