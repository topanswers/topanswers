<?php
include '../config.php';
include '../db.php';
$_SERVER['REQUEST_METHOD']==='GET' || fail(405,'only GETs allowed here');
isset($_GET['id']) || fail(400,'room id must be set');
db("set search_path to roomicon,pg_temp");
db("select login_room(nullif($1,'')::uuid,nullif($2,'')::integer)",$_COOKIE['uuid']??'',$_GET['id']);
extract(cdb("select room_id
                  , get_byte(community_dark_shade,0) community_dark_shade_r
                  , get_byte(community_dark_shade,1) community_dark_shade_g
                  , get_byte(community_dark_shade,2) community_dark_shade_b
             from one"));

header('X-Powered-By: ');
header('Cache-Control: public, max-age=31536000, immutable');


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
$id = (crc32($room_id) % $max) + 1;

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

// build pixels as long there are bits in
for($i = 0; $i < $pixelCount; $i++) {

	// get least significant bit
	$onoff = $id & 1;

	// shift that bit out
	$id = $id >> 1;
	
	// if the least significant bit is 1, draw that pixel
	if($onoff > 0) {

		// calculate upper left corner of pixel
		$x1 = MARGIN_X + floor($i / GRID_COUNT_W) * BOX_SIZE_W;
		$y1 = MARGIN_Y + ($i % GRID_COUNT_H) * BOX_SIZE_H;
		// calculate lower right corner of pixel
		$x2 = $x1 + BOX_SIZE_W;
		$y2 = $y1 + BOX_SIZE_H;
		// draw pixel
		imagefilledrectangle($im, $x1, $y1, $x2, $y2, $col);

		// draw mirrored pixel with same y-coordinates, different
		// x-coordinates for left and right edge
		$x1 = MARGIN_X + (GRID_COUNT_W-1)*BOX_SIZE_W - floor($i / GRID_COUNT_W) * BOX_SIZE_W;
		$x2 = $x1 + BOX_SIZE_W;
		imagefilledrectangle($im, $x1, $y1, $x2, $y2, $col);
	}
}

// output that image
header("Content-Type: image/png");
imagepng($im);
