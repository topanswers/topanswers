<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['community']) || fail(400,'community must be set');
db("set search_path to community,pg_temp");
if(isset($_COOKIE['uuid'])){ ccdb("select login($1::uuid)",$_COOKIE['uuid']) || fail(403,'access denied'); }
$environment = $_COOKIE['environment']??'prod';
$auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
if($auth) setcookie("uuid",$_COOKIE['uuid'],2147483647,'/','topanswers.xyz',null,true);
extract(cdb("select account_id
                   ,community_id,community_name,community_language,community_display_name,community_my_power,community_code_language,community_about_question_id,community_ask_button_text,community_banner_markdown
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning,community_tables_are_monospace
                   ,my_community_regular_font_name,my_community_monospace_font_name
             from one"));
$_GET['community']===$community_name || fail(400,'invalid community');
include '../lang/community.'.$community_language.'.php';
$jslang = substr($community_language,0,1).substr(strtok($community_language,'-'),-1);
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
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/post.css">
  <link rel="icon" href="/communityicon?community=<?=$community_name?>" type="image/png">
  <style>
    html { box-sizing: border-box; font-family: var(--regular-font-family); font-size: 16px; }
    body { xbackground: rgb(var(--rgb-mid)); }
    html, body { margin: 0; padding: 0; }
    .icon { width: 20px; height: 20px; display: block; margin: 1px; }
    .icon:not(.roomicon) { border-radius: 2px; }
    #qa { width: 100%; }
    .hover { display: none; }
    @supports (-webkit-touch-callout: none) { #qa * { -webkit-transform: translate3d(0, 0, 0); } }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/lodash.js"></script>
  <script src="/lib/jquery.js"></script>
  <?require '../markdown.php';?>
  <script>
    $(function(){
      jQuery.fn.shuffle = function () {
        var j;
        for (var i = 0; i < this.length; i++) {
          j = Math.floor(Math.random() * this.length);
          $(this[i]).before($(this[j]));
        }
        return this;
      };
      function renderQuestion(){
        $(this).find('.summary span[data-markdown]').renderMarkdownSummary();
        $(this).find('.answers>.bar:first-child+.bar+.bar+.bar').each(function(){
          var t = $(this), h = t.nextAll('.bar').addBack();
          if(h.length>1){
            t.prev().addClass('premore');
            $('<div class="bar more"><span></span><a href=".">show '+h.length+' more</a><span></span></div>').appendTo(t.parent()).click(function(){
              t.prev().removeClass('premore');
              $(this).prevAll('.bar:hidden').slideDown().end().slideUp();
              return false;
            });
            h.hide();
          }
        });
        $(this).find('.when').each(function(){
          var t = $(this);
          $(this).text((t.attr('data-prefix')||'')+moment.duration(t.data('seconds'),'seconds').humanize()+' ago'+(t.attr('data-postfix')||''));
          $(this).attr('title',moment($(this).data('at')).calendar(null, { sameDay: 'HH:mm', lastDay: '[Yesterday] HH:mm', lastWeek: '[Last] dddd HH:mm', sameElse: 'Do MMM YYYY HH:mm' }));
        });
      }
      (function(){
        var promises = [];
        $('#qa .post.deleted').remove();
        $('#qa .post:not(.processed)').find('.markdown[data-markdown]').renderMarkdown(promises);
        Promise.allSettled(promises).then(() => {
          $('#qa .post:not(.processed) .question').each(renderQuestion);
          $('#qa .post:not(.processed) .answers .summary span[data-markdown]').renderMarkdownSummary();
          $('#qa .post').addClass('processed');
          $('#qa .post').shuffle();
          $('#qa .post').slice(3).remove();
          $('a').attr('target','_blank');
        });
      })();
    });
  </script>
</head>
<body>
  <div id="qa">
    <div id="questions">
      <?$ch = curl_init('http://127.0.0.1/questions?community='.$community_name.'&search='.urlencode($_GET['search']??'')); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    </div>
  </div>
</body>
</html>
<?ob_end_flush();
