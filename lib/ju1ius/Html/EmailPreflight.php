<?php

/* vim: set foldmethod=marker */

namespace ju1ius\Html;

use ju1ius\Collections\ParameterBag;
use ju1ius\Uri;
use ju1ius\Css;
use ju1ius\Html\EmailPreflight\Warning;

/**
 * Processes HTML and CSS to improve e-mail deliverability.
 *
 * This is a port of the premailer ruby gem by Alex Dunae.
 * http://github.com/alexdunae/premailer
 *
 * @package Html
 * @author ju1ius
 * @author Alex Dunae
 **/
class EmailPreflight
{
  private static $CLIENT_SUPPORT = null;

  private static $VALID_MEDIAS = array(
    'screen', 'handheld', 'all'
  );

  /**
   * List of Css attributes that can be rendered as HTML attributes
   * 
   * TODO: too much repetition
   * TODO: background=""
   **/
  private static $RELATED_ATTRIBUTES = array(
    'h1'         => array('text-align'       => 'align'),
    'h2'         => array('text-align'       => 'align'),
    'h3'         => array('text-align'       => 'align'),
    'h4'         => array('text-align'       => 'align'),
    'h5'         => array('text-align'       => 'align'),
    'h6'         => array('text-align'       => 'align'),
    'p'          => array('text-align'       => 'align'),
    'div'        => array('text-align'       => 'align'),
    //'blockquote' => array('text-align'       => 'align'),
    'img'        => array('float'            => 'align'),
    'body'       => array('background-color' => 'bgcolor'),
    'table' => array(
      'background-color'       => 'bgcolor',
      //'padding'                => 'cellpadding',
      //'border-spacing'         => 'cellspacing',
      //'border-width'           => 'border',
      '-preflight-cellpadding' => 'cellpadding',
      '-preflight-cellspacing' => 'cellspacing',
      '-preflight-border'      => 'border',
      'width'                  => 'width',
      'height'                 => 'height',
    ),
    'tr' => array(
      'text-align'        => 'align',
      'background-color'  => 'bgcolor',
      'height'            => 'height'
    ),
    'th' => array(
      'background-color'  => 'bgcolor',
      'text-align'        => 'align',
      'vertical-align'    => 'valign',
      'width'             => 'width',
      'height'            => 'height'
    ),
    'td' => array(
      'background-color'   => 'bgcolor',
      'text-align'         => 'align',
      'vertical-align'     => 'valign',
      'width'              => 'width',
      'height'             => 'height',
      '-preflight-colspan' => 'colspan',
      '-preflight-rowspan' => 'rowspan'
    )
  );

  private
    $options,
    $stylesheet_loader,
    $css_parser,
    $dom,
    $xpath,
    $original_dom,
    $html_file_uri,
    $is_local_file,
    $base_url,
    $stylesheets,
    $user_stylesheets = array(
      'inline' => array(
        'before' => array(),
        'after' => array()
      ),
      'unmerged' => array(
        'before' => array(),
        'after' => array()
      )
    ),
    $unmergable_rules,
    $css_warnings;

  public function __construct($options=array())
  {/*{{{*/
    // set default options
    $this->options = new ParameterBag(array(
      'warn_level'        => Warning::LEVEL_NONE,
      'base_url'          => null,
      'encoding'          => 'utf-8',
      'css' => array(
        'to_html_attributes'     => true,
        'color_mode'             => 'hex',
        'strict_parsing'         => false
      ),
      'html' => array(
        'remove_styles'     => true,
        'remove_classes'    => true,
        'remove_ids'        => true,
        'remove_comments'   => true,
        'remove_whitespace' => true,
        'remove_scripts'    => true,
        'remove_objects'    => true,
      )
    ));
    $this->options->merge($options);
    //
    $this->stylesheet_loader = new Css\StyleSheetLoader();
    $this->css_parser = new Css\Parser();
    $this->dom = new \DOMDocument();
    $this->plaintext_formatter = new Formatter\PlainText();
    //
    if($this->options->get('html.remove_whitespace')) {
      $this->dom->preserveWhiteSpace = false;
      $this->dom->formatOutput = false;
      $this->dom->substituteEntities = false;
    }
  }/*}}}*/

  // ==================================================== //
  // ==================== Public API ==================== //
  // ==================================================== //

  /**
   * Returns the options of the current instance.
   *
   * @return ParameterBag The current instance's options
   **/
  public function getOptions()
  {/*{{{*/
    return $this->options;
  }/*}}}*/

  /**
   * Returns an array of EmailPreflight\Warning objects,
   * 
   * @return array
   **/
  public function getWarnings()
  {/*{{{*/
    return $this->css_warnings;
  }/*}}}*/

  /**
   * Loads an HTML string
   *
   * @param string $html The HTML input string
   *
   * @return $this
   **/
  public function loadHtml($html)
  {/*{{{*/
    $this->is_local_file = false;
    $this->dom->loadHTML($html);
    $this->initialize();
    return $this;
  }/*}}}*/

  /**
   * Loads an HTML file.
   *
   * @param string $file The url/path to an HTML file
   *
   * @return $this
   **/
  public function loadHtmlFile($file)
  {/*{{{*/
    $this->html_file_uri = new Uri($file);
    $this->is_local_file = !$this->html_file_uri->isAbsoluteUrl();
    $this->dom->loadHTMLFile($file);
    $this->initialize();
    return $this;
  }/*}}}*/
  /**
   * Adds a new stylesheet to the bottom of the list
   *
   * @param Css\StyleSheet $stylesheet The stylesheet to append
   * @param boolean $inline True is the stylesheet is to be inlined to elements
   *                        False if it is to be added to a style tag
   * @return $this
   **/
  public function appendStyleSheet(Css\StyleSheet $stylesheet, $inline=true)
  {/*{{{*/
    $dest = $inline ? 'inline' : 'unmerged';
    $this->user_stylesheets[$dest]['after'] = $stylesheet;
    return $this;
  }/*}}}*/
  /**
   * Adds a new stylesheet to the top of the list
   *
   * @param Css\StyleSheet $stylesheet The stylesheet to prepend
   * @param boolean $inline True is the stylesheet is to be inlined to elements
   *                        False if it is to be added to a style tag
   * @return $this
   **/
  public function prependStyleSheet(Css\StyleSheet $stylesheet, $inline=true)
  {/*{{{*/
    $dest = $inline ? 'inline' : 'unmerged';
    $this->user_stylesheets[$dest]['before'] = $stylesheet;
    return $this;
  }/*}}}*/
  /**
   * Adds a new stylesheet to the bottom of the list
   *
   * @param string $url url/path of the stylesheet to append
   * @param boolean $inline True is the stylesheet is to be inlined to elements
   *                        False if it is to be added to a style tag
   * @return $this
   **/
  public function appendStyleSheetFile($url, $inline=true)
  {/*{{{*/
    $stylesheet = $this->css_parser->parse(
      $this->stylesheet_loader->load($url)
    );
    return $this->appendStyleSheet($stylesheet, $inline);
  }/*}}}*/
  /**
   * Adds a new stylesheet to the top of the list
   *
   * @param string $url url/path of the stylesheet to prepend
   * @param boolean $inline True is the stylesheet is to be inlined to elements
   *                        False if it is to be added to a style tag
   * @return $this
   **/
  public function prependStyleSheetFile($url, $inline=true)
  {/*{{{*/
    $stylesheet = $this->css_parser->parse(
      $this->stylesheet_loader->load($url)
    );
    return $this->prependStyleSheet($stylesheet, $inline);
  }/*}}}*/
  /**
   * Adds a new stylesheet to the bottom of the list
   *
   * @param string $css Css text to append
   * @param boolean $inline True is the stylesheet is to be inlined to elements
   *                        False if it is to be added to a style tag
   * @return $this
   **/
  public function appendCssText($css, $inline=true)
  {/*{{{*/
    $stylesheet = $this->css_parser->parse(
      $this->stylesheet_loader->loadString($css)
    );
    return $this->appendStyleSheet($stylesheet, $inline);
  }/*}}}*/
  /**
   * Adds a new stylesheet to the top of the list
   *
   * @param string $css Css text to prepend
   * @param boolean $inline True is the stylesheet is to be inlined to elements
   *                        False if it is to be added to a style tag
   * @return $this
   **/
  public function prependCssText($css, $inline=true)
  {/*{{{*/
    $stylesheet = $this->css_parser->parse(
      $this->stylesheet_loader->loadString($css)
    );
    return $this->prependStyleSheet($stylesheet, $inline);
  }/*}}}*/

  /**
   * Returns the HTML formatted as plain text
   *
   * @return string
   **/
  public function toPlainText()
  {/*{{{*/
    if(null === $this->html_file_uri) {
      throw new \RuntimeException("No HTML file loaded");
    }
    $this->plaintext_formatter->setDom($this->dom);
    return $this->plaintext_formatter->format();
  }/*}}}*/
  /**
   * Saves the plain text formatted document to a file.
   *
   * @return int|boolean The number of bytes written, or FALSE on failure.
   **/
  public function saveTextFile($file)
  {/*{{{*/
    return file_put_contents($file, $this->toPlainText());
  }/*}}}*/

  /**
   * Returns the HTML of the loaded document
   *
   * @return string
   **/
  public function toHtml()
  {/*{{{*/
    return $this->dom->saveHTML();
  }/*}}}*/
  /**
   * Saves the internal \DOMDocument to a file
   *
   * @return int|boolean The number of bytes written or FALSE on failure.
   **/
  public function saveHtmlFile($file)
  {/*{{{*/
    return $this->dom->saveHTMLFile($file);
  }/*}}}*/

  /**
   * Returns the internal \DOMDocument object
   *
   * @return \DOMDocument
   **/
  public function getDom()
  {/*{{{*/
    return $this->dom;
  }/*}}}*/

  /**
   * Inlines the stylesheets in the HTML document.
   *
   * Styles are written to the style attribute of elements,
   * or to a style tag at the end of the body.
   *
   * @return $this
   **/
  public function inlineCss()
  {/*{{{*/
    if(null === $this->html_file_uri) {
      throw new \RuntimeException("No HTML file loaded");
    }
    // resolve links in html
    $this->resolveLinks();
    // load stylesheets
    $this->loadStyleSheetsFromDom();
    $this->loadUserStyleSheets();
    // resolve imports
    $this->resolveImports();
    // merge stylesheets
    $merged_stylesheet = $this->getMergedStyleSheet();
    // apply rules
    $this->applyMergedStyleSheet($merged_stylesheet);
    // inject unmergeable_rules in body
    $this->applyUnmergeableRules();
    // Remove unwanted attributes & elements
    $this->cleanupDom();
    // Check client support
    if(Warning::LEVEL_NONE !== $this->options->get('warn_level')) {
      $this->checkClientSupport();
    }
    //
    return $this;
  }/*}}}*/

  // ========================================================== //
  // ==================== Internal Methods ==================== //
  // ========================================================== //


  protected function initialize()
  {/*{{{*/
    $this->xpath = new \DOMXPath($this->dom);

    $this->stylesheets = array();
    $this->unmergeable_rules = array();
    $this->css_warnings = array();

    $base_url = $this->options->get('base_url');

    if($base_url) {
      $this->base_url = new Uri($base_url);
      $this->html_file_uri = $this->base_url;
    } else {
      $this->base_url = $this->html_file_uri->dirname();
    }
  }/*}}}*/

  protected function resolveLinks()
  {/*{{{*/
    $elements = $this->xpath->query(
      '//*[@href and not(starts-with(normalize-space(@href),"#"))]'
      . '|//*[@src]|//*[@background]'
    );
    foreach ($elements as $element) {
      $url = null;
      foreach(array('href', 'src', 'background') as $attr) {
        if($element->hasAttribute($attr)) {
          $url = $element->getAttribute($attr);
          break;
        }
      }
      if(!$url) continue;

      $uri = new Uri($url);
      if($uri->isAbsoluteUrl()) continue;

      if($this->is_local_file) {
        if($uri->isAbsolutePath()) continue;
        $uri = $this->base_url->join($uri);
        
      } else {
        if($uri->isAbsolutePath()) {
          $uri = $this->base_url->getRootUrl()->join($uri);
        } else {
          $uri = $this->base_url->join($uri);
        }
      }
      $element->setAttribute($attr, $uri->getUri());
    }
  }/*}}}*/

  protected function loadStyleSheetsFromDom()
  {/*{{{*/
    $elements = $this->xpath->query('//link[@rel="stylesheet"]|//style');

    foreach($elements as $element) {
      if($element->tagName === "link") {
        // Only parse stylesheets with media all|screen|handheld
        $media = $element->getAttribute('media');
        if($media) {
          try {
            $media_list = $parser->parseMediaQuery($media);
            if(!$this->checkMediaType($media_list)) {
              continue;
            }
          } catch (Css\Exception\ParseException $e) {
            $this->css_warnings[] = "Invalid media attribute: $media, replaced by media='all'";
          }
        }
        $href = $element->getAttribute('href');
        $stylesheet = $this->css_parser->parse(
          $this->stylesheet_loader->load($href)
        );

      } else if($element->tagName === "style") {

        $stylesheet = $this->css_parser->parse(
          $this->stylesheet_loader->loadString($element->textContent)
        );

      }
      if($element->getAttribute('data-inline') === "false") {
        foreach($stylesheet->getRuleList() as $rule) {
          $this->unmergeable_rules[] = $rule;
        }
      } else {
        $this->stylesheets[] = $stylesheet;
      }
      //
      if($this->options->get('html.remove_styles')) {
        $element->parentNode->removeChild($element);
      }
    }
  }/*}}}*/

  /**
   * @TODO: How to match against several media queries ???
   *        What to do if they contradict each other ?
   **/
  protected function checkMediaType(Css\MediaQueryList $media_list)
  {/*{{{*/
    foreach ($media_list as $media_query) {
      if($media_query->getRestrictor() !== 'not') {
        if(in_array($media_query->getMediaType(), self::$VALID_MEDIAS)) {
          return true;
        }
      } 
    }
    return false;
  }/*}}}*/

  private function loadUserStyleSheets()
  {/*{{{*/
    $this->stylesheets = array_merge(
      $this->user_stylesheets['inline']['before'],
      $this->stylesheets,
      $this->user_stylesheets['inline']['after']
    );
  }/*}}}*/

  private function resolveImports()
  {/*{{{*/
    foreach ($this->stylesheets as $stylesheet) {
      $resolver = new Css\Resolver\ImportResolver(
        $stylesheet, $this->base_url
      );
      $resolver->resolve();
    }
  }/*}}}*/

  protected function getMergedStyleSheet()
  { /*{{{*/
    $result = new Css\StyleSheet();
    foreach($this->stylesheets as $stylesheet) {
      $rule_list = $stylesheet->getRuleList();
      $media_list = $stylesheet->getMediaQueryList();
      if($media_list && count($media_list)) {
        $media_rule = new Css\Rule\Media(
          clone $media_list,
          clone $rule_list
        );
        $result->getRuleList()->append($media_rule);
      } else {
        $result->getRuleList()->extend($rule_list);
      }
    }
    return $result;
  }/*}}}*/

  /**
   * To apply rules, we maintain a mapping between
   * a node path and it's associated styles declarations:
   *
   * $stylemap = array(
   *   '/xpath/to/element' => array(
   *     0 => array(
   *       'specificity' => integer,
   *       'style_declaration' => Css\StyleDeclaration
   *     ),
   *     ...
   *   ),
   *   ...
   * );
   **/
  protected function applyMergedStyleSheet(Css\StyleSheet $stylesheet)
  {/*{{{*/
    $stylemap = array();
    foreach ($stylesheet->getRuleList() as $rule) {

      if ($rule instanceof Css\Rule\StyleRule) {
        $unmergeable_selectors = array();

        foreach ($rule->getSelectorList() as $selector) {

          try {
            $xpath = $selector->toXPath();
          } catch (Css\Exception\UnsupportedSelectorException $e) {
            $unmergeable_selectors[] = clone $selector;
            continue;
          }

          $elements = $this->xpath->query('//'.$xpath);

          foreach ($elements as $element) {
            $path = $element->getNodePath();
            if (!isset($stylemap[$path])) {
              $stylemap[$path] = array();
            }
            $stylemap[$path][] = array(
              'specificity' => $selector->getSpecificity(),
              'style_declaration' => $rule->getStyleDeclaration()
            );
            $style_attr = $element->getAttribute('style');
            if ($style_attr) {
              try {
                $style_declaration = $this->css_parser->parseStyleDeclaration($style_attr);
                $stylemap[$path][] = array(
                  'specificity' => 1000,
                  'style_declaration' => $style_declaration
                );
              } catch(Css\Exception\ParseException $e) {
                $element->removeAttribute('style');
              }
            }
          } // end foreach matched elements

        }// end foreach selectors

        if(count($unmergeable_selectors)) {
          $this->unmergeable_rules[] = new Css\Rule\StyleRule(
            new Css\SelectorList($unmergeable_selectors),
            clone $rule->getStyleDeclaration()
          );
        }

      } // end foreach rulelist

    }
    $output_options = array(
      'color_mode' => $this->options->get('css.color_mode')
    );
    $to_html_attributes = $this->options->get('css.to_html_attributes');

    foreach ($stylemap as $path => $styles) {
      $style_declaration = $this->mergeDeclarations($styles);
      $elements = $this->xpath->query($path);
      // FIXME: is it possible for two elements to have the same nodepath ?
      // I believe not. Anyway a little looping never hurts
      foreach ($elements as $element) {
        // Duplicate Css properties as HTML attributes
        if($to_html_attributes) {
          $this->stylesToAttributes($element, $style_declaration);
        }
        // And finally set the style attribute,
        // which is what we came here for !
        $element->setAttribute('style', $style_declaration->getCssText($output_options));
      }
    }

  }/*}}}*/

  private function applyUnmergeableRules()
  {/*{{{*/
    $options = array(
      'color_mode' => $this->options->get('css.color_mode')
    );
    $css = "";

    foreach($this->user_stylesheets['unmerged']['before'] as $stylesheet) {
      $css .= $stylesheet->getCssText($options);
    }
    //$unmergeable_stylesheet = new Css\StyleSheet();
    foreach ($this->unmergeable_rules as $rule) {
      $css .= $rule->getCssText($options);
      //$unmergeable_stylesheet->getRuleList()->append($rule);
    }
    //$css .= $unmergeable_stylesheet->getCssText($options);
    foreach($this->user_stylesheets['unmerged']['after'] as $stylesheet) {
      $css .= $stylesheet->getCssText($options);
    }

    $style = $this->dom->createElement('style', $css);
    $body = $this->xpath->query('//body')->item(0);
    $body->appendChild($style);
  }/*}}}*/

  /**
   * Merge style declarations associated to an html element
   **/
  protected function mergeDeclarations(array $styles)
  {/*{{{*/
    // Internal storage of Css properties that we will keep
    $aProperties = array();
    foreach($styles as $style)
    {
      $style_declaration = $style['style_declaration'];
      $specificity = $style['specificity'];
      //
      $style_declaration->expandShorthands();
      //
      foreach($style_declaration->getAppliedProperties() as $name => $property)
      {
        // Add the property to the list to be folded
        // http://www.w3.org/TR/css3-cascade/#cascading
        $override = false;
        $is_important = $property->getIsImportant();
        if(isset($aProperties[$name])) {
          $old_property = $aProperties[$name];
          // properties have same weight so we consider specificity
          if($is_important === $old_property['property']->getIsImportant()) {
            if($specificity >= $old_property['specificity']) $override = true;
          } else if($is_important) {
            $override = true;
          }
        } else {
          $override = true;
        }
        if($override) {
          $aProperties[$name] = array(
            'property' => clone $property,
            'specificity' => $specificity
          );
        }
      }
    }
    $merged = new Css\StyleDeclaration();
    foreach($aProperties as $name => $details) {
      $merged->append($details['property']);
    }
    // FIXME: see https://github.com/alexdunae/premailer/issues/91
    //$merged->createShorthands();
    return $merged;
  }/*}}}*/

  private function stylesToAttributes(\DOMElement $element, Css\StyleDeclaration $style_declaration)
  {/*{{{*/
    $tagname = $element->tagName;
    if(!isset(self::$RELATED_ATTRIBUTES[$tagname])) return;
    $color_mode = $this->options->get('css.color_mode');

    foreach(self::$RELATED_ATTRIBUTES[$tagname] as $css_prop => $html_attr) {
      $prop = $style_declaration->getAppliedProperty($css_prop);
      if(!$prop) continue;

      // FIXME: Should attributes already set in html have precedence ?
      //if($element->hasAttribute($html_attr)) {
        //$style_declaration->remove($css_prop);
        //continue;
      //};

      $element->setAttribute(
        $html_attr,
        $prop->getValueList()->getCssText(array(
          'color_mode' => $color_mode
        ))
      );
      
      // FIXME: html presentational attributes often have different meanings than their css counterpart(s),
      // ie. @align applies to inline & block elements, whereas text-align applies only to inline elements.
      // Is there something clever to do other than this ?
      $style_declaration->remove($prop);
    }
  }/*}}}*/

  private function cleanupDom()
  {/*{{{*/
    if($this->options->get('html.remove_scripts')) {
      foreach($this->xpath->query('//script') as $element) {
        $element->parentNode->removeChild($element);
      }
    }
    if($this->options->get('html.remove_objects')) {
      foreach($this->xpath->query('//object|//embed|//applet') as $element) {
        $element->parentNode->removeChild($element);
      }
    }
    if($this->options->get('html.remove_classes')) {
      $elements = $this->xpath->query('//*[@class]');
      foreach($elements as $element) {
        $element->removeAttribute('class');
      }
    }
    if($this->options->get('html.remove_ids')) {
      // If we are going to remove id attributes,
      // we need to hash anchors href and their target ids
      $anchors = $this->xpath->query('//a[starts-with(normalize-space(@href), "#")]');
      $anchor_targets = array();
      foreach ($anchors as $anchor) {
        $target = trim($anchor->getAttribute('href'), '# ');
        $anchor_targets[] = $target;
        $anchor->setAttribute('href', '#' . md5($target));
      }
      $elements = $this->xpath->query('//*[@id]');
      foreach($elements as $element) {
        $id = trim($element->getAttribute('id'));
        if(in_array($id, $anchor_targets)) {
          $element->setAttribute('id', md5($id));
        } else {
          $element->removeAttribute('id');
        }
      }
    }
    if($this->options->get('html.remove_comments')) {
      $elements = $this->xpath->query('//comment()');
      foreach($elements as $element) {
        $element->parentNode->removeChild($element);
      }
    }
  }/*}}}*/
  /**
   * Capabilities of e-mail clients
   *
   * Sources
   * http://campaignmonitor.com/css/
   * http://www.campaignmonitor.com/blog/archives/2007/04/a_guide_to_css_support_in_emai_2.html
   * http://www.campaignmonitor.com/blog/archives/2007/11/do_image_maps_work_in_html_ema.html
   * http://www.campaignmonitor.com/blog/archives/2007/11/how_forms_perform_in_html_emai.html
   * http://www.xavierfrenette.com/articles/css-support-in-webmail/
   * http://www.email-standards.org/
   * Updated 2008-08-26
   *
   * Support: 1 = SAFE,  2 = POOR,  3 = RISKY
   **/
  private function checkClientSupport()
  {/*{{{*/
    if(null === self::$CLIENT_SUPPORT) {
      self::$CLIENT_SUPPORT = json_decode(
        file_get_contents(__DIR__.'/EmailPreflight/Warning/client-support.json'),
        true
      );
    }
    $warn_level = $this->options->get('warn_level');

    foreach (self::$CLIENT_SUPPORT['elements'] as $tag_name => $info) {
      if($info['support'] >= $warn_level) continue;
      $count = $this->xpath->evaluate("count(//$tag_name)");
      if($count > 0) {
        $this->css_warnings[] = new Warning\UnsupportedElement(
          $info['support'], $tag_name, $info['unsupported_in']
        );
      }
    }
    foreach (self::$CLIENT_SUPPORT['attributes'] as $attr => $info) {
      if($info['support'] >= $warn_level) continue;
      $count = $this->xpath->evaluate("count(//*[$attr])");
      if($count > 0) {
        $this->css_warnings[] = new Warning\UnsupportedAttribute(
          $info['support'], $attr, $info['unsupported_in']
        );
      }
    }
    foreach (self::$CLIENT_SUPPORT['css_properties'] as $prop => $info) {
      if($info['support'] >= $warn_level) continue;
      $count = $this->xpath->evaluate("count(
        //*[@style and contains(@style, '$prop')]
        |//style[contains(text(), '$prop')]
      )");
      if($count > 0) {
        $this->css_warnings[] = new Warning\UnsupportedProperty(
          $info['support'], $prop,
          $info['unsupported_in'], $info['support_level']
        );
      }
    }
  }/*}}}*/

}
