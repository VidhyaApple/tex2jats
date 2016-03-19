<?php
//getting journal meta info from smmr_journalmeta file 
class GetJournalMeta{	
			
	function get_JM_inputs($smmr_filepath,$JM_var){	
           				  			 	  
	         $JM_var=$this->processInputsFromFile($smmr_filepath,$JM_var);	  
	         return $JM_var;    
           
     }
     
     function processInputsFromFile($a,$JM_var){		      	       
		     
		    $get_file_contents=file($a);
		      foreach($JM_var as $val => $JM_key){
				foreach($get_file_contents as $filekey => $filecontent){
					  if(strpos($filecontent,$val)!==false){
						  $JM_var[$val]=$this->processRegex($val,"$",$filecontent);  //arguments start string, end string and filecontent
					   }
			     }
			}
			return $JM_var;
		}	    
    
	    function processRegex($startvar,$endvar,$filecontent){
			//REGEX
		    
		     $regex = "/".$startvar."=(.*?)".$endvar."/s";  //this to get between (start=) and (end) 
			 preg_match($regex, $filecontent, $match);
			 return trim($match[1]);
		}
}


?>
