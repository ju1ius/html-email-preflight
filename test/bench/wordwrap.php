<?php

require 'Benchmark/Timer.php';
require __DIR__.'/../../lib/vendor/Zend/Text/MultiByte.php';
require __DIR__.'/../../lib/ju1ius/Text/MultiByte.php';
require __DIR__.'/../../lib/ju1ius/Text/Utf8.php';

$test_str = <<<EOS
às àlwàys fœr à présïdéntïàl ïnàùgùràl, sécùrïty ànd sùrvéïllàncé wéré éxtrémély tïght ïn Wàshïngtœn, DC, làst Jànùàry.

Bùt às Géœrgé W. Bùsh prépàréd tœ tàké thé œàth œf œffïcé, sécùrïty plànnérs ïnstàlléd àn éxtrà làyér œf prœtéctïœn: à prœtœtypé

http://foobarbaz.longdomainname.com/some/very/very/very/long/url.html?random=crap&query=string 
VééééééééééééééééééééééééééééééééééééééééééééééérrrrrrÿÿÿÿÿÿŸÿÿŸYÿÿÿÿŸÿÿŸÿŸŸŸÿŸÿÿlœœœœœœœœœngwŒŒŒŒŒŒrd   
https://www.google.com/search?q=php+utf-8+safe+functions&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:en-US:unofficial&client=iceweasel-a#q=php+fast+utf-8+wordwrap&hl=fr&client=iceweasel-a&rls=org.mozilla:en-US:unofficial&prmd=imvns&ei=UvE-T-GdF8if8gOCz8S1CA&start=20&sa=N&bav=on.2,or.r_gc.r_pw.r_cp.,cf.osb&fp=890627c4f272e71c&biw=1024&bih=654

sœftwàré systém tœ détéct à bïœlœgïcàl àttàck. Thé ù.S. Dépàrtmént œf Défénsé, tœgéthér wïth régïœnàl héàlth ànd émérgéncy-plànnïng àgéncïés, dïstrïbùtéd à spécïàl pàtïént-qùéry shéét tœ mïlïtàry clïnïcs, cïvïlïàn hœspïtàls ànd évén àïd stàtïœns àlœng thé pàràdé rœùté ànd àt thé ïnàùgùràl bàlls. Sœftwàré qùïckly ànàlyzéd cœmplàïnts œf sévén kéy symptœms — frœm ràshés tœ sœré thrœàts — fœr pàttérns thàt mïght ïndïcàté thé éàrly stàgés œf à bïœ-àttàck. Théré wàs à brïéf scàré: thé systém nœtïcéd à sùrgé ïn flùlïké symptœms àt mïlïtàry clïnïcs. Thànkfùlly, tésts cœnfïrméd ït wàs jùst thàt — thé flù.
EOS;

echo sprintf("Test string is %s bytes long.\n", strlen($test_str));


$timer = new Benchmark_Timer();
$nb_iterations = 100;
$timer->start();

for ($i = 0; $i < $nb_iterations; $i++) {
  $result = Zend\Text\MultiByte::wordWrap($test_str, 75, "\n", false);
}
//var_dump($result);
$timer->setMarker('Zend\Text\MultiByte');

for ($i = 0; $i < $nb_iterations; $i++) {
  $result = ju1ius\Text\MultiByte::wordwrap($test_str, 75, "\n", false);
}
//var_dump($result);
$timer->setMarker('ju1ius\Text\MultiByte');

for ($i = 0; $i < $nb_iterations; $i++) {
  $result = ju1ius\Text\Utf8::wordwrap($test_str, 75, "\n", false);
}
//var_dump($result);
$timer->setMarker('ju1ius\Text\UTF8');

echo $timer->getOutput();

