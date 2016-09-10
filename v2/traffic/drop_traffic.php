<?php
if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {
    die('Project dependencies missing.  Run composer.');
}

$mailhops = new MailHops();
$result = $mailhops->clearTraffic();
echo json_encode($result);
?>
