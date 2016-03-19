<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once("classfiles/journalmeta.php");
require_once("classfiles/articlemeta.php");
require_once("classfiles/body.php");
require_once("classfiles/back.php");

//xml creation
$doc=new DOMDocument("1.0");
$doc->formatOutput = true;           	                 	            
        //root
		$root=$doc->createElement("article");
        $doc->appendChild($root); 
        
        //front
        $front=$root->appendChild($doc->createElement("front"));
        //body
        $body=$root->appendChild($doc->createElement("body"));
        //back
         $back=$root->appendChild($doc->createElement("back"));
         

class createXML{	
	
	public $doi;
	public $fdoi;
	public $smmr_filepath;
	public $latex_filepath;
	
		function __construct($smmr,$latex){
			$this->smmr_filepath = $smmr;
			$this->latex_filepath=$latex;
      
			//file check
			if(!file_exists($smmr)){die("No journal meta file is given!");}
			if(!file_exists($latex)){die("No tex file is given!");}
		}
	
		function createFront(){
			$this->createJM();	
			$this->createAM();
		
		}
		
		function createBody(){
		$object_Body=new Body($this->latex_filepath,"front-back.xml",$this->doi,$this->fdoi);
        $object_Body->getBody();	
			
		}
	
	function createJM(){
				
		//Totally 11 inputs are needed(optional too), initialise these arrays(empty), so that if we don't get that value it will be fine
     global $JM_var;
     $JM_var=array("publisher-id","hwp","nlm-ta","journal-title","ppub","epub","publisher-name","publisher-location","day","month","year");
     $JM_var=array_fill_keys($JM_var,"");	
    
		
		global $doc;
		global $front;  
		
		$object_GetJournalMeta=new GetJournalMeta();
        $JM_var=$object_GetJournalMeta->get_JM_inputs($this->smmr_filepath,$JM_var);      
             
       
       //journal-meta
       $journal_meta=$front->appendChild($doc->createElement("journal-meta"));
       
       $arrno=0;
       foreach($JM_var as $JM_key => $JM_val){
		$arrno++; 
		  
		   //journal-meta/journal-id(3)		      
		      if((!empty($JM_val)) AND ($arrno<=3)){
			   $journal_id=$journal_meta->appendChild($doc->createElement("journal-id"));
			   $journal_id->appendChild($doc->createTextNode($JM_val));
			   $journal_id->setAttribute("journal-id-type",$JM_key);
			   continue;			   
		       }
		   
		   //journal-meta/journal-title-group/journal-title
		      if(($JM_key==="journal-title") AND (!empty($JM_val))){
			   $journal_title_group=$journal_meta->appendChild($doc->createElement("journal-title-group"));
			   $j_t=$journal_title_group->appendChild($doc->createElement("journal-title"));
               $j_t->appendChild($doc->createTextNode($JM_val));
			   continue;
		       }	
		       
		    //journal-meta/issn(2)	
		    if(($JM_key==="ppub")AND (!empty($JM_val))){
			   $issn=$journal_meta->appendChild($doc->createElement("issn"));
			   $issn->appendChild($doc->createTextNode($JM_val));
			   $issn->setAttribute("pub-type",$JM_key);
			   continue;
		       }	
		       
		    if(($JM_key==="epub")AND (!empty($JM_val))){
			   $issn=$journal_meta->appendChild($doc->createElement("issn"));
			   $issn->appendChild($doc->createTextNode($JM_val));
			   $issn->setAttribute("pub-type",$JM_key);
			   continue;
		       }
		    
		    //journal-meta/publisher/publisher-name
		      if(($JM_key==="publisher-name")AND (!empty($JM_val))){
			   $publisher=$journal_meta->appendChild($doc->createElement("publisher"));
			   $publisher_name=$publisher->appendChild($doc->createElement("publisher-name"));
			   $publisher_name->appendChild($doc->createTextNode($JM_val));
			   continue;
		       }	
		       
		     //journal-meta/publisher/publisher-loc
		      if(($JM_key==="publisher-location")AND (!empty($JM_val))){
			   $publisher_loc=$publisher->appendChild($doc->createElement("publisher-loc"));
			   $publisher_loc->appendChild($doc->createTextNode($JM_val));
			   continue;
		       }	       
	   }	   
       
	}
	
	function createAM(){
		
		global $doc;
		global $front; 
		global $JM_var;
	    $pubdate_confirm=""; 
				
		$object_GetArticleMeta=new GetArticleMeta();
        $AM_var=$object_GetArticleMeta->get_AM_inputs($this->latex_filepath);
        
        //article-meta
       $article_meta=$front->appendChild($doc->createElement("article-meta"));
                
       
        foreach($AM_var as $AM_key => $AM_val){
			
			//article-meta/article-id(2)
		      if(($AM_key==="doi") AND (!empty($AM_val))){
				  $doi_array=explode("/",$AM_val);				 
				  $doifront=$doi_array[0];				 
				  $this->doi=$doi_array[1];
				  $this->fdoi=$doifront."_".$this->doi;
			   $article_id1=$article_meta->appendChild($doc->createElement("article-id",$AM_val));
			   $article_id2=$article_meta->appendChild($doc->createElement("article-id",$this->fdoi));
			   $article_id1->setAttribute("pub-id-type","doi");
               $article_id2->setAttribute("pub-id-type","publisher-id");
               continue;
		       }	
		       
		    //article-meta/article-categories/subj-group/subject
		    if(($AM_key==="article-type") AND (!empty($AM_val))){
				$article_cat=$article_meta->appendChild($doc->createElement("article-categories"));
				$subj_group=$article_cat->appendChild($doc->createElement("subj-group"));
				$subj_group->setAttribute("subj-group-type","heading");
				$subject=$subj_group->appendChild($doc->createElement("subject"));
				$subject->appendChild($doc->createTextNode($AM_val));
				continue;				
			}
			
			
			//article-meta/title-group/article-title
			if(($AM_key==="article-title") AND (!empty($AM_val))){
				$title_group=$article_meta->appendChild($doc->createElement("title-group"));
				$article_title=$title_group->appendChild($doc->createElement("article-title"));
				$article_title->appendChild($doc->createTextNode($AM_val));
				continue;				
			}
			//article-meta/title-group/alt-title(lrh)
			if(($AM_key==="lrh") AND (!empty($AM_val))){
				$alt_title_l=$title_group->appendChild($doc->createElement("alt-title"));
				$alt_title_l->appendChild($doc->createTextNode($AM_val));
				$alt_title_l->setAttribute("alt-title-type","left-running-head");
				continue;				
			}
			//article-meta/title-group/alt-title(rrh)
			if(($AM_key==="rrh") AND (!empty($AM_val))){
				$alt_title_r=$title_group->appendChild($doc->createElement("alt-title",$AM_val));
				$alt_title_r->appendChild($doc->createTextNode($AM_val));
				$alt_title_r->setAttribute("alt-title-type","right-running-head");
				continue;				
			}
			
			//article-meta/contrib-group/contrib(n)/name->given-names,surname,xref
		    if(($AM_key==="c-author") AND (!empty($AM_val))){
				$contrib_group=$article_meta->appendChild($doc->createElement("contrib-group"));
				
				foreach($AM_var["c-author"] as $authtotalkey => $authtotalval){
					$contrib=$contrib_group->appendChild($doc->createElement("contrib"));
				    $contrib->setAttribute("contrib-type","author");
				    
				    
				    $name=$contrib->appendChild($doc->createElement("name"));				   
				    $name->appendChild($doc->createElement("surname",$authtotalval['surname']));
				    $name->appendChild($doc->createElement("given-names",rtrim($authtotalval['given-names'],".")));
				    				    
				    
				    $xref=$contrib->appendChild($doc->createElement("xref",$authtotalval['aff-rid']));
				    $xref->setAttribute("ref-type","aff");
				    $xref->setAttribute("rid","aff".$authtotalval['aff-rid']."-".$this->doi);
				      
				    if(count($authtotalval)===4){
					$contrib->setAttribute("corresp","yes");
					$xref=$contrib->appendChild($doc->createElement("xref",$authtotalval['corres-rid']));
					$xref->setAttribute("ref-type","corresp"); 
					$xref->setAttribute("rid","corresp".$authtotalval['corres-rid']."-".$this->doi); 
					}
				  					
				  }
				 continue;
			     }
			   
			    //article-meta/affiliation(n)/label
			     if(($AM_key==="affil") AND (!empty($AM_val))){
					 
					foreach($AM_var["affil"] as $afftotalkey => $afftotalval){ 					 
				      $aff=$article_meta->appendChild($doc->createElement("aff"));
				      $aff->setAttribute("id","aff".$afftotalval['aff-id']."-".$this->doi);	
				      $aff_label=$aff->appendChild($doc->createElement("label",$afftotalval['aff-id']));
				      $aff->appendChild($doc->createTextNode($afftotalval['aff-text']));
			        }
			      continue; 
			     }
			     
			     //article-meta/author-notes/corresp(n)
			     if(($AM_key==="corres") AND (!empty($AM_val))){
				   $author_notes=$article_meta->appendChild($doc->createElement("author-notes")); 
					foreach($AM_var["corres"] as $cortotalkey => $cortotalval){ 					 
				      $cor=$author_notes->appendChild($doc->createElement("corresp",$cortotalval['cortext']));
				      $cor->setAttribute("id","corresp".$cortotalval['corres-id']."-".$this->doi);
				      	
				     }
			      continue; 
			     }
			      
			      //article-meta/author-notes/corresp(n)/email
			      if(($AM_key==="corres-email") AND (!empty($AM_val))){
					  foreach($AM_var["corres-email"] as $coremailtotalkey => $coremailtotalval){ 		
					   $nodes = $doc->getElementsByTagName('corresp');
					  $nodes->item($coremailtotalval['corres-id']-1)->appendChild($doc->createElement("email",$coremailtotalval['cortextemail']));;
					  
				      }				
			      continue;
			       }
			       
			       //article-meta/pubdate
			        
			        if($pubdate_confirm===""){
						if(($JM_var["day"]!==false) OR ($JM_var["month"]!==false) OR ($JM_var["year"]!==false)){
							$pubdate=$article_meta->appendChild($doc->createElement("pub-date"));
							$pubdate->setAttribute("pub-type","epub-ppub");$pubdate_confirm="y";
			                  
			                if($JM_var["day"]!==false){$pubdate->appendChild($doc->createElement("day",$JM_var["day"]));}
			                if($JM_var["month"]!==false){$pubdate->appendChild($doc->createElement("month",$JM_var["month"]));}
			                if($JM_var["year"]!==false){$pubdate->appendChild($doc->createElement("year",$JM_var["year"]));}     
			        
			        
						}
			        }
			        
			       
			       //article-meta/volume
			       if(($AM_key==="vol") AND (!empty($AM_val))){
			       	$vol=$article_meta->appendChild($doc->createElement("volume",$AM_val)); continue;}  
			       	
			       	//article-meta/issue
			       if(($AM_key==="issue") AND (!empty($AM_val))){
			       	$issue=$article_meta->appendChild($doc->createElement("issue",$AM_val)); continue;}  
			       	
			       	//article-meta/fpage
			       if(($AM_key==="fpage") AND (!empty($AM_val))){
			       	$fpage=$article_meta->appendChild($doc->createElement("fpage",$AM_val)); continue;}  
			       	
			       	//article-meta/lpage
			       if(($AM_key==="lpage") AND (!empty($AM_val))){
			       	$lpage=$article_meta->appendChild($doc->createElement("lpage",$AM_val)); continue;}
			       	
			       	//article-meta/permissions/copyright-statement
			       	 if(($AM_key==="cr") AND (!empty($AM_val))){
					  $permissions= $article_meta->appendChild($doc->createElement("permissions"));						 
			       	  $cr=$permissions->appendChild($doc->createElement("copyright-statement","© ".$AM_val)); 
			       	
			       	continue;} 
			       	
			       	//article-meta/permissions/copyright-year
			       	 if(($AM_key==="cr-yr") AND (!empty($AM_val))){					  		 
			       	  $permissions->appendChild($doc->createElement("copyright-year",$AM_val)); 			       	
			       	continue;} 
			       	
			       	//article-meta/permissions/copyright-holder
			       	 if(($AM_key==="cr-holder") AND (!empty($AM_val))){					  						 
			       	  $cr_holder=$permissions->appendChild($doc->createElement("copyright-holder",$AM_val)); 
			       	  $cr_holder->setAttribute("content-type","sage");
			       	continue;} 
			       	
			       	//article-meta/abstract/p
			       	if(($AM_key==="abs") AND (!empty($AM_val))){
						$abstract=$article_meta->appendChild($doc->createElement("abstract"));
						$p_abs=$abstract->appendChild($doc->createElement("p"));
						$p_abs->appendChild($doc->createTextNode($AM_val));
							
			       	continue;}	
			       	
			       				       	//article-meta/kwd-group
			       	if(($AM_key==="kwd") AND (!empty($AM_val))){
						$kwd_group=$article_meta->appendChild($doc->createElement("kwd-group"));
						
						$kwd_array=explode(";",$AM_val);
							$kwd_array=array_map("trim",$kwd_array); 
						    foreach($kwd_array as $kwdkey => $kwdval){
						    $kwd_con=$kwd_group->appendChild($doc->createElement("kwd"));
						    $kwd_con->appendChild($doc->createTextNode($kwdval));
						    }
			       	continue;}
			       			
		     }
		     
	       
          
	    }
	    
	    
	    function createBack(){
		
		global $doc;
		global $front;
		global $back;
		
		
		$object_GetBack=new GetBack();
        $BK_var=$object_GetBack->get_BK_inputs($this->latex_filepath);
        
         $app_exist="";
         //back section
           foreach($BK_var['back_section'] as $sekey => $seval){
			     //back/ack
			     if(stripos($seval['title'],"acknowledgement")!==false){
                 $ack=$back->appendChild($doc->createElement("ack"));
                 $ack_title=$ack->appendChild($doc->createElement("title"));
                 $ack_title->appendChild($doc->createTextNode($seval['title']));
				 $ack_content=$ack->appendChild($doc->createElement("p"));
				 $ack_content->appendChild($doc->createTextNode($seval['content']));continue;}
                 
                 //back/app-group/app/title/sec/title
               if((count($BK_var['back_section'])>1) AND ($app_exist==="")){
			    $app_grp=$back->appendChild($doc->createElement("app-group"));
			     $app_exist="y";
			   }   
			     
			     //app/sec
			     $app=$app_grp->appendChild($doc->createElement("app")); 
			     $sec_app_title=$app->appendChild($doc->createElement("title"));
				 $sec_app_title->appendChild($doc->createTextNode($seval['title']));
			     $sec_app=$app->appendChild($doc->createElement("sec"));
			     $sec_app->appendChild($doc->createElement("title"));
			     $sec_app_p=$sec_app->appendChild($doc->createElement("p"));
			     $sec_app_p->appendChild($doc->createTextNode($seval['content']));continue;
			 }
        
         //back/ref-list
         $ref_list=$back->appendChild($doc->createElement("ref-list"));
         $ref_list->appendChild($doc->createElement("title","References"));
       
           foreach($BK_var['bib'] as $BK_key => $BK_val){
			   
			   
			   //create each ref/label/mixed-citation
	           $ref=$ref_list->appendChild($doc->createElement("ref"));
	           $ref->setAttribute("id","bibr".$BK_val['bibr']."-".$this->doi);
	              //label
	              $ref->appendChild($doc->createElement("label",$BK_val['bibr']));
	              //mixed-citation
	              $mixed=$ref->appendChild($doc->createElement("mixed-citation"));
	              $mixed->setAttribute("publication-type",$BK_val['type']);
	              
	                   
						   
	                   //ref-list/ref/mixed-citation/person-group
	                   
	                   $person_type="author";
						$person_value=trim($BK_val['author']);
											$person_group=$mixed->appendChild($doc->createElement("person-group"));
	                                      $person_group->setAttribute("person-group-type",$person_type);
	                   
	                                     $person=str_replace(" and ",",",$person_value);    //remove *and* in the author names
	                                     $person=explode(",",$person);
	                                     
	                                     foreach($person as $personkey =>$personval){
							                   if(strpos($personval,"et al")===false){
												   $name=$person_group->appendChild($doc->createElement("name"));
								               $given_name=rtrim(trim(strrchr($personval , ' '),"."));  //given-names
								                $surname=trim(substr($personval, 0, strrpos($personval, ' '))); 
								              $name->appendChild($doc->createElement("surname",$surname));
								                $name->appendChild($doc->createElement("given-names",$given_name));
								              }else{$person_group->appendChild($doc->createElement("etal"));}//etal
									   		}
									   		
									   		
										if(empty($BK_val['editors'])){
						                $mixed->appendChild($doc->createTextNode("."));}//punctuation for journal authors only not for book editors
						                
								if($BK_val['type']==="journal"){//journal
									   
						        //article-title
						          if($BK_val['title']!==""){$article_title_jn=$mixed->appendChild($doc->createElement("article-title"));
									  $article_title_jn->appendChild($doc->createTextNode($BK_val['title']));
									 $mixed->appendChild($doc->createTextNode("."));}//punctuation
						          
						          //source
						          if($BK_val['journal']!==""){$source=$mixed->appendChild($doc->createElement("source"));
									 $source->appendChild($doc->createTextNode($BK_val['journal'])); 
									  }
						          
						          //year
						          if($BK_val['year']!==""){$mixed->appendChild($doc->createElement("year",$BK_val['year']));
									  $mixed->appendChild($doc->createTextNode(";"));}//punctuation
						          
						          //volume
						          if($BK_val['volume']!==""){$mixed->appendChild($doc->createElement("volume",$BK_val['vol']));
									  $mixed->appendChild($doc->createTextNode(":"));}//punctuation
						          
						          //fpage
						          if($BK_val['fpage']!==""){$mixed->appendChild($doc->createElement("fpage",$BK_val['fpage']));
									  $mixed->appendChild($doc->createTextNode("–"));}//punctuation
						          
						          //lpage
						          if($BK_val['lpage']!==""){$mixed->appendChild($doc->createElement("lpage",$BK_val['lpage']));
									  $mixed->appendChild($doc->createTextNode("."));}//punctuation
						          
						
						   
						   
								}else{   //book
									
						          //article-title with editors
										if(!empty($BK_val['editors'])){
											preg_match("/(.*?)[.*]\\s*in:\\s*(.*?)\\s*(\\(ed[s]*\\))/is",$BK_val['editors'],$match);
						           
											//article-title
											$article_title_bk=$mixed->appendChild($doc->createElement("article-title"));
											$article_title_bk->appendChild($doc->createTextNode($match[1]));
											$mixed->appendChild($doc->createTextNode(". In:"));//punctuation   	
											
											//person-group-editor
											$person_type="editor";
											$person_value=trim($match[2]);
											$person_group=$mixed->appendChild($doc->createElement("person-group"));
	                                      $person_group->setAttribute("person-group-type",$person_type);
	                   
	                                     $person=str_replace(" and ",",",$person_value);    //remove *and* in the author names
	                                     $person=explode(",",$person);
	                                     foreach($person as $personkey =>$personval){
							                   if(strpos($personval,"et al")===false){
												    $name=$person_group->appendChild($doc->createElement("name"));
								               $given_name=rtrim(trim(strrchr($personval , ' '),"."));  //given-names
								                $surname=trim(substr($personval, 0, strrpos($personval, ' '))); 
								              $name->appendChild($doc->createElement("surname",$surname));
								                $name->appendChild($doc->createElement("given-names",$given_name));
								              }else{$person_group->appendChild($doc->createElement("etal"));}//etal
									   		}						
								           $mixed->appendChild($doc->createTextNode($match[3]."."));
						              }
						              //book-source
						              if(!empty($BK_val['book'])){$source=$mixed->appendChild($doc->createElement("source"));
										  $source->appendChild($doc->createTextNode($BK_val['book']));
										  $mixed->appendChild($doc->createTextNode(","));}//punctuation   
						              
						              //pub-loc
						              if(!empty($BK_val['pub-loc'])){$mixed->appendChild($doc->createElement("publisher-loc",$BK_val['pub-loc']));$mixed->appendChild($doc->createTextNode(":"));}//punctuation   
						              
						              //pub-name
						              if(!empty($BK_val['pub-name'])){$mixed->appendChild($doc->createElement("publisher-name",$BK_val['pub-name']));$mixed->appendChild($doc->createTextNode(","));}//punctuation   
						              
						              //year
						              if(!empty($BK_val['year'])){$mixed->appendChild($doc->createElement("year",$BK_val['year']));
										  $mixed->appendChild($doc->createTextNode(", "));}//punctuation   
						              
						              //page
						              if(!empty($BK_val['page'])){
										  preg_match("/pp.(.*?)-([\\d|\\w]*)/is",$BK_val['page'],$match);
										  $mixed->appendChild($doc->createTextNode("pp."));//punctuation   
										  $mixed->appendChild($doc->createElement("fpage",$match[1]));
										  $mixed->appendChild($doc->createTextNode("–"));//punctuation   
										  $mixed->appendChild($doc->createElement("lpage",$match[2]));
										  $mixed->appendChild($doc->createTextNode("."));}//punctuation   
								}            
     	            } 

         $doc->save("front-back.xml");
         

         
	 }

	
}


$object_createXML=new createXML("smmr_journalmeta","source.tex");
$object_createXML->createFront();
$object_createXML->createBack();
$object_createXML->createBody();

		
?>
