jQuery(document).ready(function($) {

	// handle entries with a prehash (bookmarks)
	if(window.location.hash) { 
		var bookmark = window.location.hash.split("#")[1];		
		//Was this a deep link to a tile?
		if(bookmark.indexOf("tile_") !== -1) {
			hash_array = bookmark.split('_');
			sid = hash_array[1];			
		} else { //was a link just to a menu section		
			sid = bookmark;						
		}
		loadTiles(sid);
		
    }
	
	//reset hash for each menu slider clicked
	$(".gw_chapter_menu_link").click(function(event){
 		window.location.hash=this.hash;
	});

	//onclick hash changes
    $(window).hashchange(function(){	
		// retrieve target page from URL:
     	var gw_hash = window.location.hash.split("#")[1];		
		//Was this click on a resource tile link?
		if(gw_hash.indexOf("tile_") !== -1) {
			if ($("#"+gw_hash).length == 0){ //this tile is currently on the page
				gw_hash_array = gw_hash.split('_');
				sid = gw_hash_array[1];
				loadTiles(sid);				
			} 
		} 
				 	
     });
	 
	 function loadTiles(sid) {	
	 // retrieve target page from URL:		
			//request output via ajax
			   $.post(
				  MyAjax.ajaxurl, 
				  {action: 'gw_resource_output', sid:sid})
				  .done(function(data) {
					  $("#gw_tile_stage").fadeOut("slow", function (){ 
					  $('#gw_tile_stage').html(data).fadeIn("slow", function () {
						  var bookmark = window.location.hash.split("#")[1];
						  $("."+bookmark).click();
						  if ($('#gw_tile_container').height() > $('#gw_tile_stage').height()) {
								$("#down").hover(function () {
									animateContent("down");
								}, function() { $('#gw_tile_container').stop(); });
							
								$("#up").hover(function () {
									animateContent("up");
								}, function() { $('#gw_tile_container').stop(); });
							}
					  });
							
				  });					 
			  });
		
	
	}
	
	function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
		vars[key] = value;
	});
	return vars;
}
    
	
	//create chapter accordion menu
	$(".gw_accordion").show();
	$(".gw_accordion").accordion({
		navigation: true,
		collapsible: true,
		active: false,
		autoHeight: false	 

	});

	$(".gw_menu_tile_link").localScroll({
		target: "#gw_tile_stage",
		hash: true
	});
	
	
	

	
function animateContent(direction) {  
    var animationOffset = $('#gw_tile_stage').height() - $('#gw_tile_container').height();
    if (direction == 'up') {
        animationOffset = 0;
    }
    
    $('#gw_tile_container').animate({ "marginTop": animationOffset + "px" }, 10000);
}

if($("#gw_back").length != 0) {
	var page_title = $('.post-title').html();
	var current_url = $(location).attr('href');
	var groundwork_home = current_url.split("?")[0];
	var book_title = getUrlVars()["book_title"].split("#")[0];
	$('.post-title').html(unescape(book_title));
}

	
	

 
 
 });
 