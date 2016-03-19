<?php
require_once("journalmeta.php");
class GetArticleMeta extends GetJournalMeta{
		
		function get_AM_inputs($latex_filepath){
			
		
		$AM_var=array("doi","article-type","article-title","lrh","rrh","c-author","affil","corres","corres-email","vol","issue","fpage","lpage","cr","cr-yr","cr-holder","abs","kwd");
        $AM_var=array_fill_keys($AM_var,"");
        
		// get frontmatter (between (\begin{frontmatter}) and (\end{frontmatter}))	
		$str=file_get_contents($latex_filepath);
		$front_start="begin{frontmatter}";
		$front_end="end{frontmatter}";
		$AM_content=$this->checkForRegex($front_start,$front_end,$str);    	
		
		// get catchline (between (\begin{catchline}) and (\end{catchline}))	
		$catch_start="begin{catchline}";
		$catch_end="end{catchline}";
		$catch_content=$this->checkForRegex($catch_start,$catch_end,$AM_content);    
		$catch_content=explode("\n",$catch_content);
		
			foreach($catch_content as $catchkey => $catchval){
				
				//1. DOI
	            $doi_start="doi:" ;
	            $doi_end="\\\\\\\\"; // only \\	            
	            if(stripos($catchval,$doi_start)!==false){
					
				$AM_var['doi']=$this->checkForRegex($doi_start,$doi_end,$catchval); 
			    continue;
			    }
				
				//2. copyright
				$cr_start="copyright\\," ;
				$cr_end="\\\\\\\\";
				if(stripos($catchval,$cr_start)!==false){
				
				$AM_var['cr']=$this->checkForRegex($cr_start,$cr_end,$catchval);
				    
				     // copyright-year  (regex to get only year from copyright string)
				     preg_match("/\\d+$/",$AM_var['cr'],$matchyr);
				     $AM_var['cr-yr']=$matchyr[0];
				     
				     //copyright-holder (DEFAULT:SAGE Publications)
				     $AM_var['cr-holder']="SAGE Publications";
				     
				continue;
			    }
			    
			    
			    //3. volume,issue,fpage,lpage
				$vol_start="vol." ;
				$vol_end="\\\\\\\\";
				if(stripos($catchval,$vol_start)!==false){
				$v_i_f_l=$this->checkForRegex($vol_start,$vol_end,$catchval);
					
					 preg_match("/(\\d+)\\((\\d+)\\)\\s*(\\d+)-(\\d+)/",$v_i_f_l,$matchvifl);
					 $AM_var['vol']=$matchvifl[1];
					 $AM_var['issue']=$matchvifl[2];
					 $AM_var['fpage']=$matchvifl[3];
					 $AM_var['lpage']=$matchvifl[4];
				
				continue;	 
			    }
			    
			    //4. article-type
			    
	            $article_type_start="article-type" ;
	            $article_type_end="$"; // only \\	            
	            if(stripos($catchval,$article_type_start)!==false){
				$AM_var['article-type']=$this->processRegex($article_type_start,$article_type_end,$catchval); 
			    continue;
			    }
			    
			    		    
			    //5. left-running-head
			    
	            $lrh_start="left-running-head"; 
	            $lrh_end="$"; // only \\	            
	            if(stripos($catchval,$lrh_start)!==false){
				$AM_var['lrh']=$this->processRegex($lrh_start,$lrh_end,$catchval); 
			    continue;
			    }
			    
			    //6. right-running-head
			    
	            $rrh_start="right-running-head" ;
	            $rrh_end="$"; // only \\	            
	            if(stripos($catchval,$rrh_start)!==false){
				$AM_var['rrh']=$this->processRegex($rrh_start,$rrh_end,$catchval); 
			    continue;
			    }
					
			}		
				
	   
	    //7. article-title
	    $article_title_start="title{";
		$article_title_end="}";
		$AM_var['article-title']=$this->checkForRegex($article_title_start,$article_title_end,$AM_content);   
	     
	    //8.keyword
	    $kwd_start="begin{keyword}";
	    $kwd_end="\\\\end{keyword}";
	    $AM_var['kwd']=$this->checkForRegex($kwd_start,$kwd_end,$AM_content); //explode by ; to get no.of kwd
	    
	    //9.abstract
	    $abs_start="begin{abstract}";
	    $abs_end="\\\\end{abstract}";
	    $AM_var['abs']=$this->checkForRegex($abs_start,$abs_end,$AM_content);  
	    
	     //10.contrib author
	     $c_author_regex="author";
	     $c_author=$this->curlyRegex($c_author_regex,$AM_content);
		 $AM_var["c-author"]=$c_author;
		 
		 //11.affiliation
		 $AM_var["affil"]=$this->affRegex($str);
	     
         //12.corres
         $corres_regex="cortext";
         $AM_var["corres"]=$this->corRegex($str,$corres_regex);
         
         //13.corres-email
         $corres_regex="cortextemail";
         $AM_var["corres-email"]=$this->corRegex($str,$corres_regex);
      
      return $AM_var;
      }
        
    
       function checkForRegex($s,$e,$st){   //just between two strings 
		   
		   if(stripos($st,$s)!==false){
			   if(stripos($s,"\\")!==false){
			   $s=str_replace("\\","\\\\",$s);}
			   preg_match("/".$s."(.*?)".$e."/is",$st,$match);
			   return trim($match[1]);}
		   else{
		   die("String $s or $e not present in the tex file!");}  
		   
	   }
	   
	   function curlyRegex($s,$st){   //these type regex for string{ inbetween so many {}{}} will come}
		   
		   if(stripos($st,$s)!==false){			
			  $c_author_array=array();
			  preg_match_all("/".$s."({(((?>[^{}]+)|(?1))*)})/",$st,$match); //got authorname and affiliation rid
			  
			  for($i=0;$i<count($match[2]);$i++){
				  
				  preg_match("/(.*?)\\s+(\\w+|\\d+)\\\\fnref{fn(.*?)}(%\\\\cormark{cor(.*?)})*/",$match[2][$i],$matchsep);  //seperate given names, surname, aff rid
				  
				      if(empty($matchsep[5])){
					  $tomerge=array('given-names'=>$matchsep[1],'surname'=>$matchsep[2],'aff-rid'=>$matchsep[3]);
				  
				      }else{
					  $tomerge=array('given-names'=>$matchsep[1],'surname'=>$matchsep[2],'aff-rid'=>$matchsep[3],'corres-rid'=>$matchsep[5]);}
					
				   array_push($c_author_array,$tomerge);	
			   }		  
				return $c_author_array;			  
				
	       }			   
		   else{die("String $s  not present in the tex file!");}  
	   }
	   
	   function affRegex($st){  //these type regex for \fntext[fn1]{ and}				   
		   
		    if(stripos($st,"fntext[")!==false){			
			  $aff_array=array();
			  preg_match_all("/\\\\fntext\\[fn(.*?)\\]{(.*?)}/",$st,$matchaff); //got aff id and affiliation
			  			  
			  for($i=0;$i<count($matchaff[1]);$i++){
				  
					  $tomerge=array('aff-id'=>$matchaff[1][$i],'aff-text'=>$matchaff[2][$i]);
				 
				   array_push($aff_array,$tomerge);	
			      }		  
				return $aff_array;			  
				
			  }			   
		   else{
		   die("String $s  not present in the tex file!");}  
		   
	   }
	   
	   function corRegex($st,$s){  //these type regex for \cortext[cor1]{ and}				   
		   
		    if(stripos($st,$s)!==false){			
			  $corres_array=array();
			  preg_match_all("/".$s."\\[cor(\\d+)\\]{(.*?)}/s",$st,$matchcor); //got corres id and cortext
			  			  
			  for($i=0;$i<count($matchcor[1]);$i++){
				  
					  $tomerge=array('corres-id'=>$matchcor[1][$i],$s=>$matchcor[2][$i]);
				 
				   array_push($corres_array,$tomerge);	
			      }		  
				return $corres_array;			  
				
			  }			   
		   else{
		   die("String $s  not present in the tex file!");}  
		   
	   }
	        
	
}


?>
