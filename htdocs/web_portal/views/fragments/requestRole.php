<?php if (!$params['portalIsReadOnly']) {
    echo '<div style="padding: 1em; padding-left: 1.4em; overflow: hidden;">';
        echo '<a class="gocdb_btn_secondary" href="index.php?Page_Type=Request_Role&amp;id=' . $entityId . '">';
            echo '<img class="gocdb_btn_secondary_icon" src="' . \GocContextPath::getPath() . 'img/add.png"';
            echo '<span class="gocdb_btn_secondary_text"';
                echo 'Request Role';
            echo '</span>';
        echo '</a>';
    echo '</div>';
} ?>
