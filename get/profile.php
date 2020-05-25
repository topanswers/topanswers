<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to profile,pg_temp");

if(isset($_GET['uuid'])){
  ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'') || fail(403,'access denied');
  exit(ccdb("select account_uuid from one"));
}

ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']??'meta') || fail(403,'access denied');
extract(cdb("select account_id,account_name,account_has_image,account_license_id,account_codelicense_id,account_permit_later_license,account_permit_later_codelicense,account_image_url
                   ,community_id,community_name,community_display_name,community_regular_font_is_locked,community_monospace_font_is_locked,community_image_url
                   ,community_rgb_dark,community_rgb_mid,community_rgb_light,community_rgb_highlight,community_rgb_warning
                   ,my_community_regular_font_id,my_community_monospace_font_id,my_community_regular_font_name,my_community_monospace_font_name,sesite_url,communicant_se_user_id,one_stackapps_secret
             from one"));

if(isset($_GET['action'])){
  switch($_GET['action']){
    case 'se':
      if(isset($_GET['code'])){
        $ch = curl_init('https://stackoverflow.com/oauth/access_token?client_id=17064&redirect_uri='.urlencode('https://topanswers.xyz/profile?community='.$_GET['community'].'&sesite='.$_GET['sesite'].'&action=se'));
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query(['client_id'=>'17064'
                                                            ,'client_secret'=>$one_stackapps_secret
                                                            ,'code'=>$_GET['code']
                                                            ,'redirect_uri'=>'https://topanswers.xyz/profile?community='.$_GET['community'].'&sesite='.$_GET['sesite'].'&action=se'
                                                            ]));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $token = preg_split('/=|&/',curl_exec($ch))[1];
        curl_close($ch);
        if($token){?>
          <form id="form" action="//post.topanswers.xyz/profile" method="post">
            <input type="hidden" name="action" value="se">
            <input type="hidden" name="community" value="<?=$community_name?>">
            <input type="hidden" name="sesite" value="<?=$_GET['sesite']?>">
            <input type="hidden" name="token" value="<?=$token?>">
            <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
            <noscript><input type="submit" value="Click here if you are not redirected automatically."/></noscript>
          </form>
          <script>
            document.getElementById('form').submit();
          </script><?
          exit;
        }
        exit;
      }else{
        header('Location: https://stackoverflow.com/oauth?client_id=17064&redirect_uri='.urlencode('https://topanswers.xyz/profile?community='.$_GET['community'].'&sesite='.$_GET['sesite'].'&action=se')); exit;
      }
    default: fail(400,'unrecognized action');
  }
}

$pin = str_pad(rand(0,pow(10,12)-1),12,'0',STR_PAD_LEFT);
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
             ">
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="stylesheet" href="/lib/fork-awesome/css/fork-awesome.min.css">
  <link rel="stylesheet" href="/lib/datatables/datatables.min.css">
  <link rel="icon" href="<?=$community_image_url?>" type="image/png">
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/header.css">
  <style>
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: 16px; }
    body { display: flex; flex-direction: column; background: rgb(var(--rgb-mid)); }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    main { display: flex; flex-direction: column; align-items: flex-start; overflow: auto; scroll-behavior: smooth; }
    main>fieldset { flex: 0 0 auto; display: flex; flex-direction: column; align-items: flex-start; }

    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }

    fieldset { display: inline-block; margin: 10px; border-radius: 3px; }
    :not(main)>fieldset { background: rgb(var(--rgb-white)); border: 1px solid rgb(var(--rgb-dark)); }
    legend { background: rgb(var(--rgb-white)); border: 1px solid rgb(var(--rgb-dark)); border-radius: 3px; padding: 2px 4px; }
    input[type="file"] { color: transparent; }
    input[type="submit"] { margin-left: 16px; }

    table { border-collapse: collapse; }
    td,th { border: 1px solid rgb(var(--rgb-black)); white-space: nowrap; padding: 4px; }
  </style>
  <script src="/lib/js.cookie.js"></script>
  <script src="/lib/jquery.js"></script>
  <script src="/lib/datatables/datatables.min.js"></script>
  <script>
    $(function(){
      $('#pin').click(function(){ $(this).prop('disabled',true); $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'pin', pin: '<?=$pin?>' }, xhrFields: { withCredentials: true } }).done(function(){
        $('#pin').replaceWith('<code><?=$pin?></code>'); });
      });
      $('#uuid').click(function(){ var t = $(this); $.get('/profile?uuid').done(function(r){ t.replaceWith('<span class="highlight">'+r+'</span>'); }); });
      $('[name="license"],[name="codelicense"]').on('change',function(){
        if($(this).children('option:selected').data('versioned')===true){
          $(this).next().css('color','rgb(var(--rgb-black))').find('input').prop('disabled',false);
        }else{
          $(this).next().css('color','#ccc').find('input').prop('checked',false).prop('disabled',true);
        }
      }).trigger('change');
      $('[name]').on('change input',function(){
        $(this).parents('fieldset').siblings().find('[name],input').prop('disabled',true);
        $(this).closest('fieldset').find('input[type=submit]').css('visibility','visible');
        if($(this).is('input[type=file]')) $(this).next().click();
      });
      <?if(isset($_GET['highlight-recovery'])){?>$('#uuid').click();<?}?>
      $('#community').change(function(){ window.location = '/profile?community='+$(this).find(':selected').attr('data-name'); });
      $('input[value=save]').css('visibility','hidden');
      $('table.data').DataTable({ select: true, dom: 'Pfrtip' });
      $('.select>div:last-child>div>div').each(function(){
        $(this).append('<a href="/profile?community='+$(this).data('community')+'">profile</a>');
      });
    });
  </script>
  <title>Profile - TopAnswers</title>
</head>
<body>
  <header>
    <?$ch = curl_init('http://127.0.0.1/navigationx?community='.$community_name); curl_setopt($ch, CURLOPT_HTTPHEADER, [$cookies]); curl_exec($ch); curl_close($ch);?>
    <div>
      <a class="frame"><img class="icon" src="<?=$account_image_url?>"></a>
    </div>
  </header>
  <main>
    <fieldset>
      <legend>global settings</legend>
      <fieldset>
        <legend>display name</legend>
        <form action="//post.topanswers.xyz/profile" method="post">
          <input type="hidden" name="action" value="name">
          <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
          <input type="text" name="name" placeholder="name" value="<?=$account_name?>" autocomplete="off" autofocus>
          <input type="submit" value="save">
        </form>
      </fieldset>
      <fieldset>
        <legend>picture</legend>
        <div class="frame"><img class="icon" src="<?=$account_image_url?>&random=<?=time()?>"></div>
        <?if($account_has_image){?>
          <form action="//post.topanswers.xyz/profile" method="post">
            <input type="hidden" name="action" value="remove-image">
            <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
            <input type="submit" value="Remove">
          </form>
        <?}else{?>
          <form action="//post.topanswers.xyz/profile" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="image">
            <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
            <input type="file" name="image" accept=".png,.gif,.jpg,.jpeg">
            <input type="submit" value="save">
          </form>
        <?}?>
      </fieldset>
      <fieldset>
        <legend>link another device to this account</legend>
        <ol>
          <li>Go to https://topanswers.xyz on the other device and click 'log in'</li>
          <li>Enter this PIN (within 1 minute of generation): <input id="pin" type="button" value="generate PIN"></li>
        </ol>
      </fieldset>
      <fieldset>
        <legend>account recovery</legend>
        <ul>
          <li>Your 'login key' should be kept confidential, just like a password.<span<?=isset($_GET['highlight-recovery'])?' class="highlight"':''?>> To ensure continued access to your account, please record your 'key' somewhere safe.</span></li>
          <li>It can be used in the same way as a PIN, but does not expire</li>
          <li><input id="uuid" type="button" value="show key"></li>
          <li>If you suspect your 'key' has been discovered, you should regenerate it</li>
          <li>
            <form action="//post.topanswers.xyz/profile" method="POST">
              <input type="hidden" name="action" value="regenerate">
              <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>&highlight-recovery">
              <input type="submit" value="generate new key">
            </form>
          </li>
        </ul>
      </fieldset>
      <fieldset>
        <legend>default license for new posts</legend>
        <form action="//post.topanswers.xyz/profile" method="post">
          <input type="hidden" name="action" value="license">
          <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
          <select name="license">
            <?foreach(db("select license_id,license_name,license_is_versioned from license") as $r){ extract($r);?>
              <option value="<?=$license_id?>" data-versioned="<?=$license_is_versioned?'true':'false'?>" <?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
            <?}?>
          </select>
          <label><input type="checkbox" name="orlater"<?=$account_permit_later_license?'checked':''?>>or later</label>
          <input type="submit" value="save">
        </form>
      </fieldset>
      <fieldset>
        <legend>default dual license for code in new posts</legend>
        <form action="//post.topanswers.xyz/profile" method="post">
          <input type="hidden" name="action" value="codelicense">
          <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
          <select name="codelicense">
            <?foreach(db("select codelicense_id,codelicense_name,codelicense_is_versioned from codelicense") as $r){ extract($r);?>
              <option value="<?=$codelicense_id?>" data-versioned="<?=$codelicense_is_versioned?'true':'false'?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
            <?}?>
          </select>
          <label><input type="checkbox" name="orlater"<?=$account_permit_later_codelicense?'checked':''?>>or later</label>
          <input type="submit" value="save">
        </form>
      </fieldset>
    </fieldset>
    <fieldset>
      <legend>community settings</legend>
      <fieldset>
        <legend>fonts</legend>
        <form action="//post.topanswers.xyz/profile" method="post">
          <input type="hidden" name="action" value="font">
          <input type="hidden" name="community" value="<?=$community_name?>">
          <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
          <?if(!$community_regular_font_is_locked){?>
            <div>
            <label>regular: 
              <select name="regular">
                <?foreach(db("select font_id,font_name from font where not font_is_monospace") as $r){ extract($r);?>
                  <option value="<?=$font_id?>"<?=($font_id===$my_community_regular_font_id)?' selected':''?>><?=$font_name?></option>
                <?}?>
              </select>
            </label>
            </div>
          <?}?>
          <?if(!$community_monospace_font_is_locked){?>
            <label>monospace: 
              <select name="mono">
                <?foreach(db("select font_id,font_name from font where font_is_monospace") as $r){ extract($r);?>
                  <option value="<?=$font_id?>"<?=($font_id===$my_community_monospace_font_id)?' selected':''?>><?=$font_name?></option>
                <?}?>
              </select>
            </label>
          <?}?>
          <input type="submit" value="save">
        </form>
      </fieldset>
      <?if(ccdb("select count(1)>0 from sesite")){?>
        <fieldset>
          <legend>linked accounts on Stack Exchange (<a href="/meta?q=409#a647">info</a>)</legend>
          <ul>
            <?foreach(db("select sesite_id,sesite_url,selink_user_id from sesite order by sesite_ordinal") as $r){ extract($r);?>
              <li>
                <form action="//topanswers.xyz/profile" method="get">
                  <?if($selink_user_id){?>
                    <a href="<?=$sesite_url.'/users/'.$selink_user_id?>?>"><?=substr($sesite_url,8).'/users/'.$selink_user_id?></a>
                  <?}else{?>
                    <input type="hidden" name="action" value="se">
                    <input type="hidden" name="community" value="<?=$community_name?>">
                    <input type="hidden" name="sesite" value="<?=$sesite_id?>">
                    <input type="submit" style="margin-left: 0;" value="authenticate with SE to link <?=substr($sesite_url,8)?> account">
                  <?}?>
                </form>
              </li>
            <?}?>
          </ul>
        </fieldset>
      <?}?>
      <?if(ccdb("select count(1)>0 from community where community_id<>$1",$community_id)){?>
        <fieldset>
          <legend>cross-community feeds</legend>
          <form action="//post.topanswers.xyz/profile" method="post">
            <input type="hidden" name="action" value="feeds">
            <input type="hidden" name="community" value="<?=$community_name?>">
            <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
            <table>
              <thead><tr><th>community</th><th>feed</th></tr></thead>
              <tbody>
                <?foreach(db("select community_id,community_display_name,community_feed_is_active
                              from community
                              where community_id<>$1
                              order by community_my_votes desc nulls last, community_ordinal, community_name",$community_id) as $r){ extract($r,EXTR_PREFIX_ALL,'c');?>
                  <tr>
                    <td><?=$c_community_display_name?></td>
                    <td><input type="checkbox" name="feed[]" value="<?=$c_community_id?>"<?=$c_community_feed_is_active?' checked':''?>></td>
                  </tr>
                <?}?>
              </tbody>
            </table>
            <input type="submit" style="margin-left: 0;" value="save">
          </form>
        </fieldset>
      <?}?>
    </fieldset>
    <fieldset>
      <legend>community activity</legend>
      <fieldset>
        <legend>questions</legend>
        <table class="data" data-order='[[0,"desc"]]' data-page-length='10'>
          <thead>
            <tr><th>date/time</th><th>type</th><th>title</th><th>stars</th></tr>
          </thead>
          <tbody>
            <?foreach(db("select question_id,question_title,question_votes,sanction_description
                               , to_char(question_at,'YYYY-MM-DD HH24:MI') question_at_desc
                          from question
                          order by question_at desc") as $r){extract($r);?>
              <tr>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$question_at_desc?></td>
                <td><?=$sanction_description?></td>
                <td><a href="/<?=$community_name?>?q=<?=$question_id?>"><?=$question_title?></a></td>
                <td><?=$question_votes?></td>
              </tr>
            <?}?>
          </tbody>
        </table>
      </fieldset>
      <fieldset>
        <legend>answers</legend>
        <table class="data" data-order='[[0,"desc"]]' data-page-length='10'>
          <thead>
            <tr><th>answer date/time</th><th>question type</th><th>title</th><th>answer stars</th><th>question date/time</th></tr>
          </thead>
          <tbody>
            <?foreach(db("select question_id,question_title,question_votes,answer_id,answer_votes,sanction_description
                               , to_char(question_at,'YYYY-MM-DD HH24:MI') answer_at_desc
                               , to_char(question_at,'YYYY-MM-DD HH24:MI') question_at_desc
                          from answer
                          order by question_at desc") as $r){extract($r);?>
              <tr>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$answer_at_desc?></td>
                <td><?=$sanction_description?></td>
                <td><a href="/<?=$community_name?>?q=<?=$question_id?>#a<?=$answer_id?>"><?=$question_title?></a></td>
                <td><?=$answer_votes?></td>
                <td style="font-family: <?=$my_community_monospace_font_name?>;"><?=$question_at_desc?></td>
              </tr>
            <?}?>
          </tbody>
        </table>
      </fieldset>
    </fieldset>
  </main>
</body>
</html>
<?ob_end_flush();
