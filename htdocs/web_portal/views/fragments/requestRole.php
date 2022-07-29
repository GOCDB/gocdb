<?php if (!$params['portalIsReadOnly']) {
    echo '<div style="padding: 1em; padding-left: 1.4em; overflow: hidden;">';
        echo '<a href="index.php?Page_Type=Request_Role&amp;id=' . $entityId . '">';
            echo '<img src="' . \GocContextPath::getPath() . 'img/add.png" height="20px" style="float: left; vertical-align: middle; padding-right: 1em;">';
            echo '<span class="header" style="vertical-align:middle; float: left; padding-top: 0.2em;">';
                echo 'Request Role';
            echo '</span>';
        echo '</a>';
    echo '</div>';
} ?>
