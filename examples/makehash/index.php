<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title>Mustache Example: make hash</title>
</head>
<body>
	<h1>Mustache Example: make hash</h1>
	
	<p>Most Mustache examples take a hash and use it to populate the variables in a template. This one goes the other way &ndash; assuming you have a template with mustache variables, it will generate an empty hash.</p>
	
	<p>This is useful for cases where the person writing the template and the person who will be using it are not the same person, and you need a quick and easy way to let the person populating the template know what variables are expected.</p>
	
	<h2>The Template</h2>
	
	<p>Here is our template:</p>
	
	<pre>
	&lt;h1>{{header}}&lt;/h1>
{{#bug}}
{{/bug}}

{{#items}}
  {{#first}}
    &lt;li>&lt;strong>{{name}}&lt;/strong>&lt;/li>
  {{/first}}
  {{#link}}
    &lt;li>&lt;a href="{{url}}">{{name}}&lt;/a>&lt;/li>
  {{/link}}
{{/items}}

{{#empty}}
  &lt;p>The list is empty.&lt;/p>
{{/empty}}
	</pre>
	
	<h2>The Code</h2>
	
	<p>Run the template through Mustache <br />
	(assume that we have assigned the above template to the variable $template):</p>
	
	<pre>
	$m = new Mustache($template);
	//Returns an associative array - format as json for output purposes
	echo json_encode($m->generateHash());
	</pre>
	
	<h2>The Result</h2>
	
	<pre>
<?php
require_once '../../Mustache.php';
$template = file_get_contents('template.txt');
$m = new Mustache($template);
echo json_encode($m->generateHash());
?>	
	</pre>
</body>
</html>
