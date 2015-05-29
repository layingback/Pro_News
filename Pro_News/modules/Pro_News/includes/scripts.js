// © 2008-2009 by M Waldron aka layingback
//  http://www.layingback.net
function PN_openBrWindow(theURL,winName,features,newwidth,newheight) {
  var pnwindow = window.open(theURL,winName,features);
  if (newheight != pnwindow.innerHeight || newwidth != pnwindow.innerWidth) {
	pnwindow.close();
	pnwindow = window.open(theURL,winName,features);
  }
  if (window.focus) {pnwindow.focus();}
}
// Multiple file selector by Stickman -- http://www.the-stickman.com
// with thanks to: [for Safari fixes] Luis Torrefranca -- http://www.law.pitt.edu and Shawn Parker & John Pennypacker -- http://www.fuzzycoconut.com [for duplicate name bug] 'neal'
/*Credit:
 *   Class by Stickman -- http://www.the-stickman.com
 *      with thanks to:
 *      [for Safari fixes]
 *         Luis Torrefranca -- http://www.law.pitt.edu
 *         and
 *         Shawn Parker & John Pennypacker -- http://www.fuzzycoconut.com
 *      [for duplicate name bug]
 *         'neal'
 */
function MultiSelector( list_target, max ){
	this.list_target = list_target;
	this.count = 0;
	this.id = 0;
	if( max ){
		this.max = max;
	} else {
		this.max = -1;
	};
	this.addElement = function( element ){
		if( element.tagName == 'INPUT' && element.type == 'file' ){
			element.name = 'file_' + this.id++;
			element.multi_selector = this;
			element.onchange = function(){
				var new_element = document.createElement( 'input' );
				new_element.type = 'file';
				this.parentNode.insertBefore( new_element, this );
				this.multi_selector.addElement( new_element );
				this.multi_selector.addListRow( this );
				document.getElementById( 'multifile' ).appendChild( this );			// Firefox fix: Chris Coleman
				this.style.position = 'absolute';
				this.style.left = '-1000px';
			};
			if( this.max != -1 && this.count >= this.max ){
				element.disabled = true;
			};
			this.count++;
			this.current_element = element;
		} else {
			alert( 'Error: not a file input element' );
		};
	};
	this.addListRow = function( element ){
		var new_row = document.createElement( 'div' );
		var new_row_button = document.createElement( 'input' );
		new_row_button.type = 'button';
		new_row_button.value = 'Delete';
		new_row.element = element;
		new_row_button.onclick= function(){
			this.parentNode.element.parentNode.removeChild( this.parentNode.element );
			this.parentNode.parentNode.removeChild( this.parentNode );
			this.parentNode.element.multi_selector.count--;
			this.parentNode.element.multi_selector.current_element.disabled = false;
			return false;
		};
		new_row.innerHTML = element.value + ' ';
		new_row.appendChild( new_row_button );
		this.list_target.appendChild( new_row );
	};
}
