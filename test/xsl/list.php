#! /usr/bin/php
<?php

require __DIR__.'/../../lib/ju1ius/Premailer/Formatter/TextContext.php';
require __DIR__.'/../../lib/ju1ius/Premailer/Formatter/ListFormatter.php';

use ju1ius\Premailer\Formatter\ListFormatter;


$html_doc = DOMDocument::loadHTMLFile(__DIR__.'/list.html');
$html_doc->preserveWhiteSpace = false;

$xsl_doc = DOMDocument::load(__DIR__.'/list.xsl');
$xslt = new XSLTProcessor();
$xslt->importStylesheet($xsl_doc);

echo $xslt->transformToXml($html_doc);
//$fmt = new ListFormatter($html_doc);
//$fmt->format();

//echo $html_doc->saveHTML();
