<?php
  include("func/bc-connect.php");
    
  //Select vendor_2 Table
	$select_vendor_2_table = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_vendors WHERE website_url='".$_SERVER["HTTP_HOST"]."' LIMIT 1"));
	if(($select_vendor_2_table == true) && ($select_vendor_2_table["website_url"] == $_SERVER["HTTP_HOST"]) && ($select_vendor_2_table["status"] == 1)){
        $vendor_2_account_details = $select_vendor_2_table;
    }else{
        $vendor_2_account_details = "";
    }
    
  //CSS Template Update
	$css_style_template_location = "index-bc-style-template-1.php";
	$select_vendor_2_style_template = mysqli_query($connection_server, "SELECT * FROM sas_vendor_style_templates WHERE vendor_id='".$vendor_2_account_details["id"]."'");
	if(mysqli_num_rows($select_vendor_2_style_template) == 1){
        $get_vendor_2_style_template = mysqli_fetch_array($select_vendor_2_style_template);
		$style_template_name = explode(".",trim($get_vendor_2_style_template["template_name"]))[0];
		if(!empty($style_template_name)){
			$style_template_location = "index-".$style_template_name.".php";
			if(file_exists($style_template_location)){
				$css_style_template_location =  $style_template_location;
			}
		}
	}
    include($css_style_template_location);
?>