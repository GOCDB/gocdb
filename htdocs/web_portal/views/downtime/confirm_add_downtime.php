<?php
$configService = \Factory::getConfigService();
$serviceWithEndpoints = $params['SERVICE_WITH_ENDPOINTS'];

//To reuse this page for 'add' and 'edit' we use this boolean to change a couple of bits in the page
if(isset($params['isEdit'])){
    $edit = true;
}else{
    $edit = false;
}

?>
<div class="rightPageContainer">
    <h1 class="Success">Confirm
    <?php if($edit){
        echo "Edit";
    }?>
     Downtime</h1><br />
    <?php
    echo '<p>';
    echo 'Please review your ';

    if (!($edit)) {
        if ($params['SINGLE_TIMEZONE']) {
            echo 'chosen site and the downtime ';
        } else {
            echo 'chosen sites and their downtimes ';
        }
    } else {
        echo 'downtime ';
    }

    echo 'before submitting.';
    echo '</p>';
    ?>

    <ul>
    <li><b>Severity: </b><?php xecho($params['DOWNTIME']['SEVERITY'])?></li>
    <li><b>Description: </b><?php xecho($params['DOWNTIME']['DESCRIPTION'])?></li>
    <?php /*<li><b>Times defined in: </b><?php xecho($params['DOWNTIME']['DEFINE_TZ_BY_UTC_OR_SITE'])?> timezone</li> */ ?>
    <li><b>Starting (UTC): </b>
    <?php
        //$startStamp = $params['DOWNTIME']['START_TIMESTAMP'];
        //$timestamp = new DateTime("@$startStamp"); //Little PHP magic to create date object directly from timestamp
        //echo date_format($timestamp, 'l jS \of F Y \a\t\: h:i A');
        xecho($params['DOWNTIME']['START_TIMESTAMP']);
    ?>
    </li>
    <li><b>Ending (UTC): </b>
    <?php
        //$endStamp = $params['DOWNTIME']['END_TIMESTAMP'];
        //$timestamp = new DateTime("@$endStamp"); //Little PHP magic to create date object directly from timestamp
        //echo date_format($timestamp, 'l jS \of F Y \a\t\: h:i A');
        xecho($params['DOWNTIME']['END_TIMESTAMP']);
    ?></li>

    <?php foreach ($serviceWithEndpoints as $siteID => $siteDetails) : ?>
        <?php
         $siteName = $params['SITE_LEVEL_DETAILS'][$siteID]['siteName'];

         echo '<li><strong>Site Name: </strong>';
         echo $siteName;
         echo '</li>';
        ?>

        <ul>
            <li>
                <b>Affecting Service and Endpoint(s):</b>

                <?php foreach ($siteDetails as $serviceID => $data) : ?>
                    <?php
                    $endpoints = $data['endpoints'];
                    $service = \Factory::getServiceService()
                                    ->getService($serviceID);
            $safeHostName = xssafe($service->getHostname());
                    ?>

                    <ul>
                        <li>
                            <?php
                            xecho(
                                '(' .
                                $service->getServiceType()->getName() .
                                ') '
                            );
                            echo $safeHostName;
                            ?>
        <ul>
        <?php
        foreach($endpoints as $id){
            $endpoint = \Factory::getServiceService()->getEndpoint($id);
            if($endpoint->getName() != ''){
                $name = xssafe($endpoint->getName());
            }else{
                $name = xssafe("myEndpoint");
            }
            echo "<li>" . $name . "</li>";
            }
        ?>
        </ul>
                        </li>
                    </ul>
                <?php endforeach; ?>
            </li>
        </ul>
    <?php endforeach; ?>
    </ul>
    <!-- Echo out a page type of edit or add downtime depending on type.  -->
    <?php if(!$edit):?>
    <form name="Add_Downtime" action="index.php?Page_Type=Add_Downtime"
          method="post" class="inputForm" id="Downtime_Form" name=Downtime_Form
          onsubmit="document.getElementById('confirmSubmitBtn').disabled=true">
    <?php else:?>
    <form name="Add_Downtime" action="index.php?Page_Type=Edit_Downtime"
          method="post" class="inputForm" id="Downtime_Form" name=Downtime_Form
          onsubmit="document.getElementById('confirmSubmitBtn').disabled=true">
    <?php endif;?>
        <?php $confirmed = true;?>
        <input class="input_input_text" type="hidden" name="CONFIRMED" value="<?php echo $confirmed;?>" />
         <!-- json_encode caters for UTF-8 chars -->
        <input class="input_input_text" type="hidden" name="newValues" value="<?php xecho(json_encode($params));?>" />

        <?php if(!$edit):?>
        <input id="confirmSubmitBtn" type="submit" value="Add downtime to GocDB" class="input_button"  >
        <?php else:?>
        <input id="confirmSubmitBtn" type="submit" value="Confirm Edit" class="input_button"  >
        <?php endif;?>
    </form>
</div>

