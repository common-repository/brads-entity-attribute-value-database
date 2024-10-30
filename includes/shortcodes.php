<?php
//
// shortcodes for add
//eav_startadd - this goes at the top and sets up the start of the form and handles the post
//	and insert.
//eav_field - this short codes places an entry for the field
//eav_endadd - this ends the form and places a submit button

if (defined('EAV_IMPORT') == false) {
	define("EAV_IMPORT", 3);
}

if (!function_exists('get_save_entity')) { 
 function get_save_entity($t_entity = '') {
	static $gs_entity;
	
	if (!empty($t_entity)) {
		$gs_entity = $t_entity;
	}
	return $gs_entity;
 }
}
function eav_field($atts = [], $content = null) {
	global $wpdb;
	
	if (ob_get_level() == 1)
		ob_start(); // this allows me to use echo instead of using concat all strings

	$tablename = get_save_entity();
	$tblsql = "select entity from " . $wpdb->base_prefix . "eav_tbl where tblname='" . $tablename . "'";
	$res1 = $wpdb->get_row($tblsql);
	$v_res1 = $res1->entity;

	$fieldname = sanitize_text_field($atts['field']);
	$sql = "select entity_attrib, entity_name, entity_format, entity_desc, entity_default, input_format from " . 
		$wpdb->base_prefix . "eav_attrib where entity_name = '" . $fieldname . "'";

	$result_tbl = $wpdb->get_row($sql);
	// handle default
	if (isset($result_tbl->entity_default)) 
		$default = eav_handle_defaults($result_tbl->entity_default, $v_res1, $result_tbl->entity_attrib);
	else
		$default = "";
	//handle format length
	if (isset($result_tbl->entity_format)) 
		$int = strlen(sprintf($result_tbl->entity_format, ""));
	else
		$int = 20; //default length
		
	// handle input type
	if (isset($atts['hidden'])) {
		if (sanitize_text_field($atts['hidden']) == "y") 
			$typeval="hidden";
		else {
			if (isset($result_tbl->input_format)) 
				$typeval = $result_tbl->input_format;
			else
				$typeval = "text";
		}
	}else {
		if (isset($result_tbl->input_format)) 
			$typeval = $result_tbl->input_format;
		else
			$typeval = "text";
	}

	echo '&nbsp;<input type="' . $typeval . '" size="' . $int . '" name="' .  esc_html($fieldname) . '" id="' .  esc_html($fieldname) . '" value="' . $default . '" >';
	
	if (ob_get_level() == 2) {
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else
		return "";
}


function eav_startadd( $atts = [], $content = null) {
	global $wpdb;

// This gets the table id from the post name
	$tablename = sanitize_text_field($atts['table']);
	$fnd_sql = "select entity from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $tablename . "'";
	$res_val = $wpdb->get_row($fnd_sql);
	if (isset($res_val->entity)) 
		$v_entity = $res_val->entity;
	else
		die("tblname: " . $tablename . " not defined.");
	
	

	if (ob_get_level() == 1)
		ob_start(); // this allows me to use echo instead of using concat all strings


	if (isset($_POST['eav_startadd']) ){
		$primarytbl = sanitize_text_field($_POST['tablename']);

		$v_entity = $result_tbl1->entity;
		// find new row number
		$maxid = "select max(entity_id) as maxid from " . $wpdb->base_prefix . "eav_entity where entity = " . $v_entity . " and entity_type = 0";
		$result_tbl1 =$wpdb->get_row($maxid);	
		if (isset($result_tbl1->maxid))
			$v_entity_id = $result_tbl1->maxid + 1;
		else
			$v_entity_id = 1;	
				
		$all_fields = "select entity_attrib from " . $wpdb->base_prefix . "eav_layout where entity = " . $v_entity ;
		$result_fld = $wpdb->get_results($all_fields);
		foreach($result_fld as $element) {
			// for each field in the layout first get name so we can compare to form
			$v_entity_attrib = $element->entity_attrib;
			$fieldid = "select entity_name from " . $wpdb->base_prefix . "eav_attrib where entity_attrib = " . $v_entity_attrib ;
			$result_fld = $wpdb->get_row($fieldid);
			if ( isset($_POST[$result_fld->entity_name])) {			
				// okay the field is in the post so we need to santize and insert results.
				$eav_val_char = sanitize_text_field($_POST[$result_fld->entity_name]);
				$sql = sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity, entity_id, entity_attrib, val_char, parent_entity, parent_entity_id , entity_type ) values (%s, %s, %s, '%s', 0, 0, 0)"
						, $v_entity , $v_entity_id , $v_entity_attrib , $eav_val_char );
				$return = $wpdb->query($sql );
				if ($return != 1) {
					echo "<P>Insert into eav_entity for parent record failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
				}
				$wpdb->flush();
			}
		}
		//
		// okay let us see if we have a subrecord - currently only supports 1 subrecord on form.
		if (isset($_POST['subtablename'])) {
			$subtable = sanitize_text_field($_POST['subtablename']);
			// okay so we will look for each field, also we need to collect the data in the same order
			// b/c we might have multiple rows here
			
			//okay get the table id		
			$subtblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $subtable . "'";
			$result_tbl1 =$wpdb->get_row($subtblid);
			$subv_entity = $result_tbl1->entity;
			
			$another_loop = 1;
			$loop_cnt = 0;	// this is the index for the array, which will be incremented
			
			$all_fields = "select entity_attrib from " . $wpdb->base_prefix . "eav_layout where entity = " . $subv_entity ;
			$result_fld = $wpdb->get_results($all_fields);
			foreach($result_fld as $element) {
				$subv_entity_attrib = $element->entity_attrib;
				$fieldid = "select entity_name from " . $wpdb->base_prefix . "eav_attrib where entity_attrib = " . $subv_entity_attrib ;
				$result_fld = $wpdb->get_row($fieldid);
				if ( isset($_POST[$result_fld->entity_name])) {	 // the post has this field, see how big the array is
					$fld_array = $_POST[$result_fld->entity_name];
					$fld_array_cnt = count($_POST[$result_fld->entity_name]);
					if ($fld_array_cnt > $loop_cnt) 
						$loop_cnt = $fld_array_cnt;
				}
			}
			

						
						
			// so at this point loop_cnt has how many loops we need to make
			for($i = 0; $i < $loop_cnt; $i++) {
				$maxid = "select max(entity_id) as maxid from " . $wpdb->base_prefix . "eav_entity where entity = " . $subv_entity . " and entity_type = 0";
				$result_tbl1 =$wpdb->get_row($maxid);	
				if (isset($result_tbl1->maxid))
					$subv_entity_id = $result_tbl1->maxid + 1;
				else
					$subv_entity_id = 1;	
				$all_fields = "select entity_attrib from " . $wpdb->base_prefix . "eav_layout where entity = " . $subv_entity ;
				$result_fld = $wpdb->get_results($all_fields);
				foreach($result_fld as $element) {
					$subv_entity_attrib = $element->entity_attrib;
					$fieldid = "select entity_name from " . $wpdb->base_prefix . "eav_attrib where entity_attrib = " . $subv_entity_attrib ;
					$result_fld = $wpdb->get_row($fieldid);
					if ( isset($_POST[$result_fld->entity_name])) {	 // the post has this field, see how big the array is
						$fld_array = $_POST[$result_fld->entity_name];
						$fld_val = $fld_array[$i];

						$sql = sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity, entity_id, entity_attrib, val_char, parent_entity, parent_entity_id, entity_type ) values (%s, %s, %s, '%s', %s, %s, 0)"
							, $subv_entity , $subv_entity_id , $subv_entity_attrib , $fld_val, $v_entity , $v_entity_id );
						$return = $wpdb->query($sql );
						if ($return != 1) {
							echo "<P>Insert into eav_entity for subrecord failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
						}
						$wpdb->flush();
					}
				}	
			}
		}
	} 
	
	//echo '<style>p {  font-size: 0.875em;}'; // /* 14px/16=0.875em */
	echo '<form action="" method="post">' ;
	echo '<input type="hidden" id="tablename" name="tablename" value="' . esc_html($tablename) . '">';
	echo '<input type="hidden" id="eav_startadd" name="eav_startadd" value="eav_startadd">';
	
	get_save_entity($tablename); // this saves table name so we can grab it if needed
	
	if (ob_get_level() == 2) {
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else
		return "";
}

function eav_endadd( $atts = [], $content = null) {
	global $wpdb;
	
	if (ob_get_level() == 1)
		ob_start(); // this allows me to use echo instead of using concat all strings
	
	
	echo '<input type="submit" value="Submit">';
	echo '<br> </form>';
	if (ob_get_level() == 2) {
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else
		return "";
}

// this function will fill a page with all the fields from that table definition
// and allow you to add data.  It is very basic
function eav_add( $atts = [], $content = null) {
	global $wpdb;
	

	$tablename = sanitize_text_field($atts['table']);
	
	if (ob_get_level() == 1)
		ob_start(); // this allows me to use echo instead of using concat all strings
		
	if (isset($_POST['eav_submit']) && isset($_POST['tablename'])){
		$insert_tbl = sanitize_text_field($_POST['tablename']);
		// This gets the table id from the post name
		$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $insert_tbl . "'";
		$result_tbl1 =$wpdb->get_row($tblid);
		$v_entity = $result_tbl1->entity;
		// find new row number
		$maxid = "select max(entity_id) as maxid from " . $wpdb->base_prefix . "eav_entity where entity = " . sanitize_text_field($v_entity)  . " and entity_type = 0";
		$result_tbl1 =$wpdb->get_row($maxid);
		$v_entity_id = sanitize_text_field($result_tbl1->maxid) + 1;	
		$all_fields = "select entity_attrib from " . $wpdb->base_prefix . "eav_layout where entity = " . $v_entity ;
		$result_fld = $wpdb->get_results($all_fields);
		foreach($result_fld as $element) {
			// for each field in the layout first get name so we can compare to form
			$v_entity_attrib = $element->entity_attrib;
			$fieldid = "select entity_name from " . $wpdb->base_prefix . "eav_attrib where entity_attrib = " . $v_entity_attrib ;
			$result_fld = $wpdb->get_row($fieldid);
			if ( isset($_POST[$result_fld->entity_name])) {			
				// okay the field is in the post so we need to santize and insert results.
				$eav_val_char = sanitize_text_field($_POST[$result_fld->entity_name]);
				
				$prep = $wpdb->prepare (
				"INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity, entity_id, entity_attrib, val_char, entity_type) values (%s, %s, %s, %s, 0)"
				, $v_entity . ''
				, $v_entity_id . ''
				, $v_entity_attrib . ''
				, $eav_val_char . ''
				);
				$return = $wpdb->query($prep );
				if ($return === false) {
					echo "<P>Insert into eav_entity failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
				}
				$wpdb->flush();
			}
		}
	} 	
	else if (isset($_POST['eav_update']) && isset($_POST['tablename']) && isset($_POST['entity_id'])){
		$upd_tbl = sanitize_text_field($_POST['tablename']);
		$v_entity_id = sanitize_text_field($_POST['entity_id']);
		// This gets the table id from the post name
		$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $upd_tbl . "'";
		$result_tbl1 =$wpdb->get_row($tblid);
		$v_entity = $result_tbl1->entity;
		$all_fields = "select a.entity_attrib, b.entity_name from " . $wpdb->base_prefix . "eav_layout a, " . $wpdb->base_prefix . "eav_attrib b where a.entity = " . $v_entity .
			" and a.entity_attrib=b.entity_attrib ";
		$result_fld = $wpdb->get_results($all_fields);
		foreach($result_fld as $element) {
			$fld = $element->entity_name;
			$attrib = $element->entity_attrib;
			if (isset($_POST[$fld])) {
				$val = sanitize_text_field($_POST[$fld]);
				// need to check if we insert or update
				$is_there="select val_char from " . $wpdb->base_prefix . "eav_entity " .
				" where entity_type=0 and entity=" . $v_entity .
					" and entity_id=" . $v_entity_id . 
					" and entity_attrib=" . $attrib ;
				$results_there = $wpdb->get_row($is_there);
				if (isset($results_there->val_char)) {
					$u_sql = "update " . $wpdb->base_prefix . "eav_entity set val_char='" . $val . "' " .
						" where entity_type=0 and entity=" . $v_entity .
						" and entity_id=" . $v_entity_id . 
						" and entity_attrib=" . $attrib ;
				} else {
					$u_sql = sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity, entity_id, entity_attrib, val_char, parent_entity, parent_entity_id , entity_type ) values (%s, %s, %s, '%s', 0, 0, 0)"
						, $v_entity , $v_entity_id , $attrib , $val );
				}
				$return = $wpdb->query($u_sql );
				if ($return === false) {
					echo "<P>Update into eav_entity failed: " . ' - wpdb->last_error : ' . $wpdb->last_error .
						" entity_type=0 and entity=" . $v_entity .
						" and entity_id=" . $v_entity_id . 
						" and entity_attrib=" . $attrib ;
				}
				$wpdb->flush();
			}
		}
		echo '
		<H2>Search for update or add data below for new information</H2>
     	<form method="post" action="" >
			<input type="text" id="addsrcval" name="addsrcval">
			<input type="hidden" id="tablename" name="tablename" value="' . esc_html($tablename) . '">
			<button type="submit" >Search</button>
		</form>
		';
	
		// get table id #
		$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $tablename . "'";
		$result_tbl =$wpdb->get_row($tblid);
		if (!isset($result_tbl->entity)) {
			echo "<P>Table: " .  esc_html($tablename) . " not defined in wordpress EAV Plugin";
			return;
		}
		
		//echo '<style>p {  font-size: 0.875em;}'; // /* 14px/16=0.875em */
		echo "<h3>";
		echo '<table>';
		echo '<form action="" method="post">' ;
		//show all database fields in table
		$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, a.input_format, " .
			" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
			" where a.entity_attrib=b.entity_attrib and b.entity = " . sanitize_text_field($result_tbl->entity) . " order by b.entity_order";
		$results = $wpdb->get_results($hsql);
	
		foreach($results as $element) {
			echo '<tr><td style="width: 25%; border: none;">';
			echo '<label for="'. esc_html($element->entity_name) . '"  >' .  esc_html($element->entity_desc) . ':</label>&nbsp;&nbsp;' ;
			echo '</td><td style="width: 75%; border: none;">';
			if (strlen($element->input_format)) 
				$typeval = $element->input_format;
			else
				$typeval = "text";
		
			echo '<input type="' . esc_html($typeval) . '" id=";' . esc_html($element->entity_name)  . '" name="' . esc_html($element->entity_name) . '" size="50" >';
			echo '</td></tr>';
		}
		echo "</table>";
		echo "</h3>";
		echo '<input type="hidden" id="tablename" name="tablename" value="' . esc_html($tablename) . '">';
		echo '<input type="submit" value="Submit" name="eav_submit" ></form>';
	} else if (isset($_POST['addsrcval'])) {
		$insert_tbl = sanitize_text_field($_POST['tablename']);
		// This gets the table id from the post name
		
				echo '
		<H2>Search for update or add data below for new information</H2>
     	<form method="post" action="" >
			<input type="text" id="addsrcval" name="addsrcval">
			<input type="hidden" id="tablename" name="tablename" value="' . esc_html($insert_tbl) . '">
			<button type="submit" >Search</button>
		</form>
		';
		
		
		$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $insert_tbl . "'";
		$result_tbl =$wpdb->get_row($tblid);
		
		$lookfor = sanitize_text_field(trim($_POST['addsrcval'], " "));
		if (strlen($lookfor) > 0) {
			$dsql = "select a.entity, a.entity_id, a.entity_attrib, a.val_char, a.parent_entity, a.parent_entity_id, b.entity_order " .
					" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b where a.entity = " . 
					sanitize_text_field($result_tbl->entity) . 
					" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . 
					" and a.entity_type = 0 " .
					" and a.entity_id in (select distinct entity_id from " . $wpdb->base_prefix . "eav_entity where val_char like '%" . $lookfor . "%' and entity_type = 0 )" .
					"  order by entity_id,b.entity_order ";
		} else {
				// nothing was entered on the search so just do all
				$dsql = "select a.entity,  a.entity_id, a.entity_attrib, a.val_char, a.parent_entity, a.parent_entity_id, b.entity_order, c.entity_name " .
						" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
						" , " . $wpdb->base_prefix . "eav_attrib c " .
						" where a.entity_attrib=b.entity_attrib and b.entity = " . sanitize_text_field($result_tbl->entity) .
						" and c.entity_attrib = a.entity_attrib " .
						" and a.entity_type = 0 " .
						" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . ' order by entity_id,b.entity_order' ;
		}

		echo '<form method="post" action="" >';
		echo '<table id="tbl-' . esc_html($tablename) . '">';
		echo '<tr style = "border: 1px solid black;" >';
		echo '<th style= "border: 1px solid black; padding: 0px; width: 10px">Select</th>';
		//show all database fields in table
		$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, " .
			" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
			" where a.entity_attrib=b.entity_attrib and b.entity = " . $result_tbl->entity . " order by b.entity_order";
		$results = $wpdb->get_results($hsql);
		$colno = 1;
		foreach($results as $element) {
			$fsql = "select entity_attrib, entity_name, entity_format, entity_desc from " . $wpdb->base_prefix . "eav_attrib where entity_name = '" . $element->entity_name . "'";
			$fldret = $wpdb->get_row($fsql);
			if (isset($fldret->entity_format))
				$int = strlen(sprintf($fldret->entity_format, "")) - 5;
			else
				$int = 20;
			// need to handle defaults on subrecord field here  style=""
			echo '<th style= "border: 1px solid black; padding: 0px; width: ' . $int . 'px" >' . esc_html($element->entity_desc) . '</th>';
			$colno = $colno + 1;
		}
		echo '</tr>';
		$max_col = $colno;
		echo '<tr style = "border: 1px solid black;" >';
		
		$r_id = 0;
		$colno = 1;
		$results = $wpdb->get_results($dsql);
		// only 1 checkbox at a time
		echo "<script>
			function onlyOne(checkbox) {
				var checkboxes = document.getElementsByName('entity_id')
				checkboxes.forEach((item) => {
					if (item !== checkbox) item.checked = false
				})
			}
			</script>";
		foreach($results as $element) {
			if ($r_id == 0) {
				echo '<td style= "border: 1px solid black; padding: 0px; ">
					<center>
					<input type="checkbox" id="entity_id' . esc_html($element->entity_id) . 
					'"  name="entity_id" value="' . esc_html($element->entity_id) . '" ' .
					' onclick="onlyOne(this)"></center></td>';
					' onclick="onlyOne(this)"></center></td>';
			}
			
			if (($r_id != 0) && ($r_id != $element->entity_id)) {
				//finish out row if no data
				for($ii = $colno + 1; $ii < $max_col; $ii++) {
					echo '<td style="border: 1px solid black">brad1=$ii</td>';
				}
				/* new row */
				$colno = 1;
				echo "</tr>";
				echo "<tr>";
				echo '<td style= "border: 1px solid black; padding: 0px; ">
				<center>
				<input type="checkbox" id="entity_id' . esc_html($element->entity_id) . 
					'"  name="entity_id" value="' . esc_html($element->entity_id) . '" ' .
					' onclick="onlyOne(this)">
				</center></td>';
				$r_id = $element->entity_id;
			}
			// double check and make sure we are on the same column as the header
			$newcol = sanitize_text_field($element->entity_order);
			if ($newcol != ($colno +1)) {
				// missing columns
				for($ii = ($colno +1); $ii < $newcol; $ii++)
					echo '<td style="border: 1px solid black">brad2=$ii</td>';
			}				
			echo '<td style= "border: 1px solid black; padding: 0px; ">';
			echo esc_html($element->val_char) ;
			echo '</td>';
			$r_id = $element->entity_id;
			$colno = $newcol;
			
		}
		//Finish out last row
		for($ii = $colno + 1; $ii < $max_col; $ii++)
			echo '<td style="border: 1px solid black">brad3=$ii</td>';
	
		echo '</tr>';
		echo '</table >';
		echo '<button type="submit" >submit</button>';
		echo '<input type="hidden" id="tablename" name="tablename" value="' . esc_html($insert_tbl) . '">';
		echo '</form>';
	} else if (isset($_POST['entity_id'])) {
		$v_entity_id = sanitize_text_field($_POST['entity_id']);
		$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $tablename . "'";
		$result_tbl =$wpdb->get_row($tblid);
		
		echo "<h3>";
		echo '<table>';
		echo '<form action="" method="post">' ;
		//show all database fields in table
		$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, a.input_format, " .
			" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
			" where a.entity_attrib=b.entity_attrib and b.entity = " . sanitize_text_field($result_tbl->entity) . 
			" order by b.entity_order";
		$results = $wpdb->get_results($hsql);
		$results = $wpdb->get_results($hsql);
	
		foreach($results as $element) {		
			$v_sql = "select val_char from  " . $wpdb->base_prefix . "eav_entity where " .
				"entity_type = " . EAV_REC_FLD . 
				" and entity_id = " . $v_entity_id . 
				" and entity = " . $result_tbl->entity .
				" and entity_attrib = " . $element->entity_attrib ;
			$val_result =$wpdb->get_row($v_sql);
			if (isset($val_result->val_char))
				$val_char = $val_result->val_char;
			else
				$val_char = "";
				
			echo '<tr><td style="width: 25%; border: none;">';
			echo '<label for="'. esc_html($element->entity_name) . '"  >' .  esc_html($element->entity_desc) . ':</label>&nbsp;&nbsp;' ;
			echo '</td><td style="width: 75%; border: none;">';
			if (strlen($element->input_format)) 
				$typeval = $element->input_format;
			else
				$typeval = "text";
			echo '<input type="' . esc_html($typeval) . '" id=";' . esc_html($element->entity_name);
			echo '" name="' . esc_html($element->entity_name);
			echo '" value = "' . $val_char . '" size="50" >';
			echo '</td></tr>';
		}
		echo "</table>";
		echo "</h3>";
		echo '<input type="hidden" id="tablename" name="tablename" value="' . esc_html($tablename) . '">';
		echo '<input type="hidden" id="entity_id" name="entity_id" value="' . esc_html($v_entity_id) . '">';
		echo '<input type="submit" value="Update" name="eav_update" >&nbsp;&nbsp;&nbsp;';
		echo '<input type="submit" value="View Child Record" name="eav_child" >';
		echo '</form>';
		
	} else {
		echo '
		<H2>Search for update or add data below for new information</H2>
     	<form method="post" action="" >
			<input type="text" id="addsrcval" name="addsrcval">
			<input type="hidden" id="tablename" name="tablename" value="' . esc_html($tablename) . '">
			<button type="submit" >Search</button>
		</form>
		';
	
		// get table id #
		$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $tablename . "'";
		$result_tbl =$wpdb->get_row($tblid);
		if (!isset($result_tbl->entity)) {
			echo "<P>Table: " .  esc_html($tablename) . " not defined in wordpress EAV Plugin";
			return;
		}
		
		//echo '<style>p {  font-size: 0.875em;}'; // /* 14px/16=0.875em */
		echo "<h3>";
		echo '<table>';
		echo '<form action="" method="post">' ;
		//show all database fields in table
		$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, a.input_format, " .
			" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
			" where a.entity_attrib=b.entity_attrib and b.entity = " . sanitize_text_field($result_tbl->entity) . " order by b.entity_order";
		$results = $wpdb->get_results($hsql);
		foreach($results as $element) {
			echo '<tr><td style="width: 25%; border: none;">';
			echo '<label for="'. esc_html($element->entity_name) . '"  >' .  esc_html($element->entity_desc) . ':</label>&nbsp;&nbsp;' ;
			echo '</td><td style="width: 75%; border: none;">';
			if (strlen($element->input_format)) 
				$typeval = $element->input_format;
			else
				$typeval = "text";
		
			echo '<input type="' . esc_html($typeval) . '" id=";' . esc_html($element->entity_name)  . '" name="' . esc_html($element->entity_name) . '" size="50" >';
			echo '</td></tr>';
		}
		echo "</table>";
		echo "</h3>";
		echo '<input type="hidden" id="tablename" name="tablename" value="' . esc_html($tablename) . '">';
		echo '<input type="submit" value="Submit" name="eav_submit" ></form>';
	}	
	if (ob_get_level() == 2) {
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else
		return "";
	
}

function eav_subrec($atts = [], $content = null) {
	global $wpdb;
	
	if (ob_get_level() == 1)
		ob_start(); // this allows me to use echo instead of using concat all strings
	
	
	$tablename = sanitize_text_field($atts['table']);
	// get table id #
	$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $tablename . "'";
	$result_tbl =$wpdb->get_row($tblid);
	$tbl_entity = $result_tbl->entity;

	echo '<script>
			function MyAddRow' . esc_html($tablename) . '() {
			var table = document.getElementById("tbl-' . esc_html($tablename) . '");
			var row = table.insertRow(-1);';
	$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, " .
		" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
		" where a.entity_attrib=b.entity_attrib and b.entity = " . $tbl_entity . " order by b.entity_order";
	$cellno = 1;
	$results = $wpdb->get_results($hsql);
	foreach($results as $element) {
		$fsql = "select entity_attrib, entity_name, entity_format, entity_desc, show_on_browse from " . $wpdb->base_prefix . "eav_attrib where entity_name = '" . $element->entity_name . "'";
		$fldret = $wpdb->get_row($fsql);
		$int = strlen(sprintf($fldret->entity_format, ""));
		
		if ( $fldret->show_on_browse == "y" ) {
			echo 'var cell' . $cellno . ' = row.insertCell(' . ($cellno -1 ) . ');';
			echo 'cell'. $cellno . '.innerHTML = \'<input type="text"  size="' . $int . '" name="' . 
				esc_html($element->entity_name) . '[]"/>\';';
			$cellno = $cellno + 1;
		}
	}
	echo '}	</script>';

	echo '<input type="hidden" id="subtablename" name="subtablename" value="' . esc_html($tablename) . '">';
	
	echo '<style>
		table, th, td { border: 2px solid black;}
	</style>';
	
	// This allows us a horizontal scrollable grid
	echo '<style>
		.horizontal-snap {
			margin: 0 auto;
			display: grid;
			grid-auto-flow: column;
			gap: 1rem;
			padding: 1rem;
			overflow-y: auto;
			overscroll-behavior-x: contain;
			scroll-snap-type: x mandatory;
		}		
		</style>';

	echo '<div class="horizontal-snap">'; // this makes row horizontal scrollable

	echo '<table id="tbl-' . esc_html($tablename) . '">';
	echo '<tr style = "border: 1px solid black;" >';
	//show all database fields in table
	$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, " .
		" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
		" where a.entity_attrib=b.entity_attrib and b.entity = " . $tbl_entity . " order by b.entity_order";
	$results = $wpdb->get_results($hsql);
	foreach($results as $element) {
		$fsql = "select entity_attrib, entity_name, entity_format, entity_desc, show_on_browse from " . $wpdb->base_prefix . "eav_attrib where entity_name = '" . $element->entity_name . "'";
		$fldret = $wpdb->get_row($fsql);
		if ( $fldret->show_on_browse == "y" ) {
			if (isset($fldret->entity_format))
				$int = strlen(sprintf($fldret->entity_format, "")) - 5;
			else
				$int = 20;
			// need to handle defaults on subrecord field here
			echo '<th style= "width: ' . $int . 'px" >' . esc_html($element->entity_name) . '</th>';
		}
	}
	echo '</tr>';
	echo '<tr style = "border: 1px solid black;" >';
	$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, " .
		" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
		" where a.entity_attrib=b.entity_attrib and b.entity = " . $tbl_entity . " order by b.entity_order";
	$results = $wpdb->get_results($hsql);
	foreach($results as $element) {
		$fsql = "select entity_attrib, entity_name, entity_format, entity_desc, show_on_browse from " . $wpdb->base_prefix . "eav_attrib where entity_name = '" . $element->entity_name . "'";
		$fldret = $wpdb->get_row($fsql);
		if ( $fldret->show_on_browse == "y" ) {
			$int = strlen(sprintf($fldret->entity_format, "")) ;
			echo '<td>';	
			echo '<input type="text"  size="' . $int . '" name="' . esc_html($element->entity_name) . '[]"   />';
			echo '</td>';
		}
	}
	echo '</tr>';
	echo '</table >';
	echo '</div>';  //end scroll
	echo '<P>';
	echo '<input type="button" id="add-' . esc_html($tablename) . '" name="add-' . esc_html($tablename) . '" value="Add Row" onclick="MyAddRow' . esc_html($tablename)  . '()"/>';
	echo "<P>";
	
	if (ob_get_level() == 2) {
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else
		return "";
}

// eav_showrecord (entity, id, updateable)
function eav_showrecord($v_entity, $v_id, $update) {
	global $wpdb;
	
	
	$tsql = "select tblname from " . $wpdb->base_prefix . "eav_tbl where entity = " . $v_entity;
	$results = $wpdb->get_row($tsql);
	$tablename = $results->tblname;
	

	echo '<form action="" method="post">' ;
	//show all database fields in table
	$hsql = "select  a.entity_desc, a.entity_name, a.entity_attrib, " . 
			" a.entity_format, a.input_format, a.show_on_browse, " .
			" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
			" where a.entity_attrib=b.entity_attrib " .
			" and b.entity = " . $v_entity . " order by b.entity_order";
	$results = $wpdb->get_results($hsql);	
	foreach($results as $element) {
		echo '<label for="'. esc_html($element->entity_name) . '"  >' .  esc_html($element->entity_desc) . ':</label>&nbsp;&nbsp;' ;
		
		$vsql="select val_char from " . $wpdb->base_prefix . "eav_entity where entity=" . $v_entity . 
			" and entity_id = " . $v_id . " and entity_attrib = " . $element->entity_attrib  . " and entity_type = 0";
		$v_results = $wpdb->get_row($vsql);
		if (isset($v_results->val_char)) 
			$val = $v_results->val_char;
		else
			$val = "";
		
		$thestrlen = strlen(trim($val));
		
		if (isset($element->input_format)) 
			$typeval = $element->input_format;
		else
			$typeval = "text";

		if ($thestrlen > 50) {
			echo '<textarea cols="80" name="' . esc_html($element->entity_attrib) . '" size="50" ' . $update . ">" ;
			echo esc_html($val);
			echo '</textarea>';
			echo '<br>';
		} else {
			echo '<input type="' . $typeval . '" name="' . esc_html($element->entity_attrib) . '" size="50" ' . $update ;
			echo ' value="' . esc_html($val) . '" >';
			echo '<br>';
		}
	}
	echo '<input type="hidden" name="u_entity" value="' . esc_html($v_entity) . '">';
	echo '<input type="hidden" name="u_entity_id" value="' . esc_html($v_id) . '">';
	echo '<input type="hidden" id="tablename" name="tablename" value="' . esc_html($tablename) . '">';
	if ($update != "readonly")
		echo '<input type="submit" value="Submit" name="eav_submit" ></form>';


	
}

function eav_apache( $atts = [], $content = null) {
	global $wpdb;
	
	if (ob_get_level() == 1)
		ob_start(); // this allows me to use echo instead of using concat all strings
	
	$tablename = sanitize_text_field($atts['table']);
	$fnd_sql = "select entity from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $tablename . "'";
	$res_val = $wpdb->get_row($fnd_sql);
	if (isset($res_val->entity)) 
		$v_entity = $res_val->entity;
	else
		die("tblname: " . $tablename . " not defined.");

	$fld_array = array('remotehost','localhost', 'user', 'datetime'
		,'method','request','protocol','status','bytes', 'referer', 'user_agent' );		
	$fld_cnt = count($fld_array);
	
	$err_fld_array = array('remotehost','localhost', 'user', 'datetime','request');		
	$err_fld_cnt = count($err_fld_array);
						
						
//$pattern = '/^([^ ]+) ([^ ]+) ([^ ]+) (\[[^\]]+\]) "(.*) (.*) (.*)" ([0-9\-]+) ([0-9\-]+) "(.*)" "(.*)"$/'; 

	$pattern = "";
	for($i = 0; $i < $fld_cnt; $i++) {
		$sql_f="select val_char from " . $wpdb->base_prefix . "eav_entity where " .
		" entity_type = " . EAV_IMPORT . " and entity=" . $v_entity . " and entity_id =" . ($i+1);
		$res_val = $wpdb->get_row($sql_f);
		if ($pattern == "" ) 
			$pattern = "/^" . $res_val->val_char;
		else
			$pattern = $pattern . " " . $res_val->val_char;
	}
	$pattern = $pattern . "$/"; // finish up the string

	$current_user = wp_get_current_user();
	$content = "";
	
	if (isset($_POST['submit']) && ($_POST['submit'] == 'delete-all' )) {
		$sql_del = "delete from " . $wpdb->base_prefix . "eav_entity where entity_type=0 and entity=" . $v_entity;
		$return = $wpdb->query($sql_del );
		if ($return === false) {
			echo "<P>Insert into eav_entity for parent record failed: " . ' - wpdb->last_error : ' . $wpdb->last_error;
		}
		$wpdb->flush();
		
	} 
	else if (isset($_POST['submit']) && ($_POST['submit'] == 'process-file' )) {		
		// find the max entity_id value
		$fnd_sql = "select max(entity_id) as maxval from " . $wpdb->base_prefix . "eav_entity where " .
			" entity_type = 0 and entity = " . $v_entity ;
		$res_val = $wpdb->get_row($fnd_sql);
		if (isset($res_val->maxval)) 
			$v_entity_id = $res_val->maxval + 1;
		else
			$v_entity_id = 1;
		
		$userfile_array = $_FILES['userfile'];
		$filename = $userfile_array['tmp_name'];
		$save_filename = $userfile_array['name'];
		$loop_cnt = 0;
			
		$handle = fopen($filename, "rb");
		while(!feof($handle)) {
			// read each line and trim off leading/trailing whitespace 
			if ($s = trim(fgets($handle,16384))) { 
				// match the line to the pattern 
				if (preg_match($pattern,$s,$matches)) { 
					/* put each part of the match in an appropriately-named 
					 * variable */ 
					list($whole_match,$remote_host,$localhost, $user,$time, 
						$method,$request,$protocol,$status,$bytes,$referer, 
						$user_agent) = $matches; 
					$log_array = array($remote_host,$localhost, $user,$time, 
						$method,$request,$protocol,$status,$bytes,$referer, 
						$user_agent);
					// keep track of the count of each request 
					for($j = 0; $j < $fld_cnt; $j++) {	
						$fnd_sql = "select entity_attrib from  " . $wpdb->base_prefix . "eav_attrib " .
							" where entity_name = '" . $fld_array[$j] . "'";
						$v_entity_attrib = "";
						$res_val = $wpdb->get_row($fnd_sql);
						if (isset($res_val->entity_attrib)) 
							$v_entity_attrib = $res_val->entity_attrib;
						else {
							echo "field: " . $fld_array[$j] . " not defined.";
							die ("field: " . $fld_array[$j] . " not defined.");
						}
						$prep = $wpdb->prepare (
							"INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity, entity_id, entity_attrib, val_char, entity_type, parent_entity, parent_entity_id) values (%s, %s, %s, %s, 0, 0, 0)"
							, $v_entity . ''
							, $v_entity_id . ''
							, $v_entity_attrib . ''
							, $log_array[$j] . ''
							);
						$return = $wpdb->query($prep );
						if ($return === false) {
								die( "<P>Insert into eav_entity failed: " . ' - wpdb->last_error : ' . $wpdb->last_error);
						}
						$wpdb->flush();
					}
				} else { 
					// complain if the line didn't match the pattern and try to load in, usually the file is missing the method
					// so we will grab things and see what we get.
					//$pattern = '/^([^ ]+) ([^ ]+) ([^ ]+) (\[[^\]]+\]) "(.*) (.*) (.*)" ([0-9\-]+) ([0-9\-]+) "(.*)" "(.*)"$/'; 
					if (preg_match('/^([^ ]+) ([^ ]+) ([^ ]+) (\[[^\]]+\]) "(.*)" ([0-9\-]+) (.*) "(.*)" "(.*)"$/' ,$s,$match)) { 
						list($whole_match,$remote_host,$localhost, $user,$time, $request, $status,$bytes,$referer, 
						$user_agent) = $match; 
						$method = "UNKOWN"; //force to unknown
						$protocol = "UNKNOWN";
						$log_array = array($remote_host,$localhost, $user,$time, 
						$method,$request,$protocol,$status,$bytes,$referer, $user_agent);
						for($j = 0; $j < $fld_cnt; $j++) {	
							$fnd_sql = "select entity_attrib from  " . $wpdb->base_prefix . "eav_attrib " .
							" where entity_name = '" . $fld_array[$j] . "'";
							$v_entity_attrib = "";
							$res_val = $wpdb->get_row($fnd_sql);
							if (isset($res_val->entity_attrib)) 
								$v_entity_attrib = $res_val->entity_attrib;
							else {
								echo "field: " . $err_fld_array[$j] . " not defined.";
								die ("field: " . $err_fld_array[$j] . " not defined.");
							}
							$prep = $wpdb->prepare (
								"INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity, entity_id, entity_attrib, val_char, entity_type, parent_entity, parent_entity_id) values (%s, %s, %s, %s, 0, 0, 0)"
								, $v_entity . ''
								, $v_entity_id . ''
								, $v_entity_attrib . ''
								, $log_array[$j] . ''
								);
							$return = $wpdb->query($prep );
							if ($return === false) {
								die( "<P>Insert into eav_entity failed: " . ' - wpdb->last_error : ' . $wpdb->last_error);
							}
							$wpdb->flush();
						}
					} else {
							echo "<PRE>Can't parse line $s</PRE>";
							error_log("<PRE>Can't parse line $s</PRE>"); 
					} 
				}
			}
			$v_entity_id++;
		}

		fclose($handle);
	}	
	
	echo '<form enctype="multipart/form-data" action="" method="post">';
	echo  'Import this file: <input name="userfile" type="file" id="fileToUpload" />';
	echo  '<input type="submit" id="process-file"  name="submit"  value="process-file">';
	echo  '&nbsp;&nbsp;<input type="submit" id="delete-all"  name="submit"  value="delete-all">';
	echo '</form>';
	
	if (ob_get_level() == 2) {
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else
		return "";
}

function eav_tbl( $atts = [], $content = null) {
	global $wpdb;
	
	if (ob_get_level() == 1)
		ob_start(); // this allows me to use echo instead of using concat all strings
 
	//eav_header();


	if (isset($_GET['entity']) && isset($_GET['entity_id'])) {
		// read only page
		$v_entity = sanitize_text_field($_GET['entity']);
		$v_id = sanitize_text_field($_GET['entity_id']);
		$update = "readonly";
		eav_manage_showrecord($v_entity,$v_id, $update);
		//eav_showrecord($v_entity,$v_id, $update);
	} else if (isset($_POST['entity']) && isset($_POST['entity_id'])) {
		// read only page
		$v_entity = sanitize_text_field($_POST['entity']);
		$v_id = sanitize_text_field($_POST['entity_id']);
		$update = "";
		eav_manage_showrecord($v_entity,$v_id, $update);
		//eav_showrecord($v_entity,$v_id, $update);
	} else {
		// handle update then show data
		if (isset($_POST['eav_submit'])) {
			$v_entity = sanitize_text_field($_POST['u_entity']);
			$v_id = sanitize_text_field($_POST['u_entity_id']);
			
			$sql = "select entity, entity_attrib, entity_order from " . $wpdb->base_prefix . "eav_layout " .
				" where entity= " . $v_entity . " order by entity_order";
			$results = $wpdb->get_results($sql);
			foreach($results as $element) {
				$v_attrib = $element->entity_attrib;
				if (isset($_POST[$v_attrib])) {
					// okay lets update this
					$val = sanitize_text_field($_POST[$v_attrib]);
					// need to first check if the value is there, if not we insert
					$sqlchk = "select count(*) as x from " . $wpdb->base_prefix . "eav_entity where entity=" . $v_entity . " and entity_id= " . $v_id .
						" and entity_attrib=" . $v_attrib  . " and entity_type = 0";
					$chkresult=$wpdb->get_row($sqlchk);
					if ($chkresult->x == 1) {
						$sqlupd = "update " . $wpdb->base_prefix . "eav_entity set val_char = '" . $val . "' where entity=" . $v_entity . " and entity_id= " . $v_id .
						" and entity_attrib=" . $v_attrib;
					} else {
						// need to find parent value down the road
						$sqlupd = sprintf("INSERT INTO " . $wpdb->base_prefix . "eav_entity (entity, entity_id, entity_attrib, val_char, parent_entity, parent_entity_id, entity_type ) values (%s, %s, %s, '%s', 0,0, 0)"
							, $v_entity , $v_id , $v_attrib , $val );
					}						
					$return = $wpdb->query($sqlupd );
					$wpdb->flush();
				}
			}
		}
		// this helps us determine how we are called.  
		if (isset($atts['table'])) 
			$tablename = sanitize_text_field($atts['table']);
		else {
			echo "<P>Missing table= attribute on shortcode<P>";
			exit;
		}
		if (isset($atts['flds']))
			$flds = sanitize_text_field($atts['flds']);
		else
			$flds = "";
			
		// check if user would like to limit rows
		if (isset($atts['load']))
			$sql_limit = sanitize_text_field($atts['load']);
		else
			$sql_limit = 500;
		
		if (isset($atts['filter']))
			$sql_filter = sanitize_text_field($atts['filter']);
		else
			$sql_filter = " and 1=1";

		$nosrch = "";
		$allowadd = "";
		$allowupd = "";
		
		if (isset($atts['nosrch'])) 
			$nosrch = "y";
		if (isset($atts['allowadd']))
			$allowadd = sanitize_text_field($atts['allowadd']);
		
		if (isset($atts['allowupd']))
			$allowupd = sanitize_text_field($atts['allowupd']);

		// get table id #
		$tblid = "select entity, tblname, tbldescr from " . $wpdb->base_prefix . "eav_tbl where tblname = '" . $tablename . "'";
		$result_tbl =$wpdb->get_row($tblid);
		if (isset($result_tbl->entity)) 
			$v_entity = $result_tbl->entity;
		else {
			echo "<P>tblname: $tablename not defined.<P>";
			return;
//			die("tblname: " . $tablename . " not defined.");
		}
		
		// get number of columns on record
		$tblid = "select count(*) as count from " . $wpdb->base_prefix . "eav_layout where entity = " . $result_tbl->entity;
		$result_cnt =$wpdb->get_row($tblid);
		$colid = $result_cnt->count;
		
		// so this code is going to check if we need to limit the browse shown below
		if (isset($_GET['searchvalue'])) {
			$lookfor = sanitize_text_field(trim($_GET['searchvalue'], " "));
			if (strlen($lookfor) > 0) {
				if ($flds == "") {
					// submit button pushed and we want all fields
					$dsql = "select a.entity, a.entity_id, a.entity_attrib, a.val_char, a.parent_entity, a.parent_entity_id, b.entity_order " .
					" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
					" where a.entity in (select entity from " . $wpdb->base_prefix . "eav_tbl where " .
					" entity = " . sanitize_text_field($result_tbl->entity) . " ) " .
					" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . 
					" and a.entity_type = 0 " .
					" and a.entity_id in (select DISTINCT CASE WHEN parent_entity= 0 then entity_id else parent_entity_id end " . 
					" from " . $wpdb->base_prefix . "eav_entity " .
						" where val_char like '%" . $lookfor . "%' and entity_type = 0 and " .
						" (entity=" . sanitize_text_field($result_tbl->entity) . 
						" or parent_entity=" . sanitize_text_field($result_tbl->entity) . " ))" .
					$sql_filter . 
					"  order by a.entity_id,b.entity_order ";
				} else {
					// need to look for search value in all records and return only certain fields
					$dsql = "select a.entity, a.entity_id, a.entity_attrib, a.val_char, a.parent_entity, a.parent_entity_id, b.entity_order " .
					" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
					" where a.entity in (select entity from " . $wpdb->base_prefix . "eav_tbl where " .
					" ( entity = " . sanitize_text_field($result_tbl->entity) . " )  )" .
					" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . 
					" and a.entity_type = 0 " .
					" and a.entity_id in (select DISTINCT CASE WHEN parent_entity= 0 then entity_id else parent_entity_id end " . 
					" from " . $wpdb->base_prefix . "eav_entity " .
						" where val_char like '%" . $lookfor . "%' and entity_type = 0 and " .
						" (entity=" . sanitize_text_field($result_tbl->entity) . 
						" or parent_entity=" . sanitize_text_field($result_tbl->entity) . " ))" .
					" and b.entity_order in (" . $flds . ") " .
					$sql_filter . 
					"  order by a.entity_id,b.entity_order ";
				}
					
			} else {
				// nothing was entered on the search so just do all
				if ($flds == "") {
					$dsql = "select a.entity,  a.entity_id, a.entity_attrib, " .
						" a.val_char, a.parent_entity, 	a.parent_entity_id, b.entity_order  " .
						" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
						" where a.entity_attrib=b.entity_attrib and b.entity = " . 
						sanitize_text_field($result_tbl->entity) .
						" and a.entity_type = 0 " .
						" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . 
						$sql_filter . 
						' order by a.entity_id,b.entity_order' ;
				} else {
				// normal search, limit by field order from page
					$dsql = "select a.entity,  a.entity_id, a.entity_attrib, a.val_char, " .
					"a.parent_entity, a.parent_entity_id, b.entity_order  " .
					" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
					" where a.entity_attrib=b.entity_attrib and b.entity = " . 
						sanitize_text_field($result_tbl->entity) . 
					" and a.entity_type = 0 " .
					" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . 
					" and b.entity_order in (" . $flds . ") " .
					$sql_filter . 
					' order by a.entity_id,b.entity_order' ;
				}
			}
		}else {
			if ($flds == "") {
				//if we are here we will just use the normal search
				$dsql = "select a.entity,  a.entity_id, a.entity_attrib, a.val_char, a.parent_entity, a.parent_entity_id, b.entity_order  " .
				" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
				" where a.entity_attrib=b.entity_attrib and b.entity = " . sanitize_text_field($result_tbl->entity) . 
				" and a.entity_type = 0 " .
				" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . 
				$sql_filter . 
				' order by entity_id,b.entity_order' ;
			} else {
				// normal search, limit by field order from page
				$dsql = "select a.entity,  a.entity_id, a.entity_attrib, a.val_char, " .
				"a.parent_entity, a.parent_entity_id, b.entity_order  " .
				" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
				" where a.entity_attrib=b.entity_attrib and b.entity = " . sanitize_text_field($result_tbl->entity) . 
				" and a.entity_type = 0 " .
				" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . 
				" and b.entity_order in (" . $flds . ") " .
				$sql_filter . 
				' order by entity_id,b.entity_order' ;
			}
		}

		// for now columns must be in order, but we can do case in select and then an order by on that
		// order, but also need to do on titles as well.
		$explode_flds = explode(',' , $flds);
		$explode_cnt = count($explode_flds);

		if ($nosrch != "y") {
			echo '<table style="margin-left: auto; margin-right: auto;  border: none; padding: 0px;">';
			echo '<tr style="border: none; padding: 0px;">';
			echo '<td style="border: none; padding: 0px">';
			echo '<form action="" method="get">
				<label for="seachlabel">Search Value:</label>
				<input type="text" id="searchvalue" name="searchvalue" size="50" >&nbsp;&nbsp;
				<input type="submit" value="Submit">
				<br> </form>';
			echo '</td></tr>';
			echo '</table>';
		}
	
		// This allows us a horizontal scrollable grid
		echo '<style>
		.horizontal-snap {			
			display: grid;
			grid-auto-flow: column;
			overflow-y: auto;
			overscroll-behavior-x: contain;
			scroll-snap-type: x mandatory;
		}		
		</style>';	

		if ( $flds == "") {
			//show all database fields in table
			$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, a.show_on_browse, " .
				" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
				" where a.entity_attrib=b.entity_attrib and b.entity = " . sanitize_text_field($result_tbl->entity) . ' order by b.entity_order' ;
			$results = $wpdb->get_results($hsql);
		} else {
			//$flds has a list of column numbers
	
			// get number of columns on record
			$hsql = "select count(*) as cnt from " . $wpdb->base_prefix . "eav_attrib a, " . 
					$wpdb->base_prefix . "eav_layout b " .
					" where a.entity_attrib=b.entity_attrib and b.entity = " . 
					sanitize_text_field($result_tbl->entity) .
					" and b.entity_attrib in (" . $flds . ") " ;
			$result_cnt =$wpdb->get_row($hsql);
			$colid = $result_cnt->cnt;
			
			$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, a.show_on_browse, " .
					" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . 
					"eav_layout b where a.entity_attrib=b.entity_attrib and b.entity = " . 
					sanitize_text_field($result_tbl->entity) .
					" and b.entity_order in (" . $flds . ") " .
					' order by b.entity_order' ;
			$results = $wpdb->get_results($hsql);
		}
		
		echo '<div class="horizontal-snap">'; // this makes row horizontal scrollable
		echo "\r\n";
		echo '<table style="margin-left: auto; margin-right: auto; border: 1px solid black" id="myTable" >';
		echo '<thead>';
		echo "\r\n";
		echo '<tr >';
		//echo '<th></th>';
		if ($allowupd == "y") {
			echo '<th style="border: 1px solid black">';
			echo "Click 4 Update";
			echo "</th>";
		}
		$colcnt = 0;
		foreach($results as $element) {
			$int = strlen(sprintf($element->entity_format, ""));
			echo '<th style="border: 1px solid black">' . 
				esc_html($element->entity_desc) . '</th>';
			$colcnt++;
		}
		echo '</tr>';
		echo "\r\n";
		echo "</thead>\r\n";
		$new_row = 0;

		$max_col = $colcnt;
	
		$results = $wpdb->get_results($dsql);
		$colno = 1;
		$row_count = 1;
		$loop_cnt = 0;
		echo "<tbody>\r\n";
		echo "<tr>";
		foreach($results as $element) {

			if (( $new_row <> sanitize_text_field($element->entity_id)) && ( $new_row <> 0)) {
				// okay b/c we might missing cell data and jquery will complain we need to break
				// out if we are getting close 
				
				if ($row_count >= $sql_limit) {
							//Finish out last row
					if (($colno != 1) && ($row_count <= $sql_limit)) {
						for($ii = $loop_cnt; $ii < $max_col; $ii++) {
							echo '<td style="border: 1px solid black"></td>';
						}
					}
					echo "</tr>\r\n";
					break;
				} else
					$row_count++; // keep track of the rows we have seen
	
				// We first need to check if we are not the first loop, b/c
				// if we are in the middle, we need to check and see if we skipped over a field
				// b/c there was no data, so there was no join done.
				if (($flds != "") && (($loop_cnt + 1) < $explode_cnt)) {
					if ($explode_flds[$loop_cnt] != $explode_flds[($loop_cnt + 1)]){
						$colno = $loop_cnt + 1;
						for( ; $explode_flds[$colno] == $element->entity_attrib; $colno++) {
							echo '<td style="border: 1px solid black"></td>';
						}
					}
				}
				else {	
				   //
					//finish out row if no data - was loop_cnt on start of loop
					for($ii = $colno; $ii < $max_col; $ii++) {
						//echo '<td style="border: 1px solid black">' . "colno=$colno, ii=$ii/$loop_cnt/$max_col</td>";
						echo '<td style="border: 1px solid black"></td>';
						$colno++;
					}
				}
				/* new row */
				$colno = 1;
				$loop_cnt = 0;
				echo "</tr>\r\n<tr>";
			}

			if (($allowupd == "y")&&($loop_cnt==0)) {
				//handle update
				echo '<td  style="border: 1px solid black">';
				echo '<form action="" id="review" name="review" method="post">';
				echo '<input style="text-decoration: underline; color: blue; background-color: Transparent; border: none; cursor: pointer;" type="submit" value="Update">';
				echo '<input type="hidden" id="entity" name="entity" value="' . esc_html($element->entity) . '">';
				echo '<input type="hidden" id="entity_id" name="entity_id" value="' . esc_html($element->entity_id) . '">';
				echo '</form>';
				echo "</td>";
			}


			$new_row = sanitize_text_field($element->entity_id);
			$newcol = sanitize_text_field($element->entity_order);
			

			if (($flds == "") && ($newcol != ($colno +1)) && ($colno != 1)) {
				// missing columns
				for($ii = ($colno +1); $ii < $newcol; $ii++) {
					echo '<td style="border: 1px solid black">';
					echo "MISCOL $ii/ $colno / $newcol";
					echo '</td>';
					$colno++;
				};
			} else if (($loop_cnt != 0)&&($loop_cnt < ($explode_cnt -1))) { 
				//only look if we aren't on the first field or on the last field
				
				// okay now we know what the previous value, if we increment colno by 1
				// if the explode_flds value equals entity_order then do nothing 
				if ( $explode_flds[($loop_cnt)] != $newcol)  {
					//okay so the 2 field#'s don't equal each other
					while (($explode_flds[$loop_cnt] != $newcol) && ($loop_cnt < ($explode_cnt -1))) {
						echo '<td style="border: 1px solid black">';
						echo '</td>';
						$loop_cnt++;
						$colno++;
					}
				} 
			}
			

			echo '<td style="border: 1px solid black">';
			echo '<a href="?entity=' . esc_html($element->entity) . '&entity_id=' . 
				esc_html($element->entity_id) . '">';
			//$x = sprintf("%-35.35s",  esc_html($element->val_char));
			$x = sprintf("%-35.35s",  wp_strip_all_tags($element->val_char, true));
			echo trim($x);
			if(strlen($element->val_char) > 35) 
				echo "...";
			echo '</a>';
			echo '</td>';
			$colno = $newcol; // save current order #
			$loop_cnt++;

		}
		//Finish out last row
		if (($colno != 1) && ($row_count < $sql_limit)) {
			for($ii = $colno; $ii < $max_col; $ii++)
				echo '<td style="border: 1px solid black"></td>';
		}

		echo '</tr>';
		echo "\r\n";
		echo '</tbody></table>';
		echo "\r\n";
		

		
		echo '<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>';
// JQuery Reference, If you have added jQuery reference in your master page then ignore, 
// else include this too with the below reference

		echo '<script src="https://cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js"></script>';
		echo '<link rel="stylesheet" href="https://cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css">';


		echo "<style>.dataTables_filter { visibility: hidden;}</style>";
	
		echo '<script type="text/javascript"> ' .
		"$(document).ready(
			function () {
				$('#myTable').dataTable();
					//DataTable custom search field
				$('#custom-filter').keyup( function() {table.search( this.value ).draw();} );
				$('#myTable').dataTable.ext.errMode = 'none';
			});
		</script>";

		echo '</div>'; // this ends makes row horizontal scrollable
	}	
	if (ob_get_level() == 2) {
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	} else
		return "";
}
function eav_manage_showrecord($v_entity,$v_id, $update){
  //Get the active tab from the $_GET param
  global $wpdb;
  
  
  echo '<P><a href="javascript:history.back()">Go Back</a>';
  
  ?>
<style>
  /* Style the tab */
.tab {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: #f1f1f1;
}

/* Style the buttons inside the tab */
.tab button {
  background-color: inherit;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
  display: none;
  padding: 6px 12px;
  border-top: none;
}
</style>
<script>
function eav_opentab(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(cityName).style.display = "block";
  evt.currentTarget.className += " active";
}


</script>

	<div class="tab">'
	<button class="tablinks" onclick="eav_opentab(event, 'main')">Main Table</button>
<?php
	$c1sql= "select entity, tblname from " . $wpdb->base_prefix . "eav_tbl where parent_entity = " . $v_entity ;
	$results = $wpdb->get_results($c1sql);	
	foreach($results as $xelement) {
		echo '<button class="tablinks" onclick="eav_opentab(event, ' .
				"'" . $xelement->tblname . "')" . '">Child table<br>'. $xelement->tblname . '</button>';
	}
	echo "</div>";

 ?>	
	<div id="main" class="tabcontent">
	<p><?php eav_showrecord($v_entity,$v_id, $update);?> </p>
	</div>
<?php

		// This allows us a horizontal scrollable grid
		echo '<style>
		.horizontal-snap {			
			display: grid;
			grid-auto-flow: column;
			overflow-y: auto;
			overscroll-behavior-x: contain;
			scroll-snap-type: x mandatory;
		}		
		</style>';	

	$c1sql= "select entity, tblname from " . $wpdb->base_prefix . "eav_tbl where parent_entity = " . $v_entity ;
	$results = $wpdb->get_results($c1sql);	
	foreach($results as $xelement) {
		echo '<div id="' . $xelement->tblname . '" class="tabcontent">';
		echo "<P>";
	
		// in here we have child rows to see if we have data
			$vc_entity = $xelement->entity;
			//show all database fields in table
			$hsql = "select a.entity_desc, a.entity_name, a.entity_attrib, a.entity_format, " .
				" b.entity_order from " . $wpdb->base_prefix . "eav_attrib a, " . $wpdb->base_prefix . "eav_layout b " .
				" where a.entity_attrib=b.entity_attrib and b.entity = " . $vc_entity . ' order by b.entity_order' ;
			$results = $wpdb->get_results($hsql);
			echo '<div class="horizontal-snap">'; // this makes row horizontal scrollable
			echo '<table style="margin-left: auto; margin-right: auto; width: 80%; border: 1px solid black" id="myTable" >';
			echo '<tr >';
			$colid = 0;
			foreach($results as $element) {
				$int = strlen(sprintf($element->entity_format, ""));
				//echo '<th style="width: ' . $int . 'px; border: 1px solid black"; onclick="eav_sortTable(' . $colid . ')">' . esc_html($element->entity_desc) . '</th>';
				echo '<th style="border: 1px solid black"; onclick="eav_sortTable(' . $colid . ')">' . esc_html($element->entity_desc) . '</th>';
				$colid = $colid + 1;
			}
			echo '</tr>';
			
			$new_row = 0;
			
			$dcsql = "select a.entity,  a.entity_id, a.entity_attrib, a.val_char, a.parent_entity, a.parent_entity_id, b.entity_order  " .
			" from " . $wpdb->base_prefix . "eav_entity a, " . $wpdb->base_prefix . "eav_layout b " .
			" where a.entity_attrib=b.entity_attrib and b.entity = " . $vc_entity . 
			" and a.parent_entity = " .  $v_entity . " and a.parent_entity_id = " . $v_id . " and a.entity_type = 0 " .
			" and a.entity_attrib=b.entity_attrib and a.entity=b.entity " . ' order by entity_id,b.entity_order' ;

	
			$sql="select max(entity_order) as max from " . $wpdb->base_prefix . "eav_layout where entity = " .  $vc_entity;
			$result_tbl =$wpdb->get_row($sql);
			$max_col = $result_tbl->max;
	
			$results = $wpdb->get_results($dcsql);
			$colno = 1;
			echo "<tr>";
			foreach($results as $element) {
				if (( $new_row <> sanitize_text_field($element->entity_id)) && ( $new_row <> 0)) {
					//finish out row if no data
					for($ii = $colno; $ii < $max_col; $ii++) {
						echo '<td style="border: 1px solid black"></td>';
					}
					/* new row */
					$colno = 1;
					echo "</tr>\r\n";
					echo '<tr>';
				} else if (($colno == 1)&& ($new_row == 0)) { // first time
					//
				}
				$new_row = sanitize_text_field($element->entity_id);
				$newcol = sanitize_text_field($element->entity_order);
				if ($newcol != ($colno +1)) {
					// missing columns
					for($ii = ($colno +1); $ii < $newcol; $ii++)
						echo '<td style="border: 1px solid black"></td>';
				}					
				echo '<td style="border: 1px solid black">';
				echo esc_html($element->val_char) . '</td>';
				$colno = $newcol;
			}
			//Finish out last row
			for($ii = $colno; $ii < $max_col; $ii++)
					echo '<td style="border: 1px solid black"></td>';
	
			echo "</tr></table>\r\n";
			echo '</div>'; // end of this makes row horizontal scrollable
				
		
		echo "</div>";
		
	}
	echo '<P><a href="javascript:history.back()">Go Back</a>';
}


add_shortcode('eav_field','eav_field');
add_shortcode('eav_startadd', 'eav_startadd');
add_shortcode('eav_endadd', 'eav_endadd');
add_shortcode('eav_subrec','eav_subrec');
add_shortcode('eav_add','eav_add');
add_shortcode('eav_tbl','eav_tbl');
add_shortcode('eav_apache','eav_apache');


?>