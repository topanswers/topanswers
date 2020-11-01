<?
include '../config.php';
include '../cors.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='POST' || fail(405,'only POSTs allowed here');
isset($_COOKIE['uuid']) || fail(403,'only registered users can POST');
isset($_POST['action']) || fail(400,'must have an "action" parameter');
isset($_POST['room']) || fail(400,'room not set');
db("set search_path to chat,pg_temp");
ccdb("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_POST['room']) || fail(403,'access denied');
switch($_POST['action']){
  case 'new':
    if(isset($_POST['read'])) db("select read(('{'||$1||'}')::integer[])",implode(',',$_POST['read']));
    exit(ccdb("select new($1
                        , nullif($2,'')::integer
                        , (select array_agg(j::integer) from jsonb_array_elements_text($3::jsonb) j)
                        , (select array_agg(decode(j,'hex')) from jsonb_array_elements_text($4::jsonb) j)
                        , (select array_agg(j) from jsonb_array_elements_text($5::jsonb) j)
                        )"
            , $_POST['msg']
            , $_POST['replyid']??''
            , json_encode($_POST['pings']??[])
            , json_encode($_POST['obhashes']??[])
            , json_encode($_POST['obmarkdown']??[]))
            );
  case 'read': exit(ccdb("select read(('{'||$1||'}')::integer[])",implode(',',$_POST['read'])));
  case 'edit': exit(ccdb("select change($1
                                      , $2
                                      , nullif($3,'')::integer
                                      , (select array_agg(j::integer) from jsonb_array_elements_text($4::jsonb) j)
                                      , (select array_agg(decode(j,'hex')) from jsonb_array_elements_text($5::jsonb) j)
                                      , (select array_agg(j) from jsonb_array_elements_text($6::jsonb) j)
                                      )"
                      , $_POST['editid']
                      , $_POST['msg']
                      , $_POST['replyid']??''
                      , json_encode($_POST['pings']??[])
                      , json_encode($_POST['obhashes']??[])
                      , json_encode($_POST['obmarkdown']??[]))
                      );
  case 'flag': exit(ccdb("select flag($1,1)",$_POST['id']));
  case 'unflag': exit(ccdb("select flag($1,0)",$_POST['id']));
  case 'star': exit(ccdb("select set_star($1)",$_POST['id']));
  case 'unstar': exit(ccdb("select remove_star($1)",$_POST['id']));
  case 'dismiss': exit(ccdb("select dismiss_notification($1)",$_POST['id']));
  default: fail(400,'unrecognized action');
}
