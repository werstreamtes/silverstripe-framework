<?php
	/**
	 * EditableButton
	 * Allows a user to modify the text on the button
	 */
class EditableButton extends FormField {
	
	function Field() {
		return '<span class="EditableButton"><img src="cms/images/button-left.gif" /><input name="'.$this->name.'" value="'.$this->attrValue().'" /><img src="cms/images/button-right.gif" /></span>';
	}	
}
?>