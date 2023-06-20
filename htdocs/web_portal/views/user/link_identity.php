<div class="rightPageContainer">
    <h1>Link Identifier or Recover an Account</h1>
    <div>
    This page allows you to either add a new identifier to an exisiting GOCDB
    account, or recover a GOCDB account if your identifier has changed. You
    must have access to the email address associated with that GOCDB account.
    You must be authenticated with the new identifier.
    </div>
    <br />
    <div class=Form_Holder>
        <div class=Form_Holder_2>
            <form name="Link_Identity" action="index.php?Page_Type=Link_Identity"
                  method="post" class="inputForm" id="linkIdentityForm">
                <div>
                    Your current ID string (e.g. certificate DN) is: <label><?php echo $params['idString']; ?></label>
                </div>
                <div>
                    Your current authentication type is: <label id="currentAuthType"><?php echo $params['currentAuthType']; ?></label>
                </div>

                <h2>Details of account to be linked to or recovered</h2>

                <div class="form-group" id="authTypeGroup">
                    <label class="control-label" for="authType">Authentication type *</label>
                    <div class="controls">
                        <select
                            class="form-control"
                            name="authType" id="authType"
                            size=<?php echo count($params['authTypes']); ?>
                            onchange="updateWarningMessage(); formatAuthType(); formatIdStringFromAuth();">
                            <?php
                                foreach ($params['authTypes'] as $authType) {
                                    echo "<option onclick=\"updateWarningMessage(); formatAuthType(); formatIdStringFromAuth();\" value=\"";
                                    echo $authType . "\">" . $authType . "</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <br />
                    <br class="authTextPlaceholder" />

                    <div class="hidden authTypeShared">
                        <b><span class="authTypeSelected"></span></b>
                        <span id="authTypeMsg1"></span>
                        <span>Proceeding will begin the </span>
                        <b><span class="requestType"></span></b>
                        <span> process.</span>
                    </div>
                    <div class="hidden authTypeShared">

                    </div>
                    <div class="hidden" id="authTypeRecover">
                        <span id="authTypeMsg2"></span>
                    </div>
                    <br id="authTypeRecoverPlaceholder" />
                </div>

                <div class="form-group" id="primaryIdStringGroup">
                    <label class="control-label" for="primaryIdString">ID string *
                        <label class="input_syntax" >(e.g. for X.509: /C=.../OU=.../...)</label>
                    </label>

                    <div class="controls">
                        <input class="form-control" type="text" name="primaryIdString" id="primaryIdString" onchange="formatIdString();" disabled/>
                    </div>

                    <span id="idStringError" class="label label-danger hidden"></span>
                    <br id="idStringPlaceholder" />
                </div>

                <div class="form-group" id="emailGroup">
                    <label class="control-label" for="email">E-mail address *
                        <label class="input_syntax" >(valid e-mail format)</label>
                    </label>

                    <div class="controls">
                        <input class="form-control" type="text" name="email" id="email" onchange="formatEmail();"/>
                    </div>
                    <span id="emailError" class="label label-danger hidden"></span>
                    <br id="emailPlaceholder" />
                </div>

                <h2>What happens next?</h2>
                <div>
                    <ul>
                        <li>
                            Once you have submitted this form, you will receive a confirmation
                            e-mail containing instructions on how to validate the request.
                        </li>
                        <li>
                            Any existing linking or recovery requests you have made will expire.
                        </li>

                        <li class="hidden" id="linkingDetails"> If you successfully validate your <b>linking</b> request:
                            <ul>
                                <li>
                                    Your <b>current ID string</b> and
                                    <b>authentication type</b> will be added as
                                    an alternative identifier to the account
                                    being linked to.
                                </li>
                                </li>
                                <li <?php echo $params['registered'] ? "" : "hidden"; ?>>
                                    Any roles you have with the account you are currently using will be requested
                                    for the account being linked to.
                                </li>
                                <li <?php echo $params['registered'] ? "" : "hidden"; ?>>
                                    These roles will be approved automatically if either account has permission to do so.
                                </li>
                                <li <?php echo $params['registered'] ? "" : "hidden"; ?>>
                                    <b>The account you are currently using will be deleted.</b>
                                </li>
                            </ul>
                        </li>

                        <li id="recoveryDetails"> If you successfully validate your <b>recovery</b> request:
                            <ul>
                                <li>
                                    The <b>ID string</b> of your old account that matches your <b>current authentication type</b> will be updated to your <b>current ID string</b>.
                                </li>
                                <li>
                                    <b>You will no longer be able to log in with your old ID string</b>.
                                </li>
                                <li <?php echo $params['registered'] ? "" : "hidden"; ?>>
                                    Any roles you have with the account you are currently using will be requested for your old account.
                                </li>
                                <li <?php echo $params['registered'] ? "" : "hidden"; ?>>
                                    These roles will be approved automatically if either account has permission to do so.
                                </li>

                                <li <?php echo $params['registered'] ? "" : "hidden"; ?>>
                                    <b>The account you are currently using will be deleted.</b>
                                </li>
                            </ul>
                        </li>

                        <li class="hidden invis" id="requestPlaceholder"></li>
                    </ul>
                </div>

                <br />

                <button type="submit" id="submitRequest_btn" class="btn btn-default" style="width: 100%" value="Execute" disabled>Submit</button>

            </form>
        </div>
    </div>
</div>

<style>
    .auth-warning {
        color: red;
    }
    .invis {
        opacity: 0;
    }
</style>

<script type="text/javascript" src="<?php echo \GocContextPath::getPath(); ?>javascript/linking.js"></script>
