<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to question,pg_temp");
if(isset($_GET['id'])){
  $auth = ccdb("select login_question(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']);
  $auth || fail(403,'access denied');
}else{
  $auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']??'');
  $auth||(($_GET['community']==='databases')&&isset($_GET['rdbms'])&&isset($_GET['fiddle'])) || fail(403,'need to be logged in to visit this page unless from a fiddle');
}
extract(cdb("select account_id,account_is_dev,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense
                  , account_license_name||(case when account_permit_later_license then ' or later' else '' end)
                       ||(case when account_has_codelicense then ' + '||account_codelicense_name||(case when account_permit_later_codelicense then ' or later' else '' end) else '' end) account_license
                   ,community_id,community_name,community_display_name,community_code_language,community_tables_are_monospace,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
                   ,my_community_regular_font_name,my_community_monospace_font_name
                   ,question_id,question_title,question_markdown,question_se_question_id
                  , question_license_name||(case when question_permit_later_license then ' or later' else '' end)
                       ||(case when question_has_codelicense then ' + '||question_codelicense_name||(case when question_permit_later_codelicense then ' or later' else '' end) else '' end) license
                   ,question_is_deleted,question_answered_by_me
                   ,question_when
                   ,question_account_id,question_account_is_me,question_account_name,question_account_is_imported
                   ,question_license_href,question_has_codelicense,question_codelicense_name
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
             --rgb-white: 255, 255, 255;
             --rgb-black: 0, 0, 0;
             --regular-font-family: '<?=$my_community_regular_font_name?>', serif;
             --monospace-font-family: '<?=$my_community_monospace_font_name?>', monospace;
             --markdown-table-font-family: <?=$community_tables_are_monospace?"'".$my_community_monospace_font_name."', monospace":"'".$my_community_regular_font_name."', serif;"?>
             ">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/lightbox2/css/lightbox.min.css">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <link rel="stylesheet" href="/post.css">
  <link rel="icon" href="/communityicon?community=<?=$community_name?>" type="image/png">
  <style>
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    body { display: flex; flex-direction: column; background: rgb(var(--rgb-mid)); }
    main { display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 32px 1fr; grid-gap: 10px; padding: 10px; max-width: 3000px; margin: 0 auto; height: 100%; }
    textarea, pre, code, .CodeMirror { font-family: '<?=$my_community_monospace_font_name?>', monospace; }
    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }

    #form { flex: 1 0 0; min-height: 0; }
    #title { grid-area: 1 / 1 / 2 / 3; border: 1px solid rgba(var(--rgb-dark),0.6); padding: 5px; font-size: 18px; font-family: var(--regular-font-family); }
    #codemirror-container { grid-area: 2 / 1 / 3 / 2; position: relative; margin-left: 35px; min-height: 0; min-width: 0; }
    #markdown { grid-area: 2 / 2 / 3 / 3; background: rgb(var(--rgb-white)); padding: 8px; border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 3px; overflow-y: auto; }

    #editor-buttons { grid-area: 2 / 1 / 3 / 2; justify-self: start; min-height: 0; }
    #editor-buttons>div { display: flex; flex-direction: column; background: rgb(var(--rgb-light)); border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 3px 0 0 3px; padding: 5px; }
    #editor-buttons>div i { padding: 4px; text-align: center; width: 24px; height: 24px; text-align: center; }
    #editor-buttons>div i:hover { box-shadow: 0 0 0 1px rgba(var(--rgb-dark),0.6) inset; cursor: pointer; background: rgb(var(--rgb-mid)); border-radius: 3px; }
    #editor-buttons>div i:last-child { margin-bottom: 0; }
    #editor-buttons>div br { margin-bottom: 12px; }

    #codemirror-container .CodeMirror { height: 100%; border: 1px solid rgba(var(--rgb-dark),0.6); border-radius: 0 3px 3px 3px; }
    #codemirror-container .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    #codemirror-container .CodeMirror-wrap pre { word-break: break-word; }

    @media (max-width: 576px){
      main { grid-template-columns: 1fr; grid-template-rows: 32px 1fr 1fr; padding: 2px; grid-gap: 2px; }
      #title { grid-area: 1 / 1 / 2 / 2; }
      #codemirror-container { grid-area: 2 / 1 / 3 / 2; margin: 35px 0 0 0; }
      #markdown { grid-area: 3 / 1 / 4 / 2; }
      #editor-buttons { grid-area: 2 / 1 / 3 / 2; }
      #editor-buttons>div { flex-direction: row; border-radius: 3px 3px 0 0; }
      #editor-buttons>div br { margin: 0 12px 0 0; }
    }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <?require '../markdown.php';?>
  <script src="/lib/lightbox2/js/lightbox.min.js"></script>
  <script src="/lib/moment.js"></script>
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
        'Ctrl-D': function(){ $('.button.fa-database').click(); },
        'Ctrl-O': function(){ $('.button.fa-list-ol').click(); },
        'Ctrl-U': function(){ $('.button.fa-list-ul').click(); },
        'Ctrl-Z': function(){ $('.button.fa-undo').click(); },
        'Ctrl-Y': function(){ $('.button.fa-repeat').click(); }
      } });
      var map;

      $(window).resize(_.debounce(function(){ $('body').height(window.innerHeight); })).trigger('resize');

      function render(){
        var promises = [];
        $('#markdown').attr('data-markdown',cm.getValue()).renderMarkdown(promises);
        Promise.allSettled(promises).then(() => {
          $('.post:not(.processed) .when').each(function(){
            $(this).text(moment.duration($(this).data('seconds'),'seconds').humanize()+' ago');
            $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
          });
          $('.post').addClass('processed');
        });
        map = [];
        $('#markdown [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
        <?if(!$question_id){?>localStorage.setItem('<?=$community_name?>.ask',cm.getValue());<?}?>
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
      <?if(!$question_id){?>$('input[name="title"]').on('input',function(){ localStorage.setItem('<?=$community_name?>.ask.title',$(this).val()); });<?}?>
      <?if(!$question_id&&!isset($_GET['fiddle'])){?>
        if(localStorage.getItem('<?=$community_name?>.ask')) cm.setValue(localStorage.getItem('<?=$community_name?>.ask'));
        if(localStorage.getItem('<?=$community_name?>.ask.title')) $('input[name="title"]').val(localStorage.getItem('<?=$community_name?>.ask.title'));
        if(localStorage.getItem('<?=$community_name?>.ask.type')) $('#type').val(localStorage.getItem('<?=$community_name?>.ask.type'));
      <?}?>
      $('#type').change(function(){
        $('#submit').val('submit '+$(this).val());
        $('input[name="type"').val($(this).children(":selected").text());
        <?if(!$question_id){?> localStorage.setItem('<?=$community_name?>.ask.type',$(this).val());<?}?>
      }).trigger('change');
      $('#uploadfile').change(function() { if(this.files[0].size > 2097152){ alert("File is too big — maximum 2MB"); $(this).val(''); }else{ $('#imageupload').submit(); }; });
      $('#imageupload').submit(function(){
        var d = new FormData($(this)[0]);
        $.ajax({ url: "//post.topanswers.xyz/upload", type: "POST", data: d, processData: false, cache: false, contentType: false, xhrFields: { withCredentials: true } }).done(function(r){
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
      $('.button.fa-database').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){ cm.replaceSelection(cm.getSelection()+'\n\n<> dbfiddle url\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+2)},{ch:15,line:(selectionStart.line+2)});
        }else{ cm.replaceSelection('\n\n<> dbfiddle url\n\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+2)},{ch:15,line:(selectionStart.line+2)}); }
      });
      $('.button.fa-list-ol').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){
          if(selectionStart.ch===0){ cm.replaceSelection('1. '+cm.getSelection()); cm.focus(); cm.setSelection({ch:3,line:selectionStart.line},{ch:(selectionEnd.ch+3),line:selectionStart.line});
          }else{ cm.replaceSelection('\n1. '+cm.getSelection()); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+3),line:(selectionStart.line+1)}); }
        }else{
          if(selectionStart.ch===0){ cm.replaceSelection('1. ordered list item'); cm.focus(); cm.setSelection({ch:3,line:selectionStart.line},{ch:20,line:selectionStart.line});
          }else{ cm.replaceSelection('\n1. ordered list item\n\n'); cm.focus(); cm.setSelection({ch:3,line:(selectionStart.line+1)},{ch:20,line:(selectionStart.line+1)}); }
        }
      });
      $('.button.fa-list-ul').click(function(){
        var selectionStart = cm.getCursor(true), selectionEnd = cm.getCursor(false);
        if(cm.somethingSelected()){
          if(selectionStart.ch===0){ cm.replaceSelection('* '+cm.getSelection()); cm.focus(); cm.setSelection({ch:2,line:selectionStart.line},{ch:(selectionEnd.ch+2),line:selectionStart.line});
          }else{ cm.replaceSelection('\n* '+cm.getSelection()); cm.focus(); cm.setSelection({ch:2,line:(selectionStart.line+1)},{ch:(selectionEnd.ch+2),line:(selectionStart.line+1)}); }
        }else{
          if(selectionStart.ch===0){ cm.replaceSelection('* unordered list item'); cm.focus(); cm.setSelection({ch:2,line:selectionStart.line},{ch:21,line:selectionStart.line});
          }else{ cm.replaceSelection('\n* unordered list item\n\n'); cm.focus(); cm.setSelection({ch:2,line:(selectionStart.line+1)},{ch:21,line:(selectionStart.line+1)}); }
        }
      });
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
      <?if(!$auth){?>
        $('#join').click(function(){
          if(confirm('This will set a cookie to identify your account.\nYou must be 16 or over to join TopAnswers and post your question.')){
            $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'new' }, async: false, xhrFields: { withCredentials: true } }).fail(function(r){
              alert((r.status)===429?'Rate limit hit, please try again later':responseText);
            }).done(function(r){
              alert('This login key should be kept confidential, just like a password.\nTo ensure continued access to your account, please record your key somewhere safe:\n\n'+r);
              window.location = '/question?community=databases';
            });
          }
        }).click();
      <?}?>
    });
  </script>
  <title><?=$question_id?'Edit':'Ask'?> Question - TopAnswers</title>
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
          <span class="element"><?=$account_license?> (<a href="." onclick="$(this).parent().hide().next('.element').show(); return false;">change</a>)</span>
          <span class="element wideonly" style="display: none">
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
          <select class="element" name="kind" form="form" required>
            <option value="" disabled selected>choose post type</option>
            <?foreach(db("select kind_id,kind_description,sanction_is_default from kind order by sanction_ordinal") as $r){ extract($r);?>
              <option value="<?=$kind_id?>"<?=$sanction_is_default?' selected':''?>><?=$kind_description?></option>
            <?}?>
          </select>
        <?}?>
        <button class="element" id="submit" type="submit" form="form"><?=$question_id?'update<span class="wideonly"> post under '.$license.'</span>':'submit'?></button>
        <a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
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
      <input id="title" name="title" placeholder="your question title" minlength="5" maxlength="200" autocomplete="off" autofocus required<?=$question_id?' value="'.$question_title.'"':''?>>
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
        <textarea name="markdown" minlength="50" maxlength="50000" autocomplete="off" rows="1" required placeholder="your question"><?=$question_id?$question_markdown:(isset($_GET['fiddle'])?('I have a question about this fiddle:&#13;&#10;&#13;&#10;<>https://dbfiddle.uk?rdbms='.$_GET['rdbms'].'&fiddle='.$_GET['fiddle']):'')?></textarea>
      </div>
      <div id="markdown" class="markdown noexpander"></div>
    </main>
  </form>
  <form id="imageupload" action="//post.topanswers.xyz/upload" method="post" enctype="multipart/form-data"><input id="uploadfile" name="image" type="file" accept="image/*" style="display: none;"></form>
</body>   
</html>   
<?ob_end_flush();
