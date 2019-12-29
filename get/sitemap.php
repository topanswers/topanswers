<?
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to sitemap,pg_temp");
header("Content-type: text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <?foreach(db("select community_name, question_id, to_char(change_at,'YYYY-MM-DD".'"T"'."HH24:MI:SS".'"Z"'."') change_at, round(priority::numeric,3) priority from question order by priority desc") as $r){ extract($r);?>
    <url>
      <loc>https://topanswers.xyz/<?=$community_name?>?q=<?=$question_id?></loc>
      <lastmod><?=$change_at?></lastmod>
      <changefreq>yearly</changefreq>
      <priority><?=$priority?></priority>
    </url>
  <?}?>
</urlset> 
