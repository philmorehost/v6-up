<?php
	$db_json_dtls = array("server" => "localhost:3306", "user" => "v6data_vendor", "pass" => "1122@EBEN.COM", "dbname" => "v6data_vendor");
	$db_json_encode = json_encode($db_json_dtls,true);
	$db_json_decode = json_decode($db_json_encode,true);
?> 