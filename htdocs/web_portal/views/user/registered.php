<?php
$user = $params['user'];
?>
<div class="rightPageContainer">
    <h1 class="Success">Success</h1>
    New user registered. <br />
    <a href="index.php?Page_Type=User&amp;id=<?php echo $user->getId() ?>">
    View user</a>
</div>