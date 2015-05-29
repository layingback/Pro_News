<?php $slideshow = intval($_GET['slideshow']) ? $_GET['slideshow'] : die('slideshow not set');?>
<!-- Pro_News Slideshow php code derived from Coppermine Photo Gallery 1.3.1 for CPG-Nuke -->
<!-- Copyright © 2008-2009 by M Waldron aka layingback -->
<!--  $Revision: 3.6 $ -->
<!--  $Date: 2013-05-28 13:09:05 $ -->
<!--  Author: layingback -->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="ltr" lang="en">
<head>
<title>Pro News &rsaquo; Test of Album Gallery Setup</title>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" href="themes/<?php global $CPG_SESS; echo $CPG_SESS['theme'] ?>/style/style.css" type="text/css" />
<link rel="stylesheet" type="text/css" href="themes/<?php global $CPG_SESS; echo $CPG_SESS['theme'] ?>/style/pro_news.css" />
<link rel="copyright" href="index.php?name=credits" title="Copyrights" />
</head>
<body><script type="text/javascript">
// (C) 2000 www.CodeLifter.com
// http://www.codelifter.com
// Free for all users, but leave in this  header
// NS4-6,IE4-6
// Fade effect only in IE; degrades gracefully
// Set slideShowSpeed (milliseconds)
var slideShowSpeed = <?php echo $slideshow ?>
// Duration of crossfade (seconds)
var crossFadeDuration = 3

// Specify the image files
var Pic = new Array() // don't touch this
// to add more images, just continue
// the pattern, adding to the array below
<?php
global $db, $prefix, $album_orderby, $asc_desc;
$i = 0;
$j = 0;
if (intval($_GET['id'])) {
	$aid = intval($_GET['id']) ? $_GET['id'] : die('id not set');
	$album = intval($_GET['album']) ? $_GET['album'] : die('album not set');
	$pid = intval($_GET['pid']) ? $_GET['pid'] : $pid == '';
	$start_img = '';
	$album = (isset($_GET['album'])&& $_GET['album']!='') ? $_GET['album'] : NULL;
	$album_order = array(0=>'',1=>'title',2=>'title',3=>'filename',4=>'filename',5=>'ctime',6=>'ctime',7=>'pic_rating',8=>'pic_rating');
	$meta = (isset($_GET['meta'])&& $_GET['meta']!='') ? $_GET['meta'] : NULL;
	$thisalbumlink = '';
	if (isset($album)) {
		$thisalbumlink = "&album=$album";
	}else {
		$thisalbumlink = "&meta=$meta";
	}
	$res = $db->sql_query('SELECT album_seq FROM '.$prefix.'_pronews_articles WHERE id ='.$aid);
	if ($db->sql_numrows($res) != 1) { die('id not valid'); }
	$album_seq = $db->sql_fetchrow($res);
	$db->sql_freeresult($res);
	$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title FROM '.$prefix.'_cpg_pictures';
//	$sql = 'SELECT pid pid, filepath, filename, pwidth, pheight, title, remote_url FROM '.$prefix.'_cpg_pictures';  // lb - use after installing my coppermine remote CPG hack
	$sql .= ' WHERE aid='.$album;
	$asc_desc = ($album_seq[album_seq] & 1) ? 'ASC' : 'DESC';
	if ($album_seq['album_seq'] != '0') {$sql .= ' ORDER BY '.$album_order[$album_seq[album_seq]].' '.$asc_desc;}
	$list = $db->sql_fetchrowset($db->sql_query($sql));
	foreach ($list as $picture) {
		$fullsizepath = ($picture['remote_url'] != '' && preg_match("/(?:https?\:\/\/)?([^\.]+\.?[^\.\/]+\.[^\.\/]+[^\.]+)/", $picture['remote_url'], $matches)) ? 'http://'.$matches[1].'/' : $picture['filepath'];		// lb - cpg remote_url hack support
		$picture_url = str_replace("%3A", ":", str_replace("%2F", "/", rawurlencode($fullsizepath.$picture['filename'])));
		echo "Pic[$i] = '" . $picture_url . "'\n";
		if ($pid == '' || $picture['pid'] == $pid) {
			$j = $i;
			$start_img = $picture_url;
		}
		$i++;
	$db->sql_freeresult($list);
	}

}  else {
	$picture_url = "images/pro_news/icons/pro_news.gif";
	echo "Pic[$i] = '" . $picture_url . "'\n";
	$start_img = $picture_url;
}

?>
var t;
var j = <?php echo "$j;\n" ?>
var p = Pic.length;
var pos = j;

var preLoad = new Array()

function preLoadPic(index)
{
	if (Pic[index] != ''){
		window.status='Loading : '+Pic[index];
		preLoad[index] = new Image();
		preLoad[index].src = Pic[index];
		Pic[index] = '';
		window.status='';
	}
}

function runSlideShow(){
	if (document.all){
		document.images.SlideShow.style.filter="blendTrans(duration=2)"
		document.images.SlideShow.style.filter= "blendTrans(duration=crossFadeDuration)"
		document.images.SlideShow.filters.blendTrans.Apply()
	}
	document.images.SlideShow.src = preLoad[j].src
	if (document.all){
		document.images.SlideShow.filters.blendTrans.Play()
	}
	pos = j;
	j = j + 1;
	if (j > (p-1)) j=0;
	t = setTimeout('runSlideShow()', slideShowSpeed);
	preLoadPic(j);
}

preLoadPic(j)
</script>
