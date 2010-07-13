<?php
  ini_set('display_errors', 0);
	
  include_once("Dataset_Transformr.php");
  
  if (isset($_GET['url'])) { 
  
	$data = new Dataset_Transformr;
  
	print $data->asRDF();
  
  } else {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>JSON Datasets</title>
	<style type="text/css">
	<!--
		body{padding:1em 2em;margin:0 auto;font-family:sans-serif;color:black;background:white;width:80%}th,td{font-family:sans-serif}h1,h2,h3,h4,h5,h6{text-align:left}h1,h2,h3{color:#005A9C;background:white}h1{font:170% sans-serif}h2{font:140% sans-serif}h3{font:120% sans-serif}h4{font:bold 100% sans-serif}h5{font:italic 100% sans-serif}h6{font:small-caps 100% sans-serif}div.head{margin-bottom:1em}div.head h1{margin-top:2em;clear:both}div.head table{margin-left:2em;margin-top:2em}pre{margin-left:2em}/**/dt,dd{margin-top:0;margin-bottom:0}dt{font-weight:bold}pre,code{font-family:monospace}ul.toc{list-style:disc;list-style:none}#references ul li { list-style-type:none;}pre{background-color:#d5dee3;border-top-width:4px;border-top-style:double;border-top-color:#d3d3d3;border-bottom-width:4px;border-bottom-style:double;border-bottom-color:#d3d3d3;padding:4px;margin:0em}.vcard{padding-top:30px;}
	//-->
	</style>
</head>
<body id="home">
<div id="header">
<h1><a href="./">JSON Datasets</a></h1>
<p>A JSON Dataset uses the <a href="#html-query-syntax">HTML Query syntax</a> to navigate the contents of an HTML document and output the results as RDF.</p>
</div>
<h2><a id="contents" name="contents"></a>Table of Contents</h2>
	<ol>
		<li><a href="#html-query-syntax">Html Query Syntax</a></li>
		<li><a href="#html-query-properties">Properties of HTML Query</a></li>
		<li><a href="#keyword-selectors">Keyword Selectors</a></li>
		<li><a href="#properties-of-select">Properties of keyword Selector</a></li>
		<li><a href="#root-selectors">Root Selectors</a></li>
		<li><a href="#rdf-about">Setting RDF about</a></li>
		<li><a href="#linking-to-a-transformation">Linking to a JSON Dataset</a></li>
        <li><a href="#dataset-parsing">Dataset Parsing</a></li>
		<li><a href="#examples">HTML Query Examples</a></li>
        <li><a href="#references">References</a></li>
		<li><a href="#similar">Similar Work</a></li>
	</ol>
<div id="content">
<div id="html-query-syntax">
<h2>Html Query Syntax</h2>
<p>Html Query is a self description mechanism that uses [1]<a href="#json-specification">JSON</a> to describe the contents of a Html document. Although Html Query can be used with a vocabulary such as [2]<a href="#microformats">microformats</a>, HTML query does not require the author to change the html of a document in any way, an author can just describe what already exists on a page without adding any extra attributes or elements to accommodate your intended semantics.</p>
<p>The following is an example of a simple Html Query.</p>
<pre>{
	&quot;select&quot;:  {
		&quot;from&quot;: &quot;http://example.com/&quot;,
		&quot;prefix&quot;: {
			&quot;dc&quot;: &quot;http://purl.org/dc/elements/1.1/&quot;
		},
		&quot;where&quot;: {
			&quot;title&quot;: {  &quot;label&quot;: &quot;dc:title&quot; }
		}
	}
}</pre>
<p>When the query is performed on the following url <a href="http://example.com/">http://example.com/</a>,</p>
<p>It would result in the following output.</p>
<pre><?php 
$str = '<?xml version="1.0" encoding="utf-8"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" 
	 xmlns:dc="http://purl.org/dc/elements/1.1/" 
	 xml:base="http://example.com/">
  <rdf:Description rdf:about="http://example.com/">
    <dc:title>Example Web Page</dc:title>
  </rdf:Description>
</rdf:RDF>
';
echo htmlentities($str);
?></pre>
<p>Try a live example, by clicking <a href="http://weborganics.co.uk/dataset/?url=http://weborganics.co.uk/dataset/example.json">example.json</a>.</p>
<p>Html Query uses the following patterns to select keywords and set output parameters of a html document.</p>
<pre>&quot;object&quot;:  {
	&quot;selector&quot;: {
		&quot;property&quot; : &quot;value&quot;
	}
}</pre>
<p>and...</p>
<pre>&quot;object&quot; : {
	&quot;property&quot; : &quot;value&quot;
}
</pre>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="html-query-properties">
<h2>Properties of HTML Query</h2>
<ol>
	<li id="query">
		<h3>select</h3>
		<p>All Html Queries <strong>must</strong> begin with select.</p>
		<p><em>Example:</em></p>
		<pre>{
	&quot;select&quot; : {
		...
	}
}</pre>
	</li>
	
	<li id="query-from">
		<h3>from</h3>
		<p><code>from</code> is a url for the document to be queried. The value of <code>from</code> should be an absolute url</p>
		<p><em>Example:</em></p>
		<pre>&quot;from&quot;: &quot;http://example.com/&quot;</pre>
		<p>The <code>from</code> property may be omitted. If the <code>from</code> property is omitted from a query, then the parser sets the value of <code>from</code> to the referring page.</p>
	</li>
	
	<li id="query-prefix">
		<h3>prefix</h3>
		<p><code>prefix</code> contains a comma separated list of vocabulary prefixes and uri's to be used in the RDF output of a query and in the query itself.</p>
		<p>A prefix is an abbreviation of a URI. Prefixes are used instead of using full URI's . Prefixes form the first part of a uri reference or [3]<a href="#QName">QName</a> in RDF terms.</p>
		<p><em>Pattern:</em></p>
		<pre>&quot;prefix&quot;: {
	&quot;prefix&quot;: &quot;uri&quot;,
	...
}</pre>
		<p><em>Example:</em></p>
		<pre>&quot;prefix&quot;: {
	&quot;vcard&quot;: &quot;http://www.w3.org/2006/vcard/ns#&quot;,
	...
}</pre>
		<p>A default prefix for the output document may be set using the keyword &quot;value&quot;</p>
		<p><em>Example:</em></p>
		<pre>&quot;prefix&quot;: {
	&quot;value&quot;: &quot;http://www.w3.org/2006/vcard/ns#&quot;,
	...
}</pre>
	</li>
	
	<li id="query-where">
		<h3>where</h3>
		<p><code>where</code> contains a list of comma separated html keyword selectors and their output properties. 
		<code>where</code> may contain nested <code>where</code> statements. If a <code>where</code> keyword does contain a nested <code>where</code> statement then the keyword is treated as a &quot;<a href="#root-items">root</a>&quot; value, else the keyword is a property. A property keyword should not contain further <code>where</code> statements.</p>
		<p><em>Pattern of a root keyword that contains a nested keyword:</em></p>
		<pre>&quot;where&quot;: {
	&quot;selector&quot;:  {
		&quot;property&quot;: &quot;value&quot;,
		&quot;where&quot;: {
			&quot;selector&quot; : {
				&quot;property&quot;: &quot;value&quot;
			}
		}
	}
}</pre>
		<p><em>Pattern of a keyword that is a property:</em></p>
		<pre>&quot;where&quot;: {
	&quot;selector&quot; : {
		&quot;property&quot;: &quot;value&quot;
	}
}</pre>
		
	</li>	
</ol>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="keyword-selectors">
<h2>Keyword Selectors.</h2>
<p>Html Query uses four CSS like selectors to navigate keywords of a html document. Selectors are as defined below.</p>
<ol>
	<li>
		<h3>element</h3>
		<p>The selector is an element name.</p>
		<p><em>Example:</em></p>
		<pre>&quot;h1&quot; is equal to &lt;h1&gt;&lt;/h1&gt;</pre>
	</li>
	<li>
		<h3>.class</h3>
		<p>The selector is a class name.</p>
		<p><em>Example:</em></p>
		<pre>&quot;.example&quot; is equal to class=&quot;example&quot;</pre>
	</li>
	<li>
		<h3>#id</h3>
		<p>The selector is the id of an element</p>
		<p><em>Example:</em></p>
		<pre>&quot;#example&quot; is equal to id=&quot;example&quot;</pre>
	</li>
	<li>
		<h3>attribute~=name</h3>
		<p>The selector contains an attribute name.</p>
		<p><em>Example:</em></p>
		<pre>&quot;rel~=example&quot; is equal to rel=&quot;example&quot;</pre>
	</li>
</ol>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="properties-of-select">
<h2>Properties of Select</h2>
<p>HTML Query Selectors contain six properties to set both input and output values.</p>
<p><strong>Properties</strong></p>
<ol>
	<li id="query-about">
		<h3>about</h3>
		<p>A URL for what this &quot;keyword&quot; is about.  The &quot;about&quot; property contains a space seperated list of HTML Id's which sets the subject of the keyword in [4]<a href="#rdf-concepts">RDF terms</a>.</p>
		<p><em>Pattern:</em></p>
		<pre>&quot;about&quot; :  {
	&quot;id&quot;: &quot;url&quot;,
	...
}</pre>
		<p>Id's set by the about property are matched with HTML id's on a page, the URL value is used in the output. The about pattern  allows different id's on a page to have the same URL value, or each id can have their own unique URL value.</p>
		<p><em>Example:</em></p>
		<pre>&quot;about&quot;: {
	&quot;fred&quot;: &quot;http://example.com/&quot;
}</pre>
		<p><em>Example HTML:</em></p>
		<pre>&lt;div id=&quot;fred&quot;&gt;
	...
&lt;/div&gt;</pre>
		<p>The about property can also accept a boolean value of &quot;false&quot;. Booleans in JSON <em>may</em> be unquoted strings.  Setting the about property to false prevents a parser from generating an about attribute in the RDF output.</p>
		<p><em>Example:</em></p>
		<pre>&quot;about&quot;: false</pre>
	</li>
	<li id="query-label">
		<h3>label</h3>
		<p>Labels are used as both unique identifiers in a query and element names in the RDF output. A label is a &quot;predicate&quot; or &quot;property&quot; in [4]<a href="#rdf-concepts">RDF terms</a>.</p>
		<p><em>Pattern:</em></p>
		<pre>&quot;label&quot;: &quot;property&quot;</pre>
		<p><em>Example:</em></p>
		<pre>&quot;label&quot;: &quot;foaf:name&quot;</pre>
		<p>The pre-defined type <a href="#pre-defined-types">uri</a> <em>may</em> contain a list to space seperated labels.</p>
		<p><em>Example:</em></p>
		<pre>&quot;label&quot;: &quot;foaf:primaryTopic foaf:maker&quot;</pre>
	</li>
	<li id="query-type">
		<h3>type</h3>
		<p>The datatype of a keyword or the datatype of the object in [4]<a href="#rdf-concepts">RDF terms</a>. As well as any standard datatypes such as [5]<a href="#xml-schema">XML Schema datatypes</a>,  HTML Query also supports five <a href="#pre-defined-types">Pre-Defined types</a>. If type is omitted from a keyword the parser defaults to just &quot;text&quot;.</p>
		<p><em>Pattern:</em></p>
		<pre>&quot;type&quot;: &quot;value&quot;</pre>
		<p><em>Example:</em></p>
		<pre>&quot;type&quot;: &quot;http://www.w3.org/2001/XMLSchema#dateTime&quot;</pre>
		<p id="pre-defined-types">Pre-Defined Types.</p>
		<ol>
			<li>
				<h4>text</h4>
				<p>The content is just text or a plain literal in RDF. Text is extracted in the following order, @datetime, @content, @title if none of these HTML attributes are present the value is the node value.</p>
				<p><em>Example Output:</em></p>
				<pre>&lt;label&gt;Text&lt;/label&gt;</pre>
			</li>
			
			<li>
				<h4>uri</h4>
				<p>A URI, or simply a URL. When stetting the keyword type to uri, the parser extracts the value in the following order, @src then @href.</p> 
				<p><em>Example Output:</em></p>
				<pre>&lt;label rdf:resource=&quot;http://someurl.com/&quot; /&gt;</pre>
				<p>If neither @src or @href are present the value is @id converted to an absolute relative URL, this allows the author to link to other keyword items in the RDF output.</p>
				<p><em>Example Output:</em></p>
				<pre>&lt;label rdf:resource=&quot;http://someurl.com/#id&quot; /&gt;</pre>
			</li>
			
			<li>
				<h4>uriplain</h4>
				<p>The behaviour of uriplain is the same as uri, A uriplain is outputted as a plain literal, text.</p>
				<p><em>Example Output:</em></p>
				<pre>&lt;label&gt;http://someurl.com/&lt;/label&gt;</pre>
			</li>
			
			<li>
				<h4>xmlliteral</h4>
				<p>An XMLLiteral string. An <code>xmlliteral</code> <em>may</em> contain HTML markup or special characters, if the value does contain markup the value should be converted to XHTML, all elements should use the http://www.w3.org/1999/xhtml XML namespace.</p>
				<p><em>Example Output:</em></p>
				<pre>&lt;x:label rdf:datataype=&quot;http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral&quot;&gt;
	&lt;p xmlns=&quot;http://www.w3.org/1999/xhtml&quot;&gt;Some text.&lt;/p&gt;
&lt;/x:label&gt;</pre>
			</li>
			
			<li>
				<h4>cdata</h4>
				<p>A character data section. A cdata section may contain HTML markup or special characters.</p>
				<p><em>Example Output:</em></p>
				<pre>&lt;label&gt;&lt;![CDATA[&lt;p&gt; Some text.&lt;/p&gt;]]&gt;&lt;/label&gt;</pre>
			</li>
		</ol>
	</li>
	<li id="query-content">
		<h3>content</h3>
		<p>Content contains a space seperated list of HTML Id's, and sets the default content of a keyword. Id's set by the content property are matched with HTML id's on a page. The value of content is used in the RDF output.</p>
		<p><em>Pattern:</em></p>
		<pre>&quot;content&quot; : {
	&quot;id&quot;: &quot;value&quot;
}</pre>
		<p>It is also possible to set a default content.</p>
		<p><em>Pattern:</em></p>
		<pre>&quot;content&quot;  &quot;value&quot;
</pre>
	</li>
	<li id="query-multiple">
		<h3>multiple</h3>
		<p>By default the parser ignores multiple values of the same selector, only the fist match of a selector is parsed.</p>
		<p>Multiple has a  boolean value of &quot;true&quot;, this causes the parser to extract multiple instances of the same selector with different values.</p>
		<p><em>Pattern:</em></p>
		<pre>&quot;multiple&quot;: true</pre>
		<p><em>Example:</em></p>
		<pre>&quot;rel~=friend&quot;: {
	&quot;multiple&quot;: true,
	...
}</pre>
	</li>
	<li id="query-rev">
		<h3>rev</h3>
		<p>Rev is a reverse property name. Rev can be used with any root property (a Selector that contains other keyword's) to create an extra chain before a keyword.</p>
		<p><em>Example:</em></p>
		<pre>&quot;where&quot;: {
	&quot;.vcard&quot;: {
		&quot;rev&quot;: &quot;knows&quot;,
		&quot;label&quot;: &quot;Person&quot;,
		&quot;where&quot;: {
			.....
		}
	}
}</pre>
	<p>The above example would result in the following RDF.</p>
	<pre>&lt;knows&gt;
	&lt;Person rdf:about=&quot;...&quot;&gt;
		...
	&lt;/Person&gt;
&lt;/knows&gt;</pre>
	</li>
</ol>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="root-selectors">
<h2>Root Selectors</h2>
<p>Root Selectors are determined by whether or not a keyword contains further nested <a href="#query-where">where statements</a>.  If a keyword does contain nested <a href="#query-where">where statements</a> the Selector is said to be a &quot;root selector&quot;, if not then the Selector is said to be a property.</p>
<p>Root Selectors can also have <a href="#query-type">type</a> which resolve to a rdf:parseType. Valid types are:</p>
	<ul>
		<li>collection => rdf:parseType="Collection"</li>
		<li>resource => rdf:parseType="Resource"</li>
		<li>literal => rdf:parseType="Literal"</li>
	</ul>
<p><em>Example:</em></p>
<pre>&quot;type&quot;: &quot;resource&quot;</pre>

<p>The author can also use <code>type</code> as a rdf:Type selector, this will add a rdf:Type element to the root selector.</p>
<p><em>Example:</em></p>
<pre>&quot;type&quot;: &quot;http://www.w3.org/2000/10/swap/pim/contact#ContactLocation&quot;</pre>

<p>In the absence of a root selector type and a <a href="#query-rev">reverse property name</a> all properties contained in a root selector are wrapped in a blank rdf:Description element.</p>
<p><em>Example:</em></p>
<pre>&lt;vcard:adr&gt;
  &lt;rdf:Description&gt;
    &lt;vcard:locality&gt;Albuquerque&lt;/vcard:locality&gt;
  &lt;/rdf:Description&gt;
&lt;/vcard:adr&gt;</pre>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="rdf-about">
<h2>Setting RDF about</h2>
<p>In the  absence of the <a href="#query-about">about property</a> HTML Query sets the RDF about attribute by selecting HTML values from the selected element or attribute in the following order, @href, @src and @id. If the value is @id then an absolute hash URI compiled from the <a href="#query-from">from</a> url and the value of @id. If a parser fails to select @href, @src or @id then the RDF about attribute is set to the value of <a href="#query-from">from</a></p>
<p> Blank Nodes may be generated by setting the <a href="#query-about">about property</a> to false.</p>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="linking-to-a-transformation">
<h2>Linking to a JSON Dataset</h2>
<p>A HTML Query for a page can be linked to using the html rel value &quot;dataset&quot;
The [6]<a href="#hypertext-links">HTML Link relation</a> &quot;dataset&quot; is a short uri reference to http://weborganics.co.uk/ns/dataset ( this page ).
By using rel dataset you are saying the url referenced in the href attribute of a link is a dataset  for the referring page. The link to a dataset should also contan a type specifier of "application/json"</p>
<p><em>Example:</em></p>
<pre>&lt;link rel=&quot;dataset&quot; href=&quot;http://example.com/my-dataset.json&quot; type=&quot;application/json&quot;&gt;</pre>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="dataset-parsing">
<h2>Dataset Parsing</h2>
<p>This Page supports dataset parsing available at http://weborganics.co.uk/dataset/?url=(+your url). The dataset parser supports transforming your dataset by <a href="#linking-to-a-transformation">linking to it</a> in the head of your html document</p>
<p><em>Example:</em></p>
<p><a href="http://weborganics.co.uk/dataset/?url=http://weborganics.co.uk/dataset/article.html">http://weborganics.co.uk/dataset/?url=http://weborganics.co.uk/dataset/article.html</a></p>
<p>You can also parse just a dataset , the <a href="#query-from">from property</a> must be set, this is intended to be used for testing your dataset's.</p>
<p><em>Example:</em></p>
<p><a href="http://weborganics.co.uk/dataset/?url=http://weborganics.co.uk/dataset/dataset-article.json">http://weborganics.co.uk/dataset/?url=http://weborganics.co.uk/dataset/dataset-article.json</a></p>
<h3>Bookmarklet</h3>
<p>There is also a bookmarklet that you can drag to your favourites toolbar.</p>
<p title="Bookmarklet, Drag to favorites"><em>Bookmarklet:</em> <a href="javascript:void(location.href='http://weborganics.co.uk/dataset/?url='+location.href)">DatasetParse</a></p>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="examples">
<h2>Examples</h2>
<p>The following examples were created during the development of the HTML Query syntax. Please click one of the following links to view the examples.</p>
<p>Click the link at the bottom of each page that says &quot;Get RDF dataset&quot; to test.</p>
	<ol>
    	<li>HTML <a href="article.html">Article</a>, view <a href="dataset-article.json">json</a></li>
        <li>HTML hCard as <a href="foaf.html">FOAF</a>, view <a href="dataset-foaf.json">json</a></li>
        <li>HTML <a href="hatom.html">hAtom</a>, view <a href="dataset-hatom.json">json</a></li>
        <li>HTML <a href="hcal.html">hCalendar</a>, view <a href="dataset-hcal.json">json</a></li>
        <li>HTML <a href="hproduct.html">hProduct</a>, view <a href="dataset-hproduct.json">json</a></li>
    	<li>HTML <a href="hreview.html">hReview</a>, view <a href="dataset-hreview.json">json</a></li>
        <li>HTML <a href="organization.html">Organization</a>, view <a href="dataset-organization.json">json</a></li>
        <li>HTML <a href="hcard.html">hCard</a>, view <a href="dataset-hcard.json">json</a></li>
		<li>HTML <a href="http://weborganics.co.uk/demo/haudio.html">hAudio</a>, view <a href="http://weborganics.co.uk/demo/haudio-query.json">json</a></li>
	</ol>
<p>The following are &quot;real world&quot; examples performed on live webpages.</p>
	<ol>
		<li>A youtube search for videos about the semantic web. view <a href="youtube.json">json</a> or <a href="http://weborganics.co.uk/dataset/?url=http://weborganics.co.uk/dataset/youtube.json">results</a></li>
		<li>...</li>
	</ol>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>

<div id="references">
<h2>References</h2>
<ol>
	<li>
		JSON specification. 
		<a href="http://www.json.org/" name="json-specification" id="json-specification">http://www.json.org/</a>
	</li>
	<li>
		Microformats. 
		<a href="http://microformats.org/" name="microformats" id="microformats">http://microformats.org/</a>
	</li>
	<li>
		QName. 
		<a href="http://en.wikipedia.org/wiki/QName" name="QName" id="QName">http://en.wikipedia.org/wiki/QName</a>
	</li>
	<li>
		RDF Concepts and Abstract Syntax. 
		<a href="http://www.w3.org/TR/rdf-concepts/" name="rdf-concepts" id="rdf-concepts">http://www.w3.org/TR/rdf-concepts/</a>
	</li>
	<li>
		XML Schema Part 2: Datatype's Second Edition. 
		<a href="http://www.w3.org/TR/xmlschema-2/" name="xml-schema" id="xml-schema">http://www.w3.org/TR/xmlschema-2/</a>
	</li>
	<li>
		Hypertext Links in HTML. 
		<a href="http://www.w3.org/TR/WD-htmllink-970328#link" name="hypertext-links" id="hypertext-links">http://www.w3.org/TR/WD-htmllink-970328#link</a>
	</li>
</ol>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>
<div id="similar">
<h2>Similar Work</h2>
<ol>
	<li><a href="http://json-schema.org/">JSON Schema</a></li>
</ol>
<p><a href="#contents" title="contents">[back to contents]</a></p>
</div>
</div>
<div id="license" class="vcard">
<p>
	<a class="url fn org" href="http://weborganics.co.uk/">WebOrganics</a> 2010, 
	<a href="http://creativecommons.org/licenses/publicdomain/deed.en_GB" rel="license">Public Domain Licence</a>.
</p>
<img alt="Semantic Web" src="/images/sw-horz.png" />
</div>
</body>
</html>
<?php
 ini_set('display_errors', 1);
} ?>