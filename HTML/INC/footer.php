      </div>

<?php
if ( isset($help) && SHOW_HELP ) echo help($help);
if ( isset($hint) && SHOW_HINT ) echo '<script type="text/javascript" src="JS/hints.js"></script>';
?>

    </div>
    <div id="footer">

<?php
if (!isset($bcs)) $bcs=array(array("index.php","Home"));
echo bcs($bcs) 
?>
	<div id="footer-info">
<a href="http://motp-as.network-cube.de/">MOTP-AS</a> version <?php echo $VERSION; ?>  -  <a href="mailto:motp-as@network-cube.de">motp-as@network-cube.de</a>
	</div>
    </div>
    </div>
    </body>
</html>
