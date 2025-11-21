<?php error_reporting(0);
	date_default_timezone_set('Africa/Lagos');
	include_once("db-dtl.php");
	include_once("bc-mailer.php");
	include_once("email-design.php");

	$connection = mysqli_connect($mySqlServer,$mySqlUser,$mySqlPass);
	
	if($connection){
		if(mysqli_query($connection,"CREATE DATABASE IF NOT EXISTS ".$mySqlDBName)){
			/*echo "DB Created Successfully";*/
		}
	}else{
		/*echo mysqli_connect_error($connection);*/
	}
	
	$db_connection_check = mysqli_connect($mySqlServer,$mySqlUser,$mySqlPass,$mySqlDBName);
	if($db_connection_check){
		$connection_server = mysqli_connect($mySqlServer,$mySqlUser,$mySqlPass,$mySqlDBName);
	}else{
		/*echo mysqli_connect_error($db_connection_check);*/
	}

    // Define the web host
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $protocol = "https://";
    } else {
        $protocol = "http://";
    }
    $web_http_host = $protocol . $_SERVER['HTTP_HOST'];
	
	$get_requested_website_domain_url = $_SERVER["HTTP_HOST"];
	$explode_requested_website_domain_url = array_filter(explode(".",trim($get_requested_website_domain_url)));
	if(in_array("www", $explode_requested_website_domain_url)){
		if(isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")){
			header("Location: https://".ltrim("www.",$_SERVER["HTTP_HOST"]));
		}else{
			header("Location: http://".ltrim("www.",$_SERVER["HTTP_HOST"]));
		}
	}
?>