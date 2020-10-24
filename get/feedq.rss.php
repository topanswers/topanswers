<?
include '../config.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to feedq,pg_temp");
db("select login_question($1)",$_GET['q']);
extract(cdb("select question_id,question_title,question_published_at,question_change_at
                  , (select jsonb_agg(z)
                     from (select answer_id,answer_summary
                                , to_char(answer_change_at,'Dy, dd Mon YYYY HH24:MI:SS') answer_change_at
                           from answer
                           order by answer_change_at desc limit 30) z) items
             from one"));
header("Content-type: text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
?>
<rss version="2.0">
  <channel>
    <title><?=$question_title?></title>
    <link>https://topanswers.xyz/<?=$community_name?>?q=<?=$question_id?></link>
    <lastBuildDate><?=$question_change_at?></lastBuildDate>
    <pubDate><?=$question_published_at?></pubDate>
    <ttl>30</ttl>

    <?foreach($items as $r){ extract($r,EXTR_PREFIX_ALL,'a');?>
      <item>
        <title><?=$a_answer_summary?></title>
        <link>https://topanswers.xyz/<?=$community_name?>?q=<?=$question_id?>#a<?=$a_answer_id?></link>
        <guid>https://topanswers.xyz/<?=$community_name?>?q=<?=$question_id?>#a<?=$a_answer_id?></guid>
        <lastBuildDate><?=$a_answer_change_at?></lastBuildDate>
        <pubDate><?=$a_answer_at?></pubDate>
      </item>
    <?}?>
  
  </channel>
</rss>
