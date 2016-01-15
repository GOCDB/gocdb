<!-- Scope Tags-->
<?php 
if(!isset($parentObjectTypeLabel)){
   $parentObjectTypeLabel = "Object";  
}
?>
<div class="h4">Scope Tags</div>
<br>
<div id="allscopeCheckBoxDIV">
    <span class="input_name">&check; Optional Tags 
        <span class="input_syntax" >(At least <?php echo $params['numberOfScopesRequired'] ?> optional tags must be selected)</span>
    </span>
    <div id="optionalScopeCheckBoxDIV"></div> 
    <br/>

    <span class="input_name">&check; Reserved Tags Inheritable from Parent <?php echo $parentObjectTypeLabel;?></span>
    <div id="reservedOptionalInhertiableScopeCheckBoxDIV"></div> 
    <br/> 

    <span class="input_name">&check; Reserved Tags Directly Assigned 
        <span class="input_syntax" >(WARNING - If deselected you will not be able to reselect the tag - it will be moved to the 'Protected Reserved Tags' list)</span>
    </span>
    <div id="reservedOptionalScopeCheckBoxDIV"></div> 
    <br/>

    <span class="input_name">&cross; Protected Reserved Tags 
        <span class="input_syntax" >(Can only be assigned on request)</span>
    </span>
    <div id="reservedScopeCheckBoxDIV"></div> 
    <br/>
</div>
