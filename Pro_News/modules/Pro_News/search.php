<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Copyright © 2007 - 2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.4 $
  $Date: 2012-04-03 14:20:03 $
   Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

global $MAIN_CFG, $pn_module_name;
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];
get_lang($pn_module_name);
$rslts = isset($_GET['rslts']) ? intval($_GET['rslts']) : '';
if ($rslts) {
	results($rslts);
} else {
	search();
}
return;

function search() {
	global $MAIN_CFG, $pn_module_name, $cpgtpl, $prefix, $db, $CPG_SESS, $LNG, $pagetitle;
	$id_catg = isset($_GET['id_catg']) ? $_GET['id_catg'] : '';
	$pagetitle .= ' '._BC_DELIM.' '._PNSEARCHART;
	require_once('header.php');

	$secs[-1] = _ALL;
	$seclist = $db->sql_query('SELECT id, title, view FROM '.$prefix.'_pronews_sections WHERE id<>"0" ORDER BY title');
	while (list($id, $title, $view) = $db->sql_fetchrow($seclist)) {
		if (($view == '0') || (can_admin('Pro_News')) || (is_user() && (($view == '1') || (($view > 3) && (isset($userinfo['_mem_of_groups'][$view - 3])))))) {
			$secs[$id] = $title;
		}
	}
	$db->sql_freeresult($seclist);

	$sql = 'SELECT s.title stitle, s.view, c.title, c.id FROM '.$prefix.'_pronews_sections as s,';
	$sql .= ' '.$prefix.'_pronews_cats as c WHERE c.sid=s.id';

	$list = $db->sql_fetchrowset($db->sql_query($sql));
	if (($list) && ($list != '')) {
		foreach ($list as $row) {
			if (($row['view'] == '0') || (can_admin('Pro_News')) || (is_user() && (($row['view'] == '1') || (($row['view'] > 3) && (isset($userinfo['_mem_of_groups'][$row['view'] - 3])))))) {
				$list2[$row['stitle']][$row['title']][] = $row['id'];
			}
		}
		$seccat = '<select name="s_cat"><option value="">'._ALL.'</option>';
		foreach ($list2 as $row => $value) {
			$seccat .= '<optgroup label="'.$row.'">';
			foreach ($value as $op => $tid) {
				$seccat .= '<option value="'.$tid['0'].'">'.$op.'</option>';
			}
			$seccat .= '</optgroup>';
		}
		$seccat .= '</select>';
	}

	$nowdatetmp = date('m d Y');
	$datearraytmp = explode (' ', $nowdatetmp);
	$start = '<select name="s_start_date" onchange="CheckWithDate();">';
	for ($i=1; $i<=31; $i++) {
		$start .= '<option value="'.$i.'" '.(($i == $datearraytmp[1]) ? ' selected="selected"' : '').'>';
		$start .= ($i < 10 ? '0'.$i : $i);
		$start .= "</option>\n";
	}
	$m_array = $LNG['_time']['M'];
	$start .= '</select> <select name="s_start_month" onchange="CheckWithDate();">';
	foreach ($m_array AS $num => $mon) {
		$start .= '<option value="'.$num.'" '.(($num == $datearraytmp[0]-3) ? ' selected="selected"' : '').'>';
		$start .= $mon;
		$start .= '</option>';
	}
	$start .= '</select> <select name="s_start_year" onchange="CheckWithDate();">';
	for ($i=$datearraytmp[2]-15; $i<=$datearraytmp[2]; $i++) {
		$start .= '<option value="'.$i.'" '.(($i == $datearraytmp[2]) ? ' selected="selected"' : '').'>';
		$start .= $i;
		$start .= '</option>';
	}
	$start .= '</select>';

	$end = '<select name="s_end_date" onchange="CheckWithDate();">';
	for ($i=1; $i<=31; $i++) {
		$end .= '<option value="'.$i.'" '.(($i == $datearraytmp[1]) ? ' selected="selected"' : '').'>';
		$end .= ($i < 10 ? '0'.$i : $i);
		$end .= '</option>';
	}
	$end .= '</select> <select name="s_end_month" onchange="CheckWithDate();">';
	foreach ($m_array AS $num => $mon) {
		$end .= '<option value="'.$num.'" '.(($num == $datearraytmp[0]) ? ' selected="selected"' : '').'>';
		$end .= $mon;
		$end .= '</option>';
	}
	$end .= '</select> <select name="s_end_year" onchange="CheckWithDate();">';
	for ($i=$datearraytmp[2]-15; $i<=$datearraytmp[2]; $i++) {
		$end .= '<option value="'.$i.'" '.(($i == $datearraytmp[2]) ? ' selected="selected"' : '').'>';
		$end .= $i;
		$end .= '</option>';
	}
	$end .= '</select>';

	$cpgtpl->assign_block_vars('srch_form', array(
		'S_FORMSTART' => "<script type=\"text/javascript\">\n"
."function CheckWithDate() {document.searharts.s_with_date.checked = true;}\n"
."</script>".'<form name="searharts" id="searharts" action="'.getlink($pn_module_name.'&amp;mode=srch&amp;rslts=1').'" method="post">',
		'S_FORMEND' => '</form>',
		'L_TITLE' => _PNSEARCHART,
		'S_TITLE' => _PNTTLCONTENT,
		'T_TITLE' => '<input type="text" name="s_title" size="50" />',
		'S_DESC' => _PNARTCONTENT,
		'T_DESC' => '<input type="text" name="s_desc" size="50" />',
		'S_SEC' => _PNSECTION,
		'T_SEC' => select_box('s_sec', 'ALL', $secs),
		'L_OR' => _PNOR,
		'S_CAT' => _PNCAT,
		'T_CAT' => $seccat,
		'S_POSTBY' => _PNBYAUTHOR,
		'T_POSTBY' => '<input type="text" name="s_author" size="30" />',
		'S_RATING' => _PNBYRATING,
		'T_RATING' => select_box('s_rating', '-1', array(-1=>_PNALL, 0=>_PNNONE, 1=>_PN1ORMORE, 2=>_PN2ORMORE, 3=>_PN3ORMORE, 4=>_PN4ORMORE, 5=>_PN5)),
		'S_YES' => _PNYES,
		'S_NO' => _PNNO,
		'S_DONTCARE' => _PNDONTCARE,
		'S_INCLIMG' => _PNINCLIMG,
		'T_INCLIMGY' => '<input type="radio" name="s_inclimg" value="1" />',
		'T_INCLIMGN' => '<input type="radio" name="s_inclimg" value="0" />',
		'T_INCLIMGDC' => '<input type="radio" name="s_inclimg" value="-1" checked="checked" />',
		'S_CAPTION' => _PNCAPCONTENT,
		'T_CAPTION' => '<input type="text" name="s_caption" size="30" />',
		'S_PHOTOALB' => _PNPHOTALB,
		'T_PHOTOALBY' => '<input type="radio" name="s_photalb" value="1" /> ',
		'T_PHOTOALBN' => '<input type="radio" name="s_photalb" value="0" />',
		'T_PHOTOALBDC' => '<input type="radio" name="s_photalb" value="-1" checked="checked" />',
		'S_POSTTIME' => _PNWITHDATE,
		'T_POSTTIME' => '<input type="checkbox" name="s_with_date" value="1" />',
		'S_STARTDATE' => _PNCALSTART,
		'T_STARTDATE' => $start,
		'S_ENDDATE' => _PNCALEND,
		'T_ENDDATE' => $end,
		'L_RSLTS' => _PNRESULTS,
		'S_INLIST' => _PNINLIST,
		'T_INLIST' => '<input type="radio" name="s_inlist" value="1" />',
		'S_BYART' => _PNBYART,
		'T_BYART' => '<input type="radio" name="s_inlist" value="0" checked="checked" />',
		'S_SEARCH' => '<input type="submit" value="'._PNSEARCH.'" />',
		'S_BACK' => '<input type="button" value="'._PNGOBACK2.'" onclick="javascript:history.go(-1)" />',
	));
	$cpgtpl->set_filenames(array('body' => 'pronews/search.html'));
	$cpgtpl->display('body', false);

}


// -------------------------------------------------------------------------

function results($page=1) {
	global $prefix, $db, $pn_module_name, $cpgtpl, $gblsettings, $pnsettings, $multilingual, $currentlang, $CPG_SESS, $BASEHREF, $pagetitle, $userinfo;

	$pagetitle .= ' '._BC_DELIM.' '._PNSEARCHART;

// The pagination include below is not required for CPG-Nuke 9.1.x or later
	if ($gblsettings['Version_Num'] == "9.0.6.1") {
		require_once('includes/pagination.php');
	}
// echo '<br />rslts='.print_r($_POST);
	$numarticles = isset($_POST['numarticles']) ? ($_POST['numarticles']) : '';

	$s_title = isset($_POST['s_title']) ? Fix_Quotes($_POST['s_title'],1) : '';
	$s_desc = isset($_POST['s_desc']) ? Fix_Quotes($_POST['s_desc'],1) : '';
	$s_sec = isset($_POST['s_sec']) ? intval($_POST['s_sec']) : -1;
	$s_cat = isset($_POST['s_cat']) ? intval($_POST['s_cat']) : -1;
	$s_author = isset($_POST['s_author']) ? Fix_Quotes($_POST['s_author']) : '';
	$s_rating = isset($_POST['s_rating']) ? intval($_POST['s_rating']) : -1;
	$s_inclimg = isset($_POST['s_inclimg']) ? intval($_POST['s_inclimg']) : 0;
	$s_caption = isset($_POST['s_caption']) ? Fix_Quotes($_POST['s_caption'],1) : '';
	$s_photalb = isset($_POST['s_photalb']) ? intval($_POST['s_photalb']) : 0;
	$s_with_date = isset($_POST['s_with_date']) ? intval($_POST['s_with_date']) : 0;
	$s_start_date = isset($_POST['s_start_date']) ? intval($_POST['s_start_date']) : 0;
	$s_start_month = isset($_POST['s_start_month']) ? intval($_POST['s_start_month']) : 0;
	$s_start_year = isset($_POST['s_start_year']) ? intval($_POST['s_start_year']) : 0;
	$s_end_date = isset($_POST['s_end_date']) ? intval($_POST['s_end_date']) : 0;
	$s_end_month = isset($_POST['s_end_month']) ? intval($_POST['s_end_month']) : 0;
	$s_end_year = isset($_POST['s_end_year']) ? intval($_POST['s_end_year']) : 0;
	$s_inlist = isset($_POST['s_inlist']) ? intval($_POST['s_inlist']) : '0';


	$arts_per_page = ($s_inlist) ? $pnsettings['srch_num_inlist'] : $pnsettings['srch_num_arts'];
	$offset = ($page - 1) * $arts_per_page;
// echo '<br />sec='.$s_sec.' cat='.$s_cat;
	$seceg = ($s_sec > 0 && $s_cat == 0) ? "s.id=$s_sec" : 's.id > 0';
	$categ = ($s_cat > 0) ? "catid=$s_cat" : 'catid > 0';

	$select = 'SELECT a.id as aid, postby, a.title, posttime, intro, image, caption, counter, score, ratings, catid, c.title as ctitle, icon, s.title as stitle, s.view as sview FROM ';
	$sql = $prefix.'_pronews_articles as a, '.$prefix.'_pronews_cats as c, '.$prefix.'_pronews_sections as s';
	$sql .= ' WHERE a.catid = c.id AND c.sid = s.id AND '.$seceg.' AND '.$categ;
	$sql .= ' AND approved = 1 AND active = 1';
	$sql_dtls = '';
	if ($s_title != '' && $s_desc != '') {
		$sql_dtls .= ' AND (a.title LIKE "%'.$s_title.'%" AND (a.intro LIKE "%'.$s_desc.'%" OR a.content LIKE "%'.$s_desc.'%"))';
	} else if ($s_title != '') {
		$sql_dtls .= ' AND a.title LIKE "%'.$s_title.'%"';
	} else if ($s_desc != '') {
		$sql_dtls .= ' AND (a.intro LIKE "%'.$s_desc.'%" OR a.content LIKE "%'.$s_desc.'%")';
	}
	if ($s_author != '') {
		$sql_dtls .= ' AND postby="'.$s_author.'"';
	}
	if ($s_rating == '0') {
		$sql_dtls .= ' AND ratings="0"';
	} else if ($s_rating > '0') {
		$sql_dtls .= ' AND score>="'.$s_rating.'"';
	}
	if ($s_photalb == '1') {
		$sql_dtls .= ' AND album_id!="0"';
	} else if ($s_photalb == '0') {
		$sql_dtls .= ' AND album_id="0"';
	}
	if ($s_inclimg == '1') {
		$sql_dtls .= ' AND (image!="" OR image2!="")';
	} else if ($s_inclimg == '0') {
		$sql_dtls .= ' AND image="" AND image2=""';
	}
	if ($s_caption != '') {
		$sql_dtls .= ' AND a.caption LIKE "%'.$s_caption.'%"';
	}
	if (($s_with_date == 1) && ($s_start_year != '') && ($s_end_year != '')) {
		$nowdate = date('d m Y');
		$nowdatearray = explode (' ', $nowdate);
		if (($s_start_year > $nowdatearray[2]) || ($s_end_year > $nowdatearray[2])) {
			$s_start_year = $nowdatearray[2];
			$s_end_year = $nowdatearray[2];
		}
		if (($s_start_month == 2) && ($s_start_date >= 29 )) {
			if ($s_start_year % 4 != 0) {
				$s_start_date = 28;
			}
		}
		if (($s_end_month == 2) && ($s_end_date >= 29 )) {
			if ($s_end_year % 4 != 0) {
				$s_end_date = 28;
			}
		}
		$startUnixTime = mktime(0, 0, 0,$s_start_month, $s_start_date, $s_start_year);
		$endUnixTime = mktime(59, 59 , 59,$s_end_month, $s_end_date, $s_end_year);
		$sql_dtls .= " AND (posttime > '$startUnixTime' AND posttime < '$endUnixTime')";
	}


	if ($sql_dtls != '') {			// abort if no conditions specified
		if ($multilingual) {
			$sql .= $sql_dtls." AND (alanguage='$currentlang' OR alanguage='')";
		}

		$limit = ' ORDER BY posttime DESC LIMIT '.$offset.', '.$arts_per_page;

		if ($page ==1) {
			$numarticles = $db->sql_count($sql);
		}
		$result = $db->sql_query($select.$sql.$limit);

		$list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);


		$crows  = 0;
// echo '<br />form='.rtrim(getlink($pn_module_name.'/mode=srch/rslts='), '/');
		$pages = ceil($numarticles/$arts_per_page);
		require_once('header.php');
		if ($numarticles > 0) {
			require_once('includes/nbbcode.php');
			echo '<span class="title">'._PNSEARCHRESULTS.'</span>';
			echo '<i>'.$numarticles.' '.($numarticles == '1' ? _PNFOUNDART : _PNFOUNDARTS).'</i>';
			if ($numarticles > $arts_per_page) {
				$pages = ceil($numarticles/$arts_per_page);
				echo '<i>, viewing '.($offset + 1).' - '.($page * $arts_per_page).'</i>';
				echo '<br /><br />';
				if ($pages > 1) {
					echo '<form id="pnrsltstore" action="'.getlink($pn_module_name.'&amp;mode=srch&amp;rslts=', false).'" method="post" style="margin:0;">';
					echo '<p style="float:right; margin:0;">';
					echo '<input type="hidden" name="numarticles" value="'.$numarticles.'" />';
					echo '<input type="hidden" name="s_title" value="'.$s_title.'" />';
					echo '<input type="hidden" name="s_desc" value="'.$s_desc.'" />';
					echo '<input type="hidden" name="s_sec" value="'.$s_sec.'" />';
					echo '<input type="hidden" name="s_cat" value="'.$s_cat.'" />';
					echo '<input type="hidden" name="s_author" value="'.$s_author.'" />';
					echo '<input type="hidden" name="s_rating" value="'.$s_rating.'" />';
					echo '<input type="hidden" name="s_inclimg" value="'.$s_inclimg.'" />';
					echo '<input type="hidden" name="s_photalb" value="'.$s_photalb.'" />';
					echo '<input type="hidden" name="s_with_date" value="'.$s_with_date.'" />';
					echo '<input type="hidden" name="s_start_date" value="'.$s_start_date.'" />';
					echo '<input type="hidden" name="s_start_month" value="'.$s_start_month.'" />';
					echo '<input type="hidden" name="s_start_year" value="'.$s_start_year.'" />';
					echo '<input type="hidden" name="s_end_date" value="'.$s_end_date.'" />';
					echo '<input type="hidden" name="s_end_month" value="'.$s_end_month.'" />';
					echo '<input type="hidden" name="s_end_year" value="'.$s_end_year.'" />';
					echo '<input type="hidden" name="s_inlist" value="'.$s_inlist.'" />';
					echo '</p>';
					echo '</form>';
				}
			} else {
				echo '<br /><br />';
			}
		}
	} else {
		url_redirect(getlink('&amp;mode=srch'));
	}



	if (isset($list) && $list != '' && count($list) > '0') {
		foreach ($list as $key => $row) {
//			list($id, $postby, $title, $posttime, $intro, $cat, $sec, $sview) = $db->sql_fetchrow($result);
			if (($row['sview'] == '0') || (can_admin('Pro_News')) || (is_user() && (($row['sview'] == '1') || (($row['sview'] > 3) && (isset($userinfo['_mem_of_groups'][$row['sview'] - 3])))))) {
				$url_text = $pnsettings['text_on_url'] ? '/'.str_replace(" ", ($pnsettings['url_hyphen'] ? "-" : "_"), preg_replace('/[^\w\d\s\/-]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($pnsettings['url_lcase'] ? strtolower(($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$row['title']) : ($pnsettings['sec_in_url'] ? $row['stitle'].'/' : '').($pnsettings['cat_in_url'] ? $row['ctitle'].'/' : '').$art['title'])))) : '';
				if ($s_inlist) {
//					echo '<div class="pn_s_inlist">'.($row['posttime'] > $userinfo['user_lastvisit'] ? '<img src="images/pro_news/bullet_red.png" title="New" alt="new" />' : '<img src="images/pro_news/bullet_blue.png" alt="" />').'&nbsp;'.'<a href="'.getlink("$pn_module_name&amp;aid=".$row['aid'].$url_text).'" title="'.$row['stitle'].' &raquo; '.$row['ctitle'].' &raquo; '.$row['title'].' &#10;- '.$row['postby'].' - '.ProNews::create_date('d M y', $row['posttime']).'&nbsp;'.'">'.$row['title'].'</a></div>';
					$cpgtpl->assign_block_vars('srchlist', array(
						'G_ARTNEW' => $row['posttime'] > $userinfo['user_lastvisit'] ? '1' : '',
						'T_ARTLINK' => getlink("$pn_module_name&amp;aid=".$row['aid'].$url_text),
						'T_TITLE' => $row['title'],
						'T_SECTITLE' => $row['stitle'],
						'T_CATTITLE' => $row['ctitle'],
						'T_POSTBY' => $row['postby'],
						'T_POSTTIME' => ProNews::create_date('d M y', $row['posttime']),
					));
					$cpgtpl->set_filenames(array('body' => 'pronews/search.html'));


				} else {
					if (($row['image'] != '') && ($row['image'] != '0')) {
						$imagesize = getimagesize($pnsettings['imgpath'].'/'.$row['image']);  // fitted window - layingback 061119
						$imagesizeX = $imagesize[0] + 16; $imagesizeY = $imagesize[1] + 16;
						if ($imagesize[0] > $pnsettings['max_w'] || $imagesize[1] > $pnsettings['max_h']) {
							$thumbimage = $pnsettings['imgpath'].'/thumb_'.$row['image'];
						} else {
							$thumbimage = $pnsettings['imgpath'].'/'.$row['image'];
						}														  // Check if thumb exists before linking - layingback 061122
						$display_image = '<a href="'.getlink("&amp;aid=".$row['aid'].$url_text).'"><img class="pn_image" src="'.$thumbimage.'" alt="'.$row['caption'].'" /></a>';
						$iconimage = $pnsettings['imgpath'].'/icon_'.$row['image'];
					} elseif (file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png')) {
						$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholder.png';
						$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
						$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/'.strtolower(preg_replace('/[^\w\d_]+/', '', $row['stitle'])).'/imageholdermini.png';
					} elseif (file_exists('themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png')) {
						$thumbimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholder.png';
						$display_image = '<img class="pn_image" src="'.$thumbimage.'" alt="" />';
						$iconimage = 'themes/'.$CPG_SESS['theme'].'/images/pro_news/imageholdermini.png';
					} else {
						$display_image = '';
						$thumbimage = '';
						$iconimage = '';
					}

					if(strlen($row['intro']) > $pnsettings['srch_introlen']) {
						$text = substr_replace(ProNews::stripBBCode($row['intro']),' ...',$pnsettings['srch_introlen']);
						$morelink = '1';
					} else {
						$text = $row['intro'];
						$morelink = '1';
					}
					if (can_admin($pn_module_name)) {
						$canedit = "2";
						$editlink = adminlink('&amp;mode=add&amp;do=edit&amp;id=').$row['aid'];
						$editlabel = ($morelink == '1') ? _PNSECADMIN : '<a href="'.adminlink("&amp;mode=list&amp;do=sort&amp;cat=".$row['catid']).'">'._PNSECADMIN.'</a>';       // inline Edit - layingback 061201
					} elseif (($row['sadmin'] == '0') || (($row['sadmin'] == '1') && (is_user())) || (($row['sadmin'] > '3') && (is_user()) && (in_group($row['sadmin']-3)) && ($row['postby'] == $userinfo['username']))) {
						$canedit = $row['sadmin'];
						$editlink = getlink("&amp;mode=submit&amp;do=edit&amp;id=".$row['aid']);
						$editlabel = _PNEDITARTICLE;     // User as member of authorised group edit - layingback 061201
					} else {
						$canedit = '';
					}

					$cpgtpl->assign_block_vars('newshome', array(
						'G_SECBRK' => '0',
						'G_CATBRK' => '0',
						'S_INTRO' => decode_bb_all($text, 1, true),  // true param added for images - layingback
						'S_TITLE' => $row['title'],
						'S_ICON' => ($row['icon'] != '') ? $row['icon'] : 'clearpixel.gif',
						'T_ICON' => $row['ctitle'],
						'L_POSTBY' => _PNPOSTBY,
						'S_POSTBY' => $row['postby'],
						'T_POSTBY' => getlink("Your_Account&amp;profile=".$row['postby']),
						'L_POSTON' => _PNPOSTON,
						'S_POSTTIME' => ProNews::create_date(false, $row['posttime']),
						'G_POSTBY' => ($pnsettings['show_postby']) ? '1' : '',
						'S_CATEGORY' => $row['catid'],
						'S_CATLINK' => getlink("&amp;cid=".$row['catid']),
						'S_IMAGE' => ($row['image'] == '' || $pnsettings['srch_show_img'] == '0') ? '' : $display_image,
						'T_THUMBIMAGE' => $thumbimage,
						'T_ICONIMAGE' => $iconimage,
						'T_CAP' => $row['caption'],
						'U_MORELINK' => getlink("&amp;aid=".$row['aid'].$url_text),
						'S_MORELINK' => _PNMORE,
						'G_MORELINK' => ($morelink == '1') ? '1' : '',
						'G_SOCIALNET' => $pnsettings['soc_net'],
						'U_SOCNETLINK' => urlencode($BASEHREF.getlink("&amp;aid=".$row['aid'])),
						'S_SOCTITLE' => urlencode($row['title']),
						'G_SHOW_READS' => $pnsettings['show_reads'],
						'S_READS' => ($row['counter'] != '1') ? _PNREADS : _PNREAD,
						'T_READS' => $row['counter'],
						'U_CANADMIN' => $editlink,
						'S_CANADMIN' => _PNEDIT,
						'T_CANADMIN' => $editlabel,
						'G_CANADMIN' => ($canedit != '') ? '1' : '',
						'L_ID' => _PNREFNO,
						'S_ID' => $row['aid'],
						'G_ICONS' => ($pnsettings['show_icons'] == '1') ? '1' : '',
						//Added for pn_art_foot_home.html
						'G_SCORE' => ($pnsettings['ratings'] && $row['ratings'] != '0') ? '1' : '',
						'S_SCORE' => _PNRATING,
						'T_SCORE' => ($row['ratings'] != '0') ? $row['score']/$row['ratings'] : '',
						'G_PLINK' => $pnsettings['permalink'],
						'S_PLINK' => _PNLINK,
						'T_PLINK' => getlink("&amp;aid=".$row['aid'].$url_text),
						'G_SENDPRINT' => '1',
						'G_EMAILF' => ($pnsettings['emailf'] && is_user()) ? '1' : '',
						'S_SENDART' => _PNSENDART,
						'U_SENDART' => getlink("&amp;mode=send&amp;id=".$row['aid'].$url_text),
						'G_PRINTF' => ($pnsettings['printf']) ? '1' : '',
						'S_PRINTART' => _PNPRINTART,
						'U_PRINTART' => getlink("&amp;mode=prnt&amp;id=".$row['aid'].$url_text),
					));
					$cpgtpl->set_filenames(array('body' => 'pronews/article/'.($pnsettings['srch_tmplt'] == '' ? $pnsettings['template'] : $pnsettings['srch_tmplt'])));
				}

			}
		}
			$cpgtpl->display('body', false);
		pagination('&amp;mode=srch&amp;rslts=', $pages, 1, $page);
		$cpgtpl->set_filenames(array('pagin' => 'pronews/srch_pagination.html'));
		$cpgtpl->display('pagin', false);
	} else {
		echo '<br /><br /> '._PNNOMATCHES.'<br /><br />';
		echo '<br /><div style="text-align:center;"><input type="button" value="Search Again" onclick="javascript:history.go(-1)" />&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="'._PNRETURN.' '.$pn_module_name.'" onclick="self.location.href=\''.getlink().'\'" /></div>';
	}
}

?>
