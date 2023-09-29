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
        <link rel="SHORTCUT ICON" href="'.GocContextPath::getPath().'img/Logo-1.4-FavIcon-32x32.regional.ico" />
        <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.3/moment-with-locales.min.js" integrity="sha512-vFABRuf5oGUaztndx4KoAEUVQnOvAIFs59y4tO0DILGWhQiFnFHiR+ZJfxLDyJlXgeut9Z07Svuvm+1Jv89w5g==" crossorigin="anonymous"></script>
        <script type="text/javascript" src="'.GocContextPath::getPath().'javascript/bootstrap/js/bootstrap-3.1.min.js"></script>
        <script type="text/javascript" src="'.GocContextPath::getPath().'javascript/bootstrap/js/bootstrap-select.min.js"></script>
        <script type="text/javascript" src="'.GocContextPath::getPath().'javascript/datetimepicker/js/bootstrap-datetimepicker.min.js"></script>
        <script type="text/javascript" src="'.GocContextPath::getPath().'javascript/jquery-validation/jquery.validate.min.js"></script>
        <script type="text/javascript" src="'.GocContextPath::getPath().'javascript/jquery-validation/additional-methods.min.js"></script>
        <script type="text/javascript" src="'.GocContextPath::getPath().'javascript/tablesorter/jquery.tablesorter.js"></script>
        <link rel="stylesheet" href="'.GocContextPath::getPath().'javascript/bootstrap/css/bootstrap.css" />
        <link rel="stylesheet" href="'.GocContextPath::getPath().'javascript/datetimepicker/css/bootstrap-datetimepicker.min.css" />
        <link rel="stylesheet" href="'.GocContextPath::getPath().'javascript/bootstrap/css/bootstrap-select.min.css" />
        <link rel="stylesheet" type="text/css" href="'.GocContextPath::getPath().'css/web_portal.php" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=PT+Sans">
    <link rel="stylesheet" type="text/css" href="'.GocContextPath::getPath().'css/multiple-select.css"/>
    </head>
    <body>';

    return $header;
}
