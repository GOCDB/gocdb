<div class="rightPageContainer">
    <h1 class="Success">Success</h1>
    <p>Your <?php echo $params['requestText']; ?> request has been submitted with the following details:</p>

    <ul>
        <li>Authentication type: <?php xecho($params['authType']); ?></li>
        <li>ID String: <?php xecho($params['idString']); ?></li>
        <li> Email: <?php xecho($params['email']); ?></li>
    </ul>

    <p>If these details are correct, an email  will have been sent to the address
    registered to your account. Please follow the instructions contained within it to
    complete your <?php echo $params['requestText']; ?>.</p>

    <p>If you did not receive an email, please check the above details are correct, and the
    <a href="index.php?Page_Type=Link_Identity">guidance on identity linking and account recovery</a>.</p>
</div>