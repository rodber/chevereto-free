<?php
//Filter the provided argument or stdin if the argument was not present

require_once dirname (__DIR__) . '/URLify.php';

//Print usage and exit if arguments are invalid
if($argc < 1 || $argc > 2) {
	die ("Usage (argument): php " . basename(__FILE__) . " \"<text to filter>\"\nUsage (pipe): <Arbitrary command> | php " . basename(__FILE__) . "\n");
}

//Process the provided argument
if($argc === 2) {
	$s = $argv[1];
//Or read from stdin if the argument wasn't present
} else {
	$piped = true;
	$s = file_get_contents("php://stdin");
}

echo URLify::filter ($s) . ($piped ? "\n" : "");
