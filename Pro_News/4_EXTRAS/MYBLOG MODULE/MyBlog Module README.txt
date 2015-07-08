MyBlog Module is an adjunct to Pro_News Module, and is released under GNU GPL 2 or later 
as Free/Libre Open Source Software.

It may optionally be installed alongside Pro_News on DragonflyCMS versions 9.2.1 or later 
versions of DragonflyCMS v9.

Its purpose is to allow external visitors to access a member's latest Blog page (setup under 
Pro_News using the Blog template) directly, via a simple URL:

	domain.tld/MyBlog/u=username.html

thus avoiding the need to know or remember the specific Pro_News article id number (aid=).

Note:  the above URL assumes that you have LEO enabled.  It also assumes that you have 
set up the Section in Pro_News using the Blog template with a title of MyBlog.

To allow any name to be used for your Blog Section (eg. a non-English name) do not install 
MyBlog module in the normal way.  Instead create a new folder in /modules with the same name 
as your Pro_News Blog Section (eg. Blogs) and copy the files there.  NOTE: If you have a
space in your Pro_News Blog Section title then replace it with _ (underscore).  So Photo Blogs 
would become Photo_Blogs.  Then install the module with that module name, eg. Photo_Blogs.
There are only 2 files, an install file and the main code file.

After install remember to mark the module Active.

In Administration > Modules > {your_name} > Edit set 'Show in menu?' to No.

         -------------------------------------------------------------

Please note that it may be possible to further shorten the URL by modifying your .htaccess 
file - depending on its current structure - by adding this line:

RewriteRule ^([a-zA-Z0-9_]+)/\~([a-zA-Z0-9_]+)/$ index.php?name=$1&u=$2

resulting in URL's of the form:

	domain.tld/Photo_Blogs/~username.html

where Photo_Blogs is the name of the module.  But if you do not understand your .htaccess 
file well enough to know how to make these modifications, PLEASE DO NOT TRY as the results 
of a mistake can be significant.

PLEASE NOTE THAT I CANNOT PROVIDE SUPPORT FOR .htaccess FILE MODIFICATIONS.



