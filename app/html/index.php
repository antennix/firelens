<?php
if (isset($_GET['exception'])) {
	error_log("exception");
	exit;
}else if (isset($_GET['application'])) {
	error_log("application error error-text");
	exit;
}
phpinfo();