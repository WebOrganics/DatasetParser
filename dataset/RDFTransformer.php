<?php
include_once("../arc/ARC2.php");

ARC2::inc('Class');

class RDFTransformer
{
	function __construct() {
		$this->a = $this->config();
	}
	
	public function return_output($url, $output)
	{
		$rdf = new DomDocument('1.0', 'utf-8');
		$xml = file_get_contents($url);
		$rdf->loadXML($xml);
		$rdf->formatOutput = true;
		$document = $rdf->saveXML();
		return $this->ARC2_Parse($url, $document, $output);
	}

	public function return_semhtml($url, $output, $type) {
	
		$parser = ARC2::getSemHTMLParser($this->a);
		$parser->parse($url);
		$parser->extractRDF($type);
		$triples = $parser->getTriples();
		$document = $parser->toRDFXML($triples);
		return $this->ARC2_Parse($url, $document, $output);
	}

	private function toRDFa($triples) {
		ARC2::inc('RDFaSerializer');
		$rdfa = new ARC2_RDFaSerializer($this->a, $this);
		return ( isset($triples[0]) && isset($triples[0]['s']) ) ? $rdfa->getSerializedTriples($triples) : $rdfa->getSerializedIndex($triples);
	}

	public function ARC2_Parse($url, $document, $output) {
	
	$parser = ARC2::getRDFParser($this->a);
	$parser->parse($url, $document);
	$triples = $parser->getTriples();
		
		switch ($output) 
		{
			case 'ntriples':
				$file = $this->rand_filename('nt');
				header("Content-type: text/plain");
				header("Content-Disposition: inline; filename=".$file);
				$result = $parser->toNTriples($triples); 
			break;
			
			case 'turtle':
				$file = $this->rand_filename('ttl');
				header("Content-type: text/turtle");
				header("Content-Disposition: inline; filename=".$file);
				$result = $parser->toTurtle($triples);
			break;
			
			case 'rdfjson':	
				$file = $this->rand_filename('json');
				header("Content-type: application/json");
				header("Content-Disposition: inline; filename=".$file);
				$result = $parser->toRDFJSON($triples);
			break;
			
			case 'rdf':	
				$file = $this->rand_filename('rdf');
				header("Content-type: application/rdf+xml");
				header("Content-Disposition: inline; filename=".$file);
				$result = $parser->toRDFXML($triples);
			break;
			
			case 'html':	
				$file = $this->rand_filename('html');
				header("Content-type: text/html");
				header("Content-Disposition: inline; filename=".$file);
				$result = $parser->toHTML($triples);
			break;
			
			case 'rdfa':	
				$file = $this->rand_filename('html');
				header("Content-type: text/html");
				header("Content-Disposition: inline; filename=".$file);
				$result = $this->toRDFa($triples);
			break;
		}
		return $result;
	}
	
	private function config() 
	{
	$ns = array(
		'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
		'owl' => 'http://www.w3.org/2002/07/owl#',
		'xsd' => 'http://www.w3.org/2001/XMLSchema#',
		'foaf' => 'http://xmlns.com/foaf/0.1/',
		'dc' => 'http://purl.org/dc/elements/1.1/',
		'dc_terms' => 'http://purl.org/dc/terms/',
		'dc_type' => 'http://purl.org/dc/dcmitype/',
		'rss' => 'http://purl.org/rss/1.0/',
		'taxo' => 'http://purl.org/rss/1.0/modules/taxonomy/',
		'content' => 'http://purl.org/rss/1.0/modules/content/',
		'sy' => 'http://purl.org/rss/1.0/modules/syndication/',
		'cal' => 'http://www.w3.org/2002/12/cal/ical#',
		'sioc' => 'http://rdfs.org/sioc/ns#',
		'sioct' => 'http://rdfs.org/sioc/types#',
		'doap' => 'http://usefulinc.com/ns/doap#',
		'gr' => 'http://purl.org/goodrelations/v1#',
		'geo' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
		'gv' => 'http://data-vocabulary.org/',
		'wot' => 'http://xmlns.com/wot/0.1/',
		'mo' => 'http://purl.org/ontology/mo/',
		'frbr' => 'http://purl.org/vocab/frbr/core#',
		'vs' => 'http://www.w3.org/2003/06/sw-vocab-status/ns#',
		'tl' => 'http://purl.org/NET/c4dm/timeline.owl#',
		'time' => 'http://www.w3.org/2006/time#',
		'contact' => 'http://www.w3.org/2000/10/swap/pim/contact#',
		'bio' => 'http://vocab.org/bio/0.1/',
		'rel' => 'http://purl.org/vocab/relationship/',
		'rev' => 'http://purl.org/stuff/rev#',
		'voc' => 'http://webns.net/mvcb/',
		'air' => 'http://www.daml.org/2001/10/html/airport-ont#',
		'aff' => 'http://purl.org/vocab/affiliations/0.1/',
		'cc' => 'http://creativecommons.org/ns#',
		'money' => 'http://www.purl.org/net/rdf-money/',
		'media' => 'http://purl.org/microformat/hmedia/',
		'audio' => 'http://purl.org/net/haudio#',
		'xhv' => 'http://www.w3.org/1999/xhtml/vocab#',
		'xfn' => 'http://gmpg.org/xfn/11#',
		'dbp' => ' http://dbpedia.org/property/',
		'dbpr' => 'http://dbpedia.org/resource/',
		'talk' => 'http://www.w3.org/2004/08/Presentations.owl#',
		'doc' => 'http://www.w3.org/2000/10/swap/pim/doc#',
		'act' => 'http://www.w3.org/2001/sw/',
		'org' => 'http://www.w3.org/2001/04/roadmap/org#',
		'vc' => 'http://www.w3.org/2001/vcard-rdf/3.0#',
		'vcard' => 'http://www.w3.org/2006/vcard/ns#',
		'bibo' => 'http://purl.org/ontology/bibo/',
		'mf' => 'http://poshrdf.org/ns/mf#',
		'posh' => 'http://poshrdf.org/ns/posh/',
		'label' => 'http://www.w3.org/2004/12/q/contentlabel#',
		'icra' => 'http://www.icra.org/rdfs/vocabularyv03#',
		'uri' => 'http://www.w3.org/2006/uri#',
		'ogp' => 'http://opengraphprotocol.org/schema/'
	);
	return array(
		'ns' => $ns, 
		'auto_extract' => 0, 
		'serializer_type_nodes' => 1, 
		'bnode_prefix' => 'genid'.substr(md5(uniqid(rand())), 0, 4) 
	  );
    }
	
 	private function rand_filename($ext = '') {
		return substr(md5(uniqid(rand())), 0, 8).'.'.$ext;
	}
}
/* html_convert_entities($string) -- convert named HTML entities to 
 * XML-compatible numeric entities.
 */
function html_convert_entities($string) {
  return preg_replace_callback('/([a-zA-Z][a-zA-Z0-9]+);/', 
                               'convert_entity', $string);
}
/* Swap HTML named entity with its numeric equivalent. If the entity
 * isn't in the lookup table, this function returns a blank, which
 * destroys the character in the output - this is probably the 
 * desired behaviour when producing XML. */
function convert_entity($matches) {
  static $table = array('quot'    => '#34;',
                        'amp'      => '#38;',
                        'lt'       => '#60;',
                        'gt'       => '#62;',
                        'OElig'    => '#338;',
                        'oelig'    => '#339;',
                        'Scaron'   => '#352;',
                        'scaron'   => '#353;',
                        'Yuml'     => '#376;',
                        'circ'     => '#710;',
                        'tilde'    => '#732;',
                        'ensp'     => '#8194;',
                        'emsp'     => '#8195;',
                        'thinsp'   => '#8201;',
                        'zwnj'     => '#8204;',
                        'zwj'      => '#8205;',
                        'lrm'      => '#8206;',
                        'rlm'      => '#8207;',
                        'ndash'    => '#8211;',
                        'mdash'    => '#8212;',
                        'lsquo'    => '#8216;',
                        'rsquo'    => '#8217;',
                        'sbquo'    => '#8218;',
                        'ldquo'    => '#8220;',
                        'rdquo'    => '#8221;',
                        'bdquo'    => '#8222;',
                        'dagger'   => '#8224;',
                        'Dagger'   => '#8225;',
                        'permil'   => '#8240;',
                        'lsaquo'   => '#8249;',
                        'rsaquo'   => '#8250;',
                        'euro'     => '#8364;',
                        'fnof'     => '#402;',
                        'Alpha'    => '#913;',
                        'Beta'     => '#914;',
                        'Gamma'    => '#915;',
                        'Delta'    => '#916;',
                        'Epsilon'  => '#917;',
                        'Zeta'     => '#918;',
                        'Eta'      => '#919;',
                        'Theta'    => '#920;',
                        'Iota'     => '#921;',
                        'Kappa'    => '#922;',
                        'Lambda'   => '#923;',
                        'Mu'       => '#924;',
                        'Nu'       => '#925;',
                        'Xi'       => '#926;',
                        'Omicron'  => '#927;',
                        'Pi'       => '#928;',
                        'Rho'      => '#929;',
                        'Sigma'    => '#931;',
                        'Tau'      => '#932;',
                        'Upsilon'  => '#933;',
                        'Phi'      => '#934;',
                        'Chi'      => '#935;',
                        'Psi'      => '#936;',
                        'Omega'    => '#937;',
                        'alpha'    => '#945;',
                        'beta'     => '#946;',
                        'gamma'    => '#947;',
                        'delta'    => '#948;',
                        'epsilon'  => '#949;',
                        'zeta'     => '#950;',
                        'eta'      => '#951;',
                        'theta'    => '#952;',
                        'iota'     => '#953;',
                        'kappa'    => '#954;',
                        'lambda'   => '#955;',
                        'mu'       => '#956;',
                        'nu'       => '#957;',
                        'xi'       => '#958;',
                        'omicron'  => '#959;',
                        'pi'       => '#960;',
                        'rho'      => '#961;',
                        'sigmaf'   => '#962;',
                        'sigma'    => '#963;',
                        'tau'      => '#964;',
                        'upsilon'  => '#965;',
                        'phi'      => '#966;',
                        'chi'      => '#967;',
                        'psi'      => '#968;',
                        'omega'    => '#969;',
                        'thetasym' => '#977;',
                        'upsih'    => '#978;',
                        'piv'      => '#982;',
                        'bull'     => '#8226;',
                        'hellip'   => '#8230;',
                        'prime'    => '#8242;',
                        'Prime'    => '#8243;',
                        'oline'    => '#8254;',
                        'frasl'    => '#8260;',
                        'weierp'   => '#8472;',
                        'image'    => '#8465;',
                        'real'     => '#8476;',
                        'trade'    => '#8482;',
                        'alefsym'  => '#8501;',
                        'larr'     => '#8592;',
                        'uarr'     => '#8593;',
                        'rarr'     => '#8594;',
                        'darr'     => '#8595;',
                        'harr'     => '#8596;',
                        'crarr'    => '#8629;',
                        'lArr'     => '#8656;',
                        'uArr'     => '#8657;',
                        'rArr'     => '#8658;',
                        'dArr'     => '#8659;',
                        'hArr'     => '#8660;',
                        'forall'   => '#8704;',
                        'part'     => '#8706;',
                        'exist'    => '#8707;',
                        'empty'    => '#8709;',
                        'nabla'    => '#8711;',
                        'isin'     => '#8712;',
                        'notin'    => '#8713;',
                        'ni'       => '#8715;',
                        'prod'     => '#8719;',
                        'sum'      => '#8721;',
                        'minus'    => '#8722;',
                        'lowast'   => '#8727;',
                        'radic'    => '#8730;',
                        'prop'     => '#8733;',
                        'infin'    => '#8734;',
                        'ang'      => '#8736;',
                        'and'      => '#8743;',
                        'or'       => '#8744;',
                        'cap'      => '#8745;',
                        'cup'      => '#8746;',
                        'int'      => '#8747;',
                        'there4'   => '#8756;',
                        'sim'      => '#8764;',
                        'cong'     => '#8773;',
                        'asymp'    => '#8776;',
                        'ne'       => '#8800;',
                        'equiv'    => '#8801;',
                        'le'       => '#8804;',
                        'ge'       => '#8805;',
                        'sub'      => '#8834;',
                        'sup'      => '#8835;',
                        'nsub'     => '#8836;',
                        'sube'     => '#8838;',
                        'supe'     => '#8839;',
                        'oplus'    => '#8853;',
                        'otimes'   => '#8855;',
                        'perp'     => '#8869;',
                        'sdot'     => '#8901;',
                        'lceil'    => '#8968;',
                        'rceil'    => '#8969;',
                        'lfloor'   => '#8970;',
                        'rfloor'   => '#8971;',
                        'lang'     => '#9001;',
                        'rang'     => '#9002;',
                        'loz'      => '#9674;',
                        'spades'   => '#9824;',
                        'clubs'    => '#9827;',
                        'hearts'   => '#9829;',
                        'diams'    => '#9830;',
                        'nbsp'     => '#160;',
                        'iexcl'    => '#161;',
                        'cent'     => '#162;',
                        'pound'    => '#163;',
                        'curren'   => '#164;',
                        'yen'      => '#165;',
                        'brvbar'   => '#166;',
                        'sect'     => '#167;',
                        'uml'      => '#168;',
                        'copy'     => '#169;',
                        'ordf'     => '#170;',
                        'laquo'    => '#171;',
                        'not'      => '#172;',
                        'shy'      => '#173;',
                        'reg'      => '#174;',
                        'macr'     => '#175;',
                        'deg'      => '#176;',
                        'plusmn'   => '#177;',
                        'sup2'     => '#178;',
                        'sup3'     => '#179;',
                        'acute'    => '#180;',
                        'micro'    => '#181;',
                        'para'     => '#182;',
                        'middot'   => '#183;',
                        'cedil'    => '#184;',
                        'sup1'     => '#185;',
                        'ordm'     => '#186;',
                        'raquo'    => '#187;',
                        'frac14'   => '#188;',
                        'frac12'   => '#189;',
                        'frac34'   => '#190;',
                        'iquest'   => '#191;',
                        'Agrave'   => '#192;',
                        'Aacute'   => '#193;',
                        'Acirc'    => '#194;',
                        'Atilde'   => '#195;',
                        'Auml'     => '#196;',
                        'Aring'    => '#197;',
                        'AElig'    => '#198;',
                        'Ccedil'   => '#199;',
                        'Egrave'   => '#200;',
                        'Eacute'   => '#201;',
                        'Ecirc'    => '#202;',
                        'Euml'     => '#203;',
                        'Igrave'   => '#204;',
                        'Iacute'   => '#205;',
                        'Icirc'    => '#206;',
                        'Iuml'     => '#207;',
                        'ETH'      => '#208;',
                        'Ntilde'   => '#209;',
                        'Ograve'   => '#210;',
                        'Oacute'   => '#211;',
                        'Ocirc'    => '#212;',
                        'Otilde'   => '#213;',
                        'Ouml'     => '#214;',
                        'times'    => '#215;',
                        'Oslash'   => '#216;',
                        'Ugrave'   => '#217;',
                        'Uacute'   => '#218;',
                        'Ucirc'    => '#219;',
                        'Uuml'     => '#220;',
                        'Yacute'   => '#221;',
                        'THORN'    => '#222;',
                        'szlig'    => '#223;',
                        'agrave'   => '#224;',
                        'aacute'   => '#225;',
                        'acirc'    => '#226;',
                        'atilde'   => '#227;',
                        'auml'     => '#228;',
                        'aring'    => '#229;',
                        'aelig'    => '#230;',
                        'ccedil'   => '#231;',
                        'egrave'   => '#232;',
                        'eacute'   => '#233;',
                        'ecirc'    => '#234;',
                        'euml'     => '#235;',
                        'igrave'   => '#236;',
                        'iacute'   => '#237;',
                        'icirc'    => '#238;',
                        'iuml'     => '#239;',
                        'eth'      => '#240;',
                        'ntilde'   => '#241;',
                        'ograve'   => '#242;',
                        'oacute'   => '#243;',
                        'ocirc'    => '#244;',
                        'otilde'   => '#245;',
                        'ouml'     => '#246;',
                        'divide'   => '#247;',
                        'oslash'   => '#248;',
                        'ugrave'   => '#249;',
                        'uacute'   => '#250;',
                        'ucirc'    => '#251;',
                        'uuml'     => '#252;',
                        'yacute'   => '#253;',
                        'thorn'    => '#254;',
                        'yuml'     => '#255;'
                        );
						
  // Entity not found? Destroy it.
  return isset($table[$matches[1]]) ? $table[$matches[1]] : '';
}
?>
