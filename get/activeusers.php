<?
include '../config.php';
include '../db.php';
include '../nocache.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
if(!isset($_GET['room'])) die('room not set');
db("set search_path to activeusers,pg_temp");
ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['room']);
extract(cdb("select account_id,community_language, (select jsonb_agg(z) from (select account_id,account_derived_name,communicant_votes from account order by participant_latest_chat_at desc) z) accounts
             from one"),EXTR_PREFIX_ALL,'o');
include '../lang/chat.'.$o_community_language.'.php';
?>
<?foreach($o_accounts as $r){ extract($r);?>
  <img title="<?=$account_derived_name?> (<?=$l_stars?>: <?=$l_num($communicant_votes)?>)"
       class="icon<?=($account_id===$o_account_id)?'':' pingable'?>"
       data-id="<?=$account_id?>"
       data-name="<?=explode(' ',$account_derived_name)[0]?>"
       data-fullname="<?=$account_derived_name?>"
       src="/identicon?id=<?=$account_id?>">
<?}?>
