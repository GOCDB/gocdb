<table>
    <tr>
        <td>Name:</td>
        <td><?php xecho($params['Name'])?></td>
    </tr>
    <tr>
        <td>Description:</td>
        <td><?php xecho($params['Description'])?></td>
    </tr>
    <tr>
        <td>Monitoring:</td>
        <td>
            <?php
            if ($params['AllowMonitoringException']) {
                xecho('Production ' . $params['Name'] . ' services may be un-monitored.');
            } else {
                xecho('Production ' . $params['Name'] . ' services must be monitored.');
            }
            ?>
        </td>
    </tr>
</table>
</br>
