<?    
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; style-src-elem 'self'; style-src-attr 'unsafe-inline'; img-src * data:; font-src 'self'; connect-src 'self' tio.run dbfiddle.uk post.topanswers.xyz; form-action 'self' post.topanswers.xyz;");
require '../../../config.php';
require '../../../db.php';
require '../../../nocache.php';
require '../../../hash.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to answer,pg_temp");
if(isset($_GET['id'])){
  ccdb("select login_answer(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']) || fail(403,'access denied');
}else{
  ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['question']??'') || fail(403,'access denied');
}
extract(cdb("select account_id,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense,account_license,account_image_url
                   ,answer_id,answer_markdown,answer_license
                   ,question_id,question_title,question_markdown
                   ,sanction_label_called,sanction_label_is_mandatory,sanction_default_label_id
                   ,label_code_language,label_tio_language
                   ,community_name,community_code_language,community_tables_are_monospace
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_rgb_black,community_rgb_white
                   ,community_image_url
                   ,communicant_keyboard,my_community_regular_font_name,my_community_monospace_font_name
                   ,(select jsonb_agg(z) from (select license_id,license_name,license_is_versioned from license order by license_name) z) licenses
                   ,(select jsonb_agg(z) from (select codelicense_id,codelicense_name,codelicense_is_versioned from codelicense order by codelicense_id=1 desc, codelicense_name) z) codelicenses
                   ,(select jsonb_agg(z) from (select label_id,label_name,label_code_language,label_tio_language from label order by label_name) z) labels
             from one"));
$communicant_keyboard = htmlspecialchars_decode($communicant_keyboard);
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
?>
<!doctype html>
<html style="--community:<?=$community_name?>;
             --lang-code:<?=$label_code_language?:$community_code_language?>;
             <?foreach(['dark','mid','light','highlight','warning','black','white'] as $c){?>--rgb-<?=$c?>: <?=${'community_rgb_'.$c}?>;<?}?>
             --font-regular:<?=$my_community_regular_font_name?>;
             --font-monospace:<?=$my_community_monospace_font_name?>;
             --font-table:<?=$community_tables_are_monospace?$my_community_monospace_font_name:$my_community_regular_font_name?>;
             "
  data-question-id="<?= $question_id ?>"
  data-answer-is-new="<?= $answer_id ? 'false' : 'true' ?>"
>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_regular_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/fonts/$my_community_monospace_font_name.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/fork-awesome/css/fork-awesome.min.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/lightbox2/css/lightbox.min.css")?>">
  <link rel="stylesheet" href="<?=h("/global.css")?>">
  <link rel="stylesheet" href="<?=h("/header.css")?>">
  <link rel="stylesheet" href="<?=h("/post.css")?>">
  <link rel="stylesheet" href="<?=h("/page/answer/answer.css")?>">
  <link rel="stylesheet" href="<?=h("/markdown.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/codemirror/codemirror.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/qp/qp.css")?>">
  <link rel="stylesheet" href="<?=h("/lib/katex/katex.min.css")?>">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <title><?=$answer_id?'Edit Answer':'Answer Question'?> - TopAnswers</title>
  <script src="<?=h("/require.config.js")?>"></script>
  <script data-main="<?=h("/page/answer/answer.js")?>" src="<?=h("/lib/require.js")?>"></script>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div>
      <?if($answer_id){?>
        <span class="element">editing <a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>">an answer</a> on <a href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></span>
      <?}else{?>
        <span class="element"><a href="/<?=$community_name?>?q=<?=$question_id?>">back to question</a></span>
      <?}?>
    </div>
    <div>
      <?if(!$answer_id && count($labels)){?>
        <select class="element" name="label" form="form"<?=$sanction_label_is_mandatory?' required':''?>>
          <option value=""<?=$sanction_label_is_mandatory?' disabled':''?><?=$sanction_default_label_id?'':' selected'?>>
            choose <?=$sanction_label_called?><?=$sanction_label_is_mandatory?'':' (optional)'?>
          </option>
          <?foreach($labels as $r){ extract($r);?>
            <option value="<?=$label_id?>" <?=($label_id===$sanction_default_label_id)?' selected':''?>><?=$label_name?></option>
          <?}?>
        </select>
      <?}?>
      <?if(!$answer_id){?>
        <span id="license">
          <span class="element"><?=$account_license?> (<a href=".">change</a>)</span>
          <span class="element wideonly" style="display: none;">
            <select name="license" form="form">
              <?foreach($licenses as $r){ extract($r);?>
                <option value="<?=$license_id?>" data-versioned="<?=$license_is_versioned?'true':'false'?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
              <?}?>
            </select>
            <label><input type="checkbox" name="license-orlater" form="form"<?=$account_permit_later_license?'checked':''?>>or later </label>
            <select name="codelicense" form="form">
              <?foreach($codelicenses as $r){ extract($r);?>
                <option value="<?=$codelicense_id?>" data-versioned="<?=$codelicense_is_versioned?'true':'false'?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
              <?}?>
            </select>
            <label><input type="checkbox" name="codelicense-orlater" form="form"<?=$account_permit_later_codelicense?'checked':''?>>or later</label>
          </span>
        </span>
      <?}?>
      <button class="element" id="submit" type="submit" form="form"><?=$answer_id?'update<span class="wideonly"> answer under '.$answer_license.'</span>':'post<span class="wideonly"> answer</span>'?></button>
      <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="<?=$account_image_url?>"></a>
    </div>
  </header>
  <form id="form" method="POST" action="//post.topanswers.xyz/answer">
    <?if($answer_id){?>
      <input type="hidden" name="action" value="change">
      <input type="hidden" name="id" value="<?=$answer_id?>">
    <?}else{?>
      <input type="hidden" name="action" value="new">
      <input type="hidden" name="community" value="<?=$community_name?>">
      <input type="hidden" name="question" value="<?=$question_id?>">
    <?}?>
    <main>
      <div id="question">
        <div class="title"><a href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></div>
        <div class="markdown" data-markdown="<?=$question_markdown?>"></div>
      </div>
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
        <textarea name="markdown" autocomplete="off" required placeholder="your answer"><?=$answer_id?$answer_markdown:''?></textarea>
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
  <form id="imageupload" action="//post.topanswers.xyz/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
</body>   
</html>   
