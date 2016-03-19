<?php
require_once("articlemeta.php");

class GetBack extends GetArticleMeta{
	
	function get_BK_inputs($latex_filepath){
	
	   $cnt=$this->checkForRegex("%back-start","%back-end",file_get_contents($latex_filepath));
	   return($this->getBackContent($cnt));
    }	
	   
	  function getBackContent($bk_content){
		 $content=explode("\n",$bk_content);
		 $bib=$bib_content=$sec_content="";	 
		 $temp_sec=array();
	    
	    //BACK CONTENT
		 foreach($content as $key => $val){			 	
			 
			 //bibiography
			 if(strpos($val,"begin{thebibliography}")!==false){
			 $bib="y";
			 continue;
		     }			 
		      		 
			 if(strpos($val,"end{thebibliography}")!==false){
			 $bib="";
			 $bib_content=explode("\\bibitem",$bib_content);
			 $bib_content[0]="References";
			 $BK_var["bib"]=$this->processBib($bib_content);
			 continue;
		     }
		     if($bib==="y"){
				$bib_content .=$val."\n";
				continue;
		      }
			 
			 //back_section
				if(strpos($val,"\\section")!==false){
				 if($sec==="y"){$temp_sec[count($temp_sec)-1]["content"]=$this->escapeValue($sec_content);$sec_content="";$sec="";}
					$sec="y";
					preg_match("/\\\\section[\\*]*{(.*?)}/i",$val,$match); 
					$match=array_map(array($this,'escapeValue'),$match);          
					array_push($temp_sec,array("title"=>$match[1]));continue;}
			
				if($sec==="y"){
					$sec_content .=$val."\n";
					continue;}
		      
			}
		  if($sec==="y"){$temp_sec[count($temp_sec)-1]["content"]=$this->escapeValue($sec_content);$sec_content="";$sec="";}						 
		  if(count($temp_sec) > 0){
		   $BK_var["back_section"]=$temp_sec;
		  } 
		 
		   return $BK_var;
	     }
	 
	         function processBib($bib_content){
				 
				 $temp_bib=array();
				 foreach($bib_content as $bibkey =>$bibval){
					  if($bibkey===0){
					  continue;
					  }
					      if($bibval!==""){
						 
						      if(preg_match("/\\d{4};/s",$bibval,$matchyear)){   //journal   it has 2014; whereas book doesn't
					              $bibre = "/{(.*?)}\\s*(.*?)\\.(.*?)\\\\textit{(.*?)}\\s*(\\d{4})*[;]*\\s*([\\d|\\w]*)[:]*\\s*([\\d|\\w]*)[-]*([\\d|\\w]*)/s"; 
					               preg_match($bibre,$bibval,$match);
					               $match=array_map(array($this,'escapeValue'),$match);
                                   
					               $to_push=array("bibr"=>$match[1],"type"=>"journal","author"=>$match[2],"title"=>$match[3],"journal"=>$match[4],"year"=>$match[5],"vol"=>$match[6],"fpage"=>$match[7],"lpage"=>$match[8]);}else{ //book
						 
						           $bibre =  "/{(.*?)}(.*?)\\.(.*?\\(ed[.s]*\\))*\\s*\\\\textit{(.*?)}(.*?)[:*](.*?)[,*](.*?)[,|.](\\s*[pp.].*?[-*].*?[.])*/s"; 
					                preg_match($bibre,$bibval,$match);
					                $to_push=array("bibr"=>$match[1],"type"=>"book","author"=>$match[2],"editors"=>$match[3],"book"=>$match[4],"pub-loc"=>$match[5],"pub-name"=>$match[6],"year"=>$match[7],"page"=>$match[8]);}               
					 
					         array_push($temp_bib,$to_push);}					 
				   }
				 
				 return $temp_bib;
			  }
			  
			  function escapeValue($value){
              return is_null($value) ? null : trim($value);
               }  
	}
		

?>
