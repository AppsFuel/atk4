<?php
/***********************************************************
   ..

   Reference:
     http://atk4.com/doc/ref

 **ATK4*****************************************************
   This file is part of Agile Toolkit 4 
    http://www.atk4.com/
  
   (c) 2008-2011 Agile Technologies Ireland Limited
   Distributed under Affero General Public License v3
   
   If you are using this file in YOUR web software, you
   must make your make source code for YOUR web software
   public.

   See LICENSE.txt for more information

   You can obtain non-public copy of Agile Toolkit 4 at
    http://www.atk4.com/commercial/ 

 *****************************************************ATK4**/
/**
 * Class represents a simple CMS API containing common entities:
 * - news
 * - static pages
 * - events
 * - jobs
 * This API works not only with pages, but also with RSS channels
 * It is assumed that RSS channels are all under rss/ dir of project root,
 * similar to page/ dir for pages.
 *
 * Created on 23.09.2008 by *Camper* (cmd@adevel.com)
 */
class ApiFrontend extends ApiWeb{
	public $page_object=null;
	public $content_type='page';	// content type: rss/page/etc
	public $page_class='Page';

	function init(){
		parent::init();
		$this->getLogger();
		$this->initializeTemplate();
		// base url is requred due to a Home/Events/Article.html links style


	}
	function layout_Content(){
		// required class prefix depends on the content_type
		// This function initializes content. Content is page-dependant
		$page=str_replace('/','_',$this->page);
		$page=str_replace('-','',$page);
		$class=$this->content_type.'_'.$page;
		if(method_exists($this,$class)){
			// for page we add Page class, for RSS - RSSchannel
			// TODO - this place is suspicious. Can it call arbitary function from API?
			$this->page_object=$this->add($this->content_type=='page'?$this->page_class:'RSSchannel',$this->page);
			$this->$class($this->page_object);
		}else{
			try{
				loadClass($class);
			}catch(PathFinder_Exception $e){

				$class_parts=explode('_',$page);
				$funct_parts=array();
				while($class_parts){
						array_unshift($funct_parts,array_pop($class_parts));
						$fn='page_'.join('_',$funct_parts);
						$in='page_'.join('_',$class_parts);
						try {
							loadClass($in);
						}catch(PathFinder_Exception $e2){
							continue;
						}
						// WorkAround for PHP5.2.12+ PHP bug #51425
						$tmp=new $in;
						if(!method_exists($tmp,$fn) && !method_exists($tmp,'subPageHandler'))continue;

						$this->page_object=$this->add($in,$page);
						if(method_exists($tmp,$fn)){
							$this->page_object->$fn();
						}elseif(method_exists($tmp,'subPageHandler')){
							if($this->page_object->subPageHandler(join('_',$funct_parts))===false)break;
						}
						return;
				}


				// page not found, trying to load static content
				try{
					$this->loadStaticPage($this->page);
				}catch(PathFinder_Exception $e2){
					// throw original error
					$this->pageNotFound($e);
				}
				return;
			}
			// i wish they implemented "finally"
			$this->page_object=$this->add($class,$page,'Content');
			if(method_exists($this->page_object,'initMainPage'))$this->page_object->initMainPage();
		}
	}
	function pageNotFound($e){
		throw $e;
	}
	protected function loadStaticPage($page){
		$this->page_object=$this->add($this->page_class,$page,'Content',array('page/'.strtolower($page),'_top'));
		return $this->page_object;
	}
	function execute(){
		try{
			parent::execute();
		}catch(Exception $e){
			$this->caughtException($e);
		}
	}
	function getRSSURL($rss,$args=array()){
		$tmp=array();
		foreach($args as $arg=>$val){
			if(!isset($val) || $val===false)continue;
			if(is_array($val)||is_object($val))$val=serialize($val);
			$tmp[]="$arg=".urlencode($val);
		}
		return
			$rss.'.xml'.($tmp?'?'.join('&',$tmp):'');
	}
	function formatAlert($s) {
		$r = addslashes(strip_tags($s));
		$r = str_replace("\n", " | ", $r);
		return $r;
	}
	/**
	 * Adds a floating frame that will reload its content on show.
	 */
	function addFrame($object,$title,$page){
		$frame=$object->add('AjaxFrame',"ff_$page");
		$frame->setObject($page);
		$frame->frame($title,null,null,'width="700"');
		$frame->getFrame()->add('Button','btn_close')
			->setLabel('Close')->onClick()->setFrameVisibility($frame,false);
		return $frame;
	}
}
