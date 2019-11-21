<?    
include 'db.php';
include 'nocache.php';
$uuid = $_COOKIE['uuid']??'';
ccdb("select login($1)",$uuid);
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
      extract(cdb("select sesite_url,account_id,account_community_se_user_id from community join sesite on community_sesite_id=sesite_id natural join my_account_community where community_name=$1",$_POST['community']));
      $doc = new DOMDocument();
      $doc->loadHTML(file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//div[@id='question-header']/h1/a");
      $title = $elements[0]->childNodes[0]->nodeValue;
      $elements = $xpath->query("//div[@id='question']//div[contains(concat(' ', @class, ' '), ' owner ')]//div[contains(concat(' ', @class, ' '), ' user-details ')]/a");
      $seaid = explode('/',$elements[0]->getAttribute('href'))[2];
      $seuser = explode('/',$elements[0]->getAttribute('href'))[3];
      $doc = new DOMDocument();
      $doc->loadHTML(file_get_contents($sesite_url.'/posts/'.$_POST['seqid'].'/edit'));
      $xpath = new DOMXpath($doc);
      $elements = $xpath->query("//textarea[@id='wmd-input-".$_POST['seqid']."']");
      $markdown = $elements[0]->textContent;
      $id=ccdb("select new_sequestion((select community_id from community where community_name=$1),$2,$3,$4,$5,$6)",$_POST['community'],$title,$markdown,$_POST['seqid'],$seaid,$seuser);
      if($account_community_se_user_id){
        $doc = new DOMDocument();
        $doc->loadHTML(file_get_contents($sesite_url.'/questions/'.$_POST['seqid']));
        $xpath = new DOMXpath($doc);
        $elements = $xpath->query("//div[contains(concat(' ', @class, ' '), ' answer ') and "
                                 ."boolean(.//div[contains(concat(' ', @class, ' '), ' post-signature ') and not(following-sibling::div[contains(concat(' ', @class, ' '), ' post-signature ')])]"
                                 ."//div[contains(concat(' ', @class, ' '), ' user-details ')]/a[contains(@href,'/".$account_community_se_user_id."/')])]");
        foreach($elements as $element){
          $aid = explode('-',$element->getAttribute('id'))[1];
          $doc = new DOMDocument();
          $doc->loadHTML(file_get_contents($sesite_url.'/posts/'.$aid.'/edit'));
          $xpath = new DOMXpath($doc);
          $elements = $xpath->query("//textarea[@id='wmd-input-".$aid."']");
          $markdown = preg_replace('/<!-- -->/','',$elements[0]->textContent);
          db("select new_answer($1,$2,account_license_id,account_codelicense_id) from my_account",$id,$markdown);
        }
      }
      header('Location: /'.$_POST['community'].'?q='.$id);
      exit;
    case 'change':
      db("select change_question($1,$2,$3)",$id,$_POST['title'],$_POST['markdown']);
      header('Location: /'.ccdb("select community_name from question natural join community where question_id=$1",$id).'?q='.$id);
      exit;
    case 'vote': exit(ccdb("select vote_question($1,$2)",$_POST['id'],$_POST['votes']));
    default: fail(400,'unrecognized action');
  }
}
if($id) {
  ccdb("select count(*) from question where question_id=$1",$id)==='1' || die('invalid question id');
  $community = ccdb("select community_name from question natural join community where question_id=$1",$id);
  extract(cdb("select question_type,question_title,question_markdown
                    , license_name||(case when codelicense_id<>1 then ' + '||codelicense_name else '' end) license
               from question natural join license natural join codelicense
               where question_id=$1",$id));
}else{
  if(!isset($_GET['community'])) die('Community not set');
  $community = $_GET['community'];
  ccdb("select count(*) from community where community_name=$1",$community)==='1' or die('invalid community');
}
extract(cdb("select encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from community
             where community_name=$1",$community));
extract(cdb("select account_license_id,account_codelicense_id from my_account"));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'Quattrocento', sans-serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/highlightjs/default.css">
  <link rel="stylesheet" href="/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Regular.ttf') format('truetype'); font-weight: normal; font-style: normal; }
    @font-face { font-family: 'Quattrocento'; src: url('/Quattrocento-Bold.ttf') format('truetype'); font-weight: bold; font-style: normal; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    header select { margin-right: 0.5rem; }

    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .question { margin-bottom: 0.5rem; padding: 0.5rem; border: 1px solid darkgrey; }
    .spacer { flex: 0 0 auto; min-height: 1rem; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }

    #markdown { overflow-wrap: break-word; }
    #markdown :first-child { margin-top: 0; }
    #markdown :last-child { margin-bottom: 0; }
    #markdown ul { padding-left: 2em; }
    #markdown li { margin: 0.5em 0; }
    #markdown img { max-height: 20em; max-width: 100%; }
    #markdown hr { background-color: #<?=$colour_mid?>; border: 0; height: 2px; }
    #markdown table { border-collapse: collapse; table-layout: fixed; }
    #markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
    #markdown td, #markdown th { white-space: nowrap; border: 1px solid black; padding: 0.2em; }
    #markdown blockquote { padding: 0.5rem; margin-left: 0.7rem; margin-right: 0; border-left: 0.3rem solid #<?=$colour_mid?>; background-color: #<?=$colour_light?>40; }
    #markdown code { padding: 0 0.2em; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1em; }
    #markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; }
    #markdown-editor-buttons i { padding: 0.2em; text-align: center; }
    #markdown-editor-buttons i:hover { color: #<?=$colour_highlight?>; cursor: pointer; background-color: #<?=$colour_light?>; border-radius: 0.2rem; }
    #markdown-editor-buttons i:last-child { margin-bottom: 0; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 0 0.2rem 0.2rem 0.2rem; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }

    .dbfiddle .CodeMirror { height: auto; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 0.2rem; }
    .dbfiddle .CodeMirror-scroll { margin-bottom: -30px; }
    .dbfiddle .tablewrapper { margin-top: 0.5rem; }
    .dbfiddle>div { margin-top: 0.5rem; }
    .dbfiddle fieldset { overflow: hidden; min-width: 0; }
  </style>
  <script src="/lodash.js"></script>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-inject-linenumbers.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
  <script src="/markdown-it-emoji.js"></script>
  <script src="/markdown-it-footnote.js"></script>
  <script src="/markdown-it-deflist.js"></script>
  <script src="/markdown-it-abbr.js"></script>
  <script src="/highlightjs/highlight.js"></script>
  <script src="/lightbox2/js/lightbox.min.js"></script>
  <script src="/moment.js"></script>
  <script src="/favico.js"></script>
  <script src="codemirror/codemirror.js"></script>
  <script src="codemirror/markdown.js"></script>
  <script src="codemirror/placeholder.js"></script>
  <script>
    hljs.initHighlightingOnLoad();
    $(function(){
      var md = window.markdownit({ linkify: true, highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }})
                     .use(window.markdownitSup).use(window.markdownitSub).use(window.markdownitEmoji).use(window.markdownitDeflist).use(window.markdownitFootnote).use(window.markdownitAbbr).use(window.markdownitInjectLinenumbers);
      var cm = CodeMirror.fromTextArea($('textarea')[0],{ lineWrapping: true, extraKeys: {
        Home: "goLineLeft",
        End: "goLineRight",
        'Ctrl-B': function(){ $('.button.fa-bold').click(); },
        'Ctrl-I': function(){ $('.button.fa-italic').click(); },
        'Ctrl-Q': function(){ $('.button.fa-quote-left').click(); },
        'Ctrl-K': function(){ $('.button.fa-code').click(); },
        'Ctrl-G': function(){ $('.button.fa-picture-o').click(); }
      } });
      var map;

      function fiddleMarkdown(){
        function addfiddle(o,r){
          var f = $(r).replaceAll(o);
          f.find('textarea').each(function(){ CodeMirror.fromTextArea($(this)[0],{ viewportMargin: Infinity }); });
          f.find('input').click(function(){
            f.css('opacity',0.5);
            $(this).replaceWith('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
            $.post('https://test.dbfiddle.uk/run',{ rdbms: f.data('rdbms'), statements: JSON.stringify(f.find('fieldset>textarea').map(function(){ return $(this).next('.CodeMirror')[0].CodeMirror.getValue(); }).get()) })
                .done(function(r){
              $.get('/dbfiddle?rdbms='+f.data('rdbms')+'&fiddle='+r).done(function(r){
                addfiddle(f,r);
              });
            });
          });
        }
        $(this).find('a[href*="//dbfiddle.uk/?"]')
               .filter(function(){ return $(this).attr('href').match(/https?:\/\/dbfiddle\.uk\/\?.*fiddle=[0-91-f]{32}/)&&$(this).parent().is('p')&&($(this).parent().text()===('<>'+$(this).attr('href'))); })
               .each(function(){
          var t = $(this);
          $.get('/dbfiddle?'+t.attr('href').split('?')[1]).done(function(r){
            addfiddle(t.parent(),r);
          });
        });
      }

      function render(){
        $('#markdown').html(md.render(cm.getValue()));
        $('#markdown table').wrap('<div class="tablewrapper">');
        map = [];
        $('#markdown [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
        <?if(!$id){?>localStorage.setItem('<?=$community?>.ask',cm.getValue());<?}?>
        $('#markdown').each(fiddleMarkdown);
      }

      $('textarea[name="markdown"]').show().css({ position: 'absolute', opacity: 0, 'margin-top': '4px', 'margin-left': '10px' }).attr('tabindex','-1');
      $('#community').change(function(){ window.location = '?community='+$(this).val().toLowerCase(); });
      cm.on('change',function(){
        render();
        $('textarea[name="markdown"]').val(cm.getValue()).show();
      });
      cm.on('scroll', _.throttle(function(){
        var rect = cm.getWrapperElement().getBoundingClientRect();
        var m = Math.round(cm.lineAtHeight(rect.top,"window")+cm.lineAtHeight(rect.bottom,"window"))/2;
        if(cm.getScrollInfo().top<10) $('#markdown').animate({ scrollTop: 0 });
        else if(cm.getScrollInfo().top+10>(cm.getScrollInfo().height-cm.getScrollInfo().clientHeight)) $('#markdown').animate({ scrollTop: $('#markdown').prop("scrollHeight")-$('#markdown').height() });
        else $('#markdown [data-source-line="'+map.reduce(function(prev,curr) { return ((Math.abs(curr-m)<Math.abs(prev-m))?curr:prev); })+'"]')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      },200));
      <?if(!$id){?>$('input[name="title"]').on('input',function(){ localStorage.setItem('<?=$community?>.ask.title',$(this).val()); });<?}?>
      <?if(!$id){?>
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
      render();
    });
  </script>
  <title><?=$id?'Edit':'Ask'?> Question | <?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: #<?=$colour_light?>; height: 100%;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers <?=ucfirst($community)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <?if(!$id){?>
        <select name="type" form="form"><?if($community!=='meta'){?><option selected value="question">question</option><?}?><option value="meta"><?=(($community==='meta')?'':'meta ')?>question</option><option value="blog">blog post</option></select>
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
      <?}?>
      <input id="submit" type="submit" form="form" value="<?=$id?('update '.$question_type.(($question_type==='meta')?' question':(($question_type==='blog')?' post':''))).' under '.$license:'submit'?>" style="margin: 0.5rem;">
      <a href="/profile"><img style="background-color: #<?=$colour_mid?>; padding: 0.2rem; display: block; height: 2.4rem;" src="/identicon.php?id=<?=ccdb("select account_id from login")?>"></a>
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
            <i title="Blockquote (Ctrl + Q)" class="button fa fw fa-quote-left"></i>
            <i title="Code (Ctrl + K)" class="button fa fw fa-code"></i>
            <i title="Upload Image (Ctrl + G)" class="button fa fw fa-picture-o"></i>
        <!--<br style="margin-bottom: 1em;">
            <i title="Ordered List (Ctrl + O)" class="button fa fw fa-list-ol"></i>
            <i title="Unordered List (Ctrl + U)" class="button fa fw fa-list-ul"></i>-->
          </div>
        </div>
        <div style="flex: 1 0 0; overflow-x: hidden; max-width: calc(50vw - 3vmin);">
          <textarea name="markdown" minlength="50" maxlength="50000" autocomplete="off" rows="1" required placeholder="your question"><?=$id?htmlspecialchars($question_markdown):''?></textarea>
        </div>
        <div style="flex: 0 0 2vmin;"></div>
        <div id="markdown" style="flex: 1 0 0; overflow-x: hidden; max-width: calc(50vw - 3vmin); background-color: white; padding: 0.6rem; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem; overflow-y: auto;"></div>
      </div>
    </main>
  </form>
  <form id="imageupload" action="/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
</body>   
</html>   
