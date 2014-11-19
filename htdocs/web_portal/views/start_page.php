<div class="rightPageContainer">
    <h1 class="startPage">Welcome to GOCDB</h1>
    <div class="start_page_body">
    GOCDB is the official repository for storing and presenting <a href="http://www.egi.eu" target="_blank">EGI</a>
    topology and resources information.


	<br/><br/>   
	<h2 class="startPage">What information is stored here?</h2>
    <br />

	The GOCDB data consists mainly of:
	<ul>
	<li class="no_border">Participating National Grid Initiatives (NGI)</li>
	<li class="no_border">Grid Sites providing resources to the infrastructure</li>
	<li class="no_border">Resources and services, including maintenance plans for these resources</li>
	<li class="no_border">Participating people, and their roles within EGI operations</li>
	</ul>
   Data are provided and updated by participating 
   NGIs, and are presented through this web portal.<br/><br/>
   
        Please note: 
        <ul>
          <li>It is a "catch-all" service. This means it is centrally hosted on behalf of all NGIs.</li>
          <li>If an organisation deploys and uses their own system or a local GOCDB installation, their data won't appear here.</li>
        </ul>  
   
	</div>
	<?php if(sizeof($params['roles']) > 0) { ?>
		<div class="alert alert-warning" style="width: 98%; margin-bottom:1%; float: left;">
                <span class="glyphicon glyphicon-asterisk"></span>   <b>Notification:</b> You have pending role requests - <a href="index.php?Page_Type=Role_Requests">Manage Roles</a>                   
        </div>
	<?php } ?>
    
    <!-- map Block -->
    <?php if($params['showMap']): ?> 
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false&key=<?php echo $params['googleAPIKey'];?>">
        </script>
        <!--This script provides the marker clustering functionality comment out the cluster line in googleSiteMap.js and this script to disable it-->
        <script type="text/javascript" src="javascript/googleMapClusterer.js">
        </script>
        <script type="text/javascript" src="javascript/googleSiteMap.js">
        </script> 
        <div style="display:inline-block;  ">
            <div id="GoogleMap" style="width:840px;height:400px;"></div>
        </div>
    <?php endif; ?>
</div>