<?PHP

function log_error($msg) {
	error_log($msg, 3, "/tmp/fejl.log");
}

