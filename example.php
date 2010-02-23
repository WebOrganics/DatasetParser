<?php
#require Dataset.parser class,
require_once( 'dataset.parser.php' );

#define a $url in this case get url, ?url=http://foo.com/dataset.bar 
$url = $_GET['url'];

#start a new HTMLQuery
$query = new HTMLQuery;

#echo, or print results
echo $query->this_document($url);
?>