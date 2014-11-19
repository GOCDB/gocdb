<?php 
function get_standard_header($title = null) {

	$header = '<!doctype html>
	<html>
	    <head>
			<meta http-equiv="X-UA-Compatible" content="IE=edge" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	if(!is_null($title)) {
		$header .= "<title>$title</title>";
	} else {
		$header .= "<title>GOCDB</title>";
	}
	$header .= '
		<link rel="SHORTCUT ICON" href="img/Logo-1.4-FavIcon-32x32.regional.ico" />        
        <script type="text/javascript" src="javascript/jquery/jquery-1.10.min.js"></script>
        <script type="text/javascript" src="javascript/moment/moment.min.js"></script>
        <script type="text/javascript" src="javascript/bootstrap/js/bootstrap-3.1.min.js"></script>
        <script type="text/javascript" src="javascript/datetimepicker/js/bootstrap-datetimepicker.min.js"></script>
	    <script type="text/javascript" src="javascript/jquery-validation/jquery.validate.min.js"></script>
	    <script type="text/javascript" src="javascript/jquery-validation/additional-methods.min.js"></script>
        <link rel="stylesheet" href="javascript/bootstrap/css/bootstrap.css" />
        <link rel="stylesheet" href="javascript/datetimepicker/css/bootstrap-datetimepicker.min.css" />
	    <link rel="stylesheet" type="text/css" href="css/web_portal.css" />	    
	</head>
	<body>';
		
	return $header;
}