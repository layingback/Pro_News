// -------------------------------------------------------------------
// Image Thumbnail Viewer Script- By Dynamic Drive, available at: http://www.dynamicdrive.com
// Last updated: January 1st, 2009- eliminate need for onload/onunload events
// -------------------------------------------------------------------

var thumbnailviewer = {
 enableTitle : true, //Should "title" attribute of link be used as description?
 enableAnimation : true, //Enable fading animation?
 definefooter : '<div class="footerbar"></div>', //Define HTML for footer interface
 defineLoading : '<img src="images/loading.gif" />', //Define HTML for "loading" div

///////////// No Need to Edit Beyond Here /////////////

 scrollbarwidth : 16,
 opacitystring : 'filter:progid:DXImageTransform.Microsoft.alpha(opacity=10); -moz-opacity: 0.1; opacity: 0.1',

 createthumbBox : function(){ // write out HTML for Image Thumbnail Viewer plus loading div
  document.write('<div id="overlay"></div>')
  document.write('<div id="thumbBox" onClick="thumbnailviewer.closeit()"><div id="thumbImage"></div>' + this.definefooter + '</div>')
  document.write('<div id="thumbLoading">' + this.defineLoading + '</div>')
//  overlay.addEventListener('touchstart', function(e){
//   e.preventDefault();
//  });
  this.overlay = document.getElementById('overlay');
  this.thumbBox = document.getElementById('thumbBox');
  this.thumbImage = document.getElementById('thumbImage'); //Reference div that holds the shown image
  this.thumbLoading = document.getElementById('thumbLoading'); //Reference "loading" div that will be shown while image is fetched
  this.standardbody = (document.compatMode == 'CSS1Compat')? document.documentElement : document.body; //create reference to common "body" across doctypes
 },

 centerDiv : function(divobj){ //Centers a div element on the page
  var ie = document.all && !window.opera, dom = document.getElementById,
  scroll_top = ie? this.standardbody.scrollTop : window.pageYOffset,
  scroll_left = ie? this.standardbody.scrollLeft : window.pageXOffset,
  docwidth = ie? this.standardbody.clientWidth : window.innerWidth - this.scrollbarwidth,
  docheight = ie? this.standardbody.clientHeight : window.innerHeight,
  docheightcomplete = this.standardbody.offsetHeight > this.standardbody.scrollHeight?
   this.standardbody.offsetHeight : this.standardbody.scrollHeight, //Full scroll height of document
  objwidth = divobj.offsetWidth, //width of div element
  objheight = divobj.offsetHeight, //height of div element
//Vertical position of div element: Either centered, or if element height larger than viewpoint height, 10px from top of viewpoint
  topposition = docheight>objheight? scroll_top + docheight / 2 - objheight / 2 + 'px' : scroll_top + 10 + 'px';
  divobj.style.left = docwidth / 2 - objwidth / 2 + 'px'; //Center div element horizontally
  divobj.style.top = Math.floor(parseInt(topposition)) + 'px';
  divobj.style.visibility = 'visible';
 },

 showthumbBox : function(){ //Show ThumbBox div
  thumbnailviewer.thumbLoading.style.visibility = 'hidden'; //Hide "loading" div
  this.centerDiv(this.thumbBox);
  if (this.enableAnimation){ //If fading animation enabled
   this.currentopacity = 0; //Starting opacity value
   this.opacitytimer = setInterval(function(){thumbnailviewer.opacityanimation();}, 25);
  }
 },

 loadimage : function(link){ //Load image function that gets attached to each link on the page with rel="thumbnail"
  thumbnailviewer.overlay.style.visibility = 'visible'; //Show "overlay" div
  if (this.thumbBox.style.visibility == 'visible') //if thumbox is visible on the page already
   this.closeit(); //Hide it first (not doing so causes triggers some positioning bug in Firefox
  var imageHTML = '<img src="' + link.getAttribute('href') + '" style="' + this.opacitystring + '" />'; //Construct HTML for shown image
  if (this.enableTitle && link.getAttribute('title')) //Use title attr of the link as description?
   imageHTML += '<br />' + link.getAttribute('title')
  this.centerDiv(this.thumbLoading) //Center and display "loading" div while we set up the image to be shown
  this.thumbImage.innerHTML = imageHTML //Populate thumbImage div with shown image's HTML (while still hidden)
  this.featureImage = this.thumbImage.getElementsByTagName('img')[0]; //Reference shown image itself
  if (this.featureImage.complete)
   thumbnailviewer.showthumbBox()
  else{
   this.featureImage.onload=function(){ //When target image has completely loaded
   thumbnailviewer.showthumbBox() //Display "thumbbox" div to the world!
  }
 }
 if (nxt_but != null) {
  nxt_but.style.display = "block";		// display the thumbnailviewer buttons
  prv_but.style.display = "block";
 }
 thumbnailviewer.overlay.style.visibility = 'visible'; //Show "overlay" div
 if (document.all && !window.createPopup) //Target IE5.0 browsers only. Address IE image cache not firing onload bug: panoramio.com/blog/onload-event/
  this.featureImage.src = link.getAttribute('href');
  this.featureImage.onerror = function(){ //If an error has occurred while loading the image to show
   thumbnailviewer.thumbLoading.style.visibility = 'hidden'; //Hide "loading" div, game over
   nxt_but.style.display = "none";		// hide the thumbnailviewer buttons
   prv_but.style.display = "none";
   thumbnailviewer.overlay.style.visibility = 'hidden'; //Hide "overlay" div
  };
 },

 setimgopacity : function(value){ //Sets the opacity of "thumbimage" div per the passed in value setting (0 to 1 and in between)
  var targetobject = this.featureImage;
  if (targetobject.filters && targetobject.filters[0]){ //IE syntax
   if (typeof targetobject.filters[0].opacity == 'number') //IE6
    targetobject.filters[0].opacity = value * 100;
   else //IE 5.5
    targetobject.style.filter = 'alpha(opacity=' + value * 100 + ')';
  }
  else if (typeof targetobject.style.MozOpacity != 'undefined') //Old Mozilla syntax
   targetobject.style.MozOpacity = value;
  else if (typeof targetobject.style.opacity != 'undefined') //Standard opacity syntax
   targetobject.style.opacity = value;
  else //Non of the above, stop opacity animation
   this.stopanimation();
 },

 opacityanimation : function(){ //Gradually increase opacity function
  this.setimgopacity(this.currentopacity);
  this.currentopacity += 0.1;
  if (this.currentopacity > 1)
   this.stopanimation();
 },

 stopanimation : function(){
  if (typeof this.opacitytimer != 'undefined')
   clearInterval(this.opacitytimer);
 },

 closeit : function(){ //Close "thumbbox" div function
  this.stopanimation();
  this.thumbBox.style.visibility = 'hidden';
  this.thumbImage.innerHTML = '';
  this.thumbBox.style.left = '-2000px';
  this.thumbBox.style.top = '-2000px';
  if (nxt_but != null) {
    nxt_but.style.display = "none";		// hide the thumbnailviewer buttons
    prv_but.style.display = "none";
  }
  thumbnailviewer.overlay.style.visibility = 'hidden'; //Hide "overlay" div
 },

 dotask : function(target, functionref, tasktype){ //assign a function to execute to an event handler (ie: onunload)
  var tasktype = window.addEventListener? tasktype : 'on' + tasktype;
  if (target.addEventListener)
   target.addEventListener(tasktype, functionref, false);
  else if (target.attachEvent)
   target.attachEvent(tasktype, functionref);
 },

 init1 : function(){  //Initialize thumbnail viewer script by scanning page for links with rev="defaultload"
  nxt_but = document.getElementById("pop_nxt");
  prv_but = document.getElementById("pop_prv");
  if (!this.enableAnimation)
   this.opacitystring="";
  var pagelinks=document.getElementsByTagName("a");
  for (var i=0; i<pagelinks.length; i++){ //BEGIN FOR LOOP
   if (pagelinks[i].getAttribute("rev")=="defaultload"){ //Begin if statement
    thumbnailviewer.loadimage(pagelinks[i]);
    if (nxt_but != null) {
     nxt_but.style.display = "block";		// display the thumbnailviewer buttons
     prv_but.style.display = "block";
    }
    thumbnailviewer.overlay.style.visibility = 'visible'; //Show "overlay" div
    return false;
   } //end if statement
  } //END FOR LOOP
 }, //END init1() function


 init : function(){  //Initialize thumbnail viewer script by listening to the page for clicks on links with rel="thumbnail"
  if (!this.enableAnimation)
   this.opacitystring = '';
  var pagelinks = function(e){
   var t; e = e || window.event; t = e.target || e.srcElement;
   while(t.parentNode && t.nodeName && t.nodeName.toLowerCase() != 'a')
    t = t.parentNode;
   if (t.nodeName && t.nodeName.toLowerCase() == 'a' && t.rel && t.rel == 'thumbnail'){ //Begin if statement
    if (e.preventDefault) e.preventDefault();
    thumbnailviewer.stopanimation(); //Stop any currently running fade animation on "thumbbox" div before proceeding
    thumbnailviewer.loadimage(t); //Load image
    return false;
   } //end if statement
  return undefined;
  };
  this.dotask(document, pagelinks, 'click');
 //Reposition "thumbbox" div when page is resized
  this.dotask(window, function(){
   if (thumbnailviewer.thumbBox.style.visibility=='visible')
    thumbnailviewer.centerDiv(thumbnailviewer.thumbBox);},
    'resize');
 } //END init() function

};

var nxt_but = document.getElementById("pop_nxt");
var prv_but = document.getElementById("pop_prv");
thumbnailviewer.dotask(window, function(){thumbnailviewer.init1()}, "load") //Initialize script on page load
thumbnailviewer.init();
thumbnailviewer.createthumbBox(); //Output HTML for the image thumbnail viewer
