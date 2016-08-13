<?php
	if (!$loader = @include __DIR__ . '/vendor/autoload.php') {
	    die('Project dependencies missing.  Run composer.');
	}
?>
<h1>MailHops API is Up</h1>
<ul>
	<li><a href="traffic">traffic</a></li>
</ul>
