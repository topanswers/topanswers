<?    
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_COOKIE['uuid']) or die('Not registered');
$uuid = $_COOKIE['uuid'];
$pin = str_pad(rand(0,pow(10,12)-1),12,'0',STR_PAD_LEFT);
if($uuid) ccdb("select login($1)",$uuid);
if(isset($_GET['uuid'])){
  exit(ccdb("select account_uuid from my_account"));
}
extract(cdb("select account_name,account_license_id,account_codelicense_id, account_image is not null account_has_image from my_account"));
?>
<!doctype html>
<html style="box-sizing: border-box; font-family: 'quattrocento', serif; font-size: smaller;">
<head>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="icon" href="/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="/fonts/quattrocento.css">
  <style>
    *:not(hr) { box-sizing: inherit; }
    fieldset { margin-bottom: 1rem; }
    <?if(isset($_GET['highlight-recovery'])){?>.highlight { background-color: yellow; }<?}?>
  </style>
  <script src="/lib/jquery.js"></script>
  <script>
    $(function(){
      $('#pin').click(function(){ $(this).prop('disabled',true); $.post({ url: '//post.topanswers.xyz/profile', data: { action: 'authenticate-pin', pin: '<?=$pin?>' }, xhrFields: { withCredentials: true } }).done(function(){
        $('#pin').replaceWith('<code><?=$pin?></code>'); });
      });
      $('#uuid').click(function(){ var t = $(this); $.get('/profile?uuid').done(function(r){ t.replaceWith('<span class="highlight">'+r+'</span>'); }); });
      $('[name]').on('change input',function(){ console.log('boo'); $(this).parents('fieldset').siblings('fieldset').find('[name],input').prop('disabled', true); });
      <?if(isset($_GET['highlight-recovery'])){?>$('#uuid').click();<?}?>
    });
  </script>
  <title>Profile | TopAnswers</title>
</head>
<body>
  <fieldset>
    <legend>link another device/browser to this account</legend>
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
      <li><form action="//post.topanswers.xyz/profile" method="POST"><input type="hidden" name="action" value="regen"><input type="submit" value="generate new key"></form></li>
    </ul>
  </fieldset>
  <fieldset>
    <legend>display name</legend>
    <form action="//post.topanswers.xyz/profile" method="post">
      <input type="text" name="name" placeholder="name" value="<?=$account_name?>" autocomplete="off" autofocus>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>picture</legend>
    <img src="/identicon?id=<?=ccdb("select account_id from my_account")?>">
    <?if($account_has_image==='t'){?>
      <form action="//post.topanswers.xyz/profile" method="post">
        <input type="hidden" name="image">
        <input type="submit" value="Remove">
      </form>
    <?}?>
    <hr>
    <form action="//post.topanswers.xyz/profile" method="post" enctype="multipart/form-data">
      <input type="file" name="image" accept=".png,.gif,.jpg,.jpeg">
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>default license for new posts</legend>
    <form action="//post.topanswers.xyz/profile" method="post">
      <select name="license">
        <?foreach(db("select license_id,license_name from license") as $r){ extract($r);?>
          <option value="<?=$license_id?>"<?=($license_id===$account_license_id)?' selected':''?>><?=$license_name?></option>
        <?}?>
      </select>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <fieldset>
    <legend>default additional license for code in new posts</legend>
    <form action="//post.topanswers.xyz/profile" method="post">
      <select name="codelicense">
        <?foreach(db("select codelicense_id,codelicense_name from codelicense") as $r){ extract($r);?>
          <option value="<?=$codelicense_id?>"<?=($codelicense_id===$account_codelicense_id)?' selected':''?>><?=$codelicense_name?></option>
        <?}?>
      </select>
      <input type="submit" value="Save">
    </form>
  </fieldset>
  <?foreach(db("select community_name,my_community_regular_font_id,my_community_monospace_font_id
                from account_community natural join my_account natural join community natural join (select community_id,my_community_regular_font_id,my_community_monospace_font_id from my_community) z
                order by account_community_votes desc, community_id") as $r){extract($r);?>
    <fieldset>
      <legend><?=$community_name?></legend>
      <fieldset>
        <legend>fonts</legend>
        <form action="//post.topanswers.xyz/profile" method="post">
          <input type="hidden" name="action" value="font">
          <input type="hidden" name="community" value="<?=$community_name?>">
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
          <input type="submit" value="Save">
        </form>
      </fieldset>
    </fieldset>
  <?}?>
</body>   
</html>   
