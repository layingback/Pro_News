Example Calling Module is an adjunct to Pro_News Module, and is released under GNU GPL 2 or later 
as Free/Libre Open Source Software.

It may optionally be installed alongside Pro_News on DragonflyCMS but has only been tested with 
DragonflyCMS version 9.4.0 or later versions of DragonflyCMS v9.

Its purpose is to allow to access the power and features of Pro_News from within an existing or 
new module of your choosing.

The Example module into your modules/ folder, and rename the module from Example to a name of 
your choice (without spaces).  Leave the module's file named as index.php.

After copying remember to mark the module Active.

In Administration > Modules > {your new module name} > Edit set 'Show in menu?' to No.

         -------------------------------------------------------------

The Example/index.php file is intended as a start point and its purpose is only to demonstrate 
how to call pro_News from within another module.

But of you follow the instructions in the comments, it is possible to set up a few example 
articles and run the module so that you can see it in operation.

The module consists of multiple ways to use it.  The different options are commented out, 
by a // in the first column of the line or an encompassing /* */ pair, but include:

 - display by a specific Section
 - display by a specific Category
 - display by a specific Article

It also includes example code to convert Titles into Id numbers if you do not want Id 
numbers in your calling URL.  These include:

 - Select Section Id by title
 - Select Category Id by title
 - Select Article Id by title

Finally the example shows how you might mix your output with output from Pro_News, 
either with or without templates.

The commented out echo statements are included for testing and debugging purposes only.




