// © 2008-2011 by M Waldron aka layingback
//  http://www.layingback.net
//** Featured Content Slider script- (c) Dynamic Drive DHTML code library: http://www.dynamicdrive.com.
//** July 11th, 08'- Script updated to v 2.4:
//** March 1st, 09"- Custom mode to pause slider (if in auto rotate mode) when mouse rolls over slider and pagnation DIV
// modified only to use unique CSS names - layingback
var featuredcontentslider={
// 3 variables below you can customize if desired:
// ajaxloadingmsg: '<div style="margin: 20px 0 0 20px"><img src="loading.gif" /> Fetching slider Contents. Please wait...</div>',
// bustajaxcache: true, //bust caching of external ajax page after 1st request?
enablepersist: false, //persist to last content viewed when returning to page?
settingcaches: {}, //object to cache "setting" object of each script instance
jumpTo: function(fcsid, pagenumber){ //public function to go to a slide manually.
	this.turnpage(this.settingcaches[fcsid], pagenumber)
},
/* ajaxconnect:function(setting){
	var page_request = false
	if (window.ActiveXObject){ //Test for support for ActiveXObject in IE first (as XMLHttpRequest in IE7 is broken)
		try {
		page_request = new ActiveXObject("Msxml2.XMLHTTP")
		}
		catch (e){
			try{
			page_request = new ActiveXObject("Microsoft.XMLHTTP")
			}
			catch (e){}
		}
	}
	else if (window.XMLHttpRequest) // if Mozilla, Safari etc
		page_request = new XMLHttpRequest()
	else
		return false
	var pageurl=setting.contentsource[1]
	page_request.onreadystatechange=function(){
		featuredcontentslider.ajaxpopulate(page_request, setting)
	}
	document.getElementById(setting.id).innerHTML=this.ajaxloadingmsg
	var bustcache=(!this.bustajaxcache)? "" : (pageurl.indexOf("?")!=-1)? "&"+new Date().getTime() : "?"+new Date().getTime()
	page_request.open('GET', pageurl+bustcache, true)
	page_request.send(null)
},

ajaxpopulate:function(page_request, setting){
	if (page_request.readyState == 4 && (page_request.status==200 || window.location.href.indexOf("http")==-1)){
		document.getElementById(setting.id).innerHTML=page_request.responseText
		this.buildpaginate(setting)
	}
}, */
buildcontentdivs:function(setting){
	var alldivs=document.getElementById(setting.id).getElementsByTagName("div")
	for (var i=0; i<alldivs.length; i++){
		if (this.css(alldivs[i], "pn_contentdiv", "check")){ //check for DIVs with class "contentdiv"
			setting.contentdivs.push(alldivs[i])
				alldivs[i].style.display="none" //collapse all content DIVs to begin with
				description[i]=alldivs[i].title
		}
	}
},
buildpaginate:function(setting){
	this.buildcontentdivs(setting)
	var sliderdiv=document.getElementById(setting.id)
	var pdiv=document.getElementById("paginate-"+setting.id)
	var sdiv=document.getElementById("scroller-"+setting.id)
	var t=0 // delay for clearing ispaused to avoid event bubbling on nested divs
//** March 2nd, 09 - additional code to pause rotation onMouseover
	sliderdiv.onmouseover=function(){
		clearTimeout(t)
		setting.ispaused=true
	}
	sliderdiv.onmouseout=function(){
		t=setTimeout(function(){setting.ispaused=false},10)
	}
/*	pdiv.onmouseover=function(){
		setting.ispaused=true
	}
	pdiv.onmouseout=function(){
		setting.ispaused=false
	} */ // switch to new slider scroller - (c) 2010 layingback: http://layingback.net
	if (typeof setting.toc=="string" && setting.toc!="#increment"){
		sdiv.onmouseover=function(){
			clearTimeout(t)
			setting.ispaused=true
		}
		sdiv.onmouseout=function(){
			t=setTimeout(function(){setting.ispaused=false},10)
		}
	}
	var phtml=""
	var toc=setting.toc
	var nextprev=setting.nextprev
	if (typeof toc=="string" && toc!="markup" || typeof toc=="object"){
		for (var i=1; i<=setting.contentdivs.length; i++){
			phtml+='<a href="#'+i+'" class="toc">'+(typeof toc=="string"? toc.replace(/#increment/, i) : toc[i-1])+'</a> '
		}
		phtml=(nextprev[0]!=''? '<a href="#prev" class="prev">'+nextprev[0]+'</a> ' : '') + phtml + (nextprev[1]!=''? '<a href="#next" class="next">'+nextprev[1]+'</a>' : '')
		pdiv.innerHTML=phtml
	}
	var pdivlinks=pdiv.getElementsByTagName("a")
	var toclinkscount=0 //var to keep track of actual # of toc links
	for (var i=0; i<pdivlinks.length; i++){
		if (this.css(pdivlinks[i], "toc", "check")){
			if (toclinkscount>setting.contentdivs.length-1){ //if this toc link is out of range (user defined more toc links then there are contents)
				pdivlinks[i].style.display="none" //hide this toc link
				continue
			}
			pdivlinks[i].setAttribute("rel", ++toclinkscount) //store page number inside toc link
			pdivlinks[i][setting.revealtype]=function(){
				featuredcontentslider.turnpage(setting, this.getAttribute("rel"))
				return false
			}
			setting.toclinks.push(pdivlinks[i])
		}
		else if (this.css(pdivlinks[i], "prev", "check") || this.css(pdivlinks[i], "next", "check")){ //check for links with class "prev" or "next"
			pdivlinks[i].onclick=function(){
				featuredcontentslider.turnpage(setting, this.className)
				return false
			}
		}
	}
	setting.currentpage=Math.min(setting.contentdivs.length-1, setting.currentpage)
	setting.prevpage=setting.currentpage
	this.turnpage(setting, setting.currentpage, true)
	if (setting.autorotate[0]){ //if auto rotate enabled
		pdiv[setting.revealtype]=function(){
//			featuredcontentslider.cleartimer(setting, window["fcsautorun"+setting.id])  // To continue autoscroll after manual intervention - lb
		}
		sliderdiv["onclick"]=function(){ //stop content slider when slides themselves are clicked on
//			featuredcontentslider.cleartimer(setting, window["fcsautorun"+setting.id])  // To continue autoscroll after manual intervention - lb
		}
		setting.autorotate[1]=setting.autorotate[1]+(1/setting.enablefade[1]*50) //add time to run fade animation (roughly) to delay between rotation
	 this.autorotate(setting)
	}
},
urlparamselect:function(fcsid){
	var result=window.location.search.match(new RegExp(fcsid+"=(\\d+)", "i")) //check for "?featuredcontentsliderid=2" in URL
	return (result==null)? null : parseInt(RegExp.$1) //returns null or index, where index (int) is the selected tab's index
},
turnpage:function(setting, thepage, autocall){
	var currentpage=setting.currentpage //current page # before change
	var totalpages=setting.contentdivs.length
	var turntopage=(/prev/i.test(thepage))? currentpage-1 : (/next/i.test(thepage))? currentpage+1 : parseInt(thepage)
	turntopage=(turntopage<1)? totalpages : (turntopage>totalpages)? 1 : turntopage //test for out of bound and adjust
	if (turntopage==setting.currentpage && typeof autocall=="undefined") //if a pagination link is clicked on repeatedly
		return
	setting.currentpage=turntopage
	setting.contentdivs[turntopage-1].style.zIndex=++setting.topzindex
	this.cleartimer(setting, window["fcsfade"+setting.id])
	setting.cacheprevpage=setting.prevpage
	if (typeof description[setting.currentpage]=="string") {
		document.getElementById("describediv-"+setting.id).innerHTML=description[setting.currentpage]  // add caption - (c) 2010 layingback: http://layingback.net
	}
	if (setting.enablefade[0]==true){
		setting.curopacity=0
		this.fadeup(setting)
	}
	if (setting.enablefade[0]==false){ //if fade is disabled, fire onChange event immediately (verus after fade is complete)
		setting.contentdivs[setting.prevpage-1].style.display="none" //collapse last content div shown (it was set to "block")
		setting.onChange(setting.prevpage, setting.currentpage)
	}
	setting.contentdivs[turntopage-1].style.visibility="visible"
	setting.contentdivs[turntopage-1].style.display="block"
	if (setting.prevpage<=setting.toclinks.length) //make sure pagination link exists (may not if manually defined via "markup", and user omitted)
		this.css(setting.toclinks[setting.prevpage-1], "selected", "remove")
	if (turntopage<=setting.toclinks.length) //make sure pagination link exists (may not if manually defined via "markup", and user omitted)
		this.css(setting.toclinks[turntopage-1], "selected", "add")
		if (!setting.ispaused) {  // move slider img  - (c) 2010 layingback: http://layingback.net
			if (turntopage>setting.slideafter)
				this.move(setting,"right")
			if (turntopage==1)
				this.move(setting,"reset")
		}
	setting.prevpage=turntopage
	if (this.enablepersist)
		this.setCookie("fcspersist"+setting.id, turntopage)
},
setopacity:function(setting, value){ //Sets the opacity of targetobject based on the passed in value setting (0 to 1 and in between)
	var targetobject=setting.contentdivs[setting.currentpage-1]
	if (targetobject.filters && targetobject.filters[0]){ //IE syntax
		if (typeof targetobject.filters[0].opacity=="number") //IE6
			targetobject.filters[0].opacity=value*100
		else //IE 5.5
			targetobject.style.filter="alpha(opacity="+value*100+")"
	}
	else if (typeof targetobject.style.MozOpacity!="undefined") //Old Mozilla syntax
		targetobject.style.MozOpacity=value
	else if (typeof targetobject.style.opacity!="undefined") //Standard opacity syntax
		targetobject.style.opacity=value
	setting.curopacity=value
},
fadeup:function(setting){
	if (setting.curopacity<1){
		this.setopacity(setting, setting.curopacity+setting.enablefade[1])
		window["fcsfade"+setting.id]=setTimeout(function(){featuredcontentslider.fadeup(setting)}, 50)
	}
	else{ //when fade is complete
		if (setting.cacheprevpage!=setting.currentpage) //if previous content isn't the same as the current shown div (happens the first time the page loads/ script is run)
			setting.contentdivs[setting.cacheprevpage-1].style.display="none" //collapse last content div shown (it was set to "block")
		setting.onChange(setting.cacheprevpage, setting.currentpage)
	}
},
cleartimer:function(setting, timervar){
	if (typeof timervar!="undefined"){
		clearTimeout(timervar)
		clearInterval(timervar)
		if (setting.cacheprevpage!=setting.currentpage){ //if previous content isn't the same as the current shown div
			setting.contentdivs[setting.cacheprevpage-1].style.display="none"
		}
	}
},
css:function(el, targetclass, action){
	var needle=new RegExp("(^|\\s+)"+targetclass+"($|\\s+)", "ig")
	if (action=="check")
		return needle.test(el.className)
	else if (action=="remove")
		el.className=el.className.replace(needle, "")
	else if (action=="add")
		el.className+=" "+targetclass
},

move:function(setting, action){ // move slider img  - (c) 2010 layingback: http://layingback.net
	if (typeof setting.toc=="string" && setting.toc=="markup") {
		var d = document.getElementById("scroller-"+setting.id)
		if (action=="right") {
			d.scrollLeft = d.scrollLeft + 56
		}
		else if (action=="reset")
			d.scrollLeft = 0;
		featuredcontentslider.set_buttons(setting.id)
	}
},

autorotate:function(setting){
	window["fcsautorun"+setting.id]=setInterval(function(){
		if (!setting.ispaused)
			featuredcontentslider.turnpage(setting, "next")
	}, setting.autorotate[1])
},
getCookie:function(Name){
	var re=new RegExp(Name+"=[^;]+", "i"); //construct RE to search for target name/value pair
	if (document.cookie.match(re)) //if cookie found
		return document.cookie.match(re)[0].split("=")[1] //return its value
	return null
},

setCookie:function(name, value){
	document.cookie = name+"="+value

},
init:function(setting){
	if (typeof setting.toc=="string" && setting.toc!="#increment") {
		j = document.getElementById('pn_jsreqd')
		if (j) {
			j.innerHTML=''	// clear js required warning as javascript is enabled
			featuredcontentslider.set_buttons(setting.id)
		}
		else {return false}
	}
//** August 27th, 09 - additional code to separate next/prev buttons
	var pn_prev=document.getElementById('pn_prev')
	var pn_next=document.getElementById('pn_next')
	var fcgetoffset=function(what, offsettype) {
		if (what) {
			return (what.offsetParent)? what[offsettype]+fcgetoffset(what.offsetParent, offsettype) : what[offsettype]
		}
	}
	var offx=fcgetoffset(document.getElementById(setting.id), "offsetLeft")
	var offy=fcgetoffset(document.getElementById(setting.id), "offsetTop")
	if (pn_prev) {
		pn_prev.style.left=offx+"px"
		pn_prev.style.top=offy+100+"px"
	}
	if (pn_next) {
		pn_next.style.left=offx+400+"px"
		pn_next.style.top=offy+100+"px"
	}
//** end of additional code to separate next/prev buttons
	var persistedpage=this.getCookie("fcspersist"+setting.id) || 1
	var urlselectedpage=this.urlparamselect(setting.id) //returns null or index from: mypage.htm?featuredcontentsliderid=index
	this.settingcaches[setting.id]=setting //cache "setting" object
	setting.contentdivs=[]
	setting.toclinks=[]
	setting.topzindex=0
	setting.currentpage=urlselectedpage || ((this.enablepersist)? persistedpage : 1)
	setting.prevpage=setting.currentpage
	setting.revealtype="on"+(setting.revealtype || "click")
	setting.curopacity=0
	setting.onChange=setting.onChange || function(){}
	if (setting.contentsource[0]=="inline")
		this.buildpaginate(setting)
	if (setting.contentsource[0]=="ajax")
		this.ajaxconnect(setting)
},
//**  Control script for DD's Featured Content Slider - (c) 2009-2010 layingback: http://layingback.net
//**		IE version by jscheuer1 of DynamicDrive
//**  This notice MUST stay intact for legal use
scroll_left:function(id, event){
	document.previmg.src = "images/pro_news/left_scroll.gif"
	var d = document.getElementById("scroller-"+id)
	if (event.shiftKey == 1) {
		d.scrollLeft = 0
	}
	else if (d.scrollLeft <= d.scrollWidth - d.clientWidth) {
			d.scrollLeft = d.scrollLeft - 5
			scrolling = window.setTimeout(function() {
				featuredcontentslider.scroll_left(id, {shiftKey: 0})
			}, 30);
	}
},
scroll_right:function(id, event){
	document.nextimg.src = "images/pro_news/right_scroll.gif"
	var d = document.getElementById("scroller-"+id)
	if (event.shiftKey == 1) {
		d.scrollLeft = d.scrollWidth - d.clientWidth
	}
	else if (d.scrollLeft <= d.scrollWidth) {
		d.scrollLeft = d.scrollLeft + 5
		scrolling = window.setTimeout(function() {
			featuredcontentslider.scroll_right(id, {shiftKey: 0})
		}, 30);
	}
},
scroll_stop:function(id, event){
	window.clearTimeout(scrolling)
	featuredcontentslider.set_buttons(id)
},
set_buttons:function(id){
	var d = document.getElementById("scroller-"+id)
	if (d.scrollWidth <= d.clientWidth) {
		document.nextimg.src = "images/pro_news/right_scroll.gif"
		document.previmg.src = "images/pro_news/left_scroll.gif"
	}
	else if (d.scrollLeft <= 0) {
		document.nextimg.src = "images/pro_news/right_scrollb.gif"
		document.previmg.src = "images/pro_news/left_scroll.gif"
	}
	else if (d.scrollLeft >= d.scrollWidth - d.clientWidth) {
		document.previmg.src = "images/pro_news/left_scrollb.gif"
		document.nextimg.src = "images/pro_news/right_scroll.gif"
	}
	else {
		document.previmg.src = "images/pro_news/left_scrollb.gif"
		document.nextimg.src = "images/pro_news/right_scrollb.gif"
	}
}
//** end of Control script - layingback

}


//DD Tab Menu- Script rewritten April 27th, 07: http://www.dynamicdrive.com
//**Updated Feb 23rd, 08): Adds ability for menu to revert back to default selected tab when mouse moves out of menu
//Only 2 configuration variables below:

var ddtabmenu={
	disabletablinks: false, //Disable hyperlinks in 1st level tabs with sub contents (true or false)?
	snap2original: [false, 300], //Should tab revert back to default selected when mouse moves out of menu? ([true/false, delay_millisec]

	currentpageurl: window.location.href.replace("http://"+window.location.hostname, "").replace(/^\//, ""), //get current page url (minus hostname, ie: http://www.dynamicdrive.com/)

	definemenu:function(tabid, dselected){
		this[tabid+"-menuitems"]=null
		this[tabid+"-dselected"]=-1
		this.addEvent(window, function(){ddtabmenu.init(tabid, dselected)}, "load")
	},

	showsubmenu:function(tabid, targetitem){
		var menuitems=this[tabid+"-menuitems"]
		this.clearrevert2default(tabid)
	 for (i=0; i<menuitems.length; i++){
			menuitems[i].className=""
			if (typeof menuitems[i].hasSubContent!="undefined")
				document.getElementById(menuitems[i].getAttribute("rel")).style.display="none"
		}
		targetitem.className="current"
		if (typeof targetitem.hasSubContent!="undefined")
			document.getElementById(targetitem.getAttribute("rel")).style.display="block"
	},

	isSelected:function(menuurl){
		var menuurl=menuurl.replace("http://"+menuurl.hostname, "").replace(/^\//, "")
		return (ddtabmenu.currentpageurl==menuurl)
	},

	isContained:function(m, e){
		var e=window.event || e
		var c=e.relatedTarget || ((e.type=="mouseover")? e.fromElement : e.toElement)
		while (c && c!=m)try {c=c.parentNode} catch(e){c=m}
		if (c==m)
			return true
		else
			return false
	},

	revert2default:function(outobj, tabid, e){
		if (!ddtabmenu.isContained(outobj, tabid, e)){
			window["hidetimer_"+tabid]=setTimeout(function(){
				ddtabmenu.showsubmenu(tabid, ddtabmenu[tabid+"-dselected"])
			}, ddtabmenu.snap2original[1])
		}
	},

	clearrevert2default:function(tabid){
	 if (typeof window["hidetimer_"+tabid]!="undefined")
			clearTimeout(window["hidetimer_"+tabid])
	},

	addEvent:function(target, functionref, tasktype){ //assign a function to execute to an event handler (ie: onunload)
		var tasktype=(window.addEventListener)? tasktype : "on"+tasktype
		if (target.addEventListener)
			target.addEventListener(tasktype, functionref, false)
		else if (target.attachEvent)
			target.attachEvent(tasktype, functionref)
	},

	init:function(tabid, dselected){
		var menuitems=document.getElementById(tabid).getElementsByTagName("a")
		this[tabid+"-menuitems"]=menuitems
		for (var x=0; x<menuitems.length; x++){
			if (menuitems[x].getAttribute("rel")){
				this[tabid+"-menuitems"][x].hasSubContent=true
				if (ddtabmenu.disabletablinks)
					menuitems[x].onclick=function(){return false}
				if (ddtabmenu.snap2original[0]==true){
					var submenu=document.getElementById(menuitems[x].getAttribute("rel"))
					menuitems[x].onmouseout=function(e){ddtabmenu.revert2default(submenu, tabid, e)}
					submenu.onmouseover=function(){ddtabmenu.clearrevert2default(tabid)}
					submenu.onmouseout=function(e){ddtabmenu.revert2default(this, tabid, e)}
				}
			}
			else //for items without a submenu, add onMouseout effect
				menuitems[x].onmouseout=function(e){this.className=""; if (ddtabmenu.snap2original[0]==true) ddtabmenu.revert2default(this, tabid, e)}
			menuitems[x].onmouseover=function(){ddtabmenu.showsubmenu(tabid, this)}
			if (dselected=="auto" && typeof setalready=="undefined" && this.isSelected(menuitems[x].href)){
				ddtabmenu.showsubmenu(tabid, menuitems[x])
				this[tabid+"-dselected"]=menuitems[x]
				var setalready=true
			}
			else if (parseInt(dselected)==x){
				ddtabmenu.showsubmenu(tabid, menuitems[x])
				this[tabid+"-dselected"]=menuitems[x]
			}
		}
	}
}
