<div class="rightPageContainer">
    <div class=Form_Holder>
        <div class=Form_Holder_2>
            <form name="Update_Change_Cert_Req" action="index.php?Page_Type=Retrieve_Account"
                  method="post" class="inputForm">
                <h1>Retrieve An Account</h1>
                Your current Account ID (e.g. certificate DN) is: <?php echo $params['DN'];?>
                <br/>
                <br/>
			    <span class="input_name">Old Account ID (as registered within your old account) *
			        <span class="input_syntax" >(e.g. if DN: /C=.../OU=.../...)</span>
			    </span>
			    <input class="input_input_text" type="text" name="OLDDN" />

			    <span class="input_name">E-mail address (as registered within your account) *
			        <span class="input_syntax" >(valid e-mail format)</span>
			    </span>
			    <input class="input_input_text" type="text" name="EMAIL" />

			    <span class="input_name">
			        Once you have submitted this form, you will receive a confirmation
			        e-mail containing instructions on how to validate the request.
			    </span>

		        <input class="input_button" type="submit" value="Execute" />
	        </form>
	    </div>
	</div>
</div>