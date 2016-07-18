<?php
$t=file_get_contents("table.tex");
$t=preg_replace_callback("/\\\\begin{[table|tabular]*}.*?\\\\caption{(.*?)}.*?begin{thead}(.*?)%\\\\end{thead}\\s*(.*?)\\\\end{tabular}.*?\\\\end{table}/s",function($m){
   	
	$tcap=$m[1];
	$thead=rtrim(trim($m[2]),"\\");
	$tbody=rtrim(trim($m[3]),"\\");
    
    //$table_str="[table($tcol_count)][tabcap]$tcap[tabcap]";
    
    //thead 
    $table_str="#[tcap]$tcap#[/tcap]#\n#[table]#\n";
    $thead_row=explode("\\\\",$thead);
     $thead_str="#[thead]#\n";
    foreach($thead_row as $key => $val){
		
		//tr				
		$thead_str.="#[tr]#\n";	
			$th=explode("&",$val);
			$th=array_map("trim",$th);
			$th_str="";
			
			//th
		    foreach($th as $thkey => $thval){
				//multicolumn
				if(strpos($thval,"\\multicolumn")!==false){
					preg_match("/multicolumn{(\\d+)}{.*?}{(.*?)}$/s",$thval,$mat);
				    $th_str.="#[th colspan='".$mat[1]."']#".$mat[2]."#[/th]#\n";		
				continue;	
				}
				//multirow
				if(strpos($thval,"\\multirow")!==false){
					preg_match("/multirow{(\\d+)}{.*?}{(.*?)}$/s",$thval,$mat);
				    $th_str.="#[th rowspan='".$mat[1]."']#".$mat[2]."#[/th]#\n";	
				continue;	
				}
				$th_str.="#[th]#$thval#[/th]#\n";
											
			}$thead_str.=$th_str."#[/tr]#\n";	
			
	}$thead_str.="#[/thead]#\n";
	
	//tbody
	
    $tbody_row=explode("\\\\",$tbody);
     $tbody_str="#[tbody]#\n";
    foreach($tbody_row as $key => $val){
		
		//tr				
		$tbody_str.="#[tr]#\n";	
			$td=explode("&",$val);
			$td=array_map("trim",$td);
			$td_str="";
			
			//th
		    foreach($td as $tdkey => $tdval){
				//multicolumn
				if(strpos($tdval,"\\multicolumn")!==false){
					preg_match("/multicolumn{(\\d+)}{.*?}{(.*?)}$/s",$tdval,$mat);
				    $td_str.="#[td colspan='".$mat[1]."']#".$mat[2]."#[/td]#\n";		
				continue;	
				}
				//multirow
				if(strpos($tdval,"\\multirow")!==false){
					preg_match("/multirow{(\\d+)}{.*?}{(.*?)}$/s",$tdval,$mat);
				    $td_str.="#[td rowspan='".$mat[1]."']#".$mat[2]."#[/td]#\n";	
				continue;	
				}
				$td_str.="#[td]#$tdval#[/td]#\n";
											
			}$tbody_str.=$td_str."#[/tr]#\n";	
			
	}$tbody_str.="#[/tbody]#\n";
	$table_str=$table_str.$thead_str.$tbody_str."#[/table]#\n";
	
	return $table_str;
      },$t);
   file_put_contents("table2.tex",$t);   


?>
