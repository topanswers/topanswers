<?php
include '../config.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
db("set search_path to communityicon,pg_temp");

if(!isset($_GET['community'])){
  header("Content-Type: image/jpeg");
  echo pg_unescape_bytea(ccdb("select community_image from one"));
  exit;
}

$auth = ccdb("select login_community(nullif($1,'')::uuid,$2)",$_COOKIE['uuid']??'',$_GET['community']);
extract(cdb("select community_id,community_image
                  , community_image is not null community_has_image
                  , get_byte(community_dark_shade,0) community_dark_shade_r
                  , get_byte(community_dark_shade,1) community_dark_shade_g
                  , get_byte(community_dark_shade,2) community_dark_shade_b
             from one"));

header('X-Powered-By: ');
header('Cache-Control: max-age=8600');

if($community_has_image){
  header("Content-Type: image/jpeg");
  echo pg_unescape_bytea($community_image);
  exit;
}

// Settings
define('MARGIN_X', 10);        // Margin on the left and right edge in px
define('MARGIN_Y', 10);        // Margin on the upper and lower edge in px
define('BOX_SIZE_W', 14);      // Width of the individual "pixels" in px
define('BOX_SIZE_H', 14);      // Height of the individual "pixels" in px
define('GRID_COUNT_W', 5);     // Horizontal "pixel"-count
define('GRID_COUNT_H', 5);     // Vertical "pixel"-count
define('BG_COLOR', '#FFFFFF'); // Background color as 6-digit hexadecimal rgb code

// calculate image dimensions based on settings
$sizeX = 2*MARGIN_X + GRID_COUNT_W*BOX_SIZE_W + 1;
$sizeY = 2*MARGIN_Y + GRID_COUNT_H*BOX_SIZE_H + 1;

// calculate significant pixel count and the highest presentable number
$pixelCount = GRID_COUNT_H * ceil(GRID_COUNT_W/2);
$max = pow(2, $pixelCount) - 1;

// normalize (modulo) the passed id
$id = (crc32($community_id) % $max) + 1;

// create image canvas and fill with default background color
$im = imagecreatetruecolor($sizeX, $sizeY);
$colBG_r = hexdec(substr(BG_COLOR, -6, 2));
$colBG_g = hexdec(substr(BG_COLOR, -4, 2));
$colBG_b = hexdec(substr(BG_COLOR, -2, 2));
$colBG = imagecolorallocate($im, $colBG_r, $colBG_g, $colBG_b);
$colTransparentBG = imagecolortransparent($im, $colBG);
//imagefill($im, 0, 0, $colBG);
imagefill($im, 0, 0, $colTransparentBG);
$col = imagecolorallocate($im, $community_dark_shade_r, $community_dark_shade_g, $community_dark_shade_b);
imagefilledrectangle($im, MARGIN_X, MARGIN_Y, $sizeX-MARGIN_X, $sizeY-MARGIN_Y, $col);

// output that image
header("Content-Type: image/png");
imagepng($im);
