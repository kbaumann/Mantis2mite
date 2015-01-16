<?php
############	
# VARS 
#######
	global $g_plugin_cache;	

/*
 * @local resources/objects
 */	
	$r_result = $o_userMiteData = null;
/**
 * @local array contains all configurable values
 */		
	$a_projectBindedRsrces = $a_projectUnbindedRsrces = $a_selectBoxesNewTimeEntry = array();
	
/*
 * @local strings
 */	
	$s_query = $s_output = $s_unbindedRsrces = '';
	
/*
 * @local int
 */	
	$i_userId = $i_bugId = $i_projectId = 0;
	
############	
# ACTION 
#######
	$o_pluginController = $g_plugin_cache['Mantis2mite'];
	$i_userId = $o_pluginController->getCurrentUserId();
	$i_bugId = $_GET['bug_id'];
	$i_mantisProjectId = $_GET['project_id'];
	
	$o_userMiteData = $o_pluginController->getMiteUserData();
	
	$a_projectBindedRsrces = $o_userMiteData->getBindedRsrcesForMantisProject($i_mantisProjectId);
	$a_projectUnbindedRsrces = $o_userMiteData->getUnbindedRsrcesForMantisProject($i_mantisProjectId);

    ################## added start###################
    $mite = mite::getInstance();
    $mite->init('################','####');
    $responseXML = $mite->sendRequest('get','/projects.xml');
    $mite_projects = $responseXML->xpath('project');

    // get mite customer id from mantis project mapping fields
    # get field id of Mite Mapping
    $s_query = "SELECT id FROM ".db_get_table('mantis_custom_field_table')." WHERE name='MiteId1'";
    $r_result = db_query_bound($s_query);
    if ((db_num_rows($r_result) > 0)) {
        while ($a_row = db_fetch_array($r_result)) {
            $MiteId1_field_id = $a_row["id"];
        }
    }

    # get field id of Mite Mapping
    $s_query = "SELECT id FROM ".db_get_table('mantis_custom_field_table')." WHERE name='MiteId2'";
    $r_result = db_query_bound($s_query);
    if ((db_num_rows($r_result) > 0)) {
        while ($a_row = db_fetch_array($r_result)) {
            $MiteId2_field_id = $a_row["id"];
        }
    }

    # get mite id mapping from custom field table
    $s_query = "SELECT * FROM ".db_get_table('mantis_custom_field_project_table')." WHERE project_id=".$i_mantisProjectId.
        " and (field_id = ".$MiteId1_field_id." or field_id = ".$MiteId2_field_id.")";
    $r_result = db_query_bound($s_query);
    $MiteId1 = $MiteId2 = 0;
    if ((db_num_rows($r_result) > 0)) {
        while ($a_row = db_fetch_array($r_result)) {
            if($a_row["field_id"]== $MiteId1_field_id){
                $MiteId1 = $a_row["sequence"];
            }else if($a_row["field_id"]== $MiteId2_field_id){
                $MiteId2 = $a_row["sequence"];
            }
        }
    }
    $MiteCustId = $MiteId1.$MiteId2;
    ##################### added end ##########################


    # build select box entries from binded resources
    foreach ($a_projectBindedRsrces as $s_type => $a_miteRsrces) {

        ####################### changed start #######################
        if($s_type == "projects"){
            foreach ($a_miteRsrces as $i_rsrc_id => $a_rsrc) {
                // filter binded Projects
                # loop through all mite projects, search after mite project id with given mite customer id
                foreach($mite_projects as $mite_project){
                    $mite_project = (array) $mite_project;
                    if($mite_project["id"] == $i_rsrc_id && $mite_project["customer-id"] == $MiteCustId){
                        $a_selectBoxesNewTimeEntry[$s_type] .=  "<option value='".$i_rsrc_id."'>".$a_rsrc['name']."</option>";
                    }
                }
            }
        }

        if($s_type == "services"){
            foreach ($a_miteRsrces as $i_rsrc_id => $a_rsrc) {
                $a_selectBoxesNewTimeEntry[$s_type] .=  "<option value='".$i_rsrc_id."'>".$a_rsrc['name']."</option>";
            }
        }
        ####################### changed end #######################
	}
	
    # add unbinded resources as select box entries if any
	foreach ($a_projectUnbindedRsrces as $s_type => $a_miteRsrces) {
		$s_unbindedRsrces = '';

        ####################### changed start #######################
        if($s_type == "projects"){
            foreach ($a_miteRsrces as $i_miteRsrc_id => $a_rsrc) {
                # loop through all mite projects, search after mite project id with given mite customer id
                foreach($mite_projects as $mite_project){
                    $mite_project = (array) $mite_project;

                    if($mite_project["id"] == $i_miteRsrc_id && $mite_project["customer-id"] == $MiteCustId){
                        $s_unbindedRsrces .= "<option value='$i_miteRsrc_id'>".$a_rsrc['name']."</option>";
                    }
                }

            }
        }
        if($s_type == "services"){
            foreach ($a_miteRsrces as $i_miteRsrc_id => $a_rsrc) {
               $s_unbindedRsrces .= "<option value='$i_miteRsrc_id'>".$a_rsrc['name']."</option>";
            }
        }
        ####################### changed end #######################

        if (!empty($a_projectBindedRsrces[$s_type])) {
			$a_selectBoxesNewTimeEntry[$s_type] .= 
				"<optgroup label='".lang_get('plugin_mite_other_'.$s_type)."'>".
					$s_unbindedRsrces.
				"</optgroup>";
		}else {
			$a_selectBoxesNewTimeEntry[$s_type] .= $s_unbindedRsrces;
		}
	}
	
# wrap the available entries with the HTML select tag	
	$a_selectBoxesNewTimeEntry[Mantis2mitePlugin::API_RSRC_P] = "
			<select name='plugin_mite_".Mantis2mitePlugin::API_RSRC_P."_new_time_entry' 
					id='plugin_mite_".Mantis2mitePlugin::API_RSRC_P."_new_time_entry'>".
				$a_selectBoxesNewTimeEntry[Mantis2mitePlugin::API_RSRC_P].
			"</select>";
				
# wrap the available entries with the HTML select tag	
	$a_selectBoxesNewTimeEntry[Mantis2mitePlugin::API_RSRC_S] = "
			<select name='plugin_mite_".Mantis2mitePlugin::API_RSRC_S."_new_time_entry' 
					id='plugin_mite_".Mantis2mitePlugin::API_RSRC_S."_new_time_entry'>".
				$a_selectBoxesNewTimeEntry[Mantis2mitePlugin::API_RSRC_S].
			"</select>";				

    if (count($a_projectBindedRsrces[Mantis2mitePlugin::API_RSRC_P]) == 1) {
    	
	# dirty...	
    	$i_bindedMiteProject_id = 
			current($a_projectBindedRsrces[Mantis2mitePlugin::API_RSRC_P]);
		$i_bindedMiteProject_id = $i_bindedMiteProject_id['mite_project_id'];	
			
		$a_selectBoxesNewTimeEntry[Mantis2mitePlugin::API_RSRC_P] =  
			
			$a_projectBindedRsrces[Mantis2mitePlugin::API_RSRC_P][$i_bindedMiteProject_id]['name'] ."
			<input type='hidden' name='plugin_mite_projects_new_time_entry' 
				    value='".$i_bindedMiteProject_id."' id='plugin_mite_projects_new_time_entry' />";
    }
    
# add the services select list to the output
###########################################			
	$s_output .= " 
		<fieldset><legend>".lang_get('plugin_mite_header_new_time_entry')."</legend>
			<div class='time_entry_param'>
				<label for='plugin_mite_date_new_time_entry'>".
					lang_get('plugin_mite_header_date_new_time_entry')."
				</label>
				<input type='text' name='plugin_mite_date_new_time_entry'
					   id='plugin_mite_date_new_time_entry' value='".date('Y-m-d')."' />
			</div>
			<div class='time_entry_param'>
                    <label for='plugin_mite_hours_new_time_entry'>".
        lang_get('plugin_mite_header_hours_new_time_entry')."
                    </label>
                    <input type='text' name='plugin_mite_hours_new_time_entry'
                           id='plugin_mite_hours_new_time_entry' autocomplete='off' value='0:00'/>
			</div>
			<div class='time_entry_param'>
				<label for='plugin_mite_projects_new_time_entry'>".
					lang_get('plugin_mite_header_projects_new_time_entry')."
				</label>".
				$a_selectBoxesNewTimeEntry[Mantis2mitePlugin::API_RSRC_P] ."
			</div>
			<div class='time_entry_param'>
				<label for='plugin_mite_services_new_time_entry'>".
					lang_get('plugin_mite_header_services_new_time_entry')."
				</label>".
				$a_selectBoxesNewTimeEntry[Mantis2mitePlugin::API_RSRC_S] . "
			</div>
			<div class='time_entry_param'>
				<label for='plugin_mite_note_new_time_entry'>".
        lang_get('plugin_mite_header_note_new_time_entry')."
				</label>
				<input type='text' name='plugin_mite_note_new_time_entry'
					   id='plugin_mite_note_new_time_entry' autocomplete='off' value='".
        stripslashes(Mantis2mitePlugin::replacePlaceHolders('{bug_id} {bug_summary}',
            $i_bugId))."' />
			</div>

			<div class='formularButtons'>

				<div class='buttonsLeft'>
					<button type='submit' id='plugin_mite_add_new_time_entry'>".
						lang_get('plugin_mite_add_new_time_entry') ."
					</button>
				</div>
				<div class='buttonsRight'>
					<a href='#' id='plugin_mite_cancel_adding_time_entry'>".
						lang_get('plugin_mite_cancel_adding_time_entry') ."
					</a>
				</div>
			</div>
		</fieldset>";
	
	echo $s_output;
?>