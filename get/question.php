<?    
include '../db.php';
include '../nocache.php';
$uuid = $_COOKIE['uuid']??'';
if($uuid) ccdb("select login($1)",$uuid);
$id = $_GET['id']??$_POST['id']??'0';
if($_SERVER['REQUEST_METHOD']==='POST'){
  isset($_POST['action']) or die('posts must have an "action" parameter');
  switch($_POST['action']) {
    case 'new':
      $id=ccdb("select new_question((select community_id from community where community_name=$1),(select question_type from question_type_enums where question_type=$2),$3,$4,$5,$6)",$_POST['community'],$_POST['type'],$_POST['title'],$_POST['markdown'],$_POST['license'],$_POST['codelicense']);
      if($id){?>
        <!doctype html>
        <html>
        <head>
          <script>
            localStorage.removeItem('<?=$_POST['community']?>.ask');
            localStorage.removeItem('<?=$_POST['community']?>.ask.title');
            localStorage.removeItem('<?=$_POST['community']?>.ask.type');
            window.location.href = '/<?=$_POST['community']?>?q=<?=$id?>';
          </script>
        </head>
        </html><?}
      exit;
    case 'new-se':
      db("select new_import(community_id,$2,$3) from community where community_name=$1",$_POST['community'],$_POST['seqid'],$_POST['seaids']);
      extract(cdb("select sesite_url,account_community_se_user_id from community join sesite on community_sesite_id=sesite_id natural join my_account_community where community_name=$1",$_POST['community']));
      libxml_use_internal_errors(true);
      // get the SE user-id and user-name for the question asker
      $doc = new DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//div[@id='question-header']/h1/a");
      $title = $elements[0]->childNodes[0]->nodeValue;
      $elements = $xpath->query("//div[@id='question']//div[contains(concat(' ', @class, ' '), ' owner ')]//div[contains(concat(' ', @class, ' '), ' user-details ')]/a");
      $qanon = (count($elements)===0);
      if(!$qanon){
        $seuid = explode('/',$elements[0]->getAttribute('href'))[2];
        $seuname = $elements[0]->textContent;
      }
      // get every answer id with matching SE user-id and user-name
      $answers = [];
      $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ')]/@id");
      foreach($elements as $element){
        $a = $xpath->query("//div[@id='".$element->textContent."']"
                          ."//div[contains(concat(' ', @class, ' '), ' post-signature ') and not(following-sibling::div[contains(concat(' ', @class, ' '), ' post-signature ')])]"
                          ."//div[contains(concat(' ', @class, ' '), ' user-details ')]/a");
        $answers[explode('-',$element->textContent)[1]] = (count($a)===0)?["anon"=>true]:["anon"=>false,"uid"=>explode('/',$a[0]->getAttribute('href'))[2],"uname"=>$a[0]->textContent];
      }
      // get the markdown and tags for the question
      $doc = new DOMDocument();
      $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/posts/'.$_POST['seqid'].'/edit'));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//input[@id='tagnames']/@value");
      $tags = $elements[0]->textContent;
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//textarea[@id='wmd-input-".$_POST['seqid']."']");
      $markdown = $elements[0]->textContent;
      $markdown = preg_replace('/<!--[^\n]*-->/m','',$markdown);
      $markdown = preg_replace('/^(#+)([^\n# ][^\n]*[^\n# ])(#+)$/m','$1 $2 $3',$markdown);
      $markdown = preg_replace('/^(#+)([^\n# ])/m','$1 $2',$markdown);
      $markdown = preg_replace('/http:\/\/i.stack.imgur.com\//','https://i.stack.imgur.com/',$markdown);
     //error_log('length: '.strlen($markdown));
      // add the question
      if($qanon){
        $id=ccdb("select new_sequestionanon((select community_id from community where community_name=$1),$2,$3,$4,$5)",$_POST['community'],$title,$markdown,$tags,$_POST['seqid']);
      }else{
        $id=ccdb("select new_sequestion((select community_id from community where community_name=$1),$2,$3,$4,$5,$6,$7)",$_POST['community'],$title,$markdown,$tags,$_POST['seqid'],$seuid,$seuname);
      }
      // generate an array of answers to import
      $aids = [];
      if($_POST['seaids']==='*'){
        $doc = new DOMDocument();
        $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
        $xpath = new DOMXpath($doc);
        $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ')]");
        foreach($elements as $element) array_push($aids,explode('-',$element->getAttribute('id'))[1]);
      }else{
        if($_POST['seaids']) $aids = explode(' ',$_POST['seaids']);
        if($account_community_se_user_id){
          $doc = new DOMDocument();
          $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
          $xpath = new DOMXpath($doc);
          $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ') and "
                                   ."boolean(.//div[contains(concat(' ', @class, ' '), ' post-signature ') and not(following-sibling::div[contains(concat(' ', @class, ' '), ' post-signature ')])]"
                                   ."//div[contains(concat(' ', @class, ' '), ' user-details ')]/a[contains(@href,'/".$account_community_se_user_id."/')])]");
          foreach($elements as $element){
            $aid = explode('-',$element->getAttribute('id'))[1];
            if(!in_array($aid,$aids,true)) array_push($aids,$aid);
          }
        }
      }
     //error_log('aids: '.print_r($aids,true));
      // import each selected answer
      foreach($aids as $aid){
        $doc = new DOMDocument();
        $doc->loadHTML('<meta http-equiv="Content-Type" content="charset=utf-8" />'.file_get_contents($sesite_url.'/posts/'.$aid.'/edit'));
        $xpath = new DOMXpath($doc);
        $elements = $xpath->query("//textarea[@id='wmd-input-".$aid."']");
        $markdown = $elements[0]->textContent;
        $markdown = preg_replace('/<!--[^\n]*-->/m','',$markdown);
        $markdown = preg_replace('/^(#+)([^\n# ][^\n]*[^\n# ])(#+)$/m','$1 $2 $3',$markdown);
        $markdown = preg_replace('/^(#+)([^\n# ])/m','$1 $2',$markdown);
        $markdown = preg_replace('/http:\/\/i.stack.imgur.com\//','https://i.stack.imgur.com/',$markdown);
        if($answers[$aid]['anon']){
          db("select new_seansweranon($1,$2,$3) from my_account",$id,$markdown,$aid);
        }else{
          db("select new_seanswer($1,$2,$3,$4,$5) from my_account",$id,$markdown,$aid,$answers[$aid]['uid'],$answers[$aid]['uname']);
        }
      }
      header('Location: /'.$_POST['community'].'?q='.$id);
      exit;
    case 'change':
      db("select change_question($1,$2,$3)",$id,$_POST['title'],$_POST['markdown']);
      header('Location: /'.ccdb("select community_name from question natural join community where question_id=$1",$id).'?q='.$id);
      exit;
    case 'vote': exit(ccdb("select vote_question($1,$2)",$_POST['id'],$_POST['votes']));
    case 'dismiss': exit(ccdb("select dismiss_question_notification($1)",$_POST['id']));
    case 'subscribe': exit(ccdb("select subscribe_question($1)",$_POST['id']));
    case 'unsubscribe': exit(ccdb("select unsubscribe_question($1)",$_POST['id']));
    default: fail(400,'unrecognized action');
  }
}
if($id) {
  ccdb("select count(*) from question where question_id=$1",$id)==='1' || die('invalid question id');
  extract(cdb("select question_type,question_title,question_markdown,community_code_language
                    , community_name community
                    , license_name||(case when codelicense_id<>1 then ' + '||codelicense_name else '' end) license
               from question natural join community natural join license natural join codelicense
               where question_id=$1",$id));
}else{
  if(!isset($_GET['community'])) die('Community not set');
  $community = $_GET['community'];
  ccdb("select count(*) from community where community_name=$1",$community)==='1' or die('invalid community');
  if(!$uuid&&(($community!=='databases')||!isset($_GET['rdbms'])||!isset($_GET['fiddle']))) fail(403,'need to be logged in to visit this page unless from a fiddle');
  extract(cdb("select community_code_language from community where community_name=$1",$community));
}
extract(cdb("select regular_font_name,monospace_font_name
                  , encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community natural join my_account_community
             where community_name=$1",$community));
extract(cdb("select account_license_id,account_codelicense_id from my_account"));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: '<?=$regular_font_name?>', serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/fonts/<?=$regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    textarea, pre, code, .CodeMirror { font-family: '<?=$monospace_font_name?>', monospace; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    header select { margin-right: 0.5rem; }

    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .question { margin-bottom: 0.5rem; padding: 0.5rem; border: 1px solid darkgrey; }
    .spacer { flex: 0 0 auto; min-height: 1rem; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }

    #markdown-editor-buttons i { padding: 0.2em; text-align: center; }
    #markdown-editor-buttons i:hover { color: #<?=$colour_highlight?>; cursor: pointer; background-color: #<?=$colour_light?>; border-radius: 0.2rem; }
    #markdown-editor-buttons i:last-child { margin-bottom: 0; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 0 0.2rem 0.2rem 0.2rem; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/codemirror/codemirror.js"></script>
  <script src="/lib/codemirror/markdown.js"></script>
  <script src="/lib/codemirror/sql.js"></script>
  <script src="/lib/codemirror/placeholder.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/lightbox2/js/lightbox.min.js"></script>
  <script src="/lib/moment.js"></script>
  <script src="/lib/favico.js"></script>
  <script>
    $(function(){
      var cm = CodeMirror.fromTextArea($('textarea')[0],{ lineWrapping: true, mode: 'markdown', extraKeys: {
        Home: "goLineLeft",
        End: "goLineRight",
        'Ctrl-B': function(){ $('.button.fa-bold').click(); },
        'Ctrl-I': function(){ $('.button.fa-italic').click(); },
        'Ctrl-L': function(){ $('.button.fa-link').click(); },
        'Ctrl-Q': function(){ $('.button.fa-quote-left').click(); },
        'Ctrl-K': function(){ $('.button.fa-code').click(); },
        'Ctrl-G': function(){ $('.button.fa-picture-o').click(); },
        'Ctrl-Z': function(){ $('.button.fa-undo').click(); },
        'Ctrl-Y': function(){ $('.button.fa-repeat').click(); }
      } });
      var map;

      function render(){
        $('#markdown').attr('data-markdown',cm.getValue()).renderMarkdown();
        map = [];
        $('#markdown [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
        <?if(!$id){?>localStorage.setItem('<?=$community?>.ask',cm.getValue());<?}?>
      }

      $('textarea[name="markdown"]').show().css({ position: 'absolute', opacity: 0, 'margin-top': '4px', 'margin-left': '10px' }).attr('tabindex','-1');
      $('#community').change(function(){ window.location = '?community='+$(this).val().toLowerCase(); });
      cm.on('change',_.debounce(function(){
        render();
        $('textarea[name="markdown"]').val(cm.getValue()).show();
      },500));
      cm.on('scroll', _.throttle(function(){
        var rect = cm.getWrapperElement().getBoundingClientRect();
        var m = Math.round(cm.lineAtHeight(rect.top,"window")+cm.lineAtHeight(rect.bottom,"window"))/2;
        if(cm.getScrollInfo().top<10) $('#markdown').animate({ scrollTop: 0 });
        else if(cm.getScrollInfo().top+10>(cm.getScrollInfo().height-cm.getScrollInfo().clientHeight)) $('#markdown').animate({ scrollTop: $('#markdown').prop("scrollHeight")-$('#markdown').height() });
        else $('#markdown [data-source-line="'+map.reduce(function(prev,curr) { return ((Math.abs(curr-m)<Math.abs(prev-m))?curr:prev); })+'"]')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      },200));
      <?if(!$id){?>$('input[name="title"]').on('input',function(){ localStorage.setItem('<?=$community?>.ask.title',$(this).val()); });<?}?>
      <?if(!$id&&!isset($_GET['fiddle'])){?>
        if(localStorage.getItem('<?=$community?>.ask')) cm.setValue(localStorage.getItem('<?=$community?>.ask'));
        if(localStorage.getItem('<?=$community?>.ask.title')) $('input[name="title"]').val(localStorage.getItem('<?=$community?>.ask.title'));
        if(localStorage.getItem('<?=$community?>.ask.type')) $('#type').val(localStorage.getItem('<?=$community?>.ask.type'));
      <?}?>
      $('#type').change(function(){
        $('#submit').val('submit '+$(this).val());
        $('input[name="type"').val($(this).children(":selected").text());
        <?if(!$id){?> localStorage.setItem('<?=$community?>.ask.type',$(this).val());<?}?>
      }).trigger('change');
      $('#uploadfile').change(function() { if(this.files[0].size > 2097152){ alert("File is too big — maximum 2MB"); $(this).val(''); }else{ $('#imageupload').submit(); }; });
      $('#imageupload').submit(function(){
        var d = new FormData($(this)[0]);
        $.ajax({ url: "/upload", type: "POST", data: d, processData: false, cache: false, contentType: false }).done(function(r){
          var selectionStart = cm.getCursor(), selectionEnd = cm.getCursor();
          cm.replaceSelection('!['+d.get('image').name+'](/image?hash='+r+')');
          cm.focus();
          cm.setSelection({ ch: selectionStart.ch, line: selectionStart.line },{ ch: selectionEnd.ch, line: selectionEnd.line });
          $('#imageupload').trigger('reset');
        }).fail(function(r){
          alert(r.status+' '+r.statusText+'\n'+r.responseText);
        });
        return false;
      });
      $('.button.fa-bold').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){ cm.replaceSelection('**'+cm.getSelection()+'**'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+2),line:selectionStart.line},{ch:(selectionEnd.ch+2),line:selectionEnd.line});
        }else{ cm.replaceSelection('**bold**'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+2),line:selectionStart.line},{ch:(selectionStart.ch+6),line:selectionStart.line}); }
      });
      $('.button.fa-italic').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){ cm.replaceSelection('*'+cm.getSelection()+'*'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionEnd.ch+1),line:selectionEnd.line});
        }else{ cm.replaceSelection('*italic*'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+7),line:selectionStart.line}); }
      });
      $('.button.fa-link').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false), selectionLength = cm.getSelection().length;
        if(cm.somethingSelected()){
          if(cm.getSelection().indexOf('.')===-1){ cm.replaceSelection('['+cm.getSelection()+'](https://topanswers.xyz)'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+3+selectionLength),line:selectionStart.line},{ch:(selectionStart.ch+25+selectionLength),line:selectionStart.line});
          }else{ cm.replaceSelection('[enter link description here]('+cm.getSelection()+')'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+28),line:selectionStart.line}); }
        }else{ cm.replaceSelection('[enter link description here](https://topanswers.xyz)'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+28),line:selectionStart.line}); }
      });
      $('.button.fa-quote-left').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){ cm.replaceSelection('\n> '+cm.getSelection().replace(/\n(?!$)/g,'  \n> ')+'\n'+(((cm.getSelection().length)>=cm.getLine(selectionStart.line).length)?'':'\n')); cm.focus(); cm.setSelection({ch:2,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+2),line:(selectionEnd.line+1)});
        }else{ cm.setSelection({ch: 0,line:selectionStart.line},{line:selectionStart.line}); cm.replaceSelection('> '+cm.getSelection()+'\n'); cm.focus(); cm.setSelection({ch:2,line:selectionStart.line},{line:selectionStart.line}); }
      });
      $('.button.fa-code').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){
          if(cm.getSelection().search(/\n(?=$)/g)!==-1||(cm.getCursor().ch!==0&&cm.getSelection().indexOf('\n')!==-1)){ cm.replaceSelection(((cm.getCursor().ch!==0)?'\n':'')+'```\n'+cm.getSelection().replace(/\n(?=$)/g,'')+'\n```\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+3),line:(selectionStart.line+1) });
          }else if(cm.getSelection().length===cm.getLine(selectionStart.line).length){ cm.replaceSelection('```\n'+cm.getSelection()+'\n```'); cm.focus(); cm.setSelection({ch:3,line:selectionStart.line},{ch:(selectionEnd.ch+3),line:selectionStart.line});
          }else{ cm.replaceSelection('`'+cm.getSelection()+'`'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionEnd.ch+1),line:selectionEnd.line}); }
        }else if(cm.getCursor().ch===0&&cm.getLine(selectionStart.line).length===0){ cm.replaceSelection('```\ncode\n```'); cm.focus(); cm.setSelection({ch:0,line:(selectionStart.line+1)},{ch:4,line:(selectionStart.line+1)}); 
        }else{ cm.replaceSelection('`code`'); cm.focus(); cm.setSelection({ch:(selectionStart.ch+1),line:selectionStart.line},{ch:(selectionStart.ch+5),line:selectionStart.line}); }
      });
      $('.button.fa-picture-o').click(function(){ $('#uploadfile').click(); cm.focus(); });
      $('.button.fa-list-ol').click(function(){ var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false); });
      $('.button.fa-list-ul').click(function(){ var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false); });
      $('.button.fa-undo').click(function(){ cm.undo(); cm.focus(); });
      $('.button.fa-repeat').click(function(){ cm.redo(); cm.focus(); });
      render();
      <?if(!$uuid){?>
        $('#submit').click(function(){
          if(!$('#form')[0].checkValidity()) return true;
          if(confirm('This will set a cookie to identify your account, and will post your question under a CC BY-SA license.\nYou must be 16 or over to participate at TopAnswers.')) {
            $.ajax({ type: "POST", url: '/uuid', async: false }).fail(function(r){ alert((r.status)===429?'Rate limit hit, please try again later':responseText); });
            return true;
          }else{
            return false;
          }
        });
      <?}?>
    });
  </script>
  <title><?=$id?'Edit':'Ask'?> Question - TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: #<?=$colour_light?>; height: 100%;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers <?=ucfirst($community)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <?if(!$id){?>
        <?if($uuid){?>
          <select name="type" form="form">
            <?if($community!=='meta'){?><option selected value="question">question</option><?}?>
            <option value="meta"><?=(($community==='meta')?'':'meta ')?>question</option>
            <option value="blog">blog post</option>
          </select>
          <select name="license" form="form">
            <?foreach(db("select license_id,license_name from license order by license_name") as $r){ extract($r);?>
              <option value="<?=$license_id?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
            <?}?>
          </select>
          <select name="codelicense" form="form">
            <?foreach(db("select codelicense_id,codelicense_name from codelicense order by codelicense_id<>1, codelicense_name") as $r){ extract($r);?>
              <option value="<?=$codelicense_id?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
            <?}?>
          </select>
        <?}else{?>
          <input type="hidden" form="form" name="type" value="question">
          <input type="hidden" form="form" name="license" value="6">
          <input type="hidden" form="form" name="codelicense" value="1">
        <?}?>
      <?}?>
      <input id="submit" type="submit" form="form" value="<?=$id?('update '.$question_type.(($question_type==='meta')?' question':(($question_type==='blog')?' post':''))).' under '.$license:'submit'?>" style="margin: 0.5rem;">
      <?if($uuid){?><a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2rem; display: block; height: 2.4rem;" src="/identicon?id=<?=ccdb("select account_id from login")?>"></a><?}?>
    </div>
  </header>
  <form id="form" method="POST" action="/question" style="display: flex; justify-content: center; flex: 1 0 0; padding: 2vmin; overflow-y: hidden;">
    <?if($id){?>
      <input type="hidden" name="action" value="change">
      <input type="hidden" name="id" value="<?=$id?>">
    <?}else{?>
      <input type="hidden" name="action" value="new">
      <input type="hidden" name="community" value="<?=$community?>">
    <?}?>
    <main style="display: flex; position: relative; justify-content: center; flex: 0 1 120rem; overflow-y: auto; flex-direction: column;">
      <input name="title" style="flex 0 0 auto; border: 1px solid #<?=$colour_dark?>; padding: 3px; border-radius: 0.2rem;" placeholder="your question title" minlength="5" maxlength="200" autocomplete="off" autofocus required<?=$id?' value="'.htmlspecialchars($question_title).'"':''?>>
      <div style="flex: 0 0 2vmin;"></div>
      <div style="display: flex; flex: 1 0 0; overflow: hidden;">
        <div style="flex: 0 0 1.6em;">
          <div id="markdown-editor-buttons" style="display: flex; flex-direction: column; background: #<?=$colour_mid?>; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem 0 0 0.2rem; border-right: none; padding: 0.3em;">
            <i title="Bold (Ctrl + B)" class="button fa fw fa-bold"></i>
            <i title="Italic (Ctrl + I)" class="button fa fw fa-italic"></i>
            <br style="margin-bottom: 1em;">
            <i title="Hyperlink (Ctrl + L)" class="button fa fw fa-link"></i>
            <i title="Blockquote (Ctrl + Q)" class="button fa fw fa-quote-left"></i>
            <i title="Code (Ctrl + K)" class="button fa fw fa-code"></i>
            <i title="Upload Image (Ctrl + G)" class="button fa fw fa-picture-o"></i>
        <!--<br style="margin-bottom: 1em;">
            <i title="Ordered List (Ctrl + O)" class="button fa fw fa-list-ol"></i>
            <i title="Unordered List (Ctrl + U)" class="button fa fw fa-list-ul"></i>-->
            <br style="margin-bottom: 1em;">
            <i title="Undo (Ctrl + Z)" class="button fa fw fa-undo"></i>
            <i title="Redo (Ctrl + Y)" class="button fa fw fa-repeat"></i>
          </div>
        </div>
        <div style="flex: 1 0 0; overflow-x: hidden; max-width: calc(50vw - 3vmin);">
          <textarea name="markdown" minlength="50" maxlength="50000" autocomplete="off" rows="1" required placeholder="your question"><?=$id?htmlspecialchars($question_markdown):(isset($_GET['fiddle'])?('I have a question about this fiddle:'.PHP_EOL.PHP_EOL.'<>https://dbfiddle.uk?rdbms='.$_GET['rdbms'].'&fiddle='.$_GET['fiddle']):'')?></textarea>
        </div>
        <div style="flex: 0 0 2vmin;"></div>
        <div id="markdown" class="markdown" style="flex: 1 0 0; overflow-x: hidden; max-width: calc(50vw - 3vmin); background-color: white; padding: 0.6rem; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem; overflow-y: auto;"></div>
      </div>
    </main>
  </form>
  <form id="imageupload" action="/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
</body>   
</html>   
