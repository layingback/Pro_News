<?php
/*********************************************
  Pro News Module for Dragonfly CMS
  ********************************************
  Original Beta version Copyright © 2006 by D Mower aka Kuragari
  Subsequent releases Copyright © 2007-2015 by M Waldron aka layingback
  http://www.layingback.net

  This module is released under the terms and conditions
  of the GNU GPL version 2 or any later version

  $Revision: 3.2 $
  $Date: 2010-09-08 13:13:51 $
  Author: layingback
**********************************************/
$modheader .= '
<script type="text/javascript">
<!--
        //<![CDATA[
            document.getElementsByTagName(\'html\')[0].className=\'jsOn\';
        //]]>
function domod(type)
{
 elem = document.getElementById("artlist")
 elem.modtype.value = type ;
 elem.submit() ;
}
function CheckAll()
{
elem = document.getElementById("artlist")
count = elem.elements.length;
    for (i=0; i < count; i++)
	{
    if(elem.elements[i].checked == 1)
    	{elem.elements[i].checked = 0; }
    else {elem.elements[i].checked = 1;}
	}
}
function UncheckAll(){
elem = document.getElementById("artlist")
count = elem.elements.length;
    for (i=0; i < count; i++)
	{
    if(elem.elements[i].checked == 1)
    	{elem.elements[i].checked = 0; }
    else {elem.elements[i].checked = 1;}
	}
}
-->
</script>';
