<?php

/**
 * Hidden field.
 */
class HiddenField extends FormField {
	/**
	 * Returns an hidden input field, class="hidden" and type="hidden"
	 */
	function Field() {
		//if($this->name=="ShowChooseOwn")Debug::show($this->value);
		return "<input class=\"hidden\" type=\"hidden\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />";
	}
	function FieldHolder() {
		return $this->Field();
	}
	function performReadonlyTransformation() {
		return $this;
	}
	
	function IsHidden() {
		return true;
	}

	static function create($name) { return new HiddenField($name); }
}

?>