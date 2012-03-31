#! /usr/bin/php
<?php

$html_doc = DOMDocument::loadHTMLFile(__DIR__.'/list.html');
$html_doc->preserveWhiteSpace = false;
$html_doc->formatOutput = true;

//$xsl_doc = DOMDocument::load(__DIR__.'/list.xsl');
$xsl_doc = DOMDocument::load(__DIR__.'/text-indent.xsl');
$xslt = new XSLTProcessor();
$xslt->importStylesheet($xsl_doc);

$processed_doc = $xslt->transformToDoc($html_doc);
$processed_doc->formatOutput = true;
$processed_doc->save(__DIR__.'/text-indent.xml');

//echo $processed_doc->saveXML();
//die;

$xpath = new DOMXPath($processed_doc);
$blocks = $xpath->query('//text[not(ancestor::text)]');
//$blocks = $xpath->query('/text[@line-prefix]');

$context = array(
  'depth'       => 0,
  'indent'      => "",
  'line_prefix' => "",
  'par_prefix'  => ""
);
$output = "";
foreach ($blocks as $block) {
  $output .= parseNode($block, $context);
}
echo $output;


function parseNode($node, $context)
{
  $type = $node->nodeType;
  if($type == XML_ELEMENT_NODE) {
    if ($node->tagName == 'text') {
      if ($node->hasAttribute('indent')) {
        $context['indent'] .= $node->getAttribute('indent');
        $context['depth']++; 
      }
      $context['line_prefix'] = '';
      if ($node->hasAttribute('par-prefix')) {
        $context['par_prefix'] = $node->getAttribute('par-prefix');
        // If we have a bullet, add a small padding
        // to following lines
        $context['line_prefix'] = '  ';
      }
      return parseChildren($node, $context);
    }
  } else if ($type == XML_TEXT_NODE) {
    return parseTextNode($node, $context);
  }
}

function parseChildren($node, $context)
{
  if($node->hasChildNodes()) {

    $text = "";
    $children = $node->childNodes;
    $len = $children->length;
    $first = $children->item(0);

    if($first->nodeType == XML_ELEMENT_NODE) {
      if($first->hasAttribute('indent')) {
        $text .= $context['indent'] . $context['par_prefix'] . "\n";
      }
      $text .= parseNode($first, $context);
    } else if ($first->nodeType == XML_TEXT_NODE) {
      $text .= parseTextNode($first, $context);
    }
    // Apply bullets only to first line
    $context['par_prefix'] = "";

    for($i = 1; $i < $len; $i++) {
      $child = $children->item($i);
      $text .= parseNode($child, $context);
    }
    
    return $text;

  } else {

    $text = $context['indent'] . $context['par_prefix'] . $context['line_prefix'];
    if($node->hasAttribute('lines-after')) {
      return $text . str_repeat("\n", (int)$node->getAttribute('lines-after'));
    }
    return $text . "\n";

  }
}

function parseTextNode($node, $context)
{
  $value = trim($node->nodeValue);
  $lines = explode("\n", $value);
  $indent = $context['indent'];
  $text = "";
  if(!empty($lines)) {
    if($context['par_prefix']) {
      // par_prefix has been set by the parent,
      // it means we are the first child node
      $text = $indent . $context['par_prefix'] . trim(array_shift($lines)) . "\n";
    }
  } else {
    $text = "\n";
  }
  foreach ($lines as $line) {
    $v = trim($line);
    if($v) {
      $text .= $indent . $context['line_prefix'] . $v . "\n";
    }
  }
  return $text;
}

