<div class="rightPageContainer">
    <form name="Move_Site" action="index.php?Page_Type=Admin_Move_Site" method="post" class="inputForm">
    	<h1>Move Site</h1>
    	<br />
    	
    	<span class="input_name">New NGI for selected sites</span>
    	<select class="add_edit_form" name="NewNGI">
    		<?php
    		foreach($params['Ngis'] as $NGI) {
                echo "<option value=\"". $NGI->getId() . "\">" . $NGI->getName(). "</option>";
    		}	
    		?>
    	</select>
    	
    	<span class="input_name">Please select the site(s) to be moved from 
    	    <?php echo $params['OldNgi']?></span>
		<select class="Downtime_Select" name="Sites[]" size="20" 
    	 multiple id="Sites" style="margin-left: 0em; width: 38em;">
            <?php
                foreach($params['sites'] as $site) {
                echo "<option value=\"". $site->getId() . "\">" . $site->getShortName(). "</option>";
    		}	
            ?>
        </select>
    	
    	<br>
    	<input class="input_button" type="submit" value="Move Site" />
    </form>
</div>