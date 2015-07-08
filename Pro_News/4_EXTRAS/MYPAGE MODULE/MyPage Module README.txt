MyPage Module is an adjunct to Pro_News Module, and is released under GNU GPL 2 or later 
as Free/Libre Open Source Software.

It may optionally be installed alongside Pro_News on DragonflyCMS versions 9.2.1 or later 
versions of DragonflyCMS v9.

Its purpose is to allow external visitors to access a member's Member's Page (setup under 
Pro_News) directly, via a simple URL:

	domain.tld/MyPage/u=username.html

thus avoiding the need to know or remember the specific Pro_News article id number (aid=).

Note:  the above URL assumes that you have LEO enabled.  It also requires that you have 
set up the user_page field exactly as described in CHANGES.TXT under MEMBERS' PAGES.

Install MyPage in the normal way as a new module.  There are only 2 files, a minimal 
install file and the main code file.

After install remember to mark the module Active.

In Administration > Modules > MyPage > Edit set 'Show in menu?' to No.

         -------------------------------------------------------------

Please note that it may be possible to further shorten the URL by modifying your .htaccess 
file - depending on its current structure - by adding this line:

RewriteRule ^\~([a-zA-Z0-9_]+)/$ index.php?name=MyPage&u=$1

resulting in URL's of the form:

	domain.tld/~username.html

but if you do not understand your .htaccess file enough to know how to make these modifications, 
PLEASE DO NOT TRY as the results of a mistake can be significant.

PLEASE NOTE THAT I CANNOT PROVIDE SUPPORT FOR .htaccess FILE MODIFICATIONS.

         -------------------------------------------------------------

To add 'MyPage' to a member's Profile Page requires modifying modules/Your_Account/userinfo.php

Copy the included page_white_text.png image to

	themes/{your_theme}/images/forums/lang_english/page_white_text.png

Look for a line:

		if (is_active('coppermine')) {

After the END of that if statement, and before the echo statement, insert the following code:

// layingback - code to add Members Page link
	if (is_active('Pro_News') && !empty($userinfo['user_page'])) {		// substitute correct module name for Pro_News if changed
		$usr_pg_lnk = is_active('MyPage') ? 'MyPage/u='.$username : $userinfo['user_page'];
//		$usr_pg_lnk = is_active('MyPage') ? '~'.$username : $userinfo['user_page'];
		echo '<tr>
			  <td valign="middle" style="white-space:nowrap;" align="right"><span class="gen">Member\'s Page:</span></td>
			  <td class="row1" valign="middle" width="100%">
			  <span class="gen"><a href="'.$usr_pg_lnk.'" target="_blank"><img src="'.$imgpath.'/page_white_text.png" border="0" alt="" title="" /></a></span></td>
			</tr>';
	}
// layingback - end of code

This will display a link beneath a member's existing Profile Information fields (email, Yahoo, Galleries, etc.)

If you have changed your .htaccess file to support ~username then flip the comment to use the other line.


