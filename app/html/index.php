<?php
if (isset($_GET['exception'])) {
	error_log("Exception.");
	exit;
}
phpinfo();