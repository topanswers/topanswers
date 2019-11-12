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
  extract(cdb("select question_type,question_title,question_markdown from question where question_id=$1",$id));
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

    #markdown > :first-child { margin-top: 0; }
    #markdown > :last-child { margin-bottom: 0; }
    #markdown ul { padding-left: 2em; }
    #markdown li { margin: 0.5em 0; }
    #markdown img { max-height: 20em; max-width: 100%; }
    #markdown hr { background-color: #<?=$colour_mid?>; border: 0; height: 2px; }
    #markdown table { border-collapse: collapse; table-layout: fixed; }
    #markdown .tablewrapper { max-width: 100%; padding: 1px; overflow-x: auto; }
    #markdown td, .markdown th { white-space: nowrap; border: 1px solid black; padding: 0.2em; }
    #markdown blockquote {  padding-left: 0.7em;  margin-left: 0.7em; margin-right: 0; border-left: 0.3em solid #<?=$colour_mid?>; }
    #markdown code { padding: 0 0.2em; background-color: #<?=$colour_light?>; border: 1px solid #<?=$colour_mid?>; border-radius: 1px; font-size: 1.1em; }
    #markdown pre>code { display: block; max-width: 100%; overflow-x: auto; padding: 0.4em; }

    .CodeMirror { height: 100%; border: 1px solid #<?=$colour_dark?>; font-size: 1.1rem; border-radius: 0.2rem; }
    .CodeMirror pre.CodeMirror-placeholder { color: darkgrey; }
    .CodeMirror-wrap pre { word-break: break-word; }
  </style>
  <script src="/lodash.js"></script>
  <script src="/jquery.js"></script>
  <script src="/markdown-it.js"></script>
  <script src="/markdown-it-inject-linenumbers.js"></script>
  <script src="/markdown-it-sup.js"></script>
  <script src="/markdown-it-sub.js"></script>
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
      var md = window.markdownit({ highlight: function (str, lang) { if (lang && hljs.getLanguage(lang)) { try { return hljs.highlight(lang, str).value; } catch (__) {} } return ''; }})
                     .use(window.markdownitSup).use(window.markdownitSub).use(window.markdownitDeflist).use(window.markdownitFootnote).use(window.markdownitAbbr).use(window.markdownitInjectLinenumbers);
      var cm = CodeMirror.fromTextArea($('textarea')[0],{ lineWrapping: true, extraKeys: { Home: "goLineLeft", End: "goLineRight" } });
      $('textarea[name="markdown"]').show().css({ position: 'absolute', opacity: 0, 'margin-top': '4px', 'margin-left': '10px' }).attr('tabindex','-1');
      var map;
      function render(){
        $('#markdown').html(md.render(cm.getValue()));
        $('#markdown table').wrap('<div class="tablewrapper">');
        map = [];
        $('#markdown [data-source-line]').each(function(){ map.push($(this).data('source-line')); });
        <?if(!$id){?>localStorage.setItem('<?=$community?>.ask',cm.getValue());<?}?>
      }
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
      $('#license').change(function(){ $('input[name="license"').val($(this).val()); });
      $('#codelicense').change(function(){ $('input[name="codelicense"').val($(this).val()); });
      render();
    });
  </script>
  <title>Ask | <?=ucfirst($community)?> | TopAnswers</title>
</head>
<body style="display: flex; flex-direction: column; font-size: larger; background-color: #<?=$colour_light?>; height: 100%;">
  <header style="border-bottom: 2px solid black; display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; flex: 0 0 auto;">
    <div style="margin: 0.5rem; margin-right: 0.1rem;">
      <a href="/<?=$community?>" style="color: #<?=$colour_mid?>;">TopAnswers <?=ucfirst($community)?></a>
    </div>
    <div style="display: flex; align-items: center; height: 100%;">
      <?if(!$id){?>
        <select id="type"><option selected value="question">question</option><option value="meta question">meta</option><option value="blog post">blog</option></select>
        <select id="license">
          <?foreach(db("select license_id,license_name from license") as $r){ extract($r);?>
            <option value="<?=$license_id?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
          <?}?>
        </select>
        <select id="codelicense">
          <?foreach(db("select codelicense_id,codelicense_name from codelicense") as $r){ extract($r);?>
            <option value="<?=$codelicense_id?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
          <?}?>
        </select>
      <?}?>
      <input id="submit" type="submit" form="form" value="<?=$id?('update '.$question_type.(($question_type==='meta')?' question':(($question_type==='blog')?' post':''))):'submit'?>" style="margin: 0.5rem;">
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
      <input type="hidden" name="type" value="question">
      <input type="hidden" name="license" value="<?=$account_license_id?>">
      <input type="hidden" name="codelicense" value="<?=$account_codelicense_id?>">
    <?}?>
    <main style="display: flex; position: relative; justify-content: center; flex: 0 1 120rem; overflow-y: auto; flex-direction: column;">
      <input name="title" style="flex 0 0 auto; border: 1px solid #<?=$colour_dark?>; padding: 3px; border-radius: 0.2rem;" placeholder="your question title" minlength="5" maxlength="200" autocomplete="off" autofocus required<?=$id?' value="'.htmlspecialchars($question_title).'"':''?>>
      <div style="flex: 0 0 2vmin;"></div>
      <div style="display: flex; flex: 1 0 0; overflow: hidden;">
        <div style="flex: 1 0 0; overflow-x: hidden; max-width: calc(50vw - 3vmin);">
          <textarea name="markdown" minlength="50" maxlength="50000" autocomplete="off" rows="1" required placeholder="your question"><?=$id?htmlspecialchars($question_markdown):''?></textarea>
        </div>
        <div style="flex: 0 0 2vmin;"></div>
        <div id="markdown" style="flex: 1 0 0; overflow-x: hidden; max-width: calc(50vw - 3vmin); background-color: white; padding: 1rem; border: 1px solid #<?=$colour_dark?>; border-radius: 0.2rem; overflow-y: auto;"></div>
      </div>
    </main>
  </form>
</body>   
</html>   
