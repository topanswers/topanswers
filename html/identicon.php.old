<?php

/* generate sprite for corners and sides */
function getsprite($shape,$R,$G,$B,$rotation) {
	global $spriteZ;
	$sprite=imagecreatetruecolor($spriteZ,$spriteZ);
	imageantialias($sprite,TRUE);
	$fg=imagecolorallocate($sprite,$R,$G,$B);
	$bg=imagecolorallocate($sprite,255,255,255);
	imagefilledrectangle($sprite,0,0,$spriteZ,$spriteZ,$bg);
	switch($shape) {
		case 0: // triangle
			$shape=array(
				0.5,1,
				1,0,
				1,1
			);
			break;
		case 1: // parallelogram
			$shape=array(
				0.5,0,
				1,0,
				0.5,1,
				0,1
			);
			break;
		case 2: // mouse ears
			$shape=array(
				0.5,0,
				1,0,
				1,1,
				0.5,1,
				1,0.5
			);
			break;
		case 3: // ribbon
			$shape=array(
				0,0.5,
				0.5,0,
				1,0.5,
				0.5,1,
				0.5,0.5
			);
			break;
		case 4: // sails
			$shape=array(
				0,0.5,
				1,0,
				1,1,
				0,1,
				1,0.5
			);
			break;
		case 5: // fins
			$shape=array(
				1,0,
				1,1,
				0.5,1,
				1,0.5,
				0.5,0.5
			);
			break;
		case 6: // beak
			$shape=array(
				0,0,
				1,0,
				1,0.5,
				0,0,
				0.5,1,
				0,1
			);
			break;
		case 7: // chevron
			$shape=array(
				0,0,
				0.5,0,
				1,0.5,
				0.5,1,
				0,1,
				0.5,0.5
			);
			break;
		case 8: // fish
			$shape=array(
				0.5,0,
				0.5,0.5,
				1,0.5,
				1,1,
				0.5,1,
				0.5,0.5,
				0,0.5
			);
			break;
		case 9: // kite
			$shape=array(
				0,0,
				1,0,
				0.5,0.5,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
		case 10: // trough
			$shape=array(
				0,0.5,
				0.5,1,
				1,0.5,
				0.5,0,
				1,0,
				1,1,
				0,1
			);
			break;
		case 11: // rays
			$shape=array(
				0.5,0,
				1,0,
				1,1,
				0.5,1,
				1,0.75,
				0.5,0.5,
				1,0.25
			);
			break;
		case 12: // double rhombus
			$shape=array(
				0,0.5,
				0.5,0,
				0.5,0.5,
				1,0,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
		case 13: // crown
			$shape=array(
				0,0,
				1,0,
				1,1,
				0,1,
				1,0.5,
				0.5,0.25,
				0.5,0.75,
				0,0.5,
				0.5,0.25
			);
			break;
		case 14: // radioactive
			$shape=array(
				0,0.5,
				0.5,0.5,
				0.5,0,
				1,0,
				0.5,0.5,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
		default: // tiles
			$shape=array(
				0,0,
				1,0,
				0.5,0.5,
				0.5,0,
				0,0.5,
				1,0.5,
				0.5,1,
				0.5,0.5,
				0,1
			);
			break;
	}
	/* apply ratios */
	for ($i=0;$i<count($shape);$i++)
		$shape[$i]=$shape[$i]*$spriteZ;
	imagefilledpolygon($sprite,$shape,count($shape)/2,$fg);
	/* rotate the sprite */
	for ($i=0;$i<$rotation;$i++)
		$sprite=imagerotate($sprite,90,$bg);
	return $sprite;
}

/* generate sprite for center block */
function getcenter($shape,$fR,$fG,$fB,$bR,$bG,$bB,$usebg) {
	global $spriteZ;
	$sprite=imagecreatetruecolor($spriteZ,$spriteZ);
	imageantialias($sprite,TRUE);
	$fg=imagecolorallocate($sprite,$fR,$fG,$fB);
	/* make sure there's enough contrast before we use background color of side sprite */
	if ($usebg>0 && (abs($fR-$bR)>127 || abs($fG-$bG)>127 || abs($fB-$bB)>127))
		$bg=imagecolorallocate($sprite,$bR,$bG,$bB);
	else
		$bg=imagecolorallocate($sprite,255,255,255);
	imagefilledrectangle($sprite,0,0,$spriteZ,$spriteZ,$bg);
	switch($shape) {
		case 0: // empty
			$shape=array();
			break;
		case 1: // fill
			$shape=array(
				0,0,
				1,0,
				1,1,
				0,1
			);
			break;
		case 2: // diamond
			$shape=array(
				0.5,0,
				1,0.5,
				0.5,1,
				0,0.5
			);
			break;
		case 3: // reverse diamond
			$shape=array(
				0,0,
				1,0,
				1,1,
				0,1,
				0,0.5,
				0.5,1,
				1,0.5,
				0.5,0,
				0,0.5
			);
			break;
		case 4: // cross
			$shape=array(
				0.25,0,
				0.75,0,
				0.5,0.5,
				1,0.25,
				1,0.75,
				0.5,0.5,
				0.75,1,
				0.25,1,
				0.5,0.5,
				0,0.75,
				0,0.25,
				0.5,0.5
			);
			break;
		case 5: // morning star
			$shape=array(
				0,0,
				0.5,0.25,
				1,0,
				0.75,0.5,
				1,1,
				0.5,0.75,
				0,1,
				0.25,0.5
			);
			break;
		case 6: // small square
			$shape=array(
				0.33,0.33,
				0.67,0.33,
				0.67,0.67,
				0.33,0.67
			);
			break;
		case 7: // checkerboard
			$shape=array(
				0,0,
				0.33,0,
				0.33,0.33,
				0.66,0.33,
				0.67,0,
				1,0,
				1,0.33,
				0.67,0.33,
				0.67,0.67,
				1,0.67,
				1,1,
				0.67,1,
				0.67,0.67,
				0.33,0.67,
				0.33,1,
				0,1,
				0,0.67,
				0.33,0.67,
				0.33,0.33,
				0,0.33
			);
			break;
	}
	/* apply ratios */
	for ($i=0;$i<count($shape);$i++)
		$shape[$i]=$shape[$i]*$spriteZ;
	if (count($shape)>0)
		imagefilledpolygon($sprite,$shape,count($shape)/2,$fg);
	return $sprite;
}

/* parse hash string */

$csh=hexdec(substr($_GET["hash"],0,1)); // corner sprite shape
$ssh=hexdec(substr($_GET["hash"],1,1)); // side sprite shape
$xsh=hexdec(substr($_GET["hash"],2,1))&7; // center sprite shape

$cro=hexdec(substr($_GET["hash"],3,1))&3; // corner sprite rotation
$sro=hexdec(substr($_GET["hash"],4,1))&3; // side sprite rotation
$xbg=hexdec(substr($_GET["hash"],5,1))%2; // center sprite background

/* corner sprite foreground color */
$cfr=hexdec(substr($_GET["hash"],6,2));
$cfg=hexdec(substr($_GET["hash"],8,2));
$cfb=hexdec(substr($_GET["hash"],10,2));

/* side sprite foreground color */
$sfr=hexdec(substr($_GET["hash"],12,2));
$sfg=hexdec(substr($_GET["hash"],14,2));
$sfb=hexdec(substr($_GET["hash"],16,2));

/* final angle of rotation */
$angle=hexdec(substr($_GET["hash"],18,2));

/* size of each sprite */
$spriteZ=128;

/* start with blank 3x3 identicon */
$identicon=imagecreatetruecolor($spriteZ*3,$spriteZ*3);
imageantialias($identicon,TRUE);

/* assign white as background */
$bg=imagecolorallocate($identicon,255,255,255);
imagefilledrectangle($identicon,0,0,$spriteZ,$spriteZ,$bg);

/* generate corner sprites */
$corner=getsprite($csh,$cfr,$cfg,$cfb,$cro);
imagecopy($identicon,$corner,0,0,0,0,$spriteZ,$spriteZ);
$corner=imagerotate($corner,90,$bg);
imagecopy($identicon,$corner,0,$spriteZ*2,0,0,$spriteZ,$spriteZ);
$corner=imagerotate($corner,90,$bg);
imagecopy($identicon,$corner,$spriteZ*2,$spriteZ*2,0,0,$spriteZ,$spriteZ);
$corner=imagerotate($corner,90,$bg);
imagecopy($identicon,$corner,$spriteZ*2,0,0,0,$spriteZ,$spriteZ);

/* generate side sprites */
$side=getsprite($ssh,$sfr,$sfg,$sfb,$sro);
imagecopy($identicon,$side,$spriteZ,0,0,0,$spriteZ,$spriteZ);
$side=imagerotate($side,90,$bg);
imagecopy($identicon,$side,0,$spriteZ,0,0,$spriteZ,$spriteZ);
$side=imagerotate($side,90,$bg);
imagecopy($identicon,$side,$spriteZ,$spriteZ*2,0,0,$spriteZ,$spriteZ);
$side=imagerotate($side,90,$bg);
imagecopy($identicon,$side,$spriteZ*2,$spriteZ,0,0,$spriteZ,$spriteZ);

/* generate center sprite */
$center=getcenter($xsh,$cfr,$cfg,$cfb,$sfr,$sfg,$sfb,$xbg);
imagecopy($identicon,$center,$spriteZ,$spriteZ,0,0,$spriteZ,$spriteZ);

// $identicon=imagerotate($identicon,$angle,$bg);

/* make white transparent */
imagecolortransparent($identicon,$bg);

/* create blank image according to specified dimensions */
$resized=imagecreatetruecolor($_GET["size"],$_GET["size"]);
imageantialias($resized,TRUE);

/* assign white as background */
$bg=imagecolorallocate($resized,255,255,255);
imagefilledrectangle($resized,0,0,$_GET["size"],$_GET["size"],$bg);

/* resize identicon according to specification */
imagecopyresampled($resized,$identicon,0,0,(imagesx($identicon)-$spriteZ*3)/2,(imagesx($identicon)-$spriteZ*3)/2,$_GET["size"],$_GET["size"],$spriteZ*3,$spriteZ*3);

/* make white transparent */
imagecolortransparent($resized,$bg);

/* and finally, send to standard output */
header("Content-Type: image/png");
imagepng($resized);

?>
