<?php
$authEnt = $params['authEnt'];
$site = $params['site'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Delete API Authentication Credential</h1><br/>
    <p>
    You are about to delete the following API authorisation credential:<br/>
    Credential identifier: <b><?php xecho($authEnt->getIdentifier());?><br/></b>
    Credential type: <b><?php xecho($authEnt->getType());?><br/></b>
    Site Name: <b><?php xecho($site->getName());?><br/></b>
    </p>
    <p>
        Are you sure you wish to continue?
    </p>

    <form class="inputForm" method="post" action="index.php?Page_Type=Delete_API_Authentication_Entity&authentityid=<?php echo $authEnt->getId();?>" name="removeAPIAuthenticationEntity">
        <input class="input_input_hidden" type="hidden" name="UserConfirmed" value="true" />
        <input type="submit" value="Remove this credential from GOCDB" class="input_button">
    </form>

</div>
