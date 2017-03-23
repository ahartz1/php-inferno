<?php

function try_catch_finally(callable $to_try, callable $to_catch=null, callable $and_finally=null)
{
	// If we didn't get values set empty functions
	if (!is_callable($to_try)) {
		$to_try = function() {};
	}
	if (!is_callable($to_catch)) {
		$to_catch = function() {};
	}
	if (!is_callable($and_finally)) {
		$and_finally = function() {};
	}

	try {
		$ret = $to_try();
	}
	catch (Exception $e) {
		$ret = $to_catch();
	}
	finally {
		$and_finally();
		return $ret;
	}
}
