<?php
	if (!$loader = @include __DIR__ . '/vendor/autoload.php') {
	    die('Project dependencies missing.  Run composer.');
	}
	echo 'MailHops API is Up. <br/><a href="/v1/test.php">Configuration Test.</a>';
?>
