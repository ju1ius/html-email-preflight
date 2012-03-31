#! /usr/bin/env php
<?php
require_once __DIR__.'/../lib/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespace(
  'ju1ius',
  array(
    __DIR__.'/../lib',
    __DIR__.'/../../ju1ius-libphp/lib',
    __DIR__.'/../../cssparser/lib',
  )
);
$loader->registerNamespace('Zend', __DIR__.'/../lib/vendor');
$loader->register();

$premailer = new ju1ius\Html\EmailPreflight(array(
  'warn_level' => ju1ius\Html\EmailPreflight\Warning::LEVEL_RISKY
));
$premailer->loadHtmlFile(__DIR__.'/files/001.html');
$premailer->inlineCss();

foreach($premailer->getWarnings() as $warning) {
  echo ">>> " . $warning . PHP_EOL;
}
echo $premailer->toHtml();
//$fmt = new ju1ius\Html\Formatter\PlainText();
//$fmt = new ju1ius\Html\Formatter\Markdown();
//$fmt->loadHtmlFile(__DIR__.'/xsl/premailer.html');
//echo $fmt->format();
