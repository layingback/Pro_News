<?php
/*								INSTRUCTIONS										*/

/*************************************************************************************

	Example module to use as calling module for Pro_News
	Edit the meta description, keywords and page title to suit your page
	Then activate your module through admin.php?op=modules
	You can allocate blocks left/right/center up/center down as usual


	To run this example module as a demo, login as Administrator
	Add 2 Articles to the Default category with titles of 'Test' & 'test'
	Copy this file index.php into a new folder called modules/Example/
	To Run click on the module name Example in Administration > General > Modules

*************************************************************************************/

if (!defined('CPG_NUKE')) { exit; }

# Required as Pro_News generated ones will be too late - ie after require_once(header.php)
$METATAGS['description'] = 'Example of flexibility of Pro_News CM™ - called from another module.';
$METATAGS['keywords'] = 'Pro_News, Pro_News CM™, call, flexible, powerful';
$pagetitle = '<a href="'.getlink($module_name).'">'.$module_title.'</a>'.' '._BC_DELIM.' '.'Example Module Calling on Pro_News';

# Allow for re-naming of Pro_News module
$pnsettings = $MAIN_CFG['pro_news'];
$pn_module_name = $pnsettings['module_name'];

# Enable only if you need Pro_News messages before Pro_News call
// get_lang($module_name);

# Display nothing if Pro_News module is not active
if (is_active($pn_module_name)) {
	require_once('modules/'.$pn_module_name.'/init.inc');

# -------------------------------------- optional pre pro_news code --------------------------------------- #

	# Include any dedicated CSS - for non-Pro_News output - here
//	echo '<link rel="stylesheet" type="text/css" href="themes/'.$CPG_SESS['theme'].'/style/YOUR_OWN.css" />';

	# Only required for non-Pro_News output before Pro_News output
	require_once('header.php');

	# Any non-Pro_News output - example without template - may require a dedicated CSS above - pro_news.css, etc, are already loaded
	$text =  '<div style="margin:20px;">';
	$text .= 	'<img style="float:right; margin:20px;" src="images/logo.png" alt="" />';
	$text .= 	'<br /><br />';
	$text .= 	'<h2>CPG Dragonfly™CMS</h2>';
	$text .= 	'<div>';
	$text .= 		'<b>DragonflyCMS</b> - ';
	$text .= 		'Dragonfly™CMS is a powerful, feature-rich, Open Source content management system originally based on PHP-Nuke 6.5. Since the early "CPG-Nuke CMS" days, we paid close attention to security, performance, scalability, efficiency and reliability. Subsequent development of DragonflyCMS marked yet another exciting milestone in our history.';
	$text .= 	'</div>';
	$text .= '</div>';
	$text .= '<br /><br />';

	echo $text;


# -------------------------------------- optional URL parameter capture ----------------------------------- #
	$value = 'Default Category';

	# optionally remove comment and run module with url:  YOUR_DOMAIN/{YOUR_MODULE_NAME}/Default_Category
	# note use _ character for space in Category title - also for Section and Article titles
//	$value = isset($_GET['c']) ? Fix_Quotes($_GET['c'],1,1) : '';
//	$value = str_replace('_', ' ', $value);
// echo '<pre>'; print_r($_GET); echo '</pre>';
// echo 'title parameter='.$value;


# -------------------------------------- optional pro_news search by name code ---------------------------- #

/*
	# Search for section by name
	$sql = 'SELECT id';
	$sql .= ' FROM '.$prefix.'_pronews_sections';
	$sql .= ' WHERE title="Default Section"';
	$sql .= ' LIMIT 1';
	$result = $db->sql_query($sql);
	$list = $db->sql_fetchrowset($result);
	$db->sql_freeresult($result);
	if (isset($list) && $list != '' && count($list) > '0') {
		foreach ($list as $key => $row) {
			$sid = $row['id'];
// echo ' <br />sid='.$sid;
		}
	}
*/

	# OR Search for category by name
	if ($value && !empty($value)) {
		$sql = 'SELECT id';
		$sql .= ' FROM '.$prefix.'_pronews_cats';
		$sql .= ' WHERE title="'.$value.'"';
		$sql .= ' LIMIT 1';
		$result = $db->sql_query($sql);
		$list = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		if (isset($list) && $list != '' && count($list) > '0') {
			foreach ($list as $key => $row) {
				$cid = $row['id'];
	// echo ' <br />cid='.$cid;
			}
		}
	}

/*
	# OR Search for article by name
	$sql = 'SELECT id';
	$sql .= ' FROM '.$prefix.'_pronews_articles';
	$sql .= ' WHERE title="test"';
	$sql .= ' LIMIT 1';
	$result = $db->sql_query($sql);
	$list = $db->sql_fetchrowset($result);
	$db->sql_freeresult($result);
	if (isset($list) && $list != '' && count($list) > '0') {
		foreach ($list as $key => $row) {
			$aid = $row['id'];
// echo ' <br />aid='.$aid;
		}
	}
*/


# -------------------------------------- pro_news code ---------------------------------------------------- #

	# Start buffer capture of Pro_News output
	ob_start();

	# Display Articles in specified Section
//	ProNews::display_articles($sid, '', '');

	# OR Display Articles in specified Category
	if (isset($cid)) {
		ProNews::display_articles('0', $cid, '');
	}

	# OR Display specified Article
//	ProNews::article($aid, '');


	# Close, retrieve and clear Pro_News buffer and then output
	$content = ob_get_clean();
	echo $content;


# -------------------------------------- optional post pro_news example code ------------------------------ #
# NOTE: post Pro_News output will ALWAYS display on 1st page, even if Pro_News output is multi-page

	# Any non-Pro_News output - example with template - may require a dedicated CSS above - pro_news.css, etc, are already loaded
	$text =  '<div>';
	$text .= 	'<img style="float:right; margin:20px;" src="images/pro_news/icons/pro_news.gif" alt="" />';
	$text .= 	'<br /><br /><br /><br />';
	$text .= 	'<h2>Pro_News CM™</h2>';
	$text .= 	'<div style="text-align:center">';
	$text .= 		'<b>Pro_News CM</b> - ';
	$text .= 		'Pro_News CM™ is the most complete Content Module available for Dragonfly™CMS.';
	$text .= 		'<br />Use for ANY and ALL Content, as a free standing module, as an adjunct to News, as a replacement for News,';
	$text .= 		'<br />or for ALL 3! Only 1 copy of Pro_News ever required.';
	$text .= 		'<br /><br />';
	$text .= 		'And now you can utilise the power of <b>Pro_News  CM™</b> from any module of your own!';
	$text .= 	'</div>';
	$text .= '</div>';
	$text .= '<br /><br />';

	$cpgtpl->assign_block_vars('basicempty', array(
		'S_MSG' => $text,
	));
	$tpl = 'basic.html';
	$cpgtpl->set_filenames(array('body' => 'pronews/'.$tpl));
	$cpgtpl->display('body');

}	# close if (is_active($pn_module_name))

?>
