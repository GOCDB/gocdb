<div class="rightPageContainer">
    <form name="Register" action="index.php?Page_Type=Register" method="post" class="inputForm"
          onsubmit="return confirm('Click OK to confirm you provide consent for your ID and user details to be re-published by GOCDB and made visible to other authenticated GOCDB services and users.');">
        <h1>Register</h1>
        <br />
        Register Unique Identity: <b> <?php echo($params['dn']); ?> </b>
        <br/>
        <br/>


    <div class="alert alert-warning" role="alert">
            <h3>Terms and Conditions of Account Registration</h3>
            <ul>
               <li>Registering a GOCDB account means <b>you accept that your ID string, your basic user details and roles will be visible to other authenticated users and client-services of GOCDB</b>, including those authenticated by: </br></br>
                  <ol>
                    <li>a certificate issed from a Certification Authority (CA) that is registered with the <a href="https://www.igtf.net">Interoperable Global Trust Federation (IGTF).</a></li>
                    <li>new authentication/security realms will be added here and you will be notified by email and in the portal.</li>
                  </ol>
               </li>
            </ul>
            <br>
            <ul>
               <li>Your details are re-published by GOCDB and used by EGI for Monitoring, Accounting and for use in its data processing systems.</li>
               <li><a href="https://wiki.egi.eu/wiki/GOCDB/data_privacy">Further details and terms/conditions of use here</a>.</li>
               <li>If you do not provide this consent, please <b>DO NOT register</b>.</li>
            </ul>
        </div>



        <div class="listContainer">
            <b>Authentication Attributes:</b>
            <br>
            <?php
            foreach ($params['authAttributes'] as $key => $val) {
                $attributeValStr = '';
                foreach ($val as $v) {
                    $attributeValStr .= $v . ',';
                }
                //if(strlen($attributeValStr) > 2){$attributeValStr = substr($attributeValStr, 2);}
                xecho('[' . $key . ']  [' . $attributeValStr . ']');
                echo '<br>';
            }
            ?>
        </div>
        <br>

        <span class="input_name">
            Title
        </span>

        <select name="TITLE" class="add_edit_form">
            <option value="Mr">Mr</option>
            <option value="Mrs">Mrs</option>
            <option value="Miss">Miss</option>
            <option value="Ms">Ms</option>
            <option value="Prof">Prof</option>
            <option value="Dr">Dr</option>
        </select>

        <span class="input_name">
            Forename *
            <span class="input_syntax" >(unaccentuated letters, spaces, dashes and quotes)</span>
        </span>
        <input class="input_input_text" type="text" name="FORENAME" />

        <span class="input_name">
            Surname *
            <span class="input_syntax" >(unaccentuated letters, spaces, dashes and quotes)</span>
        </span>
        <input class="input_input_text" type="text" name="SURNAME" />

        <span class="input_name">
            E-Mail *
            <span class="input_syntax" >(valid e-mail format)</span>
        </span>
        <input class="input_input_text" type="text" name="EMAIL" />

        <span class="input_name">
            Telephone Number
            <span class="input_syntax" >(numbers, optional +, dots spaces or dashes)</span>
        </span>
        <input class="input_input_text" type="text" name="TELEPHONE" />

        <br />
        <input class="input_button" type="submit" value="Submit" />
    </form>
</div>