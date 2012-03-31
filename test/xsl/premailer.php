#! /usr/bin/php
<?php

$html_doc = DOMDocument::loadHTMLFile(__DIR__.'/premailer.html');
//$html_doc->preserveWhiteSpace = false;
$html_doc->formatOutput = true;

$xsl_doc = DOMDocument::load(__DIR__.'/premailer.xsl');
$xslt = new XSLTProcessor();
$xslt->importStylesheet($xsl_doc);

echo $xslt->transformToXml($html_doc);
