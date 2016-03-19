<?php
$doi="0000000000000000";
$doc = new DOMDocument();
$doc->load('created2.xml');
$sec=$doc->getElementsByTagName("sec");
$i=1;
$x=1;

$bibr=array();
$xref=array();
//ids
foreach($sec as $seckey => $secval){  
	$secval->setAttribute("id","sec".$i++."-".$doi);	
}
$doc->save("created_own.xml");

$label=$doc->getElementsByTagName("label");
foreach($label as $labelkey => $labelval){  
	$bibr[].=$labelval->textContent;
}
   for($i=0;$i<count($bibr);$i++){
	   if($i>=4){
		   $xref[].=$bibr[$i];}}
$xmlfile=file_get_contents("created.xml");
foreach($xref as $xrefkey =>$xrefval){
	$xmlfile=str_replace($xrefval,$x++,$xmlfile);
}

	file_put_contents("created.xml",$xmlfile);
	$doc2 = new DOMDocument();
	$doc2->load("created.xml");
	$doc2->save("created2.xml");
   //cite
$f=file("created.xml");
foreach($f as $fk =>$fv){
	while(strpos($fv,"cite{")!==false){
$fv=preg_replace_callback("/\\\\cite{(.*)}/s",function($match){
	$rid=explode(",",$match[1]);
	$rid_str="";
	print_r($rid);
	foreach($rid as $matchkey =>$matchval){
		
		$rid_str.="<xref ref-type='bibr' rid=bibr".$matchval."-".$doi.">".$matchval."</xref>";
		
	}
	
	return $rid_str;
},$fv);}
file_put_contents("created3.xml",$fv,FILE_APPEND);
}
   
?>
