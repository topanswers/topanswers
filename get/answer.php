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
extract(cdb("select account_id,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense
                   ,answer_id,answer_markdown,answer_license
                   ,question_id,question_title,question_markdown
                   ,community_name,community_code_language,community_tables_are_monospace,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
                   ,my_community_regular_font_name,my_community_monospace_font_name
             from one"));
$cookies = isset($_COOKIE['uuid'])?'Cookie: uuid='.$_COOKIE['uuid'].'; '.(isset($_COOKIE['environment'])?'environment='.$_COOKIE['environment'].'; ':''):'';
ob_start(function($html){ return preg_replace('~\n\s*<~','<',$html); });
?>
<!doctype html>
<html style="--rgb-dark: <?=$community_rgb_dark?>;
             --rgb-mid: <?=$community_rgb_mid?>;
             --rgb-light: <?=$community_rgb_light?>;
             --rgb-highlight: <?=$community_rgb_highlight?>;
             --rgb-warning: <?=$community_rgb_warning?>;
             --regular-font-family: '<?=$my_community_regular_font_name?>', serif;
             --monospace-font-family: '<?=$my_community_monospace_font_name?>', monospace;
             --markdown-table-font-family: <?=$community_tables_are_monospace?"'".$my_community_monospace_font_name."', monospace":"'".$my_community_regular_font_name."', serif;"?>
             ">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/lib/codemirror/codemirror.css">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <link rel="stylesheet" href="/post.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: 14px; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    textarea, pre, code, .CodeMirror { font-family: '<?=$my_community_monospace_font_name?>', monospace; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }
    .button { background: none; border: none; padding: 0; cursor: pointer; outline: inherit; margin: 0; }

    #markdown-editor-buttons { display: flex; flex-direction: column; background: rgb(var(--rgb-mid)); border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px 0 0 3px; border-right: none; padding: 5px; }
    #markdown-editor-buttons i { padding: 4px; font-size: 15px; text-align: center; }
    #markdown-editor-buttons i:hover { color: rgb(var(--rgb_highlight)); cursor: pointer; background-color: rgb(var(--rgb_light)); border-radius: 4px; }
    #markdown-editor-buttons i:last-child { margin-bottom: 0; }

    .CodeMirror { height: 100%; border: 1px solid rgb(var(--rgb-dark)); font-size: 15px; border-radius: 0 3px 3px 3px; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lib/js.cookie.js"></script>
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
        $('#answer').attr('data-markdown',cm.getValue()).renderMarkdown(function(){
          $('.post:not(.processed) .when').each(function(){
            $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago');
            $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
          });
          $('.post').addClass('processed');
        });
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
      $('[name="license"],[name="codelicense"]').on('change',function(){
        if($(this).children('option:selected').data('versioned')===true){
          $(this).next().css('color','unset').find('input').prop('disabled',false);
        }else{
          $(this).next().css('color','rgb(var(--rgb-mid))').find('input').prop('checked',false).prop('disabled',true);
        }
      }).trigger('change');
      render();
      $('#question .markdown').renderMarkdown();
    });
  </script>
  <title><?=$answer_id?'Edit Answer':'Answer Question'?> - TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: rgb(var(--rgb-light)); height: 100%;">
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
      <?if(!$answer_id){?>
        <span class="element">
          <select name="license" form="form">
            <?foreach(db("select license_id,license_name,license_is_versioned from license") as $r){ extract($r);?>
              <option value="<?=$license_id?>" data-versioned="<?=$license_is_versioned?'true':'false'?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
            <?}?>
          </select>
          <label><input type="checkbox" name="license-orlater" form="form"<?=$account_permit_later_license?'checked':''?>>or later</label>
        </span>
        <span class="element">
          <select name="codelicense" form="form">
            <?foreach(db("select codelicense_id,codelicense_name,codelicense_is_versioned from codelicense") as $r){ extract($r);?>
              <option value="<?=$codelicense_id?>" data-versioned="<?=$codelicense_is_versioned?'true':'false'?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
            <?}?>
          </select>
          <label><input type="checkbox" name="codelicense-orlater" form="form"<?=$account_permit_later_codelicense?'checked':''?>>or later</label>
        </span>
      <?}?>
      <input class="element" id="submit" type="submit" form="form" value="<?=$answer_id?'update answer under '.$answer_license:'post answer'?>">
      <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
    </div>
  </header>
  <form id="form" method="POST" action="//post.topanswers.xyz/answer" style="display: flex; flex-direction: column; flex: 1 0 0; padding: 16px; overflow-y: hidden;">
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
        <div id="question" style="display: flex; flex-direction: column; background-color: white; border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; overflow: hidden;">
          <div style="flex: 0 0 auto; padding: 8px; font-size: 19px; border-bottom: 1px solid rgb(var(--rgb-dark));"><a style="color: black; text-decoration: none;" href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></div>
          <div class="markdown" data-markdown="<?=$question_markdown?>" style="flex: 1 0 auto; overflow-y: auto; padding: 0.6em;"></div>
        </div>
      </div>
      <div style="flex: 0 0 2vmin;"></div>
      <div style="flex: 0 0 1.6em;">
        <div id="markdown-editor-buttons">
          <i title="Bold (Ctrl + B)" class="button fa fw fa-bold"></i>
          <i title="Italic (Ctrl + I)" class="button fa fw fa-italic"></i>
          <br style="margin-bottom: 15px;">
          <i title="Hyperlink (Ctrl + L)" class="button fa fw fa-link"></i>
          <i title="Blockquote (Ctrl + Q)" class="button fa fw fa-quote-left"></i>
          <i title="Code (Ctrl + K)" class="button fa fw fa-code"></i>
          <i title="Upload Image (Ctrl + G)" class="button fa fw fa-picture-o"></i>
          <br style="margin-bottom: 15px;">
          <i title="Undo (Ctrl + Z)" class="button fa fw fa-undo"></i>
          <i title="Redo (Ctrl + Y)" class="button fa fw fa-repeat"></i>
        </div>
      </div>
      <div style="flex: 0 1 60em; max-width: calc(40vw - 2.67vmin); position: relative;">
        <textarea name="markdown" minlength="50" maxlength="50000" autocomplete="off" rows="1" autofocus required placeholder="your answer"><?=$answer_id?$answer_markdown:''?></textarea>
      </div>
      <div style="flex: 0 0 2vmin;"></div>
      <div id="answer" class="markdown noexpander" style="flex: 0 1 60em; max-width: calc(40vw - 2.67vmin); background-color: white;  padding: 7px; font-size: 16px; border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; overflow-y: auto;"></div>
    </main>
  </form>
  <form id="imageupload" action="//post.topanswers.xyz/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
</body>   
</html>   
<?ob_end_flush();
