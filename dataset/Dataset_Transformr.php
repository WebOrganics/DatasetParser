<?php
/*
 * JSON Dataset TransFormr Version: 1.5, Sunday, 31st October 2010
 * Author: Martin McEvoy info@weborganics.co.uk 
 * Web http://weborganics.co.uk/dataset/
 */

class Dataset_Transformr
{
	public $output_as_xml = '';
	public $usedata= '';
	public $useurl= '';
	
	function __construct() 
	{
		$this->url =  isset($_GET['url']) ? $_GET['url'] : $useurl;
		$this->doc = $this->get_file_contents($this->url);	
		$this->json = json_decode(utf8_encode($this->doc));
		$this->document = isset($this->json->from) ? $this->get_file_contents($this->return_url($this->json->from, $this->url)) : $this->doc;
		$this->is_json = isset($this->json->from) ? true : false;
		$this->output_type = $this->output_as_xml == true  ? 'xml' : 'rdf';
		$this->file = substr(md5(uniqid(rand())), 0, 8).'.'.$this->output_type;
		ini_set('display_errors', 0 );
	}
	
	/* Get Json  */

	protected function json_dataset($xpath, $url) 
	{
		$data = "//*[contains(concat(' ',normalize-space(@rel), ' '),' transformation ')][1]";
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
	
	/* Set Namespaces  */
	
	protected function setXmlns($object, $node) 
	{
		$result = '';
		foreach ( $object->prefix as $prefix => $urlns ) {	
			$thisns = $prefix == "value" ? "xmlns" : "xmlns:".$prefix;
			$result .= $prefix == "value" ? $node->setAttribute($thisns, $urlns) : $node->setAttributeNS('http://www.w3.org/2000/xmlns/' , $thisns, $urlns);
		}
		return $result;
	}
	
	/* reverse string search  */
		
	protected function reverse_strrchr($val, $selector)
	{
		$selector = strrpos($val, $selector);
		return ( substr($val, 0, $selector) != '' ? substr($val, 0, $selector) : null );
	}

	/* forward string search  */
	
	protected function forward_strrchr($val, $selector)
	{
		return ( strrchr($val, $selector) ? array_pop(explode($selector, $val)) : null );
	}

	/* Get attributes using css like selectors class ".", ID "#" or attribute "~=" */
	
	protected  function get_attr_value($val)
	{
		$selectors = array('class' => '.', 'id' => '#', 'attr' => '~=');
		foreach ($selectors as $attribute => $selector) {
			if(!is_null( $this->reverse_strrchr($val, $selector)) && $selector == '~=' ) {
				return  array( $this->reverse_strrchr($val, $selector) => $this->forward_strrchr($val, $selector) );
			}
			elseif(!is_null( $this->forward_strrchr($val, $selector)) && is_null($this->reverse_strrchr($val, $selector))) {
				if ($attribute != 'attr') return  array( $attribute => $this->forward_strrchr($val, $selector) );
			}
		}
	}
	
	/* return node or attribue value  */
	
	protected function return_node_or_attribute($val)
	{
		return ( !$this->get_attr_value($val) ? array('node' => $val) : $this->get_attr_value($val) );
	}

	/* return label for output  */
	
	protected function get_label($value, $item) 
	{ 
		return ( isset($value->as) ? $value->as : $item );
	}

	/* return absolute url */
	
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
	
	/* get contents of HTML File  */
	
	protected function get_file_contents($url)
	{
		$cache = curl_init();
		curl_setopt($cache, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($cache, CURLOPT_URL, $url);
		curl_setopt($cache, CURLOPT_USERAGENT, 'Mozilla/5.0');
		$result = str_replace(array("&lt;", "&gt;"), array("<", ">"), htmlentities(curl_exec($cache), ENT_NOQUOTES, "UTF-8"));
		curl_close($cache);
		return $result;
	}
	
	/* return "value" */
	
	protected function json_value($object, $value='')
	{
		return $object->$value;
	}
	
	/* test if node or attribute exists true/false */
	
	protected function test_node_attribute($property, $documentTag, $attribute) 
	{
		if (preg_match("/\b$property\b/i", $documentTag->getAttribute($attribute) ) || 
			$documentTag->nodeName == $property && $attribute == "node" ) 
		{
			return 1;
		} 
		else return 0;
	}
	
	/* get content value "content": "val" */
	
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
				elseif ($newcids = split(' ', $cid)) {	
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
	
	/* get html node value for xmlliterals and cdata sections */
	
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

	/* get text or attribute text */

	protected function return_text($documentTag)
	{
		if ($documentTag->getAttribute('datetime')) $text = $documentTag->getAttribute('datetime');
		elseif ($documentTag->getAttribute('content')) $text = $documentTag->getAttribute('content');
		elseif ($documentTag->getAttribute('title')) $text = $documentTag->getAttribute('title');
		else $text = $documentTag->nodeValue;
		$text = str_replace(array("\r\n", "\r", "\n", "\t"), '', $text);
		return $text;
	}

	/* return text nodes */
	
	protected function return_text_nodes($val, $documentTag, $prop, $xml) 
	{
		if (isset($val->content)) $text = $this->get_content($val, $documentTag);
		else $text = $this->return_text($documentTag);
		$result = $xml->createElement($this->get_label($val, $prop), $text );
		return $result;
	}
	

	/* return a resource */
	
	protected function return_resource($documentTag, $url) 
	{
		if ($documentTag->getAttribute('src')) $resource = $documentTag->getAttribute('src');
		elseif ($documentTag->getAttribute('href')) $resource = $documentTag->getAttribute('href');
		elseif ($documentTag->getAttribute('id')) $resource = $url."#".$documentTag->getAttribute('id');
		return $this->return_url($resource , $url);
	}
	
	/* return error */
	
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
	
	/* set headers */
	
	private function set_headers() 
	{
		$this->output_type == 'xml' ?
		header("Content-type: application/xml") :
		header("Content-type: application/rdf+xml");
		header("Content-Disposition: inline; filename=".$this->file);
	}
	
	/* query html, return RDF values */
	
	private function json_query_rdf_properties($url, $object, $documentTag, $root, $xml, $hasroot ='') 
	{
		foreach ($this->json_value($object, 'select') as $item => $value) {
		foreach ($this->return_node_or_attribute($item) as $attribute => $item) {
		if ($this->test_node_attribute($item, $documentTag, $attribute) != false) {	
			if (isset($value->select)) {
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
					foreach ( $this->json_value($value, 'select')  as $prop => $val ) 
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
					} else {
						if ($hasroot == true ) {
							$this->return_rdf_properties($url, $item, $value, $documentTag, $root, $xml);
						}else {
							$class = $xml->createElement('rdf:Description');
							$root->appendChild($class);
							$this->rdf_about($url, $documentTag, $value, $class);
							$this->return_rdf_properties($url, $item, $value, $documentTag, $class, $xml);
						}
					}
				}
			}
		}
	}

	/* query html, Set RDF about */
	
	private function rdf_about($url, $documentTag, $value, $class) 
	{
	$about = isset($value->about) ? $value->about : true;
	if ( $documentTag->getAttribute('id') ) {
		if ( isset($value->about) && $about != false ) {
			foreach ($value->about as $id => $uri ) {
				if ($newids = split(' ', $id)) { 
					foreach ($newids as $newid) {
						return ( $documentTag->getAttribute('id') == $newid  ? 
							$class->setAttribute("rdf:about", $uri) : 
							$class->setAttribute('rdf:about', $url.'#'.$documentTag->getAttribute('id'))
						);
					}
				}
				else {
					return ( $documentTag->getAttribute('id') == $id  ? 
						$class->setAttribute("rdf:about", $uri) : 
						$class->setAttribute('rdf:about', $url.'#'.$documentTag->getAttribute('id'))
					);
				}
			}
		} 
		elseif( $about != false ) return $class->setAttribute('rdf:about', $url.'#'.$documentTag->getAttribute('id'));
	} 
	elseif ($documentTag->getAttribute('href')) return $class->setAttribute('rdf:about', $this->return_url($documentTag->getAttribute('href'), $url));
	elseif ($documentTag->getAttribute('src')) return $class->setAttribute('rdf:about', $this->return_url($documentTag->getAttribute('src'), $url));
	elseif( $about != false ) return $class->setAttribute('rdf:about', $url); 
	}

	/* query html, return uri  */
	
	private function return_query_uri($xml, $class, $url, $val, $documentTag, $prop)
	{
		$resource = isset($val->content) ? $this->get_content($val, $documentTag, true) : $this->return_resource($documentTag, $url);
		if ($lables = explode(' ', $this->get_label($val, $prop))) {
			foreach ($lables as $lable) {
				$property = $xml->createElement($lable);
				$property->setAttribute("rdf:resource", $this->return_url($resource, $url));
				return $class->appendChild($property);
			}
		} else {
			$property = $xml->createElement($this->get_label($val, $prop));
			return $property->setAttribute("rdf:resource", $this->return_url($resource, $url));
		}
	}
	
	/* query html, return xmlliteral  */

	private function return_query_xmlliteral($xml, $class, $val, $documentTag, $prop)
	{
		$children = $documentTag->childNodes;
		$property = $xml->createElement($this->get_label($val, $prop));
		$property->setAttribute('rdf:datatype', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral');
		foreach ($children as $child) {
			if ($child != new DOMText) $child->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
			$property->appendChild($xml->importNode($child, TRUE));
		}
		$class->appendChild($property);
	}

	/* query html, return cdata  */

	private function return_query_cdata($xml, $class, $val, $documentTag, $prop, $result)
	{
		$children = $documentTag->childNodes;
		$property = $xml->createElement($this->get_label($val, $prop));
		foreach ($children as $child) {
			$result .= $this->return_node_value($child);
		}
		$cdata = $property->ownerDocument->createCDATASection($result);
		$property->appendChild($cdata);
		$class->appendChild($property);
	}
	
	/* query html, return RDF properties */
	
	private function return_rdf_properties($url, $prop, $val, $documentTag, $class, $xml) 
	{
	$resource = '';
	$result = '';
	$type = isset( $val->type ) ? $val->type : "text";
	if (!isset($val->select)) {
		switch ($type) {
			case "text":
				$property = $this->return_text_nodes($val, $documentTag, $prop, $xml);
				return $class->appendChild($property);
			break;
			case "uri":
				$property = $this->return_query_uri($xml, $class, $url, $val, $documentTag, $prop);
				return $class->appendChild($property);
			break;
			case 'uriplain': 
				$resource = isset($val->content) ? $this->get_content($val, $documentTag, true) : $this->return_resource($documentTag, $url);
				$property = $xml->createElement($this->get_label($val, $prop), $this->return_url($resource, $url));
				return $class->appendChild($property);
			break;
			case 'xmlliteral':
				return $this->return_query_xmlliteral($xml, $class, $val, $documentTag, $prop, $result);
			break;	
			case 'cdata':
				return $this->return_query_cdata($xml, $class, $val, $documentTag, $prop, $result);
			break;
			default:
				$property = $this->return_text_nodes($val, $documentTag, $prop, $xml);
				$property->setAttribute('rdf:datatype', $type);
				return $class->appendChild($property);
			break;
			}
		} 
		return $this->return_type( $xml, $prop, $val, $url, $documentTag, $class );
	}

	/* query html, return root node or property */
	
	function return_type( $xml, $prop, $val, $url, $documentTag, $class )
	{
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

	/* Save RDF document return results */
	
	public function asRDF()
	{
		if (!$this->document) return $this->return_error('1');
		$dom = new DomDocument();
		@$dom->loadHtml($this->document);
		$xpath = new DomXpath($dom);
		$this->data = $this->usedata != '' ? $this->get_file_contents($this->usedata) : $this->get_file_contents($this->json_dataset($xpath, $this->url));
		if (!$this->data) return $this->return_error('2');
		$this->json = json_decode($this->data);
		if (!$this->json) return $this->return_error('3');
		$object = $this->json;
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
		$this->set_headers();
		return $document;
	}
	
	/* END */
}
?>