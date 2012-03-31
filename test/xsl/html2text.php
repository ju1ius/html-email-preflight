#! /usr/bin/php

<?php

$html_doc = DOMDocument::loadHTMLFile(__DIR__.'/test.html');
$xsl_doc  = DOMDocument::load(__DIR__.'/test.xsl');

$proc = new XSLTProcessor();
//$proc->registerPHPFunctions();
$proc->importStyleSheet($xsl_doc);
echo $proc->transformToXML($html_doc);
