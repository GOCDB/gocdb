<?php
require_once __DIR__ . '/../../../lib/Gocdb_Services/Factory.php';
header("Content-type: text/css");
// Load variable values from local configuration //
\Factory::getConfigService()->setLocalInfoOverride($_SERVER['SERVER_NAME']);

$background_direction = \Factory::getConfigService()->getBackgroundDirection();
$background_colour1 = \Factory::getConfigService()->getBackgroundColour1();
$background_colour2 = \Factory::getConfigService()->getBackgroundColour2();
$background_colour3 = \Factory::getConfigService()->getBackgroundColour3();
$header_text_colour = \Factory::getConfigService()->getHeadingTextColour();

?>
/* table.sorter plugin, http://tablesorter.com/docs/ */
table.tablesorter {
    background-color: #CDCDCD;
    margin:10px 0pt 15px;
    width: 100%;
    text-align: left;
}
table.tablesorter thead tr th, table.tablesorter tfoot tr th {
    background-color: #E6EEEE;
    border: 1px solid #FFF;
    padding: 4px;
}
table.tablesorter thead tr .header {
    background-image: url(../img/bg.gif);
    background-repeat: no-repeat;
    background-position: center right;
    cursor: pointer;
}
table.tablesorter tbody td {
    color: #3D3D3D;
    padding: 4px;
    background-color: #FFF;
    vertical-align: top;
}
table.tablesorter tbody tr.odd td {
    background-color:#F0F0F6;
}
table.tablesorter thead tr .headerSortUp {
    background-image: url(../img/asc.gif);
}
table.tablesorter thead tr .headerSortDown {
    background-image: url(../img/desc.gif);
}
table.tablesorter thead tr .headerSortDown, table.tablesorter thead tr .headerSortUp {
    background-color: #8DBDD8;
}



body {
    background: linear-gradient(
    <?php
        // Build the linear gradient input
        $out = '';
        if ($background_colour3 != '') {
            $out = ','.$background_colour3;
        }
        if ($background_colour2 != '') {
            $out = ','.$background_colour2 . $out;
        }
        if ($background_colour1 != '') {
            $out = ','.$background_colour1 . $out;
        }
        $out = $background_direction . $out;

        echo $out;
    ?>
    );
    color: #272A4B;
    font-family: 'PT Sans', sans-serif;
    font-size: 10pt;
    text-align: center;
    text-align: left;
    height: 100%;
    margin: 0;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

html {
    height: 100%;
}

a img {
    border: none;
}

h1,th,h2,h3,h4 {
    color: <?=$header_text_colour?>;
    text-decoration: none;
    font-weight:normal;
    margin-top: 0em;
    margin-bottom: 0.2em;
    font-family: 'PT Sans', sans-serif;
}

a {
    color: #103C7A;
    text-decoration: none;
    margin-top: 0em;
    margin-bottom: 0em;
}

h4 {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}

h3 {
    padding-bottom: 1em;
    font-size: 1.5em;
    margin-top: 0em;
    border-top: 0em;
    padding-top: 0em;
}

h4 {
    padding-bottom: 1em;
    font-size: 1em;
    margin-top: 0em;
    border-top: 0em;
    padding-top: 0em;
    font-weight: normal;
}

h3.Standard_Padding {
    margin: 0em;
}

h1 {
    font-size: 2.5em;
}



/*
    *	Containing DIV's
	*	Page Container is the whole page
	*	Left Box is the menu
	*	Right Box is the smaller page contents
	*/
.page_container {
    position: relative;
    margin-left: auto;
    margin-right: auto;
    width: 83em;
    min-width: 45em;
    overflow: hidden;
    z-index: 1;
    background-color: transparent;
}

/* Credit for this section of CSS goes to Matt Lira of STFC, 2009 */
.left_box_menu {
    float: left;
    margin: 0.5em 0.5em 0.5em 0.5em;
    padding: 0.5em 0.5em 0.5em 0.5em;
    border: 1px solid #B4B4B4;
    width: 13em;
    background-color: #FCFCFC;
    border-radius: 0.4em;
}

div.Left_Search_Box {
    clear: left;
    margin-top: 0em;
}

input.Search {
    width: 85%;
    margin-left: 1em;
    margin-bottom: 0.5em;
}

input.Search_Button {
    margin-left: 1em;
    margin-bottom: 0.5em;
}

div.Left_User_Status_Box {
    clear: left;
    margin-top: 0em;
}

div.Left_Logo_Box {
    float: left;
    clear: left;
}

div.Left_Logo_Row {
    height: 25px;
    text-align: center;
    margin-bottom: 5px;
}

a.Sponsor_Link img.Sponsor_Logo {
    display:inline-block;
    vertical-align: bottom;
    height: 100%;
}

a.Sponsor_Link:hover {
    text-decoration: none;
}

div.Indented {
    margin-left: 1em;
}

.right_box {
    position: relative;
    margin: 0.0em 0.0em 0.0em 13em;
    padding: 0.5em 1em 0.0em 1em;
}

.empty {
    clear: both;
}

.Logo_Text {
    display: inline;
}

.logo_image {
    display: inline;
    height: 3em;
    text-align: center;
}

h3.Small_Bottom_Margin {
    margin-bottom: 0.25em;
    padding-bottom: 0.25em;
}

h4.menu_title {
    margin-bottom: 0.25em;
    padding-bottom: 0.25em;
    padding-left: 0.5em;
}

/* Input Form Configuration */
.inputForm {
    display: inline;
}

.input_button {
    margin-top: 0.5em;
    font-size: 0.7em;
}

.input_name {
    display: block;
    font-size: 0.9em;
}

.input_syntax {
    display: inline;
    color: #909090;
    font-style: italic;
    font-size: 0.9em;
}

span.menu_link {
    display: inline;
    font-size: 1em;
    margin-bottom: 0.5em;
    color: #103C7A;
    border: 0.1em solid #FFFFFF;
}


hr.Menu_Spacer {
    position: relative;
    width: 11em;
    left: -1.5em;
}

ul.Smaller_Top_Margin {
    margin-top: 0em;
}

ul.Smaller_Left_Padding {
    padding-left: 2em;
    margin-bottom: 0.5em;
}

li {
    color: #3E6397;
    list-style-type: circle;
    margin-left: -0.4em;
    margin-bottom: 0.3em;
}

.input_input_text,
.input_input_date,
.input_input_check {
    margin-left: 2em;
}

.input_input_text,
.input_input_date {
    width: 90%;
    margin-bottom: 1em;
}

.input_input_date {
    width: 30%;
}

.table_row_1,.table_header {
    background-color: #D3D3D3;
}

.site_table_row_1 {
    background-color: #D0E1F7;
}

.site_table_row_2 {
    background-color: #F5F5F5;
}

td.site_table {
    padding-left: 1em;
    padding-top: 0.5em;
    padding-bottom: 0.5em;
}

td.site_table_nopad {
    padding-left: 1em;
    padding-top: 0em;
    padding-bottom: 0em;
}

th.site_table {
    padding-left: 1em;
    padding-top: 0.5em;
    padding-bottom: 0.5em;
    color: black;
    font-weight: bold;
}

.start_page_body {
    line-height: 1.5em;
}

td {
    padding: 0.2em;
}

span.action {
    margin-left: auto;
    margin-right: auto;
    padding: 0em 0.25em 0em 0.25em;
    text-align: center;
}

h3.spacer {
    display: inline;
    width: 4em;
}

div.Copyright_Text {
    margin-top: 2em;
    font-size: 0.8em;
    margin-left: -2em;
}


.add_edit_form {
    margin-left: 2em;
    margin-bottom: 1em;
    width: 90%;
}

span.Error {
    position: relative;
    left: -1.5em;
}

span.New_Service_Endpoint {
    padding-bottom: 1em;
    display: block;
}

option.sectionTitle {
    background-color: #000000;
    font-weight: bold;
    font-size: 12px;
    color: white;
    text-align: center;
}

/* ################## CALENDAR CSS DIVS  ###################### */
	/* calendar icon */
img.tcalIcon {
    cursor: pointer;
    margin-left: 1px;
    vertical-align: middle;
}

/* calendar container element */
div#tcal {
    position: absolute;
    visibility: hidden;
    z-index: 100;
    width: 158px;
    padding: 2px 0 0 0;
}

/* all tables in calendar */
div#tcal table {
    width: 100%;
    border: 1px solid silver;
    border-collapse: collapse;
    background-color: white;
}

/* navigation table */
div#tcal table.ctrl {
    border-bottom: 0;
}

/* navigation buttons */
div#tcal table.ctrl td {
    width: 15px;
    height: 20px;
}

/* month year header */
div#tcal table.ctrl th {
    background-color: white;
    color: black;
    border: 0;
}

/* week days header */
div#tcal th {
    border: 1px solid silver;
    border-collapse: collapse;
    text-align: center;
    padding: 3px 0;
    font-size: 10px;
    background-color: gray;
    color: white;
}

/* date cells */
div#tcal td {
    border: 0;
    border-collapse: collapse;
    text-align: center;
    padding: 2px 0;
    font-size: 11px;
    width: 22px;
    cursor: pointer;
}

/* date highlight
   in case of conflicting settings order here determines the priority from least to most important */
div#tcal td.othermonth {
    color: silver;
}

div#tcal td.weekend {
    background-color: #ACD6F5;
}

div#tcal td.today {
    border: 1px solid red;
}

div#tcal td.selected {
    background-color: #FFB3BE;
}

/* iframe element used to suppress windowed controls in IE5/6 */
iframe#tcalIF {
    position: absolute;
    visibility: hidden;
    z-index: 98;
    border: 0;
}

/* transparent shadow */
div#tcalShade {
    position: absolute;
    visibility: hidden;
    z-index: 99;
}

div#tcalShade table {
    border: 0;
    border-collapse: collapse;
    width: 100%;
}

div#tcalShade table td {
    border: 0;
    border-collapse: collapse;
    padding: 0;
}

select.Downtime_Select {
    margin-left: 2em;
}

.header {
    color: <?=$header_text_colour?>;
    padding:0.9em;
}

div.listContainer {
    width: 99.5%;
    float: left;
    border: 1px solid #B4B4B4;
    margin-right: 10px;
    margin-top: 1.6em;
    box-shadow: 2px 2px 1px 0px #C5D0E3;
    border-radius: 0.4em;
}

span.listHeader {
    vertical-align: middle;
    float: left;
    padding-top: 0.9em;
    padding-left: 1em;
}

.leftFloat {
    float: left;
}

span.vSiteDescription {
    clear: both;
    float: left;
    padding-bottom: 0.4em;
}

span.vSitesMoreInfo {
    clear: both;
    float: left;
}

img.decoration {
    float: right;
    margin:1% 1% 1% 0%;
    height: 25px;
}

img.titleIcon{
    height:25px;
    float: right;
    margin:2% 1% 1% 0%;
}

input.vSiteSearch {
    color: grey;
    float: left;
    margin-left: 1em;
    margin-top: 0.5em;
    margin-bottom: 0.5em;
    width: 95%;
    clear: left;
}

input.vSiteSearchButton {
    float: left;
    margin-left: 1em;
    margin-bottom: 1em;
}

div.vSiteSearchResultContainer {
    overflow: auto;
    max-height: 20em;
    float: left;
    width: 100%;
    display: none;
    border-top: 1px grey solid
}

span.vSite1emBottomMargin {
    padding-bottom: 1em;
}

table.vSiteResults {
    clear: both;
    width: 100%;
}

/* Top page logo */
img.pageLogo {
    height: 60px;
    margin-right: 1.5em;
}

div.rightPageHolder {
    overflow: hidden;
    margin-top: 1em;
}

span.vSiteNotice {
    clear: both;
    display: block;
    margin-top: 2em;
    float: left;
}

img.centered {
    display: block;
    margin-left: auto;
    margin-right: auto;
}

span.topMargin {
    margin-top: 1em;
}

span.block {
    display: block;
}

form.inline {
    display: inline;
}

div.topMargin {
    padding-top: 1em;
}

div.topMargin2 {
    margin-top: 2em;
}

div.siteContainer {
    width:99.5%;
    float: left;
    clear: both;
    border: 1px solid #B4B4B4;
    margin-right: 0px;
    margin-top: 1.3em;
    padding: 1em;
    box-shadow: 1px 1px 1px 0px #C5D0E3;
    border-radius: 0.4em;
}

div.clearLeft {
    clear: left;
}

div.siteFilter {
    margin-left: 1em;
}

.leftMargin {
    margin-left: 1em;
}

.middleAlign {
    vertical-align: middle;
}

/* NGI logo (usually a flag) */
img.flag {
    height: 25px;
    width: 38px;
}

.middle {
    vertical-align: middle;
}

img.nav {
    height: 30px;
    width: 30px;
}

div.rightPageContainer {
    background: #FCFCFC;
    background-image: url('../img/contentBackground2.png');
    background-position: top right;
    background-repeat: no-repeat;
    overflow: hidden;
    margin-bottom: 3em;
    border: 1px solid #B4B4B4;
    padding: 1em;
    border-radius: 0.4em;

}

div.tableContainer {
    box-shadow: 2px 2px 1px 0px #C5D0E3;
    border: 1px solid #B4B4B4;
    border-radius: 0.4em;
}

.rounded {
    border-radius: 0.5em;
}

.datePicker{
    width:30%;
    float:left;
    margin-right:5%
}

.timePicker{
    width:30%;
    float:left;
}

.smallLabelText{
    margin-left:8px;
    font-size:11px;
    font-weight: normal;
    font-style:italic;
    display:inline-block;
}


label.error {
    font-weight: bold;
    color: red;
    padding: 2px 8px;
    margin-top: 2px;
}

.selectService{
    background-color: #D5D5D5;
}

.selectEndpoint{
}

/* Registration and User pages */
img.new_window{
  height: 1em;
}

img.person{
  height: 25px;
  vertical-align: middle;
  padding-right: 1em;
}
