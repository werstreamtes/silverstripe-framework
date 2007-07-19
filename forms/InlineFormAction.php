<?php
/**
 * Render a button that will act as 
 * If you want to add custom behaviour, please set {inlcudeDefaultJS} to false and work with behaviour.js.
 */
class InlineFormAction extends FormField {
	
	protected $includeDefaultJS = true;
	
	function performReadonlyTransformation() {
		return new InlineFormAction_ReadOnly( $this->name, $this->title );
	}
	
	function Field() {
		if($this->includeDefaultJS) {
			Requirements::javascriptTemplate('sapphire/javascript/InlineFormAction.js',array('ID'=>$this->id()));
		}
		
		return "<input type=\"submit\" name=\"action_{$this->name}\" value=\"{$this->title}\" id=\"{$this->id()}\" class=\"action$extraClass\" />";
	}	
	
	function Title() { 
		return false; 
	}
	
	/**
	 * Optionally disable the default javascript include (sapphire/javascript/InlineFormAction.js),
	 * which routes to an "admin-custom"-URL.
	 * 
	 * @param $bool boolean
	 */
	function includeDefaultJS($bool) {
		$this->includeDefaultJS = (bool)$bool;
	}
}

class InlineFormAction_ReadOnly extends FormField {
	function Field() {
		return "<input type=\"submit\" name=\"action_{$this->name}\" value=\"{$this->title}\" id=\"{$this->id()}\" disabled=\"disabled\" class=\"action$extraClass\" />";
	}	
	
	function Title() { 
		return false; 
	}
}
?>