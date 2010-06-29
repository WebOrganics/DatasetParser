<?php
/*
 Dataset TransFormr Version: 1.2, Saturday, 28th June 2010
 Author: Martin McEvoy info@weborganics.co.uk
 Usage:
   
  include_once("Dataset_Transformr.php"); # include Dataset_Transformr class
   
  $dataset = new Dataset_Transformr; # start a new dataset instance
   
  $dataset->use_curl = 1; # optional default file_get_contents
 
  # $dataset->output_as_xml = 1; # optional default output as RDF
   
  if (isset($_GET['url'])) print $dataset->asRDF(); # if ?url=... return dataset as RDF.
   
 See Also: http://weborganics.co.uk/dataset/ for more information.
 */
include_once("RDFTransformer.php");

class Dataset_Transformr
{
	public $output_as_xml = '';
	public $use_curl = '';
	public $usedata= '';
	
	function __construct() 
	{
		$this->url =  isset($_GET['url']) ? $_GET['url'] : '';
	
		$this->doc = $this->get_file_contents($this->url);	
		
		$this->json = json_decode(utf8_encode($this->doc));
		
		$this->document = isset($this->json->select->from) ? $this->get_file_contents($this->return_url($this->json->select->from, $this->url)) : $this->doc;
		
		$this->is_json = isset($this->json->select->from) ? true : false;
		
		$this->output_type = $this->output_as_xml == true  ? 'xml' : 'rdf';
		
		$this->file = $this->rand_filename($this->output_type);
	}

	/* JSON Dataset Functions */

	protected function json_dataset($xpath, $url) 
	{
		$data = "//*[contains(concat(' ',normalize-space(@rel), ' '),' dataset ')][1]";
		if ($this->is_json != false ) $json_data = $this->url;
		if($nodes = $xpath->query($data)) 
		{
			foreach($nodes as $node) {
				$resource = $node->getAttribute('href');
				$json_data = $this->return_url($resource , $url);
            }
        }
		return( isset($json_data) ? $json_data : null );
	}
	
	protected function setXmlns($object, $node) 
	{
		$result = '';
		foreach ( $object->prefix as $prefix => $urlns ) {	
			$thisns = $prefix == "value" ? "xmlns" : "xmlns:".$prefix;
			$result .= $prefix == "value" 
					   ? $node->setAttribute($thisns, $urlns) 
					   : $node->setAttributeNS('http://www.w3.org/2000/xmlns/' , $thisns, $urlns);
		}
		return $result;
	}
	
	protected function reverse_strrchr($val, $whereor)
	{
		$whereor = strrpos($val, $whereor);
		return ( substr($val, 0, $whereor) != '' ? substr($val, 0, $whereor) : null );
	}

	protected function forward_strrchr($val, $whereor)
	{
		return ( strrchr($val, $whereor) ? array_pop(explode($whereor, $val)) : null );
	}

	protected  function get_attr_value($val)
	{
		$whereors = array('class' => '.', 'id' => '#', 'attr' => '~=');
		foreach ($whereors as $attribute => $whereor) {
			if(!is_null( $this->reverse_strrchr($val, $whereor)) && $whereor == '~=' ) {
				return  array( $this->reverse_strrchr($val, $whereor) => $this->forward_strrchr($val, $whereor) );
			}
			elseif(!is_null( $this->forward_strrchr($val, $whereor)) && is_null($this->reverse_strrchr($val, $whereor))) {
				if ($attribute != 'attr') return  array( $attribute => $this->forward_strrchr($val, $whereor) );
			}
		}
	}
	
	protected function return_node_or_attribute($val)
	{
		return ( !$this->get_attr_value($val) ? array('node' => $val) : $this->get_attr_value($val) );
	}
	
	protected function get_label($value, $item) 
	{ 
		return ( isset($value->label) ? $value->label : $item );
	}

	protected function return_url($resource, $url)
    {	
        if (parse_url($resource, PHP_URL_SCHEME) != '') return $resource;
        if ($resource[0]=='#' || $resource[0]=='?') return $url.$resource;
		
        extract(parse_url($url));
		
        $path = preg_replace('#/[^/]*$#', '', $path);
		
        if ($resource[0] == '/') $path = '';
		
        $absolute = "$host$path/$resource";
        $replacements = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
		
        for($n=1; $n>0; $absolute=preg_replace($replacements, '/', $absolute, -1, $n)) {}
		
        return $scheme.'://'.$absolute;
    }
	
	protected function get_file_contents($url)
	{
		if ( $this->use_curl != '' ) {
		
			$cache = curl_init();
			curl_setopt($cache, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($cache, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt($cache, CURLOPT_URL, $url);
			curl_setopt($cache, CURLOPT_USERAGENT, 'Mozilla/5.0');
			$result = curl_exec($cache);
			curl_close($cache);
			return html_convert_entities($result);
		}
		else return html_convert_entities(file_get_contents($url));
	}
	
	protected function json_value($object, $value='')
	{
		return $object->$value;
	}
	
	protected function test_node_attribute($property, $documentTag, $attribute) 
	{
		if (preg_match("/\b$property\b/i", $documentTag->getAttribute($attribute) ) || 
			$documentTag->nodeName == $property && $attribute == "node" ) 
		{
			return 1;
		} 
		else return 0;
	}
	
	protected function get_content($value, $documentTag, $as_resource ='') 
	{
		if (is_string($value->content)) { 
			return $value->content;
		}
		else {
			foreach ($value->content as $cid => $content) {
				if ($cid == "value") {
					return $content;
				}
				elseif ($newcids = explode(' ', $cid)) {	
					foreach($newcids as $newcid) {		
						if ($documentTag->getAttribute('id') == $newcid) {
							return $content;
						}
					}
				}
				elseif ($documentTag->getAttribute('id') == $cid) {
					return $content;
				}
				else {
					return $as_resource == true ? 
					$this->return_resource($documentTag, $this->url) :
					$this->return_text($documentTag);
				}
			}
		}
	}
	
	protected function return_node_value($doc, $result = '') 
	{	
		$tmpdoc = new DOMDocument();
		$tmpdoc->appendChild($tmpdoc->importNode($doc, TRUE));
		$tmpdoc->formatOutput = true;
		$result .= $tmpdoc->saveXML();
		$result = str_replace(array("\r\n", "\r", "\n", "\t", "&#xD;"), '', $result);
		$result = trim(preg_replace('/<\?xml.*\?>/', '', $result, 1));
		return $result;
	}

	protected function return_text_nodes($val, $documentTag, $prop, $xml) 
	{
		if (isset($val->content)) $text = $this->get_content($val, $documentTag);
		else $text = $this->return_text($documentTag);
		$result = $xml->createElement($this->get_label($val, $prop), $text );
		return $result;
	}
	
	protected function return_resource($documentTag, $url) 
	{
		if ($documentTag->getAttribute('src')) $resource = $documentTag->getAttribute('src');
		elseif ($documentTag->getAttribute('href')) $resource = $documentTag->getAttribute('href');
		elseif ($documentTag->getAttribute('id')) $resource = $url."#".$documentTag->getAttribute('id');
		return $this->return_url($resource , $url);
	}
	
	protected function return_text($documentTag)
	{
		if ($documentTag->getAttribute('datetime')) $text = $documentTag->getAttribute('datetime');
		elseif ($documentTag->getAttribute('content')) $text = $documentTag->getAttribute('content');
		elseif ($documentTag->getAttribute('title')) $text = $documentTag->getAttribute('title');
		else $text = $documentTag->nodeValue;
		$text = str_replace(array("\r\n", "\r", "\n", "\t"), '', $text);
		return $text;
	}
	
	protected function return_document($parse, $document, $object) 
	{
		$this->output_type == 'xml' ?
		header("Content-type: application/xml") :
		header("Content-type: application/rdf+xml");
		header("Content-Disposition: inline; filename=".$this->file);
		return $document;
	}
	
 	private function rand_filename($ext = '') {
		return substr(md5(uniqid(rand())), 0, 8).'.'.$ext;
	}
	
	protected function return_error($num ='') 
	{
	switch ($num) {
		case "1":
			return 'Could not get the URL';
		break;
		case "2":
			return 'Could not get the Dataset';
		break;
		case "3":
			return 'Dataset not well formed please validate your dataset at <a href="http://www.jsonlint.com/">http://www.jsonlint.com/</a>';
		break;       
		}
	exit;
	}
	/* END */
	
	/* start RDF document output */

	public function asRDF()
	{
		$parse = new RDFTransformer;
		
		if (!$this->document) return $this->return_error('1');
		
		$dom = new DomDocument();
		@$dom->loadHtml($this->document);
		$xpath = new DomXpath($dom);
		
		$this->data = $this->usedata != '' ? $this->get_file_contents($this->usedata) : $this->get_file_contents($this->json_dataset($xpath, $this->url));
		if (!$this->data) return $this->return_error('2');
		
		$this->json = json_decode( html_convert_entities($this->data) );
		if (!$this->json) return $this->return_error('3');
		
		$object = $this->json->select;
		if (isset($object->from)) $this->url = $this->return_url($object->from , $this->url);
		
		$xml  = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = true;
		$xml->formatOutput = true;
		
		$root = $xml->createElementNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:RDF');
		$root->setAttribute("xml:base", $this->url);
		$this->setXmlns($object, $root);
		$xml->appendChild($root);
		
		$documentTags = $dom->getElementsByTagName('*');
		
		foreach ( $documentTags as $documentTag ) {
			$this->json_query_rdf_properties($this->url, $object, $documentTag, $root, $xml, false);
		}
		
		$document = $xml->saveXML();
		return $this->return_document($parse, $document, $object);
	}
	
	private function json_query_rdf_properties($url, $object, $documentTag, $root, $xml, $hasroot ='') 
	{
		foreach ($this->json_value($object, 'where') as $item => $value) {
		
		foreach ($this->return_node_or_attribute($item) as $attribute => $item) {
		
		if ($this->test_node_attribute($item, $documentTag, $attribute) != false) {
					
			if (isset($value->where)) {
	
			$arrvalue = !isset($arrvalue) ? $arrvalue = array() : array_values($arrvalue);
		
			$class = $xml->createElement($this->get_label($value, $item));
			$root->appendChild($class);
					
			$this->rdf_about($url, $documentTag, $value, $class);
			
			if (isset($value->type) && strrchr($value->type, 'http://')) 
			{
				$type = $xml->createElement('rdf:type');
				$type->setAttribute("rdf:resource", $value->type);
				$class->appendChild($type);
			}
					
			$documentTags = $documentTag->getElementsByTagName('*');
					
				foreach ( $documentTags as $documentTag ) {									
					
					foreach ( $this->json_value($value, 'where')  as $prop => $val ) 
					{		
						foreach ($this->return_node_or_attribute($prop) as $attribute => $property) {

							if ($this->test_node_attribute($property, $documentTag, $attribute) != false) {
								
									$parse_property = true;
									
									foreach ($arrvalue as $thiskey => $thisval) 
									{
										if ($thisval == $this->get_label($val, $property)) $parse_property = false;
									}
									if (isset($val->multiple)) $parse_property = true;
									
									if ($parse_property == true) {									
										$this->return_rdf_properties($url, $property, $val, $documentTag, $class, $xml);
										$arrvalue[] = $this->get_label($val, $property);
										}
									}
								}
							}
						}
					} 
					else 
					{
						if ($hasroot == true ) {
							$this->return_rdf_properties($url, $item, $value, $documentTag, $root, $xml);
						}else {
							$class = $xml->createElement('rdf:Description');
							$root->appendChild($class);
							$this->rdf_about($url, $documentTag, $value, $class);
							$this->return_rdf_properties($url, $item, $value, $documentTag, $class, $xml);
						};
					}
				}
			}
		}
	}

	private function return_rdf_properties($url, $prop, $val, $documentTag, $class, $xml) 
	{
		$resource = '';
		$result = '';
		$type = isset( $val->type ) ? $val->type : "text";
		
		if (!isset($val->where))
		{
			switch ($type) {
			
			    case "text":
					$property = $this->return_text_nodes($val, $documentTag, $prop, $xml);
			    break;
				
			    case "uri":
					
					$resource = isset($val->content) ? $this->get_content($val, $documentTag, true) : $this->return_resource($documentTag, $url);
					
					if ($lables = explode(' ', $this->get_label($val, $prop))) {
						foreach ($lables as $lable) {
							$property = $xml->createElement($lable);
							$property->setAttribute("rdf:resource", $this->return_url($resource, $url));
							$class->appendChild($property);
						}
					} 
					else {
						$property = $xml->createElement($this->get_label($val, $prop));
						$property->setAttribute("rdf:resource", $this->return_url($resource, $url));
					}
					
			    break;
				
				case 'uriplain': 
					$resource = isset($val->content) ? $this->get_content($val, $documentTag, true) : $this->return_resource($documentTag, $url);
					$property = $xml->createElement($this->get_label($val, $prop), $this->return_url($resource, $url));
				break;
				
				case 'xmlliteral':
					$children = $documentTag->childNodes;
					$property = $xml->createElement($this->get_label($val, $prop));
					$property->setAttribute('rdf:datatype', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral');
					foreach ($children as $child) {
						if ($child != new DOMText) $child->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
						$property->appendChild($xml->importNode($child, TRUE));
					}
				break;
						
				case 'cdata':
					$children = $documentTag->childNodes;
					$property = $xml->createElement($this->get_label($val, $prop));
					foreach ($children as $child) {
						$result .= $this->return_node_value($child);
					}
					$cdata = $property->ownerDocument->createCDATASection($result);
					$property->appendChild($cdata);
				break;
				
				default:
					$property = $this->return_text_nodes($val, $documentTag, $prop, $xml);
					$property->setAttribute('rdf:datatype', $type);
				break;
			}
			$class->appendChild($property);
		} 
		else { 
		
			if (isset($val->rev))
			{
				$root = $xml->createElement($val->rev);
				$class->appendChild($root);
				$newroot = $xml->createElement($this->get_label($val, $prop));
				$root->appendChild($newroot);
				$this->rdf_about($url, $documentTag, $val, $newroot);
			} 
			elseif (isset($val->type) && $val->type == "resource")
			{
				$newroot = $xml->createElement($this->get_label($val, $prop));
				$class->appendChild($newroot);
				$newroot->setAttribute("rdf:parseType", "Resource");	
			}
			
			elseif (isset($val->type) && $val->type == "collection")
			{
				$newroot = $xml->createElement($this->get_label($val, $prop));
				$class->appendChild($newroot);
				$newroot->setAttribute("rdf:parseType", "Collection");	
			}
			elseif (isset($val->type) && $val->type == "literal")
			{
				$newroot = $xml->createElement($this->get_label($val, $prop));
				$class->appendChild($newroot);
				$newroot->setAttribute("rdf:parseType", "Literal");	
			}
			
			else if (!isset($val->type) && !isset($val->rev))
			{
				$this->root = $xml->createElement($this->get_label($val, $prop));
				$class->appendChild($this->root);
				$newroot = $xml->createElement("rdf:Description");
				$this->root->appendChild($newroot);
			}
			
			if (isset($val->type) && strrchr($val->type, 'http://'))
			{
				$type = $xml->createElement('rdf:type');
				$type->setAttribute("rdf:resource", $val->type);
				$newroot->appendChild($type);
			}
			
			$documentTags = $documentTag->getElementsByTagName('*');
			
			foreach ( $documentTags as $documentTag ) {
				$this->json_query_rdf_properties($url, $val, $documentTag, $newroot, $xml, true);
			}
		}
		unset( $class, $text, $resource );
	}
	
	private function rdf_about($url, $documentTag, $value, $class) 
	{
	$about = isset($value->about) ? $value->about : true;
	
	if ( $documentTag->getAttribute('id') ) {
		if ( isset($value->about) && $about != false ) {
			foreach ($value->about as $id => $uri ) {
				if ($newids = explode(' ', $id)) { 
					foreach ($newids as $newid) {
						return ( $documentTag->getAttribute('id') == $newid ? 
							$class->setAttribute("rdf:about", $uri) : 
							$class->setAttribute("rdf:about", $url."#".$documentTag->getAttribute('id'))
						);
					}
				}
				else {
					return ( $documentTag->getAttribute('id') == $id ? 
						$class->setAttribute("rdf:about", $uri) : 
						$class->setAttribute("rdf:about", $url."#".$documentTag->getAttribute('id'))
					);
				}
			}
		} 
		elseif( $about != false ) return $class->setAttribute("rdf:about", $url."#".$documentTag->getAttribute('id'));
	} 
	elseif ($documentTag->getAttribute('href')) return $class->setAttribute("rdf:about", $this->return_url($documentTag->getAttribute('href'), $url));
	elseif ($documentTag->getAttribute('src')) return $class->setAttribute("rdf:about", $this->return_url($documentTag->getAttribute('src'), $url));
	elseif( $about != false ) return $class->setAttribute("rdf:about", $url); 
	}
	
	/* END */
}
?>