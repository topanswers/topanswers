<?    
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to answer,pg_temp");
if(isset($_GET['id'])){
  ccdb("select login_answer(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']) || fail(403,'access denied');
}else{
  ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['question']??'') || fail(403,'access denied');
}
extract(cdb("select account_id,account_license_id,account_codelicense_id
                   ,answer_id,answer_markdown,answer_license
                   ,question_id,question_title,question_markdown
                   ,community_name,community_code_language,colour_dark,colour_mid,colour_light,colour_highlight
                   ,my_community_regular_font_name,my_community_monospace_font_name
             from one"));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: smaller;">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    textarea, pre, code, .CodeMirror { font-family: '<?=$my_community_monospace_font_name?>', monospace; }
    header { font-size: 1rem; background-color: #<?=$colour_dark?>; white-space: nowrap; }
    header select { margin-right: 0.5rem; }

    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }
    .answer { margin-bottom: 0.5rem; padding: 0.5rem; border: 1px solid darkgrey; }
    .spacer { flex: 0 0 auto; min-height: 1rem; width: 100%; text-align: right; font-size: smaller; font-style: italic; color: #<?=$colour_dark?>60; background-color: #<?=$colour_mid?>; }
    .frame { border: 1px solid #<?=$colour_dark?>; margin: 2px; outline: 1px solid #<?=$colour_light?>; background-color: #<?=$colour_light?>; }
    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 4px; }

    #markdown-editor-buttons i { padding: 0.2em; margin-bottom: 0.3em; text-align: center; }
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
      var cm = CodeMirror.fromTextArea($('textarea')[0],{ lineWrapping: true, mode: 'markdown', inputStyle: 'contenteditable', spellcheck: true, extraKeys: {
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
        $('#answer').attr('data-markdown',cm.getValue()).renderMarkdown();
        map = [];
        $('#answer [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
        <?if(!$answer_id){?>localStorage.setItem('<?=$community_name?>.answer.<?=$question_id?>',cm.getValue());<?}?>
      }

      $('textarea[name="markdown"]').show().css({ position: 'absolute', opacity: 0, top: '4px', left: '10px' }).attr('tabindex','-1');
      $('#community').change(function(){ window.location = '?community='+$(this).val().toLowerCase(); });
      cm.on('change',_.debounce(function(){
        render();
        $('textarea[name="markdown"]').val(cm.getValue()).show();
      },500));
      cm.on('scroll', _.throttle(function(){
        var rect = cm.getWrapperElement().getBoundingClientRect();
        var m = Math.round(cm.lineAtHeight(rect.top,"window")+cm.lineAtHeight(rect.bottom,"window"))/2;
        if(cm.getScrollInfo().top<10) $('#answer').animate({ scrollTop: 0 });
        else if(cm.getScrollInfo().top+10>(cm.getScrollInfo().height-cm.getScrollInfo().clientHeight)) $('#answer').animate({ scrollTop: $('#answer').prop("scrollHeight")-$('#answer').height() });
        else $('#answer [data-source-line="'+map.reduce(function(prev,curr) { return ((Math.abs(curr-m)<Math.abs(prev-m))?curr:prev); })+'"]')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      },200));
      <?if(!$answer_id){?>
        if(localStorage.getItem('<?=$community_name?>.answer.<?=$question_id?>')) cm.setValue(localStorage.getItem('<?=$community_name?>.answer.<?=$question_id?>'));
      <?}?>
      $('#uploadfile').change(function() { if(this.files[0].size > 2097152){ alert("File is too big — maximum 2MB"); $(this).val(''); }else{ $('#imageupload').submit(); }; });
      $('#imageupload').submit(function(){
        var d = new FormData($(this)[0]);
        $.post({ url: "//post.topanswers.xyz/upload", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
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
        if(cm.somethingSelected()){ cm.replaceSelection('**'+cm.getSelection()+'**'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+2), line: selectionStart.line },{ ch: (selectionEnd.ch+2), line: selectionEnd.line });
        }else{ cm.replaceSelection('**bold**'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+2), line: selectionStart.line },{ ch: (selectionStart.ch+6), line: selectionStart.line }); }
      });
      $('.button.fa-italic').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){ cm.replaceSelection('*'+cm.getSelection()+'*'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+1), line: selectionStart.line },{ ch: (selectionEnd.ch+1), line: selectionEnd.line });
        }else{ cm.replaceSelection('*italic*'); cm.focus(); cm.setSelection({ ch: (selectionStart.ch+1), line: selectionStart.line },{ ch: (selectionStart.ch+7), line: selectionStart.line }); }
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
      $('.button.fa-undo').click(function(){ cm.undo(); cm.focus(); });
      $('.button.fa-repeat').click(function(){ cm.redo(); cm.focus(); });
      render();
      $('#license').change(function(){ $('input[name="license"').val($(this).val()); });
      $('#codelicense').change(function(){ $('input[name="codelicense"').val($(this).val()); });
      $('#question .markdown').renderMarkdown();
    });
  </script>
  <title><?=$answer_id?'Edit Answer':'Answer Question'?> - TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: #<?=$colour_light?>; height: 100%;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community_name?>" style="color: #<?=$colour_mid?>;">TopAnswers <?=ucfirst($community_name)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <?if(!$answer_id){?>
        <select name="license" form="form">
          <?foreach(db("select license_id,license_name from license") as $r){ extract($r);?>
            <option value="<?=$license_id?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
          <?}?>
        </select>
        <select name="codelicense" form="form">
          <?foreach(db("select codelicense_id,codelicense_name from codelicense") as $r){ extract($r);?>
            <option value="<?=$codelicense_id?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
          <?}?>
        </select>
      <?}?>
      <input id="submit" type="submit" form="form" value="<?=$answer_id?'update answer under '.$answer_license:'post answer'?>" style="margin: 0.5rem;">
      <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
    </div>
  </header>
  <form id="form" method="POST" action="//post.topanswers.xyz/answer" style="display: flex; flex-direction: column; flex: 1 0 0; padding: 2vmin; overflow-y: hidden;">
    <?if($answer_id){?>
      <input type="hidden" name="action" value="change">
      <input type="hidden" name="id" value="<?=$answer_id?>">
    <?}else{?>
      <input type="hidden" name="action" value="new">
      <input type="hidden" name="community" value="<?=$community_name?>">
      <input type="hidden" name="question" value="<?=$question_id?>">
    <?}?>
    <main style="display: flex; position: relative; justify-content: center; flex: 1 0 0; overflow-y: auto;">
      <div style="flex: 0 1.5 50em; max-width: 20vw; overflow-x: hidden;">
        <div id="question" style="display: flex; flex-direction: column; background-color: white; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem; overflow: hidden;">
          <div style="flex: 0 0 auto; padding: 8px; font-size: 19px; border-bottom: 1px solid #<?=$colour_dark?>;"><?=$question_title?></div>
          <div class="markdown" data-markdown="<?=$question_markdown?>" style="flex: 1 0 auto; overflow-y: auto; padding: 0.6em;"></div>
        </div>
      </div>
      <div style="flex: 0 0 2vmin;"></div>
      <div style="flex: 0 0 1.6em;">
        <div id="markdown-editor-buttons" style="display: flex; flex-direction: column; background: #<?=$colour_mid?>; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem 0 0 0.2rem; border-right: none; padding: 0.3em;">
          <i title="Bold (Ctrl + B)" class="button fa fw fa-bold"></i>
          <i title="Italic (Ctrl + I)" class="button fa fw fa-italic"></i>
          <br style="margin-bottom: 1em;">
          <i title="Hyperlink (Ctrl + L)" class="button fa fw fa-link"></i>
          <i title="Blockquote (Ctrl + Q)" class="button fa fw fa-quote-left"></i>
          <i title="Code (Ctrl + K)" class="button fa fw fa-code"></i>
          <i title="Upload Image (Ctrl + G)" class="button fa fw fa-picture-o"></i>
          <br style="margin-bottom: 1em;">
          <i title="Undo (Ctrl + Z)" class="button fa fw fa-undo"></i>
          <i title="Redo (Ctrl + Y)" class="button fa fw fa-repeat"></i>
        </div>
      </div>
      <div style="flex: 0 1 60em; max-width: calc(40vw - 2.67vmin); position: relative;">
        <textarea name="markdown" minlength="50" maxlength="50000" autocomplete="off" rows="1" autofocus required placeholder="your answer"><?=$answer_id?$answer_markdown:''?></textarea>
      </div>
      <div style="flex: 0 0 2vmin;"></div>
      <div id="answer" class="markdown" style="flex: 0 1 60em; max-width: calc(40vw - 2.67vmin); background-color: white; padding: 0.6rem; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem; overflow-y: auto;"></div>
    </main>
  </form>
  <form id="imageupload" action="//post.topanswers.xyz/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
</body>   
</html>   
