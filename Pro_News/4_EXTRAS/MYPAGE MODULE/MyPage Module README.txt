MyPage Module is an adjunct to Pro_News Module, and is released under GNU GPL 2 or later 
as Free/Libre Open SOurce Software.

It may optionally be installed alongside Pro_News on DragonflyCMS versions 9.2.1 or later 
versions of DragonflyCMS v9.

It's purpose is to allow external visitors to access a member's Member's Page (setup under 
Pro_News) directly, via a simple URL:

	domain.tld/MyPage/u=username.html

thus avoiding the need to know or remember the specific Pro_News article id number (aid=).

Note:  the above URL assumes that you have LEO enabled.  It also requires that you have 
set up the user_page field exactly as described in CHANGES.TXT under MEMBERS' PAGES.

Install MyPages in the normal way as a new module.  There are only 2 files, a minimal 
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



