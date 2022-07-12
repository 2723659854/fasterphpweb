<?php

/*function save_file($filename,$data,$pattern="w")
{
    $fp = @fopen($filename, $pattern);
    fwrite($fp, $data);
    fclose($fp);
}
$s = file_get_contents("./http1.png");
$s="PNG\r".$s;
print_r($s);
$ks="";
for($i=0;$i< strlen($s);$i++)
{
    if(ord($s[$i])==32) $ks.= chr(0);
    else $ks .= $s[$i];
}
save_file("./omgg.png",$ks);*/

$str = '2
------WebKitFormBoundary3zYaeSsyBYaVdmsQ--
';
$isMatched = preg_replace('/------WebKitFormBoundary/', '', $str);
var_dump($isMatched);