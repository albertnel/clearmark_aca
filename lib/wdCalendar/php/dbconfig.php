<?php
class DBConnection{
	function getConnection(){
	  //change to your database server/user name/password
		mysql_connect("localhost","root","") or
         die("Could not connect: " . mysql_error());
    //change to your database name
		mysql_select_db("clearmark_aca") or
		     die("Could not select database: " . mysql_error());
	}
}
?>