<?php
/**
* Plugin Name: Brad's Entity Attribute Value Database
* Plugin URI: http://mobilebsmith.hopto.org
* Description: Brad's Entity Attribute Value Database
* Version: 2.13
* Author: Bradley Smith
* Author URI: http://mobilebsmith.hopto.org
**/

//entity_type defines - don't change
define("EAV_REC_FLD", 0);	// this shows the value is record/field information
define("EAV_SQL", 2);		// this shows the value is sql for default field handling
define("EAV_IMPORT", 3);	// this shows the value is format for preg_match(), 
							// entity = table id we will insert into, entity_id = field order
							// entity_attrib = attrib value(fieldname)
define("EAV_BUTTON", 4);	// this is for buttons and the php code behind it.
 
register_activation_hook( __FILE__, 'eav_import_init' );
function eav_import_init(){
		
	global $wpdb;
	global $wp;
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	// setup table
	// manager_role is the role_id value that allows update.  Prompts against
	// $wpdb->base_prefix . "prflxtrflds_roles_id"
	//entity			int,   // this is the table reference
	//entity_id		int,   // this is the row number for each row inserted
	//entity_attrib	int,	// this is the field# 
	//val_char		varchar(128), //this is the actual value
	
	$sql2 = "CREATE TABLE " . $wpdb->base_prefix . "eav_entity (
		entity_type		int,
		entity			int,   
		entity_id		int, 
		entity_attrib	int,	
		val_char		text,
		parent_entity	int,
		parent_entity_id	int,
	PRIMARY KEY(entity_type	,entity, entity_id, entity_attrib),
	KEY index_val (val_char(500), entity),
	KEY index_pval (val_char(500), parent_entity)
	)";
	dbDelta( $sql2 );
	
	$sql2 = "CREATE TABLE " . $wpdb->base_prefix . "eav_attrib (
		entity_attrib	int,
		entity_name		varchar(64),
		entity_format	varchar(16),
		entity_desc		varchar(256),
		entity_default	varchar(256),
		input_format	varchar(32),
		show_on_browse	varchar(1),
	PRIMARY KEY (entity_attrib, entity_name )
	)";
	dbDelta( $sql2 );
	
	$sql2 = "CREATE TABLE " . $wpdb->base_prefix . "eav_tbl (
		entity		int,
		tblname		varchar(32),
		tbldescr	varchar(256),
		parent_entity	int,
	PRIMARY KEY (entity, tblname )
	)";
	dbDelta( $sql2 );
	
	$sql2 = "CREATE TABLE " . $wpdb->base_prefix . "eav_layout (
		entity			int,
		entity_attrib	int,
		entity_order	int,
	PRIMARY KEY (entity, entity_attrib )
	)";
	dbDelta( $sql2 );
	

}

require_once( plugin_dir_path( __FILE__ ) . 'includes/admin_menu.php');

require_once( plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php');


// expands #xxxx values in string
function eav_handle_string($sql_string) {

	// #userid must come before #user, because #user is contained in #userid
	$input_f_array = array( "#userid", "#user", "#today", "#now");
	$newstring = $sql_string;
	for($i = 0; $i < count($input_f_array); $i++) {
		$u_contain = stripos  ($newstring, $input_f_array[$i], 0);
		if ($u_contain !== false ) {
			$middle = "'" . eav_handle_defaults($input_f_array[$i], 0, 0) . "'";
			$v_string = str_ireplace($input_f_array[$i], $middle, $newstring);
			$newstring = $v_string;
		}
	}
	return $newstring;
	
}
function eav_handle_sql ($v_entity, $v_attrib)
{
	global $wpdb;
	global $wp;
	
	$sql = sprintf("select val_char from " . $wpdb->base_prefix . "eav_entity " .
			"where entity_type=%d and entity=%d and entity_id=0 and entity_attrib=%d ",
				EAV_SQL, $v_entity, $v_attrib);
	$eav_sql = $wpdb->get_row($sql);
	if (isset($eav_sql->val_char)) {
		$v_text = $eav_sql->val_char;	
		$sql = eav_handle_string($eav_sql->val_char);
		$testout = $wpdb->get_row($sql);
		//$datat = array_shift($testout);
		$array = json_decode(json_encode($testout), true);
		$single_val = array_shift($array);
		return $single_val;
	}
	return "";
	
	
}
function eav_handle_defaults($value, $v_entity, $v_attrib) {
		global $wpdb;
		global $wp;
		
		$current_user =  wp_get_current_user();
		$return = "";

		switch($value) {
			case "#user";
				$return = $current_user->user_login;
				break;
			case "#userid";
				$return = $current_user->ID;
				break;
			case "#today";
				$return = date("Y-m-d");
				break;
			case "#now";  //2000-01-01T00:00:00
				//date_default_timezone_set('America/New_York');
				$return = current_time ("Y-m-d\TH:i:s", false);
				break;
			case "%sql";
				if(($v_entity != 0) && ($v_attrib != 0)) 
					$return = eav_handle_sql($v_entity, $v_attrib);
				else
					$return = "";
				break;
		}
		
		return $return;		
}

function eav_header() {
echo '
<style>
th  { cursor: pointer; }
</style>
<script>
function eav_sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("myTable");
  switching = true;
  //Set the sorting direction to ascending:
  dir = "asc"; 
  /*Make a loop that will continue until
  no switching has been done:*/
  while (switching) {
    //start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /*Loop through all table rows (except the
    first, which contains table headers):*/
    for (i = 1; i < (rows.length - 1); i++) {
      //start by saying there should be no switching:
      shouldSwitch = false;
      /*Get the two elements you want to compare,
      one from current row and one from the next:*/
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /*check if the two rows should switch place,
      based on the direction, asc or desc:*/
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch= true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          //if so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /*If a switch has been marked, make the switch
      and mark that a switch has been done:*/
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      //Each time a switch is done, increase this count by 1:
      switchcount ++;      
    } else {
      /*If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again.*/
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
</script>
';
}

?>
