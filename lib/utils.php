<?php

function read_int($value) {
	if ($value == null) {
		return null;
	} else {
		return (int)$value;
	} 
}

function read_float($value) {
	if ($value == null) {
		return null;
	} else {
		return (float)$value;
	} 
}


