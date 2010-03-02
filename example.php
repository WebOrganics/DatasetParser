<?php
#require Dataset.parser class,
include_once( 'application/Dataset_Parser.php' );

#start a new HTMLQuery
$query = new HTMLQuery;

#define a $query in this case get url, example.php?query=http://foo.com/dataset.bar 
$url = $_GET['query'];

#echo, or print results
print $query->this_document($url);
?>