<?php
#require DatasetParse class,
require_once( 'datasetParser.php' );

#define a $url in this case get url, ?url=http://foo.com/dataset.bar 
$url = $_GET['url'];

#echo, or print results
echo DatasetParse::html_query($url);
?>