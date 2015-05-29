<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2008 by CPG-Nuke Dev Team
  http://dragonflycms.com

  Modified and Copyright © 2007 by Poldi to support Pro_News
  http://www.green-dragon.de
  Enhanced to limit access and Copyright © 2008-2010 by layingback
  http://layingback.net

  $Revision: 3.4 $
  $Date: 2012-11-28 08:05:36 $
  Author: layingback

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Original Source: /cvs/html/rss/news2.php
  Original Revision: 9.5
  Original Author: djmaze
  Original Date: 2005/04/28 01:40:44
**********************************************/
define('XMLFEED', 1);
$root_path = dirname(dirname(__FILE__));
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
	$root_path = str_replace('\\', '/', $root_path); //Damn' windows
}

if (strlen($root_path) > 2) define('BASEDIR', $root_path.'/');
else define('BASEDIR', '../');

require_once(BASEDIR.'includes/cmsinit.inc');
require_once(BASEDIR.'includes/functions/language.php');
require_once(BASEDIR.'includes/nbbcode.php');

$pnsettings = $MAIN_CFG['pro_news'];

$modulename = $pnsettings['module_name'];
if ($modulename == '' || !is_active($modulename)) { die($modulename.' (Pro_News) not active'); }

if ($pnsettings['enbl_rss'] != '0') {
	$where = ($pnsettings['enbl_rss'] >='2' && isset($_GET['sid']) && is_numeric($_GET['sid'])) ? ' AND sid="'.intval($_GET['sid']).'"' : '';
	$where .= ($pnsettings['enbl_rss'] >='4' && isset($_GET['cid']) && is_numeric($_GET['cid'])) ? ' AND c.id="'.intval($_GET['cid']).'"' : '';

	$artsortkey = $pnsettings['art_ordr'] / '2';
	$artsortord = ($pnsettings['art_ordr'] % '2')  ? 'DESC' : 'ASC';
	if ($artsortkey < 1) {
		$artsortfld = 'posttime';
	} elseif ($artsortkey < 2) {
		$artsortfld = 'a.title';
	} elseif ($artsortkey < 3) {
		$artsortfld = 'ratings';
	} else {
		$artsortfld = 'a.counter';
	}

	$sql = 'SELECT a.id, a.title, posttime, intro';
	$sql .= ' FROM '.$prefix.'_pronews_articles as a';
	$sql .= ', '.$prefix.'_pronews_cats as c';
	$sql .= ', '.$prefix.'_pronews_sections as s';
	$sql .= ' WHERE a.catid=c.id AND c.sid=s.id';
	$sql .= ' AND a.approved="1" AND a.active="1"';
	$sql .= ($multilingual ? ' AND (alanguage="'.$currentlang.'" OR alanguage="")' : '');

	if ($pnsettings['enbl_rss'] =='1' || $pnsettings['enbl_rss'] =='2' || $pnsettings['enbl_rss'] =='4') {
		$sql .= ' AND s.in_home="1"';
	}

	$sql .= ' AND (s.view="0" OR s.view="3")';
	$sql .= ($where) ? $where : '';

	if ($pnsettings['display_by'] == '0') {
		$sql .= " ORDER BY display_order DESC, ";
	} elseif ($pnsettings['display_by'] == '1')  {
		$sql .= " ORDER BY s.sequence ASC, display_order DESC, ";
	} else {
		$sql .= " ORDER BY s.sequence ASC, c.sequence ASC, display_order DESC,";
	}

	$sql .= ' '.$artsortfld.' '.$artsortord.' LIMIT 20';

	$result = $db->sql_query($sql);
}

if ($row = $db->sql_fetchrow($result)) {
	$date = date('D, d M Y H:i:s \G\M\T', $row['posttime']);
	header("Date: $date");
} else {
	$date = date('D, d M Y H:i:s \G\M\T', gmtime());
}

$BASEHREF = ereg_replace('//rss.', '//', $BASEHREF);
header('Content-Type: text/xml'); // application/rss+xml
echo '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
<channel>
  <title>'.htmlprepare($sitename).'</title>
  <link>'.$BASEHREF.'</link>
  <description>'.htmlprepare($backend_title).'</description>
  <language>'.$backend_language.'</language>
  <pubDate>'.$date.'</pubDate>
  <ttl>'.(60*24).'</ttl>
  <generator>CPG-Nuke Dragonfly</generator>
  <copyright>'.htmlprepare($sitename).'</copyright>
  <category>News</category>
  <docs>http://backend.userland.com/rss</docs>
  <image>
	<url>'.$BASEHREF.'images/'.$MAIN_CFG['global']['site_logo'].'</url>
	<title>'.htmlprepare($sitename).'</title>
	<link>'.$BASEHREF."</link>
  </image>\n\n";
if ($row) {
	do {
		echo '<item>
  <title>'.htmlprepare($row['title']).'</title>
  <link>'.getlink($modulename.'&amp;aid='.$row['id'], true, true).'</link>
  <description>'.htmlprepare(decode_bb_all($row['intro'], 1, true), false, ENT_QUOTES, true).'</description>
  <pubDate>'.date('D, d M Y H:i:s \G\M\T', $row['posttime'])."</pubDate>
</item>\n\n";
	}
	while ($row = $db->sql_fetchrow($result));
}
?>
</channel>
</rss>
