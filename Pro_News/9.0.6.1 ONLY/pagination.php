<?php
/*********************************************
  CPG Dragonfly™ CMS
  ********************************************
  Copyright © 2004 - 2005 by CPG-Nuke Dev Team
  http://www.dragonflycms.com

  Additions to work exclusively with Pro_News
  Copyright © 2007 - 2009 by M Waldron aka layingback
  http://www.layingback.net

  Dragonfly is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.1 $
  $Date: 2009-01-02 16:53:26 $
  Author: layingback
  
  NOTE: THIS MODULE NOT REQUIRED FOR CPG_NUKE 9.1.x, OR LATER
        (Uses built function in 9.1+ instead)
        Include under /modules/Pro_News/includes/ for 9.0.6.1

  Etracted from:
  Source: /cvs/html/modules/News/index.php
  Revision: 9.11
  Author: djmaze
  Date: 2005/08/21 16:19:40
**********************************************/
if (!defined('CPG_NUKE')) { exit; }

function pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext=TRUE)
{
	global $cpgtpl, $bgcolor3;
	function pagination_page($page, $url, $first=false) {
		global $cpgtpl;
		$cpgtpl->assign_block_vars('pagination', array('PAGE' => $page, 'URL' => $url, 'FIRST' => $first));
	}
	$total_pages = ceil($num_items/$per_page);
	$on_page = floor($start_item / $per_page);

	if ($total_pages < 2) { return $cpgtpl->assign_var('B_PAGINATION', false); }
	get_lang('Submit_News');
	$cpgtpl->assign_vars(array(
		'B_PAGINATION' => true,
		'PAGINATION_PREV' => ($add_prevnext && $on_page > 1) ? getlink($base_url.(($on_page-1)*$per_page)) : false,
		'PAGINATION_NEXT' => ($add_prevnext && $on_page < $total_pages) ? getlink($base_url.($on_page+$per_page)) : false,
		'L_PREVIOUS' => _PREVIOUSPAGE,
		'L_NEXT' => _NEXTPAGE,
		'L_GOTO_PAGE' => 'Go to:',
	));
	if ($total_pages > 10) {
		$init_page_max = ($total_pages > 3) ? 3 : $total_pages;
		for ($i = 1; $i <= $init_page_max; $i++) {
			pagination_page($i, ($i == $on_page) ? false : getlink($base_url.($i*$per_page)), ($i == 1));
		}
		if ($total_pages > 3) {
			if ($on_page > 1 && $on_page < $total_pages) {
				if ($on_page > 5) { pagination_page(' ... ', false, true); }
				$init_page_min = ($on_page > 4) ? $on_page : 5;
				$init_page_max = ($on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;
				for ($i = $init_page_min - 1; $i < $init_page_max + 2; $i++) {
					pagination_page($i, ($i == $on_page) ? false : getlink($base_url.($i*$per_page)), ($on_page <= 5 && $i == $init_page_min-1));
				}
				if ($on_page < $total_pages-4) { pagination_page(' ... ', false, true); }
			} else {
				pagination_page(' ... ', false, true);
			}
			for ($i = $total_pages - 2; $i <= $total_pages; $i++) {
				pagination_page($i, ($i == $on_page) ? false : getlink($base_url.($i*$per_page)));
			}
		}
	} else {
		for ($i = 1; $i <= $total_pages; $i++) {
			pagination_page($i, ($i == $on_page) ? false : getlink($base_url.($i*$per_page)));
		}
	}
}

