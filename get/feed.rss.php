<?
include '../config.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to feed,pg_temp");
db("select login_community($1)",$_GET['community']);
extract(cdb("select community_name,community_display_name
                  , (select jsonb_agg(z)
                     from (select question_id,question_title
                                , to_char(question_at,'Dy, dd Mon YYYY HH24:MI:SS') question_at
                           from question q
                           order by q.question_at desc limit 30) z) items
             from one"));
header("Content-type: text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
?>
<rss version="2.0">
  <channel>
    <title>TA <?=$community_display_name?></title>
    <link>https://topanswers.xyz</link>
    <lastBuildDate><?=$items[0]['question_at']?></lastBuildDate>
    <pubDate><?=$items[0]['question_at']?></pubDate>
    <ttl>600</ttl>

    <?foreach($items as $r){ extract($r,EXTR_PREFIX_ALL,'q');?>
      <item>
        <title><?=$q_question_title?></title>
        <link>https://topanswers.xyz/<?=$community_name?>?q=<?=$q_question_id?></link>
        <guid>https://topanswers.xyz/<?=$community_name?>?q=<?=$q_question_id?></guid>
        <pubDate><?=$question_at?></pubDate>
      </item>
    <?}?>
  
  </channel>
</rss>
