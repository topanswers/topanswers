<?
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to profile,pg_temp");

if(isset($_GET['uuid'])){
  ccdb("select login(nullif($1,'')::uuid)",$_COOKIE['uuid']??'') || fail(403,'access denied');
  exit(ccdb("select account_uuid from one"));
}

ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']??'meta') || fail(403,'access denied');
extract(cdb("select account_id,account_name,account_has_image,account_license_id,account_codelicense_id,community_id,community_name,community_display_name,colour_dark,colour_mid,colour_light,colour_highlight,colour_warning
                   ,my_community_regular_font_id,my_community_monospace_font_id,my_community_regular_font_name,my_community_monospace_font_name,sesite_url,communicant_se_user_id,one_stackapps_secret
             from one"));

if(isset($_GET['action'])){
  switch($_GET['action']){
    case 'se':
      if(isset($_GET['code'])){
        $ch = curl_init('https://stackoverflow.com/oauth/access_token?client_id=17064&redirect_uri='.urlencode('https://topanswers.xyz/profile?community='.$_GET['community'].'&action=se'));
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query(['client_id'=>'17064'
                                                            ,'client_secret'=>$one_stackapps_secret
                                                            ,'code'=>$_GET['code']
                                                            ,'redirect_uri'=>'https://topanswers.xyz/profile?community='.$_GET['community'].'&action=se'
                                                            ]));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $token = preg_split('/=|&/',curl_exec($ch))[1];
        curl_close($ch);
        if($token){?>
          <form id="form" action="//post.topanswers.xyz/profile" method="post">
            <input type="hidden" name="action" value="se">
            <input type="hidden" name="community" value="<?=$community_name?>">
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
        header('Location: https://stackoverflow.com/oauth?client_id=17064&redirect_uri='.urlencode('https://topanswers.xyz/profile?community='.$_GET['community'].'&action=se')); exit;
      }
    default: fail(400,'unrecognized action');
  }
}

$pin = str_pad(rand(0,pow(10,12)-1),12,'0',STR_PAD_LEFT);
?>
<!doctype html>
<html>
<head>
  <link rel="stylesheet" href="/fonts/<?=$my_community_regular_font_name?>.css">
  <link rel="stylesheet" href="/fonts/<?=$my_community_monospace_font_name?>.css">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <style>
    *:not(hr) { box-sizing: inherit; }
    html { box-sizing: border-box; font-family: '<?=$my_community_regular_font_name?>', serif; font-size: 16px; }
    body { display: flex; flex-direction: column; background: #<?=$colour_dark?>; }
    html, body { height: 100vh; overflow: hidden; margin: 0; padding: 0; }
    header, header>div { display: flex; min-width: 0; overflow: hidden; align-items: center; white-space: nowrap; }
    header { min-height: 30px; flex-wrap: wrap; justify-content: space-between; font-size: 14px; background: #<?=$colour_dark?>; white-space: nowrap; border-bottom: 2px solid black; }
    header a { color: #<?=$colour_light?>; }
    main { display: flex; flex-direction: column; align-items: flex-start; overflow: auto; scroll-behavior: smooth; }
    main>fieldset { display: flex; flex-direction: column; align-items: flex-start; }

    .frame { display: inline-block; border: 1px solid #<?=$colour_dark?>; margin: 2px; outline: 1px solid #<?=$colour_light?>; background-color: #<?=$colour_light?>; }
    .icon { width: 20px; height: 20px; display: block; margin: 1px; border-radius: 2px; }
    .element { margin: 0 4px; }

    fieldset { display: inline-block; margin: 16px; border-radius: 3px; }
    :not(main)>fieldset { background-color: white; border: none; }
    legend { background-color: white; box-shadow: 0 0 1px 1px #<?=$colour_dark?>; border-radius: 3px; padding: 2px 4px; }
    input[type='file'] { color: transparent; }
    <?if(isset($_GET['highlight-recovery'])){?>.highlight { background-color: yellow; }<?}?>
  </style>
  <script src="/lib/jquery.js"></script>
  <script>
    $(function(){
      $('#pin').click(function(){ $(this).prop('disabled',true); $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'pin', pin: '<?=$pin?>' }, xhrFields: { withCredentials: true } }).done(function(){
        $('#pin').replaceWith('<code><?=$pin?></code>'); });
      });
      $('#uuid').click(function(){ var t = $(this); $.get('/profile?uuid').done(function(r){ t.replaceWith('<span class="highlight">'+r+'</span>'); }); });
      $('[name]').on('change input',function(){
        $(this).parents('fieldset').siblings().find('[name],input').prop('disabled',true);
        $(this).closest('fieldset').find('input[type=submit]').css('visibility','visible');
        if($(this).is('input[type=file]')) $(this).next().click();
      });
      <?if(isset($_GET['highlight-recovery'])){?>$('#uuid').click();<?}?>
      $('#community').change(function(){ window.location = '/profile?community='+$(this).find(':selected').attr('data-name'); });
      $('input[value=save]').css('visibility','hidden');
    });
  </script>
  <title>Profile | TopAnswers</title>
</head>
<body>
  <header>
    <div>
      <a class="element" href="/<?=$community_name?>">TopAnswers</a>
      <select id="community" class="element">
        <?foreach(db("select community_name,community_room_id,community_display_name from community order by community_name desc") as $r){ extract($r,EXTR_PREFIX_ALL,'s');?>
          <option value="<?=$s_community_room_id?>" data-name="<?=$s_community_name?>"<?=($community_name===$s_community_name)?' selected':''?>><?=$s_community_display_name?></option>
        <?}?>
      </select>
    </div>
    <div>
      <a class="frame"><img class="icon" src="/identicon?id=<?=$account_id?>"></a>
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
        <div class="frame"><img class="icon" src="/identicon?id=<?=$account_id?>&random=<?=time()?>"></div>
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
          <li>Your 'login key' should be kept confidential, just like a password.<span class="highlight"> To ensure continued access to your account, record your 'key' somewhere safe.</span></li>
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
            <?foreach(db("select license_id,license_name from license") as $r){ extract($r);?>
              <option value="<?=$license_id?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
            <?}?>
          </select>
          <input type="submit" value="save">
        </form>
      </fieldset>
      <fieldset>
        <legend>default additional license for code in new posts</legend>
        <form action="//post.topanswers.xyz/profile" method="post">
          <input type="hidden" name="action" value="codelicense">
          <input type="hidden" name="location" value="//topanswers.xyz/profile?community=<?=$community_name?>">
          <select name="codelicense">
            <?foreach(db("select codelicense_id,codelicense_name from codelicense") as $r){ extract($r);?>
              <option value="<?=$codelicense_id?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
            <?}?>
          </select>
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
          <label>regular
            <select name="regular">
              <?foreach(db("select font_id,font_name from font where not font_is_monospace") as $r){ extract($r);?>
                <option value="<?=$font_id?>"<?=($font_id===$my_community_regular_font_id)?' selected':''?>><?=$font_name?></option>
              <?}?>
            </select>
          </label>
          <label>monospace
            <select name="mono">
              <?foreach(db("select font_id,font_name from font where font_is_monospace") as $r){ extract($r);?>
                <option value="<?=$font_id?>"<?=($font_id===$my_community_monospace_font_id)?' selected':''?>><?=$font_name?></option>
              <?}?>
            </select>
          </label>
          <input type="submit" value="save">
        </form>
      </fieldset>
      <?if($sesite_url){?>
        <fieldset>
          <legend>linked account on <a href="<?=$sesite_url?>"><?=substr($sesite_url,8)?></a></legend>
          <form action="//topanswers.xyz/profile" method="get">
            <?if($communicant_se_user_id){?>
              <a href="<?=$sesite_url.'/users/'.$communicant_se_user_id?>?>"><?=substr($sesite_url,8).'/users/'.$communicant_se_user_id?></a>
            <?}else{?>
              <input type="hidden" name="action" value="se">
              <input type="hidden" name="community" value="<?=$community_name?>">
              <input type="submit" value="authenticate with SE to link account">
              <a href="/meta?q=409#a647">info</a>
            <?}?>
          </form>
        </fieldset>
      <?}?>
    </fieldset>
  </main>
</body>
</html>
