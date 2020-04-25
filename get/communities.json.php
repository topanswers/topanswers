<?
include '../config.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
db("set search_path to community,pg_temp");
exit(ccdb("select json_agg(z)
           from (select community_display_name \"name\"
                      , 'https://topanswers.xyz/'||community_name canonical_url
                      , community_name url_slug
                      , 'https://topanswers.xyz/communityicon?community='||community_name logo_url
                      , community_rgb_dark color_dark
                      , community_rgb_light color_light
                 from community
                 order by community_ordinal) z"));
