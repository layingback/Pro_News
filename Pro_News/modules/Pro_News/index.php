<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007 - 2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  Slide show script (C) 2000 www.CodeLifter.com
  see module/Pro_News/includes/slideshow.inc for info

  $Revision: 3.18 $
  $Date: 2013-04-22 09:37:42 $
  Author: layingback
**********************************************/
if (!defined('CPG_NUKE')) { exit; }
global $home;
if (!$MAIN_CFG['pro_news']['SEOtitle']) { $pagetitle .= '<a href="'.getlink($module_name).'">'.$module_title.'</a>'; } // changed $module_name -> $module_title - layingback 061212
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
if (file_exists('themes/'.$CPG_SESS['theme'].'/style/pro_news.css')) {
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/pro_news.css" />';
	if (ereg('MSIE 7.0', $user_agent) || ereg('MSIE ([0-6].[0-9]{1,2})', $user_agent)) {
		$modheader .= '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/pro_newsie.css" />';
	}
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/pn_specific.css" />';
} else {
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/default/style/pro_news.css" />';
	if (ereg('MSIE 7.0', $user_agent) || ereg('MSIE ([0-6].[0-9]{1,2})', $user_agent)) {
		$modheader .= '<link rel="stylesheet" type="text/css" href="themes/default/style/pro_newsie.css" />';
	}
	$modheader .= '<link rel="stylesheet" type="text/css" href="themes/default/style/pn_specific.css" />';
}
$modheader .= "<script type=\"text/javascript\" src=\"modules/$module_name/includes/scripts.js\"></script>\n";  // added layingback
require_once('modules/'.$module_name.'/includes/load_js.inc');
require_once('modules/'.$module_name.'/functions.php');

if (!Cache::array_load('board_config', 'Forums', true)) {
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_bbconfig');
	while ($row = $db->sql_fetchrow($result)) {
		$board_config[$row['config_name']] =  str_replace("'", "\'",$row['config_value']);
		$new[$config_name] = ( isset($_POST[$config_name]) ) ? $_POST[$config_name] : $board_config[$config_name];
		if ($config_name == 'smilies_path') {
			$new['smilies_path'] = 'images/smiles';
		}
	}
	Cache::array_save('board_config', 'Forums');
}

// layingback - code added to limit image display width - comment out for legacy behaviour
if (!Cache::array_load('attach_config', 'Forums', true)) {
	$result = $db->sql_query('SELECT * FROM '.$prefix.'_bbattachments_config');
	while ($row = $db->sql_fetchrow($result)) {
		$attach_config[$row['config_name']] = $row['config_value'];
	}
	$db->sql_freeresult($result);
	$attach_config['board_lang'] = $board_config['default_lang'];
	Cache::array_save('attach_config', 'Forums', $attach_config);
}
$attach_config['img_max_width_remote'] = 1;
if ($attach_config['img_max_width_remote']) {
	$modheader .= '
<style type="text/css">.pn_content img{max-width:'.$attach_config['img_link_width'].'px}
<!--[if lt IE 8]>
.pn_content img {
	width: expression(this.clientWidth > '.$attach_config['img_link_width'].' ? "'.$attach_config['img_link_width'].'px" : this.clientWidth+"px");
}
<![endif]-->
</style>
';
}
// end of image display width limit

if (isset($_POST['aid']) && isset($_POST['score'.$_POST['aid']])) {
	$aid = intval($_POST['aid']);
	$score = intval($_POST['score'.$_POST['aid']]);
	if ($score > 0 && $score < 6) {
		$rcookie = array();
		if (isset($_COOKIE['pnrcookie'])) {
			$rcookie = explode(':', base64_decode($_COOKIE['pnrcookie']));
		}
		for ($i=0; $i < sizeof($rcookie); $i++) {
			if ($rcookie[$i] == $aid) {
				$rated = _PNALREADYRATED;
				break;
			}
		}
		if (!isset($rated)) {
			$rated = _PNTHANKSRATE;
			$rcookie[] = $aid;
			$db->sql_query("UPDATE ".$prefix."_pronews_articles SET score=score+$score, ratings=ratings+1 WHERE id=$aid");
			$info = base64_encode(implode(':', $rcookie));
			setcookie('pnrcookie',$info,gmtime()+86400, $MAIN_CFG['cookie']['path']);
		}
		cpg_error($rated, _PNARTICLERATE, getlink('&aid='.$aid));
	} else {
		cpg_error(_PNDIDNTRATE, _PNARTICLERATE);
	}
}

$aid = isset($_GET['aid']) ? intval($_GET['aid']) : '';
$cid = isset($_GET['cid']) ? intval($_GET['cid']) : '';
$sid = isset($_GET['sid']) ? intval($_GET['sid']) : '';
$usr = isset($_GET['usr']) ? Fix_Quotes($_GET['usr'],1) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : '0';
$id = isset($_GET['id']) ? intval($_GET['id']) : '';
$npic = isset($_GET['npic']) ? intval($_GET['npic']) : '';
$fpro = isset($_GET['fpro']) ? Fix_Quotes($_GET['fpro'],1) : '';
$mode = isset($_GET['mode']) ? Fix_Quotes($_GET['mode'],1) : '';
$sec = isset($_GET['sec']) ? intval($_GET['sec']) : '';
$cat = isset($_GET['cat']) ? intval($_GET['cat']) : '';
//echo '<br /> GETcat='.$cat;

if ($mode) {
	if ($mode == 'gllry' && ($id == '' || $npic == '')) { $mode = ''; }
} else {
	if ($home) {
		if ($pnsettings['hdlines']) {$mode = 'hdln';}
		elseif ($pnsettings['art_inhome']) {$mode= 'art';}
		else {$mode = 'home';}
	}
	elseif ($aid != '') {$mode = 'art';}
	elseif ($cid != '') {$mode = 'cat';}
	elseif ($sid != '') {$mode = 'sec';}
	elseif (isset($_GET['mode'])) {$mode = Fix_Quotes($_GET['mode'],1);}
	elseif (isset($_GET['discuss'])) {ProNews::discussThis(intval($_GET['discuss']));}
	else {$mode = '';}
}

switch ($mode) {
	case 'art':
		ProNews::article($aid,$page);
	break;

	case 'cat':
		ProNews::display_articles('0',$cid, $usr);
	break;

	case 'sec':
		ProNews::display_articles($sid, $cid, $usr);
	break;

	case 'hdln':
		ProNews::display_hdlines($sid, $cid, $usr);
	break;

	case 'home':
		ProNews::display_articles($sid, $cid, $usr);
	break;

	case 'submit':
		$func = (isset($_GET['do'])) ? $_GET['do'] : '';
		ProNews::submit_article($func);
	break;

	case 'slide':
		ProNews::slideshow();
	break;

	case 'gllry':
		ProNews::gallery($id,$npic);
	break;

	case 'send':
		ProNews::sendart($id);
	break;

	case 'prnt':
		ProNews::printformat($id);
	break;

	case 'prmte':
		ProNews::promote($id,$fpro);
	break;

//added by rosbif for all article list
	case 'allarts':
		ProNews::section_list('allarts','1',$sec,$cat);
	break;

	case 'newarts':
		ProNews::section_list('newarts','1',$sec,$cat);
	break;
//end rosbif

	case 'srch':
		ProNews::search();
	break;

	case 'upld':
		$func = (isset($_GET['dir'])) ? $_GET['dir'] : '';
		ProNews::mod_upld($func);
	break;

	default:
		ProNews::section_list('allarts','',$sec,$cat);
	break;
}

