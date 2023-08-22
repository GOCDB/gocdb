<div class="rightPageContainer">
    <h1 class="Success">Success</h1><br />
    <?php
    if (count($params['submittedDowntimes']) != 1) {
        echo '<p>';
        echo 'New Downtimes successfully created. ';
        echo 'Please click the links below for more information.';
        echo '</p>';
    } else {
        echo '<p>';
        echo 'New Downtime successfully created. ';
        echo 'Please click the link below for more information.';
        echo '</p>';
    }

    echo '<ul>';
    foreach ($params['submittedDowntimes'] as $siteName => $downtimeDetails) {
        echo '<li>';
        echo $siteName . ':';
        echo '<a href="index.php?Page_Type=Downtime&id=';
        echo $downtimeDetails->getId();
        echo '"> Downtime ' . $downtimeDetails->getId() . '</a>';
        echo '</li>';
    }
    echo '</ul>';
    ?>
</div>
