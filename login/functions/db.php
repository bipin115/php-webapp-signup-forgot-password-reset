<?php
$con= mysqli_connect('localhost','root','','login_db');

function row_count($results)
{
	return mysqli_num_rows($results);
}

function escape($string)
{
	global $con;
	//return mysql_real_escape_string($con,$string);
	return mysqli_escape_string($con,$string);
}

function query ($query){
	global $con;
	return mysqli_query($con,$query);
}

function fetch_array($results){
	global $con;
	return mysqli_fetch_array($results);
}

function confirm($results){
	global $con;
	if(!$results)
	{
		die("QUERY FAILED".mysqli_error($con));
	}
}
?> 