<?PHP

/*

PLUGIN META INFO FOR WORDPRESS LISTINGS

Plugin Name: GroundWork

Description: Wordpress plugin to organize and deploy large scale staff/professional development materials

Version: 0.9

Author: Chris Nilsson

*/



register_activation_hook( __FILE__, 'gw_activate' );
register_deactivation_hook( __FILE__, 'gw_deactivate' );


add_action('admin_menu', 'gw_admin_menu');
add_action( 'admin_enqueue_scripts', 'gw_admin_styles_scripts' );
add_action( 'wp_enqueue_scripts', 'gw_styles_scripts' );
add_action('wp_ajax_store_sort', 'store_sort');
add_action('wp_ajax_load_tile_sort', 'load_tile_sort');
add_action('wp_ajax_store_resource_sort', 'store_resource_sort');
add_action('wp_ajax_gw_resource_output', 'gw_resource_output');
add_action('wp_ajax_nopriv_gw_resource_output', 'gw_resource_output');
add_action('wp_ajax_gw_avalible_tile_list_ajax', 'gw_avalible_tile_list_ajax');
add_action( 'admin_init', 'gw_setup' );
add_action('template_redirect','gw_is_restricted');

add_image_size( 'GroundWork', 180, 110 ); 

add_shortcode( 'GroundWork', 'gw_groundwork' );
add_shortcode( 'groundwork', 'gw_groundwork' );


//Activate the plugin
function gw_activate() {

	add_option("gw_name", "");

	if (!defined('GW_VERSION_NUM'))
    define('GW_VERSION_NUM', '1.0.0');

	add_option(GW_VERSION_KEY, GW_VERSION_NUM);

	global $wpdb;
  
	//create tables
	$table_name = $wpdb->prefix . "gw_sections";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
						`sid` int(11) NOT NULL AUTO_INCREMENT,
  						`title` text COLLATE utf8_unicode_ci,
  						`detail` text COLLATE utf8_unicode_ci,
  						`restricted` tinyint(4) DEFAULT '0',
  						`logo` int(11) DEFAULT NULL,
  						`parent_sid` int(11) DEFAULT '0',
  						`section_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`sid`)

						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		  dbDelta($sql);
	}
	$table_name = $wpdb->prefix . "gw_tiles";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
					      `tid` int(11) NOT NULL AUTO_INCREMENT,
						  `title` text COLLATE utf8_unicode_ci,
						  `description` text COLLATE utf8_unicode_ci,
						  `embed_code` text COLLATE utf8_unicode_ci,
						  `links` text COLLATE utf8_unicode_ci,
						  PRIMARY KEY (`tid`)

						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		  dbDelta($sql);
	}
	$table_name = $wpdb->prefix . "gw_lookup";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
							`resource_order` int(11) NOT NULL AUTO_INCREMENT,
 							`sid` int(11) DEFAULT NULL,
 							`tid` int(11) DEFAULT NULL,
  PRIMARY KEY (`resource_order`)
							) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		  dbDelta($sql);
	}
	

}





//deactivate the plugin
function gw_deactivate() {
	global $wpdb;
	
    $table = $wpdb->prefix . "gw_sections";
	$wpdb->query("DROP TABLE IF EXISTS $table");
	$table = $wpdb->prefix . "gw_tiles";
	$wpdb->query("DROP TABLE IF EXISTS $table");
	$table = $wpdb->prefix . "gw_lookup";
	$wpdb->query("DROP TABLE IF EXISTS $table");

}

//include css and scripts on admin page
function gw_admin_styles_scripts() {
	
	wp_register_style( 'gw_admin_style', plugins_url( 'css/gw_admin.css', __FILE__ ), array(), '', 'all' ); 
	wp_enqueue_style( 'gw_admin_style' ); 
	wp_register_script(	'gw_nestedSortable', 								//handle
						plugins_url( 'js/nestedSortable.js', __FILE__ ), 	//source
						array('jquery-ui-sortable'), 						//dependencies
						'', 												//version
						false 												//in footer
					);  
					
	wp_register_script(	'gw_admin_js', 								//handle
						plugins_url( 'js/gw_admin.js', __FILE__ ), 	//source
						array('gw_nestedSortable'), 				//dependencies
						'', 										//version
						false 										//in footer
					);  
	
	wp_enqueue_script('gw_admin_js'); 
	wp_enqueue_media();


	$params = array(  
					'processing_file' => plugins_url( 'Processing.php', __FILE__ )
					);
	wp_localize_script( 'gw_admin_js', 'gw_admin_js_params', $params );
}

//include css and scripts on front end
function gw_styles_scripts() {
	
	
	wp_register_style( 'gw_ui_style', plugins_url( 'css/gw-jquery-ui-blue.css', __FILE__ ), array(), '', 'all' ); 
	wp_enqueue_style( 'gw_ui_style' ); 
	
					
	wp_register_style( 'gw_style', plugins_url( 'css/gw.css', __FILE__ ), array(), '', 'all' ); 
	wp_enqueue_style( 'gw_style' ); 
	wp_register_script(	'gw_js', 								//handle
						plugins_url( 'js/gw.js', __FILE__ ), 	//source
						array('jquery-ui-accordion'), 						//dependencies
						'', 										//version
						false 										//in footer
					);  
	wp_enqueue_script('gw_js');
	wp_localize_script( 'gw_js', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	wp_register_script(	'gw_hashtag', 								//handle
						plugins_url( 'js/hashtag_event.js', __FILE__ ), 	//source
						array('jquery'), 						//dependencies
						'', 										//version
						false 										//in footer
					);  
	wp_enqueue_script('gw_hashtag');
	wp_register_script(	'gw_scrollTo', 								//handle
						plugins_url( 'js/scrollTo.js', __FILE__ ), 	//source
						array('jquery'), 						//dependencies
						'', 										//version
						false 										//in footer
					);  
	wp_enqueue_script('gw_scrollTo');
	wp_register_script(	'gw_localScroll', 								//handle
						plugins_url( 'js/localScroll.js', __FILE__ ), 	//source
						array('jquery'), 						//dependencies
						'', 										//version
						false 										//in footer
					);  
	wp_enqueue_script('gw_localScroll');


}

//setup options
function gw_setup() {
	global $pagenow;

	if ( 'media-upload.php' == $pagenow || 'async-upload.php' == $pagenow ) {
		// Now we'll replace the 'Insert into Post Button' inside Thickbox
		add_filter( 'gettext', 'replace_thickbox_text'  , 1, 3 );
	}
}

function replace_thickbox_text($translated_text, $text, $domain) {
	if ('Insert into Post' == $text) {
		$referer = strpos( wp_get_referer(), 'GroundWork-Sections' );
		if ( $referer != '' ) {
			return __('Set as Section Logo', 'wptuts' );
		}
	}
	return $translated_text;
}


//build the admin menu locations
function gw_admin_menu() {
	
	$icon_url = plugins_url('images/groundwork.png', __FILE__);

	add_menu_page(		'GroundWork', 		//page title
						'GroundWork', 		//menu title
						'edit_posts',		//capibility required to access 
						'GroundWork-admin', //page slug
						'gw_admin_welcome'	//menu icon
						
					);
	//sections	
	add_submenu_page( 	'GroundWork-admin', 	//parent slug
						'Manage Sections', 		//page title
						'Manage Sections',		//menue title
						'edit_posts',			//capibility required to access
						'GroundWork-Sections', 	//page slug
						'gw_admin_sections'		//function to run
						);
	//resources					
	add_submenu_page( 	'GroundWork-admin', 	//parent slug
						'Manage Resources', 	//page title
						'Manage Resources',		//menue title
						'edit_posts',			//capibility required to access
						'GroundWork-Resources', //page slug
						'gw_admin_resources'	//function to run
						);


}

//build the main landing page
function gw_admin_welcome() {
	echo '<h2>GroundWork</h2>
			<h3>Simple Elegant Resource Management</h3>
			GroundWork allows you to create Resource "Books" and maintain them quickly and easily. Your users will love how easy it is to locate just the right resource using the beautiful "Bookshelf" and simple Chapter-Section navigation.<br /><br />
Visit a live GroundWork library at the <a href="http://icafe.lcisd.org/resources" target="_new">iCafe</a><br/><br />
The four videos below will get you started or you can simply begin adding resources and creating books. 
Add the shortcode <strong>[groundwork]</strong> to a page to view your bookshelf!<br />
<h3>1 - Creating your first Book</h3>
<iframe width="420" height="315" src="http://www.youtube.com/embed/7N9eJdQiPIs" frameborder="0" allowfullscreen></iframe>
<h3>2 - Creating your first Resource Tile</h3>
<iframe width="420" height="315" src="http://www.youtube.com/embed/F3uLCYytQao" frameborder="0" allowfullscreen></iframe>
<h3>3 - Adding Resource Tiles to a Book</h3>
<iframe width="420" height="315" src="http://www.youtube.com/embed/phMJfvPXrig" frameborder="0" allowfullscreen></iframe>
<h3>4 - Adding your library to a Page</h3>
<iframe width="420" height="315" src="http://www.youtube.com/embed/fp8IatJUJDA" frameborder="0" allowfullscreen></iframe>
<br /><br />
<strong>Please send any questions, comments, or suggestions to chrisdnilsson@gmail.com</strong>
 ';
 
}

//build the sections manager
function gw_admin_sections() {
	global $wpdb;
	
	if ($_POST) {//update options
	
		if (isset($_POST['delete'])) {//delete section
			$sid = $_POST['sid']; 
      		$table_name = $wpdb->prefix . "gw_sections";
			$wpdb->query( 
				$wpdb->prepare( 
					"
					 DELETE FROM $table_name
					 WHERE sid = %d", $sid
					)
			);
			
			$table_name = $wpdb->prefix . "gw_lookup";
			$wpdb->query( 
				$wpdb->prepare( 
					"
					 DELETE FROM $table_name
					 WHERE sid = %d", $sid
					)
			);
			$table_name = $wpdb->prefix . "gw_sections";
			$wpdb->update($table_name, array('parent_sid' => "0"), array('parent_sid' => "$sid"));
		$show_edit = 'false';
    	
		} else {//add new or edit existing section
     		
    
			
			if ($_POST['form'] == 'add_node') {
				echo '<div id="message" class="updated fade"><p>Your new node has been added below.</p></div>';
			
				$title = $_POST['title'];
				$detail = $_POST['detail'];
				$logo_id = $_POST['image_id'];
				$restricted = $_POST['restricted'];
				$table_name = $wpdb->prefix . "gw_sections";			
				$wpdb->insert($table_name, array('title' => "$title", 'detail' => "$detail", 'restricted' => "$restricted", 'logo' => "$logo_id"));
				
				
			} else if ($_POST['form'] == 'edit_node') {
				echo '<div id="message" class="updated fade"><p>Your changes have been saved.</p></div>';
		
				$title = $_POST['title'];
				$detail = $_POST['detail'];
			
				$logo_id = $_POST['image_id'];
				$restricted = $_POST['restricted'];
				$sid = $_POST['sid']; 
									
				$table_name = $wpdb->prefix . "gw_sections";			
				$wpdb->update($table_name, array('title' => "$title", 'detail' => "$detail", 'restricted' => "$restricted", 'logo' => "$logo_id"), array('sid' => "$sid"));
				
				$show_edit = 'false';
				
				
			}
			
		}
	
	}
	
	$output = '<div class="wrap">
            <h2>Manage Resource Organization</h2>';
	
	if ($_GET) {//update options
		if (($_GET['state'] == 'edit') && ($show_edit != 'false')) {//edit existing section
			$sid = $_GET['sid'];
			$table_name = $wpdb->prefix . "gw_sections";			
			$section_data = $wpdb->get_row("SELECT * FROM $table_name WHERE sid = $sid");
			$test = $wpdb->show_errors();
			$title = $section_data->title; 
			$detail = $section_data->detail; 
			$restricted = $section_data->restricted; 
			$logo = $section_data->logo; 
			
			if ($restricted == 1) {
				$checked = 'checked';	
			} else {
				$checked = '';
			}
			
$output .= '
		
			<h3>Edit Container</h3>
            <form method="post">
                <fieldset class="options">
				
                  <div style="float: left">
					  <label for="title"><strong>Book/Chapter/Section Title</strong></label>
					  <br />
					  <input id="title" type="text" name="title" size="20" value="'.$title.'"/>
					  <br /><br /><br /><br />
					  <input type="submit" name="newnode_save" value="Save Changes &raquo;" class="button-primary" />
					  <br /><br /><br /><br />
					  <input type="submit" name="delete" value="Delete Container &raquo;" class="button button-red" onclick="return confirm(\'Are you sure you want to permenantly delete container?\');"/>
                    </div>
					<div id="gw_preview_title"><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Required for Books</strong></div>
					<div style="float: left; padding-left:10px" class="gw_border"> 
					
						<div style="float: left; padding-left:10px"> 						
						  <label for="detail"><strong>Tag Line</strong></label>
						  <br />
						  <input id="detail" type="text" name="detail" size="30" value="'.$detail.'"/>
						   
						  <br /><br />
						  
						  
						  <input id="gw_update_logo_button" type="button" name="image" class="button" value="Upload Book Cover Image" />
						  <br /> 
						  <em>Images should be 180 x 110px</em> 
						  <br />
						  <br />
						  <label for="image"><strong>Members Only Book?</strong></label>
						  <br />	
						  <input name="restricted" type="checkbox" value="1" '.$checked.'/>
						</div>
						
						<div style="float: left; padding-left:30px">
						<strong>Live Preview</strong>
						  <ul id="gw_new_top_node">
							  <li id="post-1" class="gw_bags ">
								  <div class="gw_thumb">
									  '.wp_get_attachment_image( $logo, 'GroundWork').'
								  </div>
								  <h2 id="gw_title">
									  '.$title.'
								  </h2>
								  <span id="gw_detail">'.$detail.'</span>
							  </li>
						  </ul>
						</div>
						
					</div>	
				
				 <input type="hidden" id="gw_logo_id" name="image_id" value="'.$logo.'"/> 
				  <input name="sid" type="hidden" value="'.$sid.'" />
				 <input name="form" type="hidden" value="edit_node" />
			</fieldset>	 
				
            
			</form>
			
	
		
		
';

	
	} else {
		$output .= '
		
			<h3><a href="#" id="showadd_node_form">Add New Container</a></h3>
            <form method="post" id="newnode">
                <fieldset class="options">
				
                  <div style="float: left">
					  <label for="title"><strong>Book/Chapter/Section Title</strong></label>
					  <br />
					  <input id="title" type="text" name="title" size="20"/>
					  <br /><br /><br /><br /><br /><br />
					  <input type="submit" name="newnode_save" value="Add Container &raquo;" class="button-primary" />
                    </div>
					<div id="gw_preview_title"><strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Required for Books</strong></div>
					<div style="float: left; padding-left:10px" class="gw_border"> 
					
						<div style="float: left; padding-left:10px"> 						
						  <label for="detail"><strong>Tag Line</strong></label>
						  <br />
						  <input id="detail" type="text" name="detail" size="30"/>
						   
						  <br /><br />
						  
						  
						  <input id="gw_upload_logo_button" type="button" name="image" class="button" value="Upload Book Cover Image" />
						  <br /> 
						  <em>Images should be 180 x 110px</em> 
						  <br />
						  <br />
						  <label for="image"><strong>Members Only Book?</strong></label>
						  <br />	
						  <input name="restricted" type="checkbox" value="1" />
						</div>
						
						<div style="float: left; padding-left:30px">
						<strong>Live Preview</strong>
						  <ul id="gw_new_top_node">
							  <li id="post-1" class="gw_bags ">
								  <div class="gw_thumb">
									  <img width="337" height="332" id="gw_newnode_image" src="" />
								  </div>
								  <h2 id="gw_title">
									  
								  </h2>
								  <span id="gw_detail"></span>
							  </li>
						  </ul>
						</div>
						
					</div>	
				
				 <input type="hidden" id="gw_logo_id" name="image_id" />  
				 <input name="form" type="hidden" value="add_node" />
			</fieldset>	 
				
            
			</form>
';
	}
		
} else {


}

$output .= '</div>';
	
	
	
	$table_name = $wpdb->prefix . "gw_sections";
	
	//Start the output page
	$output .= '<h3>Current Resource Tree</h3>	
				 <div class="gw_sort">
				 Simply drag and drop to change order or nesting
					<ol class="gw_sortable">';
	//build level one
	$level1_nodes = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_sid = 0 ORDER BY section_order");
	if ($level1_nodes) {
		foreach ($level1_nodes as $level1_node) {
			$output .= gw_generate_section_sort($level1_node->sid, $level1_node->title, $level1_node->detail);
				
				//build level two
				$level2_nodes = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_sid = $level1_node->sid ORDER BY section_order");
				if ($level2_nodes) {
					$output .= '<ol>';
					foreach ($level2_nodes as $level2_node) {
						$output .= gw_generate_section_sort($level2_node->sid, $level2_node->title, $level2_node->detail);
						
						//build level three
						$level3_nodes = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_sid = $level2_node->sid ORDER BY section_order");
						if ($level3_nodes) {
							$output .= '<ol>';
							foreach ($level3_nodes as $level3_node) {
								$output .= gw_generate_section_sort($level3_node->sid, $level3_node->title, $level3_node->detail);
							}
						$output .= '</ol>';
						}
					}
				$output .= '</ol>';
				}
	
		}
	}
	
	$output .= '</ol>
				</div>';


	
$output .= '<div class="gw_assigned_tiles">
			<strong>Resources For</strong><br />
			<div id="gw_add_tiles_heading">&nbsp;</div>
			<br />
				<ul id="gw_assigned_tiles_sort" class="gw_connectedSortable">';
			
					
			
$output .= '	</ul>
			</div>';

$output .= '<div class="gw_avalible_tiles">
				<strong>Avalible Tiles</strong><br />
			<div id="gw_tile_display_options"><a href="#" class="gw_tile_display_mode" id="gw_unused">Unused Tiles</a> | <a href="#" class="gw_tile_display_mode" id="gw_all">All Tiles</a></div>
			<br />
			
			<ul id="gw_avalible_tiles_sort" class="gw_connectedSortable">';
 				
					$output .= gw_avalible_tile_list();
				
$output .= '</ul>
			</div>';

$output .= '<div class="spacer" style="clear: both;"></div>';

echo $output;

}

//generate lists of tiles for the section manager
function gw_avalible_tile_list() {
	global $wpdb;
	//list all the existing tiles
	$tile_list = '';
	$table_name = $wpdb->prefix . "gw_tiles";
	$tiles = $wpdb->get_results("SELECT * FROM $table_name ORDER BY title");
	if ($tiles) {
	
		foreach ($tiles as $tile) {
			$tile_list .= '<li id="gw_tile_'.$tile->tid.'">'.$tile->title.'</li>';
		}
	}
	
	return $tile_list;
	
}

//generate lists of tiles for the section manager
function gw_avalible_tile_list_ajax() {
	global $wpdb;
	if(!empty($_POST['mode'])) {
		global $wpdb;	
		$mode = $_POST['mode'];
		if ($mode == 'gw_all') {
			//list all the existing tiles
			$tile_list = '';
			$table_name = $wpdb->prefix . "gw_tiles";
			$tiles = $wpdb->get_results("SELECT * FROM $table_name ORDER BY title");
			if ($tiles) {
			
				foreach ($tiles as $tile) {
					$tile_list .= '<li id="gw_tile_'.$tile->tid.'">'.$tile->title.'</li>';
				}
			}
		}else if ($mode == 'gw_unused'){
			
			$table_gw_lookup = $wpdb->prefix . "gw_lookup";
		 	$table_gw_tiles = $wpdb->prefix . "gw_tiles";

	  		$tiles = $wpdb->get_results("SELECT $table_gw_tiles.tid, $table_gw_tiles.title FROM $table_gw_tiles LEFT JOIN $table_gw_lookup ON $table_gw_lookup.tid = $table_gw_tiles.tid WHERE $table_gw_lookup.tid IS NULL");
			if ($tiles) {
			
				foreach ($tiles as $tile) {
					$tile_list .= '<li id="gw_tile_'.$tile->tid.'">'.$tile->title.'</li>';
				}
			} else {
			
				$tile_list = 'All tiles are currently associated with a section.<br /><br />Please create new tiles or switch back to "All Tiles" view.<br /><br />Tiles may be reused in multiple sections';
			}
		}
	}
	echo $tile_list;
	die;
	
}


//create the nodes of the sortable tree in the section admin page
function gw_generate_section_sort($sid, $title, $detail) {
	
	$sortable_section = '
							<li id="list_'.$sid.'">
								<div class="gw_sortable_container">
									<div class="gw_sort_expand_control">
										<span class="disclose">
											<span></span>
										</span>
									<a href="'.admin_url().'admin.php?page=GroundWork-Sections&state=edit&sid='.$sid.'">edit</a>
									</div>
									<div class="gw_sort_text">
										<div class="gw_sort_title">
											'.$title.'
										</div>
										
										<div class="gw_sort_detail">
											'.$detail.'
										</div>
										<div class="gw_add_tiles"><a class="gw_add_resource" id="gw_add_'.$sid.'">Add Resource Tiles</a></div>
									</div>
								</div>';
							
	return $sortable_section;
	
}


//process the ajax call on a resort of section order
function store_sort() {
	if (!empty($_POST["list"]) && $_POST["update_sql"] = 'ok') {
		global $wpdb;
		parse_str($_POST['list'], $order);
		$index = 0;
		foreach ($order['list'] as $sid => $parent_id) {
			//insert new order into the DB
			$table_name = $wpdb->prefix . "gw_sections";			
			$wpdb->UPDATE($table_name, array('parent_sid' => "$parent_id", 'section_order' => "$index"), array('sid' => "$sid"));
			$index++;
		}
	
	}
	
}

function load_tile_sort() {
	if(!empty($_POST['section'])) {
	global $wpdb;	
	$section = $_POST['section'];
	//list all the existing tiles
	  $tile_list = '';
	  $table_gw_lookup = $wpdb->prefix . "gw_lookup";
	  $table_gw_tiles = $wpdb->prefix . "gw_tiles";
	  $tiles = $wpdb->get_results("SELECT * FROM $table_gw_tiles INNER JOIN $table_gw_lookup ON $table_gw_lookup.tid = $table_gw_tiles.tid WHERE $table_gw_lookup.sid = $section ORDER BY  $table_gw_lookup.resource_order");
	  if ($tiles) {
		  
		  foreach ($tiles as $tile) {
			  $tile_list .= '<li id="gw_tile_'.$tile->tid.'">'.$tile->title.'</li>';
		  }
	  
	  }
		
	}
	$table_name = $wpdb->prefix . "gw_sections";
	$section = $wpdb->get_row("SELECT title FROM $table_name WHERE sid = $section");
	
	$response = array(
   	 'tile_list'=>$tile_list,
   	 'section_heading'=>$section->title
);

	echo json_encode($response);
	die;
}

//update the database with new tiles in a section
function store_resource_sort() {
	if(!empty($_POST['ul'])) {
		if($_POST['ul'] == 'gw_assigned_tiles_sort') {
			$section = $_POST['section'];
			global $wpdb;
			$table_name = $wpdb->prefix . "gw_lookup";
			$wpdb->query( 
				$wpdb->prepare( 
					"
					 DELETE FROM $table_name
					 WHERE sid = %d", $section
					)
			);
			parse_str($_POST['list'], $order);
			
			foreach ($order['gw_tile'] as $tid) {
				//insert new order into the DB
				$table_name = $wpdb->prefix . "gw_lookup";			
				$wpdb->insert($table_name, array('sid' => "$section", 'tid' => "$tid"));
				
			}
		}
	}
die;
}

//create resource manager admin section
function gw_admin_resources() {
global $wpdb;
$state = 'show_form';
if ($_POST) {//update options
			if ($_POST['form'] == 'add_resource') { //add new tile
				echo '<div id="message" class="updated fade"><p>Your new tile is shown below. You can add this tile to your library in the GroundWork Section Manager</p></div>';
				$state = 'display';
				$title = $_POST['title'];
				$description = $_POST['description'];
				$embed_code = $_POST['embed_code'];
				$links = $_POST['links'];
				$table_name = $wpdb->prefix . "gw_tiles";			
				$wpdb->insert($table_name, array('title' => "$title", 'description' => "$description", 'embed_code' => "$embed_code", 'links' => "$links"));
				$embed_code =  gw_safe_iframe($embed_code);
				$tid = $wpdb->insert_id;
				$edit_override = 'true';
				
				
				
			} else if ($_POST['form'] == 'edit_resource') { //edit existing tile
				echo '<div id="message" class="updated fade"><p>Your changes are shown below.</p></div>';
				$state = 'display';
				$title = $_POST['title'];
				$description = $_POST['description'];
				$embed_code = $_POST['embed_code'];
				$links = $_POST['links'];
				$tid = $_POST['tid'];
				$table_name = $wpdb->prefix . "gw_tiles";			
				$wpdb->UPDATE($table_name, array('title' => "$title", 'description' => "$description", 'embed_code' => "$embed_code", 'links' => "$links"), array('tid' => "$tid"));
				$edit_override = 'true';
				$embed_code =  gw_safe_iframe(stripslashes($embed_code));
				$edit_override = 'true';
			}
			
	}
if ($_GET['tid']) { //display the tile the user clicked on from the list of tile
	
	if ($edit_override == 'true') { //tile was edited reset to dislay the tile...not the edit form
		$state = 'display';
	} else {
		$state = $_GET['state'];
		$tid = $_GET['tid'];
	}
	if ($state == 'delete') { //delete tile
		echo '<div id="message" class="updated fade"><p>Tile deleted.</p></div>';
			$table_name = $wpdb->prefix . "gw_tiles";
			$wpdb->query( 
				$wpdb->prepare( 
					"
					 DELETE FROM $table_name
					 WHERE tid = %d", $tid
					)
			);
			$table_name = $wpdb->prefix . "gw_lookup";
			$wpdb->query( 
				$wpdb->prepare( 
					"
					 DELETE FROM $table_name
					 WHERE tid = %d", $tid
					)
			);
		$state = 'show_form';
	} else { //show the new, edited, or clicked on tile
		if ($edit_override == 'true') {
			$state = 'display';
		} else {
			$state = $_GET['state'];
		}
	}
}

		 
$output = '
<h3>Create and Manage Resource Tiles</h3>
<div class="gw_tile_edit">';

if ($state == 'show_form') {//show the new tile screen
$output .= '
<form method="post" id="new_resource">
	<div class="gw_tile">
		
		<h2>Title: <input type="text" name="title" size="40"/></h2>
		
			<label for="description"><strong>Description</strong></label>
			<br />
			<textarea name="description" cols="80" rows="2"></textarea>
			<br />
			<br />
		
			<label for="embed_code"><strong>Video Embed Code</strong></label>
			<br />
			<textarea name="embed_code" cols="80" rows="3"></textarea>
			<br />
			Copy from video site (YouTube, Vimeo, SchoolTube, etc.) 
			<br />
			<em>You must embed video with a width of <strong>400px</strong> or less</em>	
			<br />
			<br />
			
			<label for="links"><strong>Additional Resource Links</strong></label>
			<br />
			<textarea name="links" cols="80" rows="5"></textarea>
			<br />
			Include links to documentation, how-to guides, external web sites, etc. 
			<br />
			List the links as <strong>Title, URL</strong> seperated by a comma.  Press return after each entry.<br />
			<em>Title, http://www.link.to.resource<br />
			Title, http://www.link.to.resource<br />
			Title, http://www.link.to.resource<br />...</em>
		
		
		<div class="gw_buttons"><input type="submit" name="new_resource_save" value="Add Resource" /></div>
		<div class="spacer" style="clear: both;"></div>
	</div>
	
<input name="form" type="hidden" value="add_resource" />
</form>

';
} else if ($state == 'display') { //show the tile

	$output .= gw_make_tile('none', $tid, true);

} else if ($state == 'edit') {//edit existing tile
	$table_name = $wpdb->prefix . "gw_tiles";			
	$tile_data = $wpdb->get_row("SELECT * FROM $table_name WHERE tid = $tid");
	$title = $tile_data->title; 
	$description = $tile_data->description; 
	$embed_code_actual = stripslashes($tile_data->embed_code); 
	$embed_code =  gw_safe_iframe($embed_code_actual);
	$links = $tile_data->links; 
	$output .= '
	<form method="post" id="new_resource">
		<div class="gw_tile">
			
			<h2>Title: <input type="text" name="title" size="40" value="'.$title.'"/></h2>
			
				<label for="description"><strong>Description</strong></label>
				<br />
				<textarea name="description" cols="80" rows="2">'.$description.'</textarea>
				<br />
				<br />
			
				<label for="embed_code"><strong>Video Embed Code</strong></label>
				<br />
				<textarea name="embed_code" cols="80" rows="3">'.$embed_code_actual.'</textarea>
				<br />
				Copy from video site (YouTube, Vimeo, SchoolTube, etc.) 
				<br />
				<em>You must embed video with a width of <strong>400px</strong> or less</em>	
				<br />
				<br />
				
				<label for="links"><strong>Additional Resource Links</strong></label>
				<br />
				<textarea name="links" cols="80" rows="5">'.$links.'</textarea>
				<br />
				Include links to documentation, how-to guides, external web sites, etc. 
				<br />
				List the links as <strong>Title, URL</strong> seperated by a comma. Press return after each entry.<br />
				<em>Title, http://www.link.to.resource<br />
				Title, http://www.link.to.resource<br />
				Title, http://www.link.to.resource<br />...</em>
			
			
			<div class="gw_buttons"><input type="submit" name="new_resource_save" value="Cancel" /><input type="submit" name="new_resource_save" value="Save" onclick="location.href=document.URL.split(\'?\')[0];"></div>
			<div class="spacer" style="clear: both;"></div>
		</div>
	<input name="tid" type="hidden" value="'.$tid.'" />
	<input name="form" type="hidden" value="edit_resource" />
	</form>
	
	';	
}
//close the resource tile container
$output .= '
</div>
<div class="gw_tile_list">
<strong>Resource Tiles and ID #\'s</strong><br />
<a class="gw_add_tile" href="'.admin_url().'admin.php?page=GroundWork-Resources">Add New Tile</a>
';


//list all the existing tiles
$table_name = $wpdb->prefix . "gw_tiles";
$tiles = $wpdb->get_results("SELECT * FROM $table_name ORDER BY tid DESC");
if ($tiles) {
	$output .= '<div id="gw_tile_list_ul"><ul>';
	foreach ($tiles as $tile) {
		$output .= '<li><a href="'.admin_url().'admin.php?page=GroundWork-Resources&state=display&tid='.$tile->tid.'" title="'.$tile->description.'">'.$tile->tid.' - '.$tile->title.'</a></li>';
	}
$output .= '</ul></div>';
}

$output .= '</div>
';
	echo $output;
	
}


//create resource tile
function gw_make_tile($sid, $tid, $edit) {
	
		//DB funtions
		global $wpdb;
		$table_name = $wpdb->prefix . "gw_tiles";			
		$tile_data = $wpdb->get_row("SELECT * FROM $table_name WHERE tid = $tid");
		$title = stripslashes($tile_data->title); 
		$description = stripslashes($tile_data->description); 
		$embed_code_actual = stripslashes($tile_data->embed_code); 
		$embed_code =  gw_safe_iframe($embed_code_actual);
		$links = $tile_data->links; 
		if (strlen($links) != 0) {
			$links_section = '<strong>Resource Links</strong>
				<ul>
					'.gw_convert_links($links).'
				</ul>';
		}
	$output = '
		<div class="gw_tile" id="section_'.$sid.'_tile_'.$tid.'">
			<h2>'.$title.'</h2>
			<div class="gw_tile_description">'.$description.'</div>
			<div class="spacer" style="clear: both;"></div>
			'.gw_has_video($embed_code).'
			<div class="gw_tile_links">
			'.$links_section.'
			</div>
			<div class="spacer" style="clear: both;"></div>
		</div>';
	if ($edit) {
		$output .= '
			<div class="gw_edit_tile_links"><a class="gw_edit_tile" href="'.admin_url().'admin.php?page=GroundWork-Resources&state=edit&tid='.$tid.'">Edit</a> | <a class="gw_delete_tile"href="'.admin_url().'admin.php?page=GroundWork-Resources&state=delete&tid='.$tid.'" onclick="return confirm(\'Are you sure you want to permenantly delete this resource tile?\');">Delete</a></div>';
	}
	
	return $output;

}


//change layout for video or not
function gw_has_video($embed_code) {
	if (strlen($embed_code) == 0) {
		return '<div class="gw_tile_no_video">
			'.$embed_code.'
		</div>';
	} else {
		return '<div class="gw_tile_video">
			'.$embed_code.'
		</div>';
	}
}

//ensure bad embed code doesn't break the layout
function gw_safe_iframe($embed_code) {
	if (strlen($embed_code) == 0) {
		 return '';
	} else if (!strncmp($embed_code, '<iframe ', 8)) {
			if ((substr($embed_code, -9) === '</iframe>')) {
				return $embed_code;
			} else {
				return '<font color="red">Your video embed code was not correct. Your code should look similar to:</font> <br /> ' .htmlspecialchars('<iframe width="400" height="225" src="http://www.youtube.com/embed/12345abcde" frameborder="0" allowfullscreen></iframe>');
			}
		} else {
			return '<font color="red">Your video embed code was not correct. Your code should look similar to: </font><br /> ' .htmlspecialchars('<iframe width="400" height="225" src="http://www.youtube.com/embed/12345abcde" frameborder="0" allowfullscreen></iframe>');
		}
}



function gw_convert_links($links) { //convert the comma list of links and titles to real html links
	$link = explode("\n", $links);
	reset($link);
	$link_list = '';
	foreach($link as $li) {
	 list($title, $url) = explode(",", $li);
	 	$link_list .= '<li><a href="'.$url.'" target="_new">'.$title.'</a></li>';
	}
	return $link_list;
}

//output Groundwork Ajax calls
function gw_groundwork() {
	add_filter( 'edit_post_link', '__return_false' );
	ob_start(); // begin output buffering
	$output ='';
	$output .= '<div id="groundwork">';	
	$output .= gw_display();
	$output .= '</div>';
	echo $output;
	
	$groundwork = ob_get_contents(); // end output buffering
    ob_end_clean(); // grab the buffer contents and empty the buffer
    return $groundwork;
	
   
}

//check if this is part of a restricted book
function gw_is_restricted() {
	global $wpdb;
	if ($_GET['book']) {//OK...we are not showing the first page
		$sid = $_GET['book'];
		$table_name = $wpdb->prefix . "gw_sections";
		$restricted = $wpdb->get_row("SELECT restricted FROM $table_name WHERE sid = $sid");
		if ($restricted->restricted == 1) {
			if (!is_user_logged_in()) { 
				auth_redirect(); 
			}
		}
	}
}


function gw_display() {
	//DB funtions
	global $wpdb;
	$output = '';
	$url = 'http';
	if ($_SERVER['HTTPS'] == 'on') {
		$url .= 's';
	}	
	$url .= '://';
	$url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$url = substr($url, 0, strpos($url, '?'));

	//check if we have any post requests
	if ($_GET['book']) {//OK...we are not showing the first page
		$sid = $_GET['book'];
		$output .= '<div id="gw_back"><a href="'.$url.'">Back to Library</a></div><div id="gw_side_navigation">';
		$table_name = $wpdb->prefix . "gw_sections";
		//build level two
				$chapters = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_sid = $sid ORDER BY section_order");
				if ($chapters) {
					$output .= '<div class="gw_accordion">'; 
					
					foreach ($chapters as $chapter) {//generate chapter navigation
						$output .= gw_generate_headings($chapter->sid, $chapter->title, $chapter->detail);
						$output .= '<div>';
						$output .= gw_generate_sections($chapter->sid);
						
						//build level three
						$sections = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_sid = $chapter->sid ORDER BY section_order");
						
						if ($sections) {
							$output .= '<div class="gw_accordion">';
							foreach ($sections as $section) { //generate section navigation
								$output .= gw_generate_headings($section->sid, $section->title, $section->detail);
								$output .= '<div>'.gw_generate_sections($section->sid).'</div>';
								
							}
							$output .= '</div>';
						}
						
						$output .= '</div>';
					}
						$output .= '</div>';
				}
				$output .= '</div>';
				
				//Resource Tile Area
				
				
				
				
				
				$output .= '<div id="gw_tile_stage">&nbsp;</div>';
							//<div id="gw_scroll_links"><div id="up">Up</div><div id="down">Down</div></div>';
					
			
				
				
		
	} else {
	
	$output .= '<ul id="gw_books">';
	$table_name = $wpdb->prefix . "gw_sections";
	$level1_nodes = $wpdb->get_results("SELECT * FROM $table_name WHERE parent_sid = 0 ORDER BY section_order");
	if ($level1_nodes) {
		foreach ($level1_nodes as $level1_node) {
			$output .= gw_make_book($level1_node->sid, $level1_node->title, $level1_node->detail, $level1_node->logo);				
		}
	}
	$output .= 		'</ul>';
				
	}
	
		return $output;
	
}

//output the resource tiles for the selected section
function gw_resource_output() {
	if ($_POST['sid']) {//OK...we are not showing the first page
	
		$output = '';
		//DB funtions
		global $wpdb;
		$sid = $_POST['sid'];		
		$table_name = $wpdb->prefix . "gw_lookup";
		$resources = $wpdb->get_results("SELECT tid FROM $table_name WHERE sid = $sid ORDER BY resource_order");
		
		if ($resources) {
			$output .= '<div id="gw_tile_container">';
			foreach ($resources as $resource) {
				$output .= gw_make_tile($sid, $resource->tid, false);
			}
			$output .= '<div id="gw_tile_spacer">&nbsp;</div></div>';
		}
	}
	echo $output;
	die();
}

//create the top level books
function gw_make_book($sid, $title, $detail, $image) {
	 $section = '<a href="?book='.$sid.'&book_title='.$title.'" class="gw_book_tile"><li class="gw_book">
						<div class="gw_thumb">
							'.wp_get_attachment_image( $image, 'GroundWork').'
						</div>
						<h2 >'.$title.'</h2>
						<span>'.$detail.'</span>
					</li></a>';
	
	return $section;
	
}

//create the chapters (accordian menu)
function gw_generate_headings($sid, $title, $detail) {
	return '<div><a class="gw_chapter_menu_link" href="#'.$sid.'" alt="'.$detail.'">'.$title.'</a></div>';
}

//create the sections (sub chapter links)
function gw_generate_sections($sid) {
	$current_section = $sid;
	$output = '';
	global $wpdb;
				
		$table_gw_lookup = $wpdb->prefix . "gw_lookup";
		$table_gw_tiles = $wpdb->prefix . "gw_tiles";
		$resources = $wpdb->get_results("SELECT $table_gw_tiles.tid, $table_gw_tiles.title, $table_gw_tiles.description FROM $table_gw_tiles INNER JOIN $table_gw_lookup ON $table_gw_lookup.tid = $table_gw_tiles.tid WHERE $table_gw_lookup.sid = $current_section ORDER BY  $table_gw_lookup.resource_order");
		echo $wpdb->last_error;
		echo $wp_query->request;
	
		if ($resources) {
			$output .= '<ul class="gw_menu_list">';
			foreach ($resources as $resource) {
				$output .= '<li class="gw_menu_tile_link"><a class="section_'.$sid.'_tile_'.$resource->tid.' "href="#section_'.$sid.'_tile_'.$resource->tid.'">'.$resource->title.'</a></li>';
			}
			$output .= '</ul>';
		}
		
	return $output;
}



?>