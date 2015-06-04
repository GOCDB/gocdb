<div class="rightPageContainer">
    <form name="Register" action="index.php?Page_Type=Register" method="post" class="inputForm">
    	<h1>Register</h1>
    	<br />
        Register Unique Identity: <b> <?php echo($params['dn']); ?> </b>
        <br/> 
        <br/> 

        <div class="listContainer">
            <b>Authentication Attributes:</b>
            <br>
            <?php
            foreach ($params['authAttributes'] as $key => $val) {
                $attributeValStr = '';
                foreach ($val as $v) {
                    $attributeValStr .= $v . ' ,';
                }
                if(strlen($attributeValStr) > 2){$attributeValStr = substr($attributeValStr, 2);}
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