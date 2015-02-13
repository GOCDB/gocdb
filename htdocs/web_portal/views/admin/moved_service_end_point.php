<div class="rightPageContainer">
	<h1 class="Success">Success</h1><br />
	<?php $Site = $params['NewSite'];?>
	The following services have been moved to 
	<a href="index.php?Page_Type=Site&id=<?php echo $Site->getId();?>">
    <?php xecho($Site->getShortName());?>
    </a>:
	<?php 
        foreach($params['Services'] as $sep){
            echo "<br />"
            	. "<a href=\"index.php?Page_Type=Service&id=" . $sep->getId() ."\">"
            	.  xssafe($sep->getHostName())
            	. "</a> ";
        }	
	?>
</div>