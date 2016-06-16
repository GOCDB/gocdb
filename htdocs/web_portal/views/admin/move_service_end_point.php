<div class="rightPageContainer">
    <form name="Move_Service" action="index.php?Page_Type=Admin_Move_SEP" method="post" class="inputForm">
        <h1>Move Service</h1>
        <br />
        
        <span class="input_name">New site for selected services</span>
        <select class="add_edit_form" name="NewSite">
            <?php
            foreach($params['Sites'] as $Site) {
                echo "<option value=\"". $Site->getId() . "\">" . xssafe($Site->getShortName()). "</option>";
            }	
            ?>
        </select>
        
        <span class="input_name">Please select the service(s) to be moved from 
            <?php xecho($params['OldSite'])?></span>
        <select class="Downtime_Select" name="Services[]" size="20" 
         multiple id="Sites" style="margin-left: 0em; width: 38em;">
            <?php
                foreach($params['Services'] as $sep) {
                echo "<option value=\"". $sep->getId() . "\">" . xssafe($sep->getHostName()). "</option>";
            }	
            ?>
        </select>
        
        <br>
        <input class="input_button" type="submit" value="Move Service" />
    </form>
</div>