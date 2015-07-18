Example Calling Module is an adjunct to Pro_News Module, and is released under GNU GPL 2 or later 
as Free/Libre Open Source Software.

It may optionally be installed alongside Pro_News on DragonflyCMS but has only been tested with 
DragonflyCMS version 9.4.0.  It is expected to work on later versions of DragonflyCMS v9 only.

Its purpose is to allow you to access the power and features of Pro_News from within an existing, 
or new, module of your choosing.

Place the entire Example module into your modules/ folder, and rename the module from Example to
a name of your choice (without spaces).  Leave the module's file named as index.php.

After copying remember to mark the module Active.

Optionally, in Administration > Modules > {your new module name} > Edit, set 'Show in menu?' to No.

         -------------------------------------------------------------

The Example/index.php file is intended as a start point and its purpose is only to demonstrate 
how to call Pro_News from within another module.

But if you follow the instructions in the comments, it is possible to set up a few example 
articles and run the module so that you - as Adminstrator - can see it in operation.

The module consists of a variety of ways to call Pro_News.  The different options are commented out, 
by a // in the first column of the line or an encompassing /* */ pair, but include:

 - display by a specific Section
 - display by a specific Category - including retrieving title from URL parameter
 - display by a specific Article

It also includes example code to convert Titles into Id numbers if you do not want Id 
numbers in your calling URL.  These include:

 - Select Section Id by title
 - Select Category Id by title
 - Select Article Id by title

Finally the example shows how you might mix your own output with output from Pro_News, 
either with or without templates.

The // commented out echo statements are included for testing and debugging purposes only.


PLEASE NOTE:  This module is for example only, and as such is the very simplest of code. It
would be wise to include additional validation, checks and error handling in your final code.

