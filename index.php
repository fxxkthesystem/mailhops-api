<?php
	if (!$loader = @include __DIR__ . '/vendor/autoload.php') {
	    die('Project dependencies missing.  Run composer.');
	}
?>
<h1>MailHops API is Up</h1>
<ul>
	<li><a href="v1/test.php">v1 test</a></li>
	<li><a href="v2/test.php">v2 test</a></li>
	<li><a href="traffic">traffic</a></li>
</ul>
