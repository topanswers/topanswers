<?
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
header("Content-Type: text/plain");
if($_SERVER['SERVER_NAME']==='topanswers.xyz'){?>
User-Agent: *
Disallow: /poll?
Disallow: /question?
Disallow: /answer?
Disallow: /profile$

User-agent: Googlebot-Image
Disallow: /identicon*
Disallow: /roomicon*

Sitemap: https://topanswers.xyz/sitemap.xml

<?}else{?>
User-agent: *
Disallow: / 

<?}?>
