<?php

/**
 * This field lets you put an arbitrary piece of HTML into your forms.
 * If there's not much behaviour around the HTML, it might not be worth going to the effort of
 * making a special field type for it.  So you can use LiteralField.  If you pass it a viewabledata object,
 * it will turn it into a string for you. 
 * @pacakge forms
 */
class LiteralField extends DatalessField {
	protected $content;
	
	function __construct($name, $content) {
		parent::__construct($name);
		$this->content = $content;
	}
	function FieldHolder() {
		return is_object($this->content) ? $this->content->forTemplate() : $this->content; 
	}
	function Field() {
		return $this->FieldHolder();
	}
  
  /**
  * @desc Sets the content of this field to a new value
  */
  function setContent($content) {
    $this->content = $content;
  }

	function performReadonlyTransformation() {
		return $this;
	}
}

?>