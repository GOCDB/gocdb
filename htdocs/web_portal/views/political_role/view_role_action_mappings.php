<!--
/*
 * Copyright (C) 2015 STFC
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
--> 

<div class="rightPageContainer">
    <div style="float: left; text-align: center;">
        <img src="<?php echo \GocContextPath::getPath() ?>img/gocdbRoles.jpg" style="width: 140px; height: 200px;" /> <!-- user.png class="pageLogo"-->
    </div>
    <div style="float: right;">
        <h1 style="float: left; margin-left: 0em; padding-bottom: 0.3em;">
            Role Action Mappings
        </h1>
	<span style="clear: both; float: left; padding-bottom: 0.4em;">
            <br>
	    <ol>
		<li>In GOCDB, users request Roles over domain objects such as Projects, NGIs, Sites and ServiceGroups.</li>
		<li>A Role enables Actions on target objects (often, the target is the same as the domain object).</li>
		<li>Some Roles allow actions over different targets. </li>
		<li>The Role-Action mapping matrix is shown below.</li>
	    </ol> 
	</span>
    </div>
    <?php /*
    <div class="listContainer"> 
	<div style="float: left;">
	    <?php 
	     $allActionsOverByRoleType = $params['allActionsOverByRoleType'];
	     foreach($allActionsOverByRoleType as $actionsOverByRoleType => $actionsOverArray){
		 echo $actionsOverByRoleType.'<br>'; 
		 foreach($actionsOverArray as $actionsOver){
		     echo $actionsOver[0].' '.$actionsOver[1].'<br>'; 
		 }
	     }
	    ?>
	</div>
    </div>
     */ ?>
	    
    <div class="listContainer"> 
	<div>

	    <?php
	    $roleTypeActionTarget_byObjectType = $params['roleTypeActionTarget_byObjectType'];
	    $inc = 0; 
	    foreach ($roleTypeActionTarget_byObjectType as $over => $roleActionTargetArray) {
		++$inc; 
		?>
	    <h3><?php echo $over; ?> Roles</h3> 
    	    <table id="roleActionMapTable<?php echo($inc)?>" class="table table-striped table-condensed tablesorter">
    		<thead>
    		    <tr>
    			<th>RoleType</th>
    			<th>Action</th>
    			<th>On (Target of Action)</th>
    		    </tr>
    		</thead>
    		<tbody>
			<?php foreach ($roleActionTargetArray as $roleActionTarget) { ?>
			    <tr>
				<td><?php echo $roleActionTarget[0] ?></td>
				<td><?php echo $roleActionTarget[1] ?></td>
				<td><?php echo $roleActionTarget[2] ?></td>
			    </tr>	
			<?php } ?>
    		</tbody>
    	    </table>
	    <?php } ?>

	</div>

    </div>
 
</div>

<script>
   $(document).ready(function() 
    { 
        $("#roleActionMapTable1").tablesorter(); 
	 $("#roleActionMapTable2").tablesorter(); 
	  $("#roleActionMapTable3").tablesorter(); 
	   $("#roleActionMapTable4").tablesorter(); 
    } 
);  
</script>    
    

