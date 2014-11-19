<div class="rightPageContainer">
	 <form name="Move_Site" action="index.php?Page_Type=Admin_Move_Site" method="post" class="inputForm">
    	<h1>Move Site</h1>
    	<br />
    	
    	<span class="input_name">Please select the NGI from which you wish to move sites</span>
    	<select class="add_edit_form" name="OldNGI">
    		<?php
    		foreach($params['Ngis'] as $NGI) {
                echo "<option value=\"". $NGI->getId() . "\">" . $NGI->getName(). "</option>";
    		}	
    		?>
    	</select>
    	
    	<input class="input_button" type="submit" value="Move Sites from this NGI" />
    </form>
</div>