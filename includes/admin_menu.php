<?php

if (defined('EAV_REC_FLD') == false) {
	define("EAV_REC_FLD", 0);	// this shows the value is record/field information
}

if (defined('EAV_SQL') == false) {
	define("EAV_SQL", 2);
}

if (defined('EAV_IMPORT') == false) {
	define("EAV_IMPORT", 3);
}
if (defined('EAV_BUTTON') == false) {
	define("EAV_BUTTON", 4);
}


function eav_main_page(){
	
		ob_start(); // this allows me to use 1 echo at the end
		
        echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>';
        echo "<h2>Welcome to Brad's Entity Attribute Value Database</h2></div>";
		echo "<P>This welcome page is where I will place ongoing information when I update this plugin.
			Also this will (I hope) have enough documentation to get you started. So what is an Entity
			Attribute Value Database? Well the easiest way is to have you read about with the links below, 
			and if you have any other please let me know.";
		echo "<ul><li>https://blog.greglow.com/2018/02/12/sql-design-entity-attribute-value-tables-part-1/</li>";
		echo "<li>https://blog.greglow.com/2018/02/19/sql-design-entity-attribute-value-tables-part-2-pros-cons/</li>";
		echo "<li>https://wikipedia.org/wiki/Entity-attribute-value_model</li></ul>";
		echo "<P>Okay so inshort this plugin is meant to allow people to track items (people,cars,etc) without
		the need to create a database table for each thing.  All data is currently stored in 4 tables.";
		echo "<P>As things progress hopefully I will get parent/child, default values, incrementing values, and
		many other things going.  This is of course the first plugin I have released, so as always I am looking for 
		ways to do things better.";
		echo "<P>This first version is more of a proof of concept and to see what others think as I develop more.";
		echo "<P>Okay so first there is very small amount of error checking, and onto the help section:<P>";
		echo '<ol type="a"><li>Admin Pages
			<ol type="i">
				<li>Manage Records - This is where you will define the record names you want to keep things in</li>
				<li>Mange Attributes - This is where you will define your fields</li>
				<li>Manage Record Layout - This is where you will define what fields are in each of your records</li>
			</ol>
		</li><li>shortcodes
			<ol type="i">
				<li>[eav_tbl table="tablenamehere"] - allows you to browse a table and search for a value
				options are: allowupd="y" allowadd="y" flds="N,N,N,N" (where N is a field number, example 2,4,6,8
				and the fields currently must be order (lowest to highest) currently.
				</li><li>[eav_add table="tablenamehere"] - allows you to add records, shows all fields and is very basic
				</li><li>[eav_startadd table="tablename"] - sets up a form for data entry on a page.
				</li><li>[eav_field field="fielname"] - places an an input area for the field, optional is hidden="y"
				which will hide the field on the page.
				</li><li>[eav_endadd] - companion shortcode for eav_startadd 
				</li><li>[eav_subrec table="tablename"] - allows data entry for child record (if defined).  Note that this will show all fields on the child record.
				</li><li>[eav_apache table="tablename"] - allows you to load apache web log file
				</li>
		</li><li>demo apps
			<ol type="i">
				<li>Guest Registration - installs fields and tables and demo page showing how to use
				some of the shortcodes above
				</li><li>Apache Log - installs fields so that the shortcode [eav_apache] works to import data
		</li></ol>';
		
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;

}


add_action( 'admin_menu', 'eav_add_info_menu' );  
function eav_add_info_menu(){
	
    $page_title = 'Credits and Info';
	$menu_title = "EAV Settings";
	$capability = 'manage_options';
	$menu_slug  = 'eav_manage_settings';
	$function   = 'eav_manage_settings';
	$icon_url   = 'dashicons-media-code';
	$position   = 4;
	
    add_menu_page( $page_title,$menu_title,	$capability,$menu_slug,	$function,$icon_url,$position );
//	$submenu1_slug = 'eav_manage_tbl';
//    add_submenu_page( $menu_slug, 'Manage Records Title', 'Manage Records'
//		, 'manage_options',$submenu1_slug , $submenu1_slug);
//	$submenu2_slug = 'eav_manage_attrib';
//	add_submenu_page( $menu_slug, 'Manage Attributes title', 'Manage Attributes'
//		, 'manage_options', $submenu2_slug, $submenu2_slug);
//	$submenu2_slug = 'eav_manage_reclayout';
//	add_submenu_page( $menu_slug, 'Manage Record Layout', 'Manage Record Layout'
//		, 'manage_options', $submenu2_slug, $submenu2_slug);
		
// this option allows you to setup to use some sql to grab data for default values
//	$submenu2_slug = 'eav_manage_sql';
//	add_submenu_page( $menu_slug, 'SQL Default', 'SQL Default'
//		, 'manage_options', $submenu2_slug, $submenu2_slug);
		
	// button code
	$submenu2_slug = 'eav_manage_settings';
	add_submenu_page( $menu_slug, 'Manage Settings', 'Manage Settings'
		, 'manage_options', $submenu2_slug, $submenu2_slug);
		
// this is for importing of data
	$submenu2_slug = 'eav_manage_import';
	add_submenu_page( $menu_slug, 'Manage Import', 'Manage Import'
		, 'manage_options', $submenu2_slug, $submenu2_slug);
// future coding
	$submenu2_slug = 'eav_manage_apps';
	add_submenu_page( $menu_slug, 'Install Demo Apps', 'Install Demo Apps'
		, 'manage_options', $submenu2_slug, $submenu2_slug);
	


}

function eav_manage_sql()
{
	global $wpdb;

	ob_start(); // this allows me to use echo and then use sanitize_text_field() at the end
	
	$_POST = array_map( 'stripslashes_deep', $_POST ); // strips off wordpress escaping quotes


	echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>
        <h2>Manage SQL defaults</h2></div>';
	// initialize variables
	$v_entity="";
	$v_attrib ="";
	$v_text = "";
	$v_results = "";
	$update = false;

	//user selected a record
	if (isset($_POST['selrec'])) {
		$v_entity = sanitize_text_field($_POST['recname']);
	}
	//user selected a fild after selecting a record
	if (isset($_POST['selfld'])) {
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_attrib = sanitize_text_field($_POST['fldname']);
		// need to check if we have sql already
		$sql = sprintf("select val_char from " . $wpdb->base_prefix . "eav_entity " .
			"where entity_type=%d and entity=%d and entity_id=0 and entity_attrib=%d ",
				EAV_SQL, $v_entity, $v_attrib);
		$eav_sql = $wpdb->get_row($sql);
		if (isset($eav_sql->val_char)) {
				$v_text = $eav_sql->val_char;
				$update = true;
		}
	}
	//user is adding sql
	if (isset($_POST['addsql'])) {
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_attrib = sanitize_text_field($_POST['entity_attrib']);
		$v_text = sanitize_text_field($_POST['sqltext']);
		
		$prep = $wpdb->prepare (
			"INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity_type, entity, entity_id, entity_attrib, " . 
				" val_char, parent_entity, parent_entity_id) 
			values (%d, %d, 0, %d, %s, 0, 0) "
				,EAV_SQL
				, $v_entity
				, $v_attrib
				, $v_text . ''
				);
		$return = $wpdb->query($prep );
		if ($return == false) {
				echo "<P>Insert into eav_entity failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
		}		
		$wpdb->flush();
		$update=true;
	}
	//user is updating  sql
	if (isset($_POST['updatesql'])) {
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_attrib = sanitize_text_field($_POST['entity_attrib']);
		$v_text = sanitize_text_field($_POST['sqltext']);
			$prep = $wpdb->prepare (
			"update " . $wpdb->base_prefix . "eav_entity set val_char=%s where entity_type=%d and entity=%d " .
			" and entity_attrib=%d and entity_id=0 "
				, $v_text . ''
				, EAV_SQL
				, $v_entity
				, $v_attrib
			);
			$return = $wpdb->query($prep );	
		$wpdb->flush();
		$update=true;
	}
	//user is testing sql
	if (isset($_POST['testsql'])) {
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_attrib = sanitize_text_field($_POST['entity_attrib']);
		$v_text = sanitize_text_field($_POST['sqltext']);
		$sql = eav_handle_string($v_text);
		$testout = $wpdb->get_row($sql);
		$array = json_decode(json_encode($testout), true);
		$v_results = array_shift($array);
		$update=true;
	}

	echo '<form action="" method="post">';
	echo '<select name="recname" id="recname" >';
	echo '<option value=""></option>';
	$sql = "select entity,tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl ";
	$results = $wpdb->get_results($sql);
	foreach($results as $element) {
		if ($v_entity == $element->entity)
			echo '<option value="' . esc_html($element->entity) .'" selected>' . esc_html($element->tblname) . '</option>';
		else
			echo '<option value="' . esc_html($element->entity) .'">' . esc_html($element->tblname) . '</option>';
	}
	echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="selrec" value="Select Record">';
	echo '</form>';	
	
	if ($v_entity != "") {
		echo '<form action="" method="post">';
		echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($v_entity) . '">';
		echo '<select name="fldname" id="fldname" >';
		echo '<option value=""></option>';
		$sql = "select a.entity_attrib ,b.entity_name from " . $wpdb->base_prefix . "eav_layout a, " . $wpdb->base_prefix . "eav_attrib b " .
			" where a.entity_attrib=b.entity_attrib and a.entity=" . esc_html($v_entity) ;
		$results = $wpdb->get_results($sql);
		foreach($results as $element) {
			if ( $v_attrib == $element->entity_attrib) 
				echo '<option value="' . esc_html($element->entity_attrib) .'" selected >' . esc_html($element->entity_name) . '</option>';
			else
				echo '<option value="' . esc_html($element->entity_attrib) .'">' . esc_html($element->entity_name) . '</option>';
		}
		echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="selfld" value="Select Field">';
		echo '</form>';	
		echo '<P>' ;
		
		if (($v_entity != "") && ($v_attrib != "")) {

			echo '<form action="" method="post">';
			echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($v_entity) . '">';
			echo '<input type="hidden" id="entity_attrib" name="entity_attrib" value ="' . esc_html($v_attrib) . '">';
			echo '<table>';
			echo '<tr><td><label for="l_sqltext">SQL Text:</label>';
			echo '</td><td>';
			echo '<textarea id="sqltext" name="sqltext" rows="8" cols="70" >' . esc_html($v_text) . '</textarea>';
			echo '</td><td><B>Test Result:&nbsp;&nbsp;</B>' . esc_html($v_results) . '</td>';
			echo '</tr></table>';
			echo '<P>';
			if($update == true)
				echo '<input type="submit" name="updatesql" value="Update SQL">';
			else
				echo '<input type="submit" name="addsql" value="Add SQL">';
			echo '<input type="submit" name="testsql" value="Test SQL">
				<br> </form>';
		}
	}
	
	// show current sql's 
	//
	if ($v_entity != "") {
			$sql = "select b.entity_name, b.entity_desc, a.val_char from " . 
				$wpdb->base_prefix . "eav_entity a, " .
				$wpdb->base_prefix . "eav_attrib b " .
				" where a.entity_attrib=b.entity_attrib and a.entity_type =" . EAV_SQL . " and entity=" . $v_entity ;
		echo '<table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
			<th style="border: 1px solid black"; onclick="eav_sortTable(0)">Field Name</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(1)">Description</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(2)">SQL</th>
			</tr>
		';
		$results = $wpdb->get_results($sql);
		$row_count = 1; 

		foreach($results as $element) {
			echo '<tr style="border: 1px solid black; vertical-align: top; padding: 0px;">';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_name) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_desc) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->val_char) . '</td>';
			echo '</tr>';
            $row_count = $row_count + 1;
		}
		echo "</table>";
	}
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;	

}

function install_guest_reg(){
	global $wpdb;
	

	// this setups the fields
	$fld_array = array('unit_number','arrival_date','depart_date','guest_authorize'
		,'enteredby','entereddate','lastupdate','lastupdateby' ,'guest_name'
		,'guest_driver_lic','guest_auto_plate','guest_make_model','guest_email');
		
	$fld_desc_array = array('Unit Number','Arrival Date','Departure Date','Guest Authorization'
		,'Entered By','Entered Date','Last Update','Last Update By','Guest Name'
		,'Guest Driver Lic#','Guest Auto Plate#','Guest Auto Make/Model','Guest Email');
	$fld_default_array = array('%sql','Arrival Date','Departure Date','Guest Authorization'
		,'#user','#today','#now','#user',''
		,'','','','');
	$fld_input_array = array('text','date','date','text'
		,'text','date','datetime-local','text','text'
		,'text','text','text','text');
	$fld_show = array('y','y','y','y'
		,'n','n','n','n','y'
		,'y','y','y','y');

	$fld_count = count($fld_array);
	for ($i = 0; $i < $fld_count; $i++) {
		$sql_ck = "select entity_name from " . $wpdb->base_prefix . "eav_attrib " .
			" where entity_name = '" . $fld_array[$i] . "'";
		$eav_fldexist = $wpdb->get_row($sql_ck);
		if ( !isset($eav_fldexist->entity_name)) {
			$sql_max="select max(entity_attrib) as maxnu from " . $wpdb->base_prefix . "eav_attrib";
			$max = $wpdb->get_row($sql_max);
			if (isset($max->maxnu))
				$max_val = $max->maxnu + 1;
			else
				$max_val = 1;
			if ($fld_array[$i] == "unit_number")
				$unit_no_val = $max_val;
			$sql_ins= "insert into " . $wpdb->base_prefix . "eav_attrib " .
				" (entity_attrib,entity_name,entity_format,entity_desc,entity_default,input_format,show_on_browse) " .
				" values " .
				"( " . $max_val . ", '" . $fld_array[$i] . "' , '%20.20s' , '" . $fld_desc_array[$i] . "' , " .
					" '" . $fld_default_array[$i] . "', '" . $fld_input_array[$i] . "','" . $fld_show[$i] . "')";
			$return = $wpdb->get_var($sql_ins);
			$wpdb->flush();
		}
	}
	
	// next up is the records
	$rec_name = "coa_guest_reg";
	$rec_desc = "Condo Guest Registration";
	$rec_ck = "select tblname from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $rec_name . "'";
	$result = $wpdb->get_row($rec_ck);
	if (!isset($result->tblname)) {
		$sql_max="select max(entity) as maxnu from " . $wpdb->base_prefix . "eav_tbl";
		$max = $wpdb->get_row($sql_max);
		if (isset($max->maxnu))
			$max_val = $max->maxnu + 1;
		else
			$max_val = 1;
		$sql_ins= "insert into " . $wpdb->base_prefix . "eav_tbl " .
			"(entity,tblname,tbldescr,parent_entity) " .
			" values " .
			"( " . $max_val . " , '" . $rec_name . "' , '" . $rec_desc . "' , 0 ) ";
		$return = $wpdb->get_var($sql_ins);
		$wpdb->flush();

		// next up record layout
		$layout_array = array('unit_number','arrival_date','depart_date','guest_authorize'
			,'enteredby','entereddate','lastupdate','lastupdateby' );
		$lay_count = count($layout_array);
		for ($i = 0; $i < $lay_count; $i++) {
			$sql_fldno = "select entity_attrib from " . $wpdb->base_prefix . "eav_attrib " .
				" where entity_name = '" . $layout_array[$i] . "'";
			$res_fldno = $wpdb->get_row($sql_fldno);
			$sql_lay = "insert into " . $wpdb->base_prefix . "eav_layout " .
				"(entity, entity_attrib, entity_order) " .
				" values " .
				"( " . $max_val . "," . $res_fldno->entity_attrib . "," . ($i + 1) . ")";
			$return = $wpdb->get_var($sql_lay);
			$wpdb->flush();
		}
		//next we need to insert the %sql, note that since unit# is the first field
		$sql_str = "select user_value from wp_prflxtrflds_user_field_data where field_id = 1 and user_id = #userid";
		$sql_ins = "insert into " . $wpdb->base_prefix . "eav_entity " .
			"(entity_type, entity, entity_id, entity_attrib, val_char, parent_entity_id, parent_entity) " .
			" values " .
			"( " . EAV_SQL . ", " . $max_val .
			", 0 , " . $unit_no_val . " , '" . $sql_str . "' ,0,0 ) ";			

		$return = $wpdb->get_var($sql_ins);
		$wpdb->flush();
		
		// next up is the child record
		$sql_base="select entity from  " . $wpdb->base_prefix . "eav_tbl " .
			" where tblname = 'coa_guest_reg'";
		$base = $wpdb->get_row($sql_base);
			
		$sql_max="select max(entity) as maxnu from " . $wpdb->base_prefix . "eav_tbl";
		$max = $wpdb->get_row($sql_max);
		if (isset($max->maxnu))
			$max_val = $max->maxnu + 1;
		else
			$max_val = 1;
		$sql_ins= "insert into " . $wpdb->base_prefix . "eav_tbl " .
			"(entity,tblname,tbldescr,parent_entity) " .
			" values " .
			"( " . $max_val . " , 'coa_guest_auto' , 'Condo Guest Auto Registration' , " . $base->entity . " ) ";
		$return = $wpdb->get_var($sql_ins);
		$wpdb->flush();
			
		// next up record layout
		$layout_array = array('guest_name','guest_driver_lic','guest_auto_plate'
			,'guest_make_model','guest_email' );
		$lay_count = count($layout_array);
		for ($i = 0; $i < $lay_count; $i++) {
			$sql_fldno = "select entity_attrib from " . $wpdb->base_prefix . "eav_attrib " .
				" where entity_name = '" . $layout_array[$i] . "'";
			$res_fldno = $wpdb->get_row($sql_fldno);
			$sql_lay = "insert into " . $wpdb->base_prefix . "eav_layout " .
				"(entity, entity_attrib, entity_order) " .
				" values " .
				"( " . $max_val . "," . $res_fldno->entity_attrib . "," . $i . ")";
			$return = $wpdb->get_var($sql_lay);
			$wpdb->flush();
		}
		// next hook up the page
		if ( null === $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = 'coa-guest-registration'"
		, 'ARRAY_A' ) ) {
    
		$current_user =  wp_get_current_user();
		
		$page = array(
		'post_title'  => __( 'Guest Registration' ),
		'post_status' => 'publish',
		'post_author' => $current_user->ID,
		'post_type'   => 'page',
		'post_name'	=> 'coa-guest-registration',
		'post_content'  => preg_replace( "/\r|\n/", "", '
		[eav_startadd table=coa_guest_reg] [eav_field field=lastupdate hidden=y]</p>

<table style="width: 100%; border-collapse: collapse; border-style: none;">
<tbody>
<tr>
<td style="width: 50%; border-style: none;"><strong>Unit#</strong>[eav_field field=unit_number]</td>
<td style="width: 50%; border-style: none; text-align: right;"><strong>Entered by</strong>[eav_field field=enteredby]</td>
</tr>
<tr>
<td style="width: 50%; border-style: none;"></td>
<td style="width: 50%; border-style: none; text-align: right;">Entered Date [eav_field field=entereddate]</td>
</tr>
</tbody>
</table>
<p style="text-align: left;"><strong>NOTE: Unit Owners are responsible for their guests’ compliance with all condominium Rules and Regulations and in particular:</strong></p>
<strong>GUESTS IN YOUR ABSENCE:</strong>
<ul>
 	<li>Prior to occupancy; Guests are required to present a LETTER OF AUTHORIZATION signed by the owner of the unit upon arrival in the building.</li>
 	<li>No one will be allowed entry to the building without one. Guests must also sign a Registration Card upon arrival before entering the unit.</li>
 	<li>Unit KEYS must be provided by you to your guests.</li>
 	<li>Please notify Management in writing in advance of Guests arrival….providing dates of arrival and departure names, and number in party.</li>
 	<li>Please advise your guests we have a NO PETS policy.</li>
 	<li>You are responsible for your guests, so please provide them with the Rules &amp; Regulations and agree to abide by them. Only persons identified on this authorization will occupy the unit.</li>
</ul>
Vehicles – Motor-Homes, campers, pickup trucks with camper bodies, trailers (camper, boat or horse), boats, jet skis or similar water craft, or motorcycles are not permitted on Sand Key Club property.

<input type="checkbox" id="guest_authorize" name="guest_authorize" value="yes" required="" /> By checking the box I/we hereby authorize the following guest(s): (Four Maximum)
<P>
Arrival Date:[eav_field field=arrival_date]                                Departure Date:[eav_field field=depart_date]
<P>
<strong>Guest Auto Registration</strong>

[eav_subrec table=<span>coa_guest_auto]</span>

[eav_endadd]
',)
		);
    
		// insert the post into the database
		wp_insert_post( $page );	
		}	
	}
}
function install_web_stat(){
	global $wpdb;
	

	// this setups the fields
	$fld_array = array('remotehost','localhost', 'user', 'datetime'
		,'method','request','protocol','status','bytes', 'referer', 'user_agent' );
		
	$fld_desc_array = array('Remote Host','Local Host','User', 'Date/Time'
		,'Method','Request','Protocol','Status','Bytes', 'Referer', 'User Agent');
	
	$fld_default_array = array('','','',''
		,'','','','','','','');
		
	$fld_input_array = array('text','text','text','text','text'
		,'text','text','text','text','text','text');
	$fld_show = array('y','y','y','y'
		,'y','y','y','y','y','y','y');
		
		
	$fld_import = array('([^ ]+)','([^ ]+)','([^ ]+)','(\\\[[^\\\]]+\\\])'
		, '"(.*)' , '(.*)' ,'(.*)"' , '([0-9\\\-]+)' , '([0-9\\\-]+)' , '"(.*)"' , '"(.*)"' );


	$fld_count = count($fld_array);
	for ($i = 0; $i < $fld_count; $i++) {
		$sql_ck = "select entity_name from " . $wpdb->base_prefix . "eav_attrib " .
			" where entity_name = '" . $fld_array[$i] . "'";
		$eav_fldexist = $wpdb->get_row($sql_ck);
		if ( !isset($eav_fldexist->entity_name)) {
			$sql_max="select max(entity_attrib) as maxnu from " . $wpdb->base_prefix . "eav_attrib";
			$max = $wpdb->get_row($sql_max);
			if (isset($max->maxnu))
				$max_val = $max->maxnu + 1;
			else
				$max_val = 1;
			if ($fld_array[$i] == "remotehost")
				$start_no_val = $max_val;
			$sql_ins= "insert into " . $wpdb->base_prefix . "eav_attrib " .
				" (entity_attrib,entity_name,entity_format,entity_desc,entity_default,input_format,show_on_browse) " .
				" values " .
				"( " . $max_val . ", '" . $fld_array[$i] . "' , '%20.20s' , '" . $fld_desc_array[$i] . "' , " .
					" '" . $fld_default_array[$i] . "', '" . $fld_input_array[$i] . "','" . $fld_show[$i] . "')";
					
			$return = $wpdb->get_var($sql_ins);
			$wpdb->flush();
		}
	}
	
	// next up is the records
	$rec_name = "apache_stat";
	$rec_desc = "Apache Log Stats";
	$rec_ck = "select tblname from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $rec_name . "'";
	$result = $wpdb->get_row($rec_ck);
	if (!isset($result->tblname)) {
		$sql_max="select max(entity) as maxnu from " . $wpdb->base_prefix . "eav_tbl";
		$max = $wpdb->get_row($sql_max);
		if (isset($max->maxnu))
			$max_val = $max->maxnu + 1;
		else
			$max_val = 1;
		$sql_ins= "insert into " . $wpdb->base_prefix . "eav_tbl " .
			"(entity,tblname,tbldescr,parent_entity) " .
			" values " .
			"( " . $max_val . " , '" . $rec_name . "' , '" . $rec_desc . "' , 0 ) ";
		$return = $wpdb->get_var($sql_ins);
		$wpdb->flush();

		// next up record layout
		$lay_count = count($fld_array);
		for ($i = 0; $i < $lay_count; $i++) {
			$sql_fldno = "select entity_attrib from " . $wpdb->base_prefix . "eav_attrib " .
				" where entity_name = '" . $fld_array[$i] . "'";
			$res_fldno = $wpdb->get_row($sql_fldno);
			$sql_lay = "insert into " . $wpdb->base_prefix . "eav_layout " .
				"(entity, entity_attrib, entity_order) " .
				" values " .
				"( " . $max_val . "," . $res_fldno->entity_attrib . "," . ($i + 1) . ")";
			$return = $wpdb->get_var($sql_lay);
			$wpdb->flush();
		}
			
		// next up is the import format

		$lay_count = count($fld_import);
		for ($i = 0; $i < $lay_count; $i++) {
			$sql_fldno = "select entity_attrib from " . $wpdb->base_prefix . "eav_layout " .
				" where entity = " .  $max_val . " and entity_order=" . ($i+1);
			$res_fldno = $wpdb->get_row($sql_fldno);
			
			$sql_insert =sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity_type, entity, entity_id, entity_attrib, " . 
				" val_char, parent_entity, parent_entity_id) values (%d, %d, %d, %d, '%s', 0, 0)",
					EAV_IMPORT, $max_val, $i+1, $res_fldno->entity_attrib,  $fld_import[$i]);
			$return = $wpdb->query($sql_insert  );
			$wpdb->flush();
		}
	}
}
function eav_manage_apps() {
	
	ob_start(); // this allows me to use echo and then use sanitize_text_field() at the end
	

	if (isset($_POST['guest_reg'])) {
		// install the demo guest registration system
		install_guest_reg();
	}
	if (isset($_POST['apache_log'])) {
		// install the demo guest registration system
		install_web_stat();
	}
	
	echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Install Demo Apps</h2></div><P>';
	echo '<form action="" method="post">
		<label>
		<input type="checkbox" id="guest_reg" name="guest_reg" value="guest_reg">
		Install demo guest registration (v1.0)
		<br>
		<input type="checkbox" id="apache_log" name="apache_log" value="apache_log">
		Install Apache Log (v1.0)
		<P><input type="submit" value="Install Demo Apps" name="install_demo_app">
		 </form>';
	
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;
}

function eav_manage_tbl(){
	global $wpdb;
	
	ob_start(); // this allows me to use echo and then use sanitize_text_field() at the end
	
	eav_header();

$post_url = sanitize_text_field($_SERVER['REQUEST_URI']);	

	if (isset($_POST['addrecord'])){ //If it is the first time, it does nothing   
			//if we are in a post then we can do an sql insert and then pull it down below
			$sql_max="select max(entity) + 1 as max_val from " . $wpdb->base_prefix . "eav_tbl";
			$eav_maxtbl = $wpdb->get_row($sql_max);
			if (isset($eav_maxtbl->max_val))
				$max_val = $eav_maxtbl->max_val;
			else
				$max_val = 1;
				
			$eav_index = $max_val;
			$eav_tblname = strtolower(sanitize_text_field($_POST['recordname']));
			$eav_descr = sanitize_text_field($_POST['recorddesc']);
			$sql_insert = sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_tbl (entity, tblname, tbldescr, parent_entity) values (%s, '%s', '%s', %s)",$eav_index ,$eav_tblname,$eav_descr, 0);
			
			$return = $wpdb->query($sql_insert  );
			if ($return == false) {
					echo "<P>Insert into eav_tbl failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
			}
			$wpdb->flush();
			
			echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Manage Records</h2></div>';
	
			echo '<form action="" method="post">
					<label for="seachlabel">Add Record:</label>
					<input type="text" id="recordname" name="recordname" size="30" ><br>
					<label for="seachlabel">Description:</label>
					<input type="text" id="recorddesc" name="recorddesc" size="64" ><br>
					<input type="submit" value="addrecord" name="addrecord">
					<br> </form>';

	} else 	if (isset($_POST['updrecord'])){ //If it is the first time, it does nothing   
			//if we are in a post then we can do an sql insert and then pull it down below
				$eav_tablid = sanitize_text_field($_POST['entity']);
				$eav_tblname = strtolower(sanitize_text_field($_POST['recordname']));
				$eav_descr = sanitize_text_field($_POST['recorddesc']);
				if (isset($_POST['parentrecname']))
					$eav_parent = trim(sanitize_text_field($_POST['parentrecname'])) + 0;
				else
					$eav_parent = 0;
				$sql = sprintf("update " . $wpdb->base_prefix . "eav_tbl set tblname='%s', tbldescr='%s',parent_entity=%d  where entity=%s", $eav_tblname, $eav_descr, $eav_parent, $eav_tablid);

				$return = $wpdb->query($sql );
				$wpdb->flush();
				
				echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Manage Records</h2></div>';
	
				echo '<form action="" method="post">
					<label for="seachlabel">Add Record:</label>
					<input type="text" id="recordname" name="recordname" size="30" ><br>
					<label for="seachlabel">Description:</label>
					<input type="text" id="recorddesc" name="recorddesc" size="64" ><br>
					<input type="submit" value="addrecord" name="addrecord">
					<br> </form>';
	}else if(isset($_GET['entity'])) {
		echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Edit Record</h2></div>';
		
		$tablid =  sanitize_text_field($_GET['entity']);
		$sql = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where entity = " . $tablid;
		$eav_tblinfo = $wpdb->get_row($sql);
		echo "<br>allow editing of record<br>";
		echo '<form action="" method="post"><label for="seachlabel">Record:</label>';
		echo '<input type="text" id="recordname" name="recordname" size="30" value="' . esc_html($eav_tblinfo->tblname) . '"><br>';
		echo '<label for="seachlabel">Description:</label>';
		echo '<input type="text" id="recorddesc" name="recorddesc" size="64" value="' . esc_html($eav_tblinfo->tbldescr) . '"><br>';
		echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($tablid) . '">';
		echo '<label for="seachlabel">Parent Record:</label>	<select name="parentrecname" id="parentrecname" >';
		$sql = "select entity,tblname, tbldescr, parent_entity from " . $wpdb->base_prefix . "eav_tbl ";
		$results = $wpdb->get_results($sql);
		echo '<option value="0"></option>';
		foreach($results as $element) {
			if ($eav_tblinfo->tblname != sanitize_text_field($element->tblname) )
				echo '<option value="' . esc_html($element->entity) .'">' . esc_html($element->tblname) . '</option>';
		}
		echo '</select><br>';
		
		
		echo '<input type="submit" value="updrecord" name="updrecord" >';
		echo '<br> </form>';
	}else {		
		echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div><h2>Manage Records</h2></div>';
	
		echo '<form action="" method="post">
		<label for="seachlabel">Add Record:</label>
		<input type="text" id="recordname" name="recordname" size="30" ><br>
		<label for="seachlabel">Description:</label>
		<input type="text" id="recorddesc" name="recorddesc" size="64" ><br>
		<input type="submit" value="addrecord" name="addrecord">
		 <br> </form>';
	}	 
		$sql = "select a.entity, a.tblname, a.tbldescr, a.parent_entity , b.tblname as b_tblname from " . $wpdb->base_prefix . "eav_tbl a LEFT OUTER JOIN " . $wpdb->base_prefix . "eav_tbl b ON a.parent_entity = b.entity";
		echo '<table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
			<th style="width:5%; border: 1px solid black"; onclick="eav_sortTable(0); cursor: wait">Table ID</th>
			<th style="width:20%; border: 1px solid black"; onclick="eav_sortTable(1); cursor: progress">Table Name</th>
			<th style="width:55%; border: 1px solid black"; onclick="eav_sortTable(2); cursor: pointer">Description</th>
			<th style="width:20%; border: 1px solid black"; onclick="eav_sortTable(3); cursor: pointer">Parent Record</th>
			</tr>
		';
		$results = $wpdb->get_results($sql);
		$row_count = 1; 
		foreach($results as $element) {
			echo '<tr style="border: 1px solid black; vertical-align: top; padding: 0px;">';
            echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px; width:100px">';
			//note that the functional name is now in the URL below
			echo '<a href="' . esc_html($post_url) . '&entity=' . esc_html($element->entity) . '">';
			echo esc_html($element->entity) . '</a></td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->tblname) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->tbldescr) . '</td>';
			if ( strlen($element->b_tblname) > 0 )
				echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->b_tblname) . '</td>';
			else
				echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;"></td>';
			echo '</tr>';
            $row_count = $row_count + 1;
	}
		
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;
}
function eav_manage_attrib(){
	global $wpdb;
	

	ob_start(); // this allows me to use 1 echo at the end
	
	eav_header();
	
	$post_url = sanitize_text_field($_SERVER['REQUEST_URI']);

	if (isset($_GET['entity_attribute']) && (!isset($_POST['updfld'])) ) {
		// this means we need to edit this field
		$eav_attrib1 = sanitize_text_field($_GET['entity_attribute']);
		echo "<H1>Edit Field " . esc_html($eav_attrib1)  . "</H1><br>";
		$sql_edit = "select entity_attrib, entity_name, entity_format, entity_desc, entity_default, input_format, show_on_browse " .
			" from " . $wpdb->base_prefix . "eav_attrib " .
			" where entity_attrib = " . $eav_attrib1;
		$eav_fldupdate = $wpdb->get_row($sql_edit);
		echo '<form action="' . esc_html($post_url) . '" method="post">
		<label for="seachlabel">Field:</label>
		<input type="text" id="entity_name" name="entity_name" value = "' . esc_html($eav_fldupdate->entity_name) . '" size="30" ><br>
		<label for="seachlabel">Format(default %20.20s):</label>
		<input type="text" id="entity_format" name="entity_format" size="30" value="' . esc_html($eav_fldupdate->entity_format) . '"><br>
		<label for="seachlabel">Description:</label>
		<input type="text" id="entity_desc" name="entity_desc" size="64" value = "' . esc_html($eav_fldupdate->entity_desc) . '"  ><br>
		<label for="seachlabel">Default:</label>
		<input type="text" id="entity_default" name="entity_default" size="64" value = "' . esc_html($eav_fldupdate->entity_default) . '"  ><br>
		<input type="hidden" id="updfld" name="updfld" value ="updfld">
		<label for="seachlabel">Input Format:</label> ';
		// handle default if there
		if (isset($eav_fldupdate->input_format) && (strlen(trim($eav_fldupdate->input_format)) > 0))
			$def_input = $eav_fldupdate->input_format;
		else
			$def_input = "";
		

		$input_f_array = array("text", "date", "datetime-local");
		echo '<select name="input_format" id="input_format" >';
		for($i = 0; $i < count($input_f_array); $i++) { // for each format type we support
			if($def_input == $input_f_array[$i]) {
				echo '<option value="' . esc_html($input_f_array[$i]) .'" selected >' .  esc_html($input_f_array[$i]) . '</option>';
			}else {
				echo '<option value="' . esc_html($input_f_array[$i]) .'" >' .  esc_html($input_f_array[$i]) . '</option>';
			}
		}
		echo '</select><br>';
		echo '<label for="seachlabel">Show on Browse/Update:</label> ';
		echo '<select name="show_on_browse" id="show_on_browse">';
		if ($eav_fldupdate->show_on_browse == 'n') 
			echo '<option value="y" >Yes</option><option value="n" selected>No</option>';
		else
			echo '<option value="y" selected>Yes</option><option value="n">No</option>';
		echo '</select><br>';
		echo '<input type="hidden" id="entity_attrib" name="entity_attrib" value="' . esc_html($eav_attrib1) . '">
		<P>
		<input type="submit" id="update" name="eav_submit" value="Update Field">
		<input type="submit" id="update" name="eav_submit" value="Delete Field">
		 <br> </form>';
		echo '<P><PRE>
		<B>Current formats supported are:</B>
		%s = string format, to specify a size use %20s or %20.20s - %20.20s will eventually trunk output
		<br>
		<B>Current defaults are:</B>
		#today = current date
		#user = current user
		#userid = current user id (wordpress id)
		#now = current date/time
		%sql = uses SQL to find the default, based off sql setup
		</PRE>';
	} else {
		if (isset($_POST['updfld'])) {
			echo "<PRE>in update</PRE>";
			if (sanitize_text_field($_POST['eav_submit']) == 'Update Field') {
				$u_entity_attrib = sanitize_text_field($_POST['entity_attrib']);
				$u_entity_name =  str_replace(' ', '_', strtolower(sanitize_text_field($_POST['entity_name']))); 
				$u_entity_format = strtolower(sanitize_mime_type($_POST['entity_format']));
				$u_entity_default = strtolower(sanitize_text_field($_POST['entity_default']));
				$u_entity_input = strtolower(sanitize_text_field($_POST['input_format']));
				$u_show = strtolower(sanitize_text_field($_POST['show_on_browse']));
				// sanitize_mime_type strips off the % (percent) sometimes so we just double check that
				if ($u_entity_format[1] != "%")
					$u_entity_format = "%" . $u_entity_format;
				$u_entity_desc = sanitize_text_field($_POST['entity_desc']);
				$usql = sprintf("update " . $wpdb->base_prefix . "eav_attrib set entity_name='%s', entity_format='%s', entity_desc='%s', entity_default='%s', input_format='%s',show_on_browse='%s' where  entity_attrib = %s" 
						,$u_entity_name	,$u_entity_format,$u_entity_desc, $u_entity_default, $u_entity_input, $u_show, $u_entity_attrib);
				
				//$prep = $wpdb->prepare ($usql);
				$return = $wpdb->query($usql );
				if ($return == false) {
					echo "<P>Update eav_attrib failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
				}
				$wpdb->flush();
			} else if (sanitize_text_field($_POST['eav_submit']) == 'Delete Field') {
				$table= $wpdb->base_prefix . 'eav_attrib';
				$id = sanitize_text_field($_POST['entity_attrib']);
				$wpdb->delete( $table, array( 'entity_attrib' => $id ) );
				// maybe do an error check here?
			} else {
				echo "<br>unknown POST<br>";
			}
		} else if (isset($_POST['addfld'])){ //If it is the first time, it does nothing   
			//if we are in a post then we can do an sql insert and then pull it down below
			$sql_max="select max(entity_attrib) as maxnu from " . $wpdb->base_prefix . "eav_attrib";
			$max = $wpdb->get_row($sql_max);
			if (isset($max->maxnu))
				$max_val = $max->maxnu + 1;
			else
				$max_val = 1;

			$eav_index = $max_val;
			$tmpval = strtolower(sanitize_text_field($_POST['entity_name']));
			$eav_fldname =  str_replace(' ', '_', $tmpval);
			$u_entity_format = strtolower(sanitize_mime_type($_POST['entity_format']));
			// sanitize_mime_type strips off the % (percent) sometimes so we just double check that
			if ($u_entity_format[1] != "%")
					$u_entity_format = "%" . $u_entity_format;
			$eav_descr = sanitize_text_field($_POST['entity_desc']);
			//* need to check for uniqueness
			$unique = "select count(*) as entity_attrib from " . $wpdb->base_prefix . "eav_attrib where entity_name = '" . $eav_fldname . "'" ;
			$eav_unique = $wpdb->get_row($unique);
			if ( isset($eav_unique->entity_attrib) && ($eav_unique->entity_attrib == 0 )) {
				$usql = sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_attrib (entity_attrib, entity_name, entity_format, entity_desc, entity_default) values (%s, '%s', '%s', '%s', '')"
					,$eav_index	,$eav_fldname,$u_entity_format,$eav_descr );
					
				$return = $wpdb->query($usql );
				if ($return == false) {
						echo "<P>Insert into eav_attrib failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
				}
				$wpdb->flush();
			} else {
					echo '<script language="javascript">';
					echo 'alert("This field is already defined.")';
					echo '</script>';
			}
		}
        echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>
        <h2>Manage Attributes</h2></div>';

		echo '<form action="' . esc_html($post_url) . '" method="post">
		<label for="seachlabel">Add Field:</label>
		<input type="text" id="entity_name" name="entity_name" size="30" ><br>
		<label for="seachlabel">Format(current not used, default %20.20s):</label>
		<input type="text" id="entity_format" name="entity_format" size="30" value="%20.20s"><br>
		<label for="seachlabel">Description:</label>
		<input type="text" id="entity_desc" name="entity_desc" size="64" ><br>
		<input type="hidden" name="addfld" id="addfld" value="addfld">
		<P>
		<input type="submit" id="addfld" value="Add Field">
		 <br> </form>';

		$sql = "select entity_attrib, entity_name, entity_format, entity_desc, entity_default, input_format, show_on_browse  from " . $wpdb->base_prefix . "eav_attrib ";
		echo '<table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
			<th style="border: 1px solid black"; onclick="eav_sortTable(0)">Field ID</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(1)">Field Name</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(2)">Format</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(3)">Description</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(4)">Default</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(5)">Input Format</th>
			<th style="width: 60px; border: 1px solid black"; onclick="eav_sortTable(6)">Show on Browse</th>
			</tr>
		';
		$results = $wpdb->get_results($sql);
		$row_count = 1; 

		foreach($results as $element) {
			echo '<tr style="border: 1px solid black; vertical-align: top; padding: 0px;">';
            echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px; width:100px">';
			echo '<a href="' . esc_html($post_url) . '&entity_attribute=' . esc_html($element->entity_attrib) . '">';
			echo esc_html($element->entity_attrib) . '</a></td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_name) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_format) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_desc) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_default) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->input_format) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->show_on_browse) . '</td>';
			echo '</tr>';
            $row_count = $row_count + 1;
		}
		echo "</table>";
	}
	
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;

}
function eav_manage_reclayout(){
	global $wpdb, $wp;
	
	ob_start(); // this allows me to use echo and then use sanitize_text_field() at the end
	
	$post_url = sanitize_text_field($_SERVER['REQUEST_URI']);
	
	// this allows us to reset the url, so if we are editing a record/field, if they select
	// a new record, the url gets cleared out.
//	if (isset($_GET['page'])) {
//		$eav_pagename = '/wp-admin/admin.php?page=' . sanitize_text_field($_GET['page']);
//	} else;
	
	
	$eav_tblname = array();
	echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>
        <h2>Record Layout</h2></div>';
	
	
	if (isset($_POST['recname']) && (strlen(sanitize_text_field($_POST['recname'])) > 0)){
		echo '<form action="' . esc_html($post_url) . '" method="post">';
		echo '	<label for="selectrecord">Select Record:</label>';
	
		$sql1 = "select tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where entity =" . sanitize_text_field($_POST['recname']);
		$eav_tblname = $wpdb->get_row($sql1);
		
		echo '	<select name="recname" id="recname" >';
		$sql = "select entity,tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl ";
		$results = $wpdb->get_results($sql);
		foreach($results as $element) {
			if ($eav_tblname->tblname == $element->tblname )
				echo '<option value="' . esc_html($element->entity) .'" selected >' .  esc_html($element->tblname) . '</option>';
			else
				echo '<option value="' . esc_html($element->entity) .'">' . esc_html($element->tblname) . '</option>';
		}
	} else if ((isset($_POST['updrecfld']) || isset($_POST['delrecfld'])) && (sanitize_text_field($_POST['neworder']))  )    {
		//This is for editing of record
		$eav_recname = sanitize_text_field($_POST['recno']);
		$eav_attrib = sanitize_text_field($_POST['fldattrib']);
		$eav_newno = sanitize_text_field($_POST['neworder']);

		if (isset($_POST['updrecfld'])) {
			// reorder record order
			$fndmax="select count(*) as count from " . $wpdb->base_prefix . "eav_layout where entity=" . $eav_recname;
			$resmax= $wpdb->get_row($fndmax);
			$maxno = $resmax->count;
			
			if ($eav_newno > $maxno)
				$eav_newno = $maxno;
			
			$sqlupd = "update " . $wpdb->base_prefix . "eav_layout set entity_order = " . $eav_newno . " where entity =" . $eav_recname . " and entity_attrib= " . $eav_attrib ;
			$return = $wpdb->query($sqlupd );
			
			$loopsql = "select entity, entity_attrib, entity_order from " . $wpdb->base_prefix . "eav_layout where entity=" . $eav_recname . " order by entity_order desc";
			$results = $wpdb->get_results($loopsql);

			foreach($results as $element) {
				//skip this field
				if ( $element->entity_attrib != $eav_attrib ) {
					$sqlupd = "update " . $wpdb->base_prefix . "eav_layout set entity_order = " . $maxno . " where entity =" . $eav_recname . " and entity_attrib= " . $element->entity_attrib ;
					//$prep = $wpdb->prepare($sqlupd);
					$return = $wpdb->query($sqlupd );
				}
				$maxno = $maxno - 1;
			}
		} else if (isset($_POST['delrecfld'])) {
			// remove field from record layout
			$table='eav_layout';
			$sql = "delete from " . $wpdb->base_prefix . "eav_layout where entity = " . $eav_recname . " and entity_attrib = " . $eav_attrib;
			//$prep = $wpdb->prepare($sql);
			$return = $wpdb->query($sql );
			if ($return == false) {
						echo "<P>Delete eav_layout failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
			}
		}
		$sql1 = "select tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where entity =" . $eav_recname ;
		$eav_tblname = $wpdb->get_row($sql1);
		
		echo '<form action="' . esc_html($post_url) . '" method="post">';
		echo '	<label for="selectrecord">Select Record:</label>';
		echo '	<select name="recname" id="recname" >';
		$sql = "select entity,tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl ";
		$results = $wpdb->get_results($sql);
		foreach($results as $element) {
			if ($eav_tblname->tblname == $element->tblname )
				echo '<option value="' . esc_html($element->entity) .'" selected >' .  esc_html($element->tblname) . '</option>';
			else
				echo '<option value="' . esc_html($element->entity) .'">' . esc_html($element->tblname) . '</option>';
		}
	} else {
		echo '<form action="' . esc_html($post_url) . '" method="post">';
		echo '	<label for="selectrecord">Select Record:</label>';
	
		// If not a post with table name default with blank
		// or check if the person is drilling down
		if (isset($_GET['entity']) && isset($_GET['entity_order'])) {
			$sql1 = "select tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where entity =" . sanitize_text_field($_GET['entity']);
			$eav_tblname = $wpdb->get_row($sql1);
	
			echo '	<select name="recname" id="recname" >';
			$sql = "select entity,tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl ";
			$results = $wpdb->get_results($sql);
			foreach($results as $element) {
				if ($eav_tblname->tblname == $element->tblname) 
					echo '<option value="' . esc_html($element->entity) .'" selected >' .  esc_html($element->tblname) . '</option>';
				else
					echo '<option value="' . esc_html($element->entity) .'">' . esc_html($element->tblname) . '</option>';
			}
		} 
		else {
			echo '	<select name="recname" id="recname" >';
			echo '<option value=""></option>';
			$sql = "select entity,tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl ";
			$results = $wpdb->get_results($sql);
			foreach($results as $element) {
				echo '<option value="' . esc_html($element->entity) .'">' . esc_html($element->tblname) . '</option>';
			}
		}
	}
	echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Select Record"></form>';

// okay this finds if we are adding a field via submit/post
	if (isset($_POST['fldname']) && (strlen(sanitize_text_field($_POST['fldname'])) > 0)){
		$sql1="select max(entity_order) as maxnu from " . $wpdb->base_prefix . "eav_layout where entity = " . sanitize_text_field($_POST['recname']);
		$max = $wpdb->get_row($sql1);
		if (isset($max->maxnu))
			$max_val = $max->maxnu + 1;
		else
			$max_val = 1;

		$sql="select entity_attrib from " . $wpdb->base_prefix . "eav_attrib where entity_name = '" . sanitize_text_field($_POST['fldname']) . "'";
		$result = $wpdb->get_row($sql);
		$prep = $wpdb->prepare (
			"INSERT INTO " . $wpdb->base_prefix . "eav_layout (entity, entity_attrib, entity_order) 
			values (%s, %s, %s)"
				,sanitize_text_field($_POST['recname'])
				,sanitize_text_field($result->entity_attrib)  . ''
				,sanitize_text_field($max_val)  . ''
		);
		$return = $wpdb->query($prep );
		if ($return == false) {
			echo "<P>Insert into eav_layout failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
		}
		$wpdb->flush();
	}
// okay this finds when we are sbmit/post the record name selection
	if (isset($_POST['recname']) && (strlen(sanitize_text_field($_POST['recname'])) > 0)){
		echo '<P>Add Field to Record: ';
		echo '<form action="" method="post">';
		echo '<input type="hidden" id="recname" name="recname" value="' .  esc_html(sanitize_text_field($_POST['recname'])) . '">' ;
		$sql = "select entity_name from " . $wpdb->base_prefix . "eav_attrib "; //need criteria to not show fields already in record
		$results = $wpdb->get_results($sql);
	
		echo '<select name="fldname" id="fldname">';
		foreach($results as $element) {
			echo '<option value="' . esc_html($element->entity_name) .'">' . esc_html($element->entity_name) . '</option>';
		}
		echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Add Field"></form>';
	}
	
// this shows the record layout on the post
	if ((isset($_POST['recname']) && (strlen(sanitize_text_field($_POST['recname'])) > 0))
			|| ((isset($_POST['updrecfld']) || isset($_POST['delrecfld'])) && (sanitize_text_field($_POST['neworder']))) ) {
		echo '<P><table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
			<th style="border: 1px solid black; ">Order #</th>
			<th style="border: 1px solid black; ">Field Name</th>
			<th style="border: 1px solid black; ">Format</th>
			<th style="border: 1px solid black; ">Description</th>
			</tr>
		';
		
		// only set if it was in the post, otherwise we should have it
		if (isset($_POST['recname']))
			$eav_recname = sanitize_text_field($_POST['recname']);
		$sql="select a.entity, a.entity_attrib, a.entity_order, " .
			"b.entity_name, b.entity_format, b.entity_desc " .
			"from " . $wpdb->base_prefix . "eav_layout a, " . $wpdb->base_prefix . "eav_attrib b where a.entity=" . $eav_recname .
			" and a.entity_attrib = b.entity_attrib order by a.entity_order";
		$results = $wpdb->get_results($sql);
		foreach($results as $element) {
			echo '<tr><td style="border: 1px solid black; vertical-align: top; padding: 0px;">';
			echo '<a href="' . esc_html($post_url) . '&entity=' . esc_html($eav_recname) . '&entity_order=' . esc_html($element->entity_order);
			echo '&entity_attrib=' . esc_html($element->entity_attrib) . '">';
			echo esc_html($element->entity_order)  . '</a></td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_name) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_format) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_desc) . '</td>';
			echo '</tr>';
		}	
		echo '</table>';
	}
// okay here is where we edit record layout info on field
	if (isset($_GET['entity']) && isset($_GET['entity_order']) && isset($_GET['entity_attrib']) && (!isset($_POST['updrecfld'])) ) {
		$eav_ent = sanitize_text_field($_GET['entity']);
		$eav_att = sanitize_text_field($_GET['entity_attrib']);
		
		echo '<P><table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
			<th style="border: 1px solid black; ">Order #</th>
			<th style="border: 1px solid black; ">Field Name</th>
			<th style="border: 1px solid black; ">Format</th>
			<th style="border: 1px solid black; ">Description</th>
			</tr>
		';

		$sql="select a.entity, a.entity_attrib, a.entity_order, " .
			"b.entity_name, b.entity_format, b.entity_desc " .
			"from " . $wpdb->base_prefix . "eav_layout a, " . $wpdb->base_prefix . "eav_attrib b where a.entity=" . $eav_ent .
			" and a.entity_attrib = b.entity_attrib order by a.entity_order";
		$results = $wpdb->get_results($sql);
		foreach($results as $element) {
			echo '<tr><td>';
			echo esc_html($element->entity_order)  . '</td>';
			echo '<td>' . esc_html($element->entity_name) . '</td>';
			echo '<td>' . esc_html($element->entity_format) . '</td>';
			echo '<td>' . esc_html($element->entity_desc) . '</td>';
			echo '</tr>';
			// save fieldname
			if ( $element->entity_attrib == $eav_att ) {
				$eav_desc = $element->entity_desc;
				$eav_name = $element->entity_name;
			}
		}	
		echo '</table>';
		
		//echo "<P>Record Layout Edit on Field<B> " . esc_html($eav_name) . " - " . esc_html($eav_desc) . "</B> - in progress<P> ";
		echo "<P>Record Layout Edit on Field<B> " .  esc_html($eav_desc) . "</B> - in progress<P> ";

		$sql = "select a.entity, a.entity_attrib, a.entity_order,b.entity_name, b.entity_format, b.entity_desc
			from " . $wpdb->base_prefix . "eav_layout a, " . $wpdb->base_prefix . "eav_attrib b where a.entity=" . $eav_ent .
			" and b.entity_attrib=" . $eav_att . 
			" and a.entity_attrib = b.entity_attrib 
			order by a.entity_order
			";
		$eav_result = $wpdb->get_row($sql);
		echo '<form action="' . esc_html($post_url) . '" method="post">';
		echo '<input type="hidden" id="recno" name="recno" value="' .  esc_html($eav_ent) . '">' ;
		echo '<input type="hidden" id="fldattrib" name="fldattrib" value="' .  esc_html($eav_att) . '">' ;

		echo '<label for="labneworder">New Order of field:</label>';
		echo '<input type="number" min="1" id="neworder" name="neworder" value=' . esc_html($eav_result->entity_order) . ' ><br>';
		echo '<P><input type="submit" id="delrecfld" name="delrecfld" value="Delete Field from Record">';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<input type="submit" id="updrecfld" name="updrecfld" value="Update Record Field"></form>';
		echo '</form>';
	}
	
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;
}
function eav_manage_import()
{
	global $wpdb, $wp;
	
	ob_start(); // this allows me to use echo and then use sanitize_text_field() at the end
	eav_header(); // this allows for sort of the table
	
	echo '<div class="wrap"><div id="icon-options-general" class="icon32"><br></div>
        <h2>Manage Import</h2></div>';
	
	// initialize variables
	$v_entity="";
	$v_attrib ="";
	$v_text = "";
	$v_results = "";
	$update = false;

	//user selected a record
	if (isset($_POST['selrec'])) {
		$v_entity = sanitize_text_field($_POST['recname']);
	}
	//user selected a fild after selecting a record
	if (isset($_POST['selfld'])) {
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_attrib = sanitize_text_field($_POST['fldname']);
		// need to check if we have sql already
		$sql = sprintf("select val_char, entity_id from " . $wpdb->base_prefix . "eav_entity " .
			"where entity_type=%d and entity=%d and entity_attrib=%d ",
				EAV_IMPORT, $v_entity, $v_attrib);
		$eav_sql = $wpdb->get_row($sql);
		if (isset($eav_sql->val_char)) {
				$v_order = $eav_sql->entity_id;
				$v_text = $eav_sql->val_char;
				$update = true;
		} else {
				$v_order = "";
				$v_text = "";
		}
	}
	//user is adding sql
	if (isset($_POST['addformat'])) {
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_order = sanitize_text_field($_POST['order_num']);
		$v_attrib = sanitize_text_field($_POST['entity_attrib']);
		$v_text = sanitize_text_field($_POST['entity_format']);
			$sql_insert =sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity_type, entity, entity_id, entity_attrib, " . 
				" val_char, parent_entity, parent_entity_id) values (%d, %d, %d, %d, '%s', 0, 0)",
					EAV_IMPORT, $v_entity, $v_order, $v_attrib,  $v_text);
			$return = $wpdb->query($sql_insert  );
			if ($return == false) {
				echo "<P>Insert into eav_entity failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
			}		
		$wpdb->flush();
		$update=true;
	}
	//user is updating  sql
	if (isset($_POST['updatesql'])) {
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_attrib = sanitize_text_field($_POST['entity_attrib']);
		$v_order = sanitize_text_field($_POST['order_num']);
		$v_text = stripslashes(sanitize_text_field($_POST['entity_format']));
			$prep = $wpdb->prepare (
			"update " . $wpdb->base_prefix . "eav_entity set val_char=%s, entity_id=%d where entity_type=%d and entity=%d " .
			" and entity_attrib=%d  "
				, $v_text . ''
				, $v_order
				, EAV_IMPORT
				, $v_entity
				, $v_attrib
				
			);
			$return = $wpdb->query($prep );	
		$wpdb->flush();
		$update=true;
	}

	echo '<form action="" method="post">';
	if ($v_entity == "") 
		echo '<select name="recname" id="recname" >';
	else
		echo '<select disabled name="recname" id="recname" >';
	echo '<option value=""></option>';
	$sql = "select entity,tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl ";
	$results = $wpdb->get_results($sql);
	$nobut = 0;
	foreach($results as $element) {
		if ($v_entity == $element->entity) {
			echo '<option value="' . esc_html($element->entity) .'" selected>' . esc_html($element->tblname) . '</option>';
			$nobut = 1;
		}
		else {
			echo '<option value="' . esc_html($element->entity) .'">' . esc_html($element->tblname) . '</option>';
		}
	}
	if ($nobut == 0) {
		echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="selrec" value="Select Record">';
	} else {
		echo '</select>';
	}

	echo '</form>';	
	
	if ($v_entity != "") {
		echo '<form action="" method="post">';
		echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($v_entity) . '">';
		echo '<select name="fldname" id="fldname" >';
		echo '<option value=""></option>';
		$sql = "select a.entity_attrib ,b.entity_name from " . $wpdb->base_prefix . "eav_layout a, " . $wpdb->base_prefix . "eav_attrib b " .
			" where a.entity_attrib=b.entity_attrib and a.entity=" . esc_html($v_entity) ;
		$results = $wpdb->get_results($sql);
		foreach($results as $element) {
			if ( $v_attrib == $element->entity_attrib) 
				echo '<option value="' . esc_html($element->entity_attrib) .'" selected >' . esc_html($element->entity_name) . '</option>';
			else
				echo '<option value="' . esc_html($element->entity_attrib) .'">' . esc_html($element->entity_name) . '</option>';
		}
		echo '</select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="selfld" value="Select Field">';
		echo '</form>';	
		echo '<P>' ;
		
		if (($v_entity != "") && ($v_attrib != "")) {
			if ($v_order == "")
				$v_f1 = "";	
			else
				$v_f1 = $v_order;
			
			if ($v_text == "")
				$v_f2 = "([^ ]+)";
			else
				$v_f2 = $v_text;
				
			
			echo '<form action="" method="post">';
			echo '<label for="seachlabel">Order:</label>
				<input type="text" id="order_num" name="order_num" size="30" value="' . $v_f1 . '"><br>
				<label for="seachlabel">Format(preg_match format 1 word):</label>
				<input type="text" id="entity_format" name="entity_format" size="30" value="' . $v_f2 . '"><br>
				<input type="hidden" name="addfromat" id="addfromat" value="addfromat">
				';
			echo '<input type="hidden" id="entity" name="entity" value ="' . esc_html($v_entity) . '">';
			echo '<input type="hidden" id="entity_attrib" name="entity_attrib" value ="' . esc_html($v_attrib) . '">';
			echo '<P>';
			if($update == true)
				echo '<input type="submit" name="updatesql" value="Update Format">';
			else
				echo '<input type="submit" name="addformat" value="Add Format">';
			echo '<br> </form>';
		}
		$sql = "select a.entity_id, b.entity_name, a.val_char from " . $wpdb->base_prefix . "eav_entity a, " .
			$wpdb->base_prefix . "eav_attrib b where " .
			"a.entity_type = " . EAV_IMPORT . " and a.entity_attrib=b.entity_attrib and " .
			"a.entity = " . $v_entity ;
		echo '<table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
		echo '<tr >
			<th style="border: 1px solid black"; onclick="eav_sortTable(0)">Field Order</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(1)">Field Name</th>
			<th style="border: 1px solid black"; onclick="eav_sortTable(2)">Format</th>
			</tr>
		';
		$results = $wpdb->get_results($sql);
		$row_count = 1; 

		foreach($results as $element) {
			echo '<tr style="border: 1px solid black; vertical-align: top; padding: 0px;">';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_id) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->entity_name) . '</td>';
			echo '<td style="border: 1px solid black; vertical-align: top; padding: 0px;">' . esc_html($element->val_char) . '</td>';
			echo '</tr>';
            $row_count = $row_count + 1;
		}
	}
	$content = ob_get_contents();
	ob_end_clean();
	echo $content;
}
function eav_manage_settings(){
	//Admin page html callback
//Print out html for admin page

  // check user capabilities
  //if ( ! current_user_can( 'manage_options' ) ) {
  //  return;
  //}

  //Get the active tab from the $_GET param
  $default_tab = null;
  $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
  
  ?>
  <!-- Our admin page content should all be inside .wrap -->
  <div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="?page=eav_manage_settings" class="nav-tab <?php if($tab===null):?>nav-tab-active<?php endif; ?>">Info</a>
      <a href="?page=eav_manage_settings&tab=records" class="nav-tab <?php if($tab==='records'):?>nav-tab-active<?php endif; ?>">Manage Records</a>
      <a href="?page=eav_manage_settings&tab=attribute" class="nav-tab <?php if($tab==='attribute'):?>nav-tab-active<?php endif; ?>">Manage Attributes/Fields</a>
	  <a href="?page=eav_manage_settings&tab=layout" class="nav-tab <?php if($tab==='layout'):?>nav-tab-active<?php endif; ?>">Manage Record Layout</a>
	  <a href="?page=eav_manage_settings&tab=sql" class="nav-tab <?php if($tab==='sql'):?>nav-tab-active<?php endif; ?>">Manage Default via SQL</a>
    </nav>

    <div class="tab-content">
    <?php switch($tab) :
      case 'records':
        echo 'records'; //Put your HTML here
		eav_manage_tbl();
        break;
      case 'attribute':
		eav_manage_attrib();
        break;
	  case 'sql':
        echo 'sql';
		eav_manage_sql();
        break;
	  case 'layout':
        echo 'layout';
		eav_manage_reclayout();
        break;
      default:
		eav_main_page();
        break;
    endswitch; ?>
    </div>
  </div>
  <?php
}

?>