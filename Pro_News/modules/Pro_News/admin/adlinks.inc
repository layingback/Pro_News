<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007 - 2009 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.3 $
  $Date: 2010-02-17 15:08:45 $
   Author: layingback
**********************************************/
if (can_admin(strtolower($module))) {
	get_lang('Pro_News');
	$menuitems['_AMENU3'][$module]['URL'] = adminlink($module);
	$menuitems['_AMENU3'][$module]['IMG'] = 'pro_news';
	$menuitems['_AMENU3'][$module]['MOD'] = 'Pro News';
	$menuitems['_AMENU3'][$module]['SUB'][_PNADDSTORY] = adminlink($module.'&amp;mode=add');
	$menuitems['_AMENU3'][$module]['SUB'][_PNARTICLES] = adminlink($module.'&amp;mode=list');
	$menuitems['_AMENU3'][$module]['SUB'][_PNGLRY] = adminlink($module.'&amp;mode=glry');
	$menuitems['_AMENU3'][$module]['SUB'][_PNUPLOAD] = adminlink($module.'&amp;mode=upld');
	$menuitems['_AMENU3'][$module]['SUB'][_PNSECTIONS] = adminlink($module.'&amp;mode=sec');
	$menuitems['_AMENU3'][$module]['SUB'][_PNCATS] = adminlink($module.'&amp;mode=cat');
	$menuitems['_AMENU3'][$module]['SUB'][_PNBLOCKS] = adminlink($module.'&amp;mode=blk');
	$menuitems['_AMENU3'][$module]['SUB'][_PNCONFIG] = adminlink($module.'&amp;mode=cfg');
}
