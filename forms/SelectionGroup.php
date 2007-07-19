<?php

/**
 * SelectionGroup represents a number of fields that are selectable by a radio button that appears at
 * the beginning of each item.  Using CSS, you can configure the field to only display its contents if
 * the corresponding radio button is selected.
 */
class SelectionGroup extends CompositeField {
	/**
	 * Create a new selection group.
	 * @param name The field name of the selection group.
	 * @param items The list of items to show.  This should be a map.  The keys will be the radio
	 * button option names, and the values should be the associated field to display.  If you want,
	 * you can make this field a composite field.
	 * 
	 * If you want to a have a title that is different from the value of the key, you can express it
	 * as "InternalVal//This is the Title"
	 */
	function __construct($name, $items) {
		$this->name = $name;
		parent::__construct($items);
	}
	
	function FieldSet() {
		$items = parent::FieldSet()->toArray();
		
		if(!$items || !in_array($this->value, array_keys($items))) {
			$firstSelected = " class=\"selected\"";
			$checked = " checked=\"checked\"";
		}
		
		$count = 0;
		foreach($items as $key => $item) {
			if(strpos($key,'//') !== false) {
				list($key,$title) = explode('//', $key,2);
			} else {
				$title = $key;
			}
			
			if($this->value == $key) {
				$firstSelected = " class=\"selected\"";
				$checked = " checked=\"checked\"";
			}
			
			$itemID = $this->ID() . '_' . (++$count);
			$extra = array(
				"RadioButton" => "<input class=\"selector\" type=\"radio\" id=\"$itemID\" name=\"$this->name\" value=\"$key\"$checked />",
				"RadioLabel" => "<label for=\"$itemID\">$title</label>",
				"Selected" => $firstSelected,
			);
			if(is_object($item)) $newItems[] = $item->customise($extra);
			else $newItems[] = new ArrayData($extra);

			$firstSelected = $checked ="";			
		}
		return new DataObjectSet($newItems);
	}
	
	function hasData() {
		return true;
	}
	
	function FieldHolder() {
		Requirements::javascript('jsparty/prototype.js');
		Requirements::javascript('jsparty/behaviour.js');
		Requirements::javascript('jsparty/prototype_improvements.js');
		Requirements::javascript('sapphire/javascript/SelectionGroup.js');
		
		
		return $this->renderWith("SelectionGroup");
	}
}

?>