<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to usr,pg_temp");

ccdb("select login_communityuser(nullif($1,'')::uuid,$2,$3)",$_COOKIE['uuid']??'',$_GET['community']??'meta',$_GET['id']);
extract(cdb("select account_id
                   ,user_account_id,user_account_name,user_account_name_is_derived
                   ,community_id,community_name,community_display_name
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
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
             ">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/datatables/datatables.min.css">
  <link rel="icon" href="/communityicon?community=<?=$community_name?>" type="image/png">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <style>
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: 16px; }
    body { display: flex; flex-direction: column; background: rgb(var(--rgb-mid)); }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    main { display: flex; flex-direction: column; align-items: flex-start; overflow: auto; scroll-behavior: smooth; }
    main>fieldset { display: flex; flex-direction: column; align-items: flex-start; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }

    fieldset { display: inline-block; margin: 10px; border-radius: 3px; background: white; border: 1px solid rgb(var(--rgb-dark)); padding: 8px; }
    legend { background: white; border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; padding: 2px 4px; }
    input[type="file"] { color: transparent; }
    input[type="submit"] { margin-left: 16px; }

    div.panel:not(#answers) { display: none; }

    table { border-collapse: collapse !important; }
    td,th { border: 1px solid black; white-space: nowrap; }
    table.dataTable thead th { padding: 5px 18px; }
    table.dataTable tbody td { padding: 5px 10px; }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/datatables/datatables.min.js"></script>
  <script>
    $(function(){
      $('#community').change(function(){ window.location = '/profile?community='+$(this).find(':selected').attr('data-name'); });
      $('input[value=save]').css('visibility','hidden');
      $('table').DataTable({
        dom: 'Pfrtip',
        language: { searchPanes: { emptyPanes: null } },
        preDrawCallback: function (settings) {
          $(this).closest('.dataTables_wrapper').find('.dataTables_paginate,.dataTables_info,.dataTables_filter').toggle((new $.fn.dataTable.Api(settings)).page.info().pages > 1);
        }
      });
      $('a.panel').click(function(){
        var panels = $('div.panel'), panel = $('#'+$(this).data('panel'));
        $('a.panel:not([href])').attr('href','.');
        $(this).removeAttr('href');
        panels.hide();
        panel.show();
        return false;
      });
    });
  </script>
  <title><?=$user_account_name?> - TopAnswers</title>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigation?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div><?if($account_id){?><a class="frame" href="/profile?community=<?=$community_name?>" title="profile"><img class="icon" src="/identicon?id=<?=$account_id?>"></a><?}?></div>
  </header>
  <main>
    <fieldset>
      <div style="display: flex; align-items: center;">
        <div class="frame"><img class="icon" src="/identicon?id=<?=$user_account_id?>&random=<?=time()?>"></div>
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
            <?foreach(db("select question_id,question_title,question_votes,answer_id,answer_votes,kind_description
                               , to_char(answer_at,'YYYY-MM-DD HH24:MI') answer_at_desc
                               , to_char(question_at,'YYYY-MM-DD HH24:MI') question_at_desc
                          from answer
                          order by question_at desc") as $r){extract($r);?>
              <tr>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$answer_at_desc?></td>
                <td><?=$kind_description?></td>
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
            <?foreach(db("select question_id,question_title,question_votes,kind_description,kind_has_question_votes
                               , to_char(question_at,'YYYY-MM-DD HH24:MI') question_at_desc
                          from question
                          order by question_at desc") as $r){extract($r);?>
              <tr>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$question_at_desc?></td>
                <td><?=$kind_description?></td>
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
            <?foreach(db("select community_id,community_name,community_question_count,community_answer_count,community_votes
                          from community
                          order by community_votes desc, community_answer_count desc, community_question_count desc, community_id") as $r){extract($r,EXTR_PREFIX_ALL,'c');?>
              <tr>
                <td><?=($community_name!==$c_community_name)?('<a href="?id='.$_GET['id'].'&community='.$c_community_name.'">'.$c_community_name.'</a>'):$c_community_name?></td>
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
