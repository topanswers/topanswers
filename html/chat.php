<?
$connection = pg_connect('dbname=postgres user=world') or die(header('HTTP/1.0 500 Internal Server Error'));
function db($query,...$params) {
  global $connection;
  pg_send_query_params($connection, $query, $params);
  $res = pg_get_result($connection);
  if(pg_result_error($res)){ header('HTTP/1.0 500 Internal Server Error'); exit(pg_result_error_field($res,PGSQL_DIAG_SQLSTATE).htmlspecialchars(pg_result_error($res))); }
  ($rows = pg_fetch_all($res)) || ($rows = []);
  return $rows;
}
function cdb($query,...$params){ return current(db($query,...$params)); }
function ccdb($query,...$params){ return current(cdb($query,...$params)); }
header('X-Powered-By: ');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
$uuid = $_COOKIE['uuid'] ?? false;
if($uuid) ccdb("select login($1)",$uuid);
if($_SERVER['REQUEST_METHOD']==='POST'){
  isset($_POST['action']) or die('posts must have an "action" parameter');
  switch($_POST['action']) {
    case 'flag': exit(ccdb("select set_chat_flag($1)",$_POST['id']));
    case 'unflag': exit(ccdb("select remove_chat_flag($1)",$_POST['id']));
    case 'star': exit(ccdb("select set_chat_star($1)",$_POST['id']));
    case 'unstar': exit(ccdb("select remove_chat_star($1)",$_POST['id']));
    case 'dismiss': exit(ccdb("select dismiss_notification($1)",$_POST['id']));
    default: die('unrecognized action');
  }
}
if(isset($_GET['changes'])) exit(ccdb("select coalesce(jsonb_agg(jsonb_build_array(chat_id,chat_change_id)),'[]') from chat where chat_change_id>$1",$_GET['id']));
if(isset($_GET['change'])) exit(ccdb("select json_build_object('change',chat_change_id,'flags',chat_flag_count,'i_flagged',chat_flag_at is not null,'stars',chat_star_count,'i_starred',chat_star_at is not null,'msg',chat_markdown)
                                      from chat natural left join chat_flag natural left join chat_star
                                      where chat_id=$1",$_GET['id']));
if(!isset($_GET['room'])) die('room not set');
$room = $_GET['room'];
ccdb("select count(*) from room where room_id=$1",$room)==='1' or die('invalid room');
if(isset($_GET['poll'])) exit(ccdb("select json_build_object('c',coalesce((select max(chat_id) from chat where room_id=$1),0)
                                                            ,'cc',coalesce((select max(chat_change_id) from chat where room_id=$1),0)
                                                            ,'n',coalesce((select max(chat_notification_at)::text from chat_notification natural join chat),'')
                                  )",$room));
extract(cdb("select community_name community
                  , encode(community_dark_shade,'hex') colour_dark, encode(community_mid_shade,'hex') colour_mid, encode(community_light_shade,'hex') colour_light, encode(community_highlight_color,'hex') colour_highlight
             from room natural join community
             where room_id=$1",$room));
$id = $_GET['id']??ccdb("select greatest(min(chat_id)-1,0) from (select chat_id from chat where room_id=$1 order by chat_id desc limit 100) z",$room);
?>
<?foreach(db("select *
              from(select *, (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and coalesce(chat_gap<60,false) chat_account_is_repeat
                   from (select chat_id,account_id,chat_reply_id,chat_markdown,account_is_me,chat_flag_count,chat_star_count,chat_at,chat_change_id
                              , to_char(chat_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') chat_at_iso
                              , coalesce(nullif(account_name,''),'Anonymous') account_name
                              , (select coalesce(nullif(account_name,''),'Anonymous') from chat natural join account where chat_id=c.chat_reply_id) reply_account_name
                              , (select account_is_me from chat natural join account where chat_id=c.chat_reply_id) reply_account_is_me
                              , round(extract('epoch' from chat_at-(lag(chat_at) over (order by chat_at)))) chat_gap
                              , chat_flag_at is not null is_flagged
                              , chat_star_at is not null is_starred
                              , (lag(account_id) over (order by chat_at)) is not distinct from account_id and chat_reply_id is null and (lag(chat_reply_id) over (order by chat_at)) is null chat_account_will_repeat
                         from chat c natural join account natural left join chat_flag natural left join chat_star
                         where room_id=$1 and chat_id>=$2".($uuid?"":" and chat_flag_count=0").") z ) z
              where chat_id>$2
              order by chat_at",$room,$id) as $r){ extract($r);?>
  <?if($chat_gap&&($chat_account_is_repeat==='f')){?>
    <div class="spacer<?=$chat_gap>600?' bigspacer':''?>" style="line-height: <?=round(log(1+$chat_gap)/4,2)?>em;" data-gap="<?=$chat_gap?>" data-at="<?=$chat_at_iso?>"><span></span><span></span></div>
  <?}?>
  <div id="c<?=$chat_id?>" class="message<?=($account_is_me==='t')?' mine':''?><?=($chat_account_is_repeat==='t')?' merged':''?>" data-id="<?=$chat_id?>" data-name="<?=$account_name?>" data-reply-id="<?=$chat_reply_id?>" data-change-id="<?=$chat_change_id?>" data-at="<?=$chat_at_iso?>">
    <small class="who"><?=($account_is_me==='t')?'<em>Me</em>':$account_name?><?=$chat_reply_id?'<a href="#c'.$chat_reply_id.'" style="color: #'.$colour_dark.'; text-decoration: none;">&nbsp;replying to&nbsp;</a>'.(($reply_account_is_me==='t')?'<em>Me</em>':$reply_account_name):''?>:</small>
    <img class="identicon" src="/identicon.php?id=<?=$account_id?>">
    <div class="markdown-wrapper">
      <button class="button reply" title="reply"><i class="fa fa-reply fa-rotate-180"></i></button>
      <div class="markdown" data-markdown="<?=htmlspecialchars($chat_markdown)?>"></div>
    </div>
    <span class="buttons">
      <?if($account_is_me==='t'){?>
        <button class="button<?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star"></i><span><?=($chat_star_count>0)?$chat_star_count:''?></span></button>
        <button class="button<?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag"></i><span><?=($chat_flag_count>0)?$chat_flag_count:''?></span></button>
      <?}else{?>
        <button class="button <?=($is_starred==='t')?'unstar':'star'?><?=($chat_star_count>0)?' marked':''?>"><i class="fa fa-fw fa-star<?=($is_starred==='t')?'':'-o'?>"></i><span><?=($chat_star_count>0)?$chat_star_count:''?></span></button>
        <button class="button <?=($is_flagged==='t')?'unflag':'flag'?><?=($chat_flag_count>0)?' marked':''?>"><i class="fa fa-fw fa-flag<?=($is_flagged==='t')?'':'-o'?>"></i><span><?=($chat_flag_count>0)?$chat_flag_count:''?></span></button>
      <?}?>
    </span>
  </div>
<?}?>
