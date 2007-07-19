<?php

/**
 * ModelAsController will hand over all control to the appopriate model object
 * It uses URLSegment to determine the right object.  Also, if (ModelClass)_Controller exists,
 * that controller will be used instead.  It should be a subclass of ContentController.
 */
class ModelAsController extends Controller implements NestedController {
	
	public function run($requestParams) {
		$this->init();
		return $this->getNestedController()->run($requestParams);
	}
	
	public function init() {
		Versioned::choose_site_stage();
	}

	public function getNestedController() {
		if($this->urlParams['URLSegment']) {

			$child = DataObject::get_one("SiteTree", "URLSegment = '" . addslashes($this->urlParams['URLSegment']) . "'");
			if(!$child) {
				header("HTTP/1.0 404 Not Found");
				$child = $this->get404Page();
			}
		
			if($child) {
				if(isset($_REQUEST['debug'])) Debug::message("Using record #$child->ID of type $child->class with URL {$this->urlParams['URLSegment']}");
				
				$controllerClass = "{$child->class}_Controller";
	
				if($this->urlParams['Action'] && ClassInfo::exists($controllerClass.'_'.$this->urlParams['Action'])) {
					$controllerClass = $controllerClass.'_'.$this->urlParams['Action'];	
				}
	
				if(ClassInfo::exists($controllerClass)) {
					$controller = new $controllerClass($child);
				} else {
					$controller = $child;
				}
				$controller->setURLParams($this->urlParams);
			
				return $controller;
			} else {
				die("The requested page couldn't be found.");
			}
			
		} else {
			user_error("ModelAsController not geting a URLSegment.  It looks like the site isn't redirecting to home", E_USER_ERROR);
		}
	}
	
	protected function get404Page() {
		if($page = DataObject::get_one("ErrorPage", "ErrorCode = '404'")) return $page;
		else return DataObject::get_one("SiteTree", "URLSegment = '404'");
	}
}

?>
