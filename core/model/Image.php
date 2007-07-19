<?php

/**
 * @package sapphire
 * @subpackage core
 */

/**
 * Represents an image attached to a page.
 */
class Image extends File {
	/**
	 * The width of an image thumbnail in a strip.
	 * @var int
	 */
	public static $strip_thumbnail_width = 50;
	
	/**
	 * The height of an image thumbnail in a strip.
	 * @var int
	 */
	public static $strip_thumbnail_height = 50;
	
	/**
	 * The width of an image thumbnail in the CMS.
	 * @var int
	 */
	public static $cms_thumbnail_width = 100;
	
	/**
	 * The height of an image thumbnail in the CMS.
	 */
	public static $cms_thumbnail_height = 100;
	
	/**
	 * The width of an image thumbnail in the Asset section.
	 */
	public static $asset_thumbnail_width = 100;
	
	/**
	 * The height of an image thumbnail in the Asset section.
	 */
	public static $asset_thumbnail_height = 100;
	
	/**
	 * The width of an image preview in the Asset section.
	 */
	public static $asset_preview_width = 400;
	
	/**
	 * The height of an image preview in the Asset section.
	 */
	public static $asset_preview_height = 200;
	
	/**
	 * Set up template methods to access the transformations generated by 'generate' methods.
	 */
	public function defineMethods() {
		$methodNames = $this->allMethodNames();
		foreach($methodNames as $methodName) {
			if(substr($methodName,0,8) == 'generate') {
				$this->addWrapperMethod(substr($methodName,8), 'getFormattedImage');
			}
		}
		
		parent::defineMethods();
	}
	
	/**
	 * An image exists if it has a filename.
	 * @return boolean
	 */
	public function exists() {
		if(isset($this->record["Filename"])) {
			return true;
		}		
	}

	/**
	 * Get the URL for this Image.
	 * @return boolean
	 */
	function URL() {
		return Director::baseURL() . $this->Filename;
	}
	
	/**
	 * Return an XHTML img tag for this Image.
	 * @return string
	 */
	function Tag() {
		if(file_exists("../" . $this->Filename)) {
			$url = $this->URL();
			$title = $this->Title;
			return "<img src=\"$url\" alt=\"$title\" />";
		}
	}
	
	/**
	 * Return an XHTML img tag for this Image.
	 * @return string
	 */
	function forTemplate() {
		return $this->Tag();
	}
		
	/**
	 * Load a recently uploaded image into this image field.
	 * @param array $tmpFile The array entry from $_FILES
	 * @return boolean Returns true if successful
	 */
	function loadUploaded($tmpFile) {
		if(parent::loadUploaded($tmpFile)) {
			$this->deleteFormattedImages();
			return true;
		}
	}

	function loadUploadedImage($tmpFile) {
		if(!is_array($tmpFile)) {
			user_error("Image::loadUploadedImage() Not passed an array.  Most likely, the form hasn't got the right enctype", E_USER_ERROR);
		}
		
		if(!$tmpFile['size']) {
			return;
		}
		
		$base = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
		$class = $this->class;

		// Create a folder		
		if(!file_exists("$base/assets")) {
			mkdir("$base/assets", 02775);
		}
		
		if(!file_exists("$base/assets/$class")) {
			mkdir("$base/assets/$class", 02775);
		}

		// Generate default filename
		$file = str_replace(' ', '-',$tmpFile['name']);
		$file = ereg_replace('[^A-Za-z0-9+.-]+','',$file);
		$file = ereg_replace('-+', '-',$file);
		if(!$file) {
			$file = "file.jpg";
		}
		
		$file = "assets/$class/$file";
		
		while(file_exists("$base/$file")) {
			$i = $i ? ($i+1) : 2;
			$oldFile = $file;
			$file = ereg_replace('[0-9]*(\.[^.]+$)',$i . '\\1', $file);
			if($oldFile == $file && $i > 2) user_error("Couldn't fix $file with $i", E_USER_ERROR);
		}
		
		if(file_exists($tmpFile['tmp_name']) && copy($tmpFile['tmp_name'], "$base/$file")) {
			// Remove the old images

			$this->deleteFormattedImages();
			return true;
		}
	}
	
	public function SetWidth($width) {
		return $this->getFormattedImage('SetWidth', $width);
	}
	
	/**
	 * Resize this Image by width, keeping aspect ratio. Use in templates with $SetWidth.
	 * @return GD
	 */
	public function generateSetWidth(GD $gd, $width) {
		return $gd->resizeByWidth($width);
	}
	
	/**
	 * Resize this Image by height, keeping aspect ratio. Use in templates with $SetHeight.
	 * @return GD
	 */
	public function generateSetHeight(GD $gd, $height){
		return $gd->resizeByHeight($height);
	}
	
	public function CMSThumbnail() {
		return $this->getFormattedImage('CMSThumbnail');
	}
	
	/**
	 * Resize this image for the CMS. Use in templates with $CMSThumbnail.
	 * @return GD
	 */
	function generateCMSThumbnail(GD $gd) {
		return $gd->paddedResize($this->stat('cms_thumbnail_width'),$this->stat('cms_thumbnail_height'));
	}
	
	/**
	 * Resize this image for preview in the Asset section. Use in templates with $AssetLibraryPreview.
	 * @return GD
	 */
	function generateAssetLibraryPreview(GD $gd) {
		return $gd->paddedResize($this->stat('asset_preview_width'),$this->stat('asset_preview_height'));
	}
	
	/**
	 * Resize this image for thumbnail in the Asset section. Use in templates with $AssetLibraryThumbnail.
	 * @return GD
	 */
	function generateAssetLibraryThumbnail(GD $gd) {
		return $gd->paddedResize($this->stat('asset_thumbnail_width'),$this->stat('asset_thumbnail_height'));
	}
	
	/**
	 * Resize this image for use as a thumbnail in a strip. Use in templates with $StripThumbnail.
	 * @return GD
	 */
	function generateStripThumbnail(GD $gd) {
		return $gd->croppedResize($this->stat('strip_thumbnail_width'),$this->stat('strip_thumbnail_height'));
	}

	/**
	 * Return an image object representing the image in the given format.
	 * This image will be generated using generateFormattedImage().
	 * The generated image is cached, to flush the cache append ?flush=1 to your URL.
	 * @param string $format The name of the format.
	 * @param string $arg1 An argument to pass to the generate function.
	 * @param string $arg2 A second argument to pass to the generate function.
	 * @return Image_Cached
	 */
	function getFormattedImage($format, $arg1 = null, $arg2 = null) {
		if($this->ID && $this->Filename && Director::fileExists($this->Filename)) {
			$cacheFile = $this->cacheFilename($format, $arg1, $arg2);

			if(!file_exists("../".$cacheFile) || isset($_GET['flush'])) {
				$this->generateFormattedImage($format, $arg1, $arg2);
			}
			
			return new Image_Cached($cacheFile);
		}
	}
	
	/**
	 * Return the filename for the cached image, given it's format name and arguments.
	 * @param string $format The format name.
	 * @param string $arg1 The first argument passed to the generate function.
	 * @param string $arg2 The second argument passed to the generate function.
	 * @return string
	 */
	function cacheFilename($format, $arg1 = null, $arg2 = null) {
		$folder = $this->ParentID ? $this->Parent()->Filename : "assets/";
		
		$format = $format.$arg1.$arg2;
		
		return $folder . "_resampled/$format-" . $this->Name;
	}
	
	/**
	 * Generate an image on the specified format. It will save the image
	 * at the location specified by cacheFilename(). The image will be generated
	 * using the specific 'generate' method for the specified format.
	 * @var string $format Name of the format to generate.
	 * @var string $arg1 Argument to pass to the generate method.
	 * @var string $arg2 A second argument to pass to the generate method.
	 */
	function generateFormattedImage($format, $arg1 = null, $arg2 = null) {
		$cacheFile = $this->cacheFilename($format, $arg1, $arg2);
	
		$gd = new GD("../" . $this->Filename);
		
		
		if($gd->hasGD()){
			$generateFunc = "generate$format";		
			if($this->hasMethod($generateFunc)){
				$gd = $this->$generateFunc($gd, $arg1, $arg2);
				if($gd){
					$gd->writeTo("../" . $cacheFile);
				}
	
			} else {
				USER_ERROR("Image::generateFormattedImage - Image $format function not found.",E_USER_WARNING);
			}
		}
	}
	
	/**
	 * Generate a resized copy of this image with the given width & height.
	 * Use in templates with $ResizedImage.
	 */
	function generateResizedImage($gd, $width, $height) {
		if(is_numeric($gd) || !$gd){
			USER_ERROR("Image::generateFormattedImage - generateResizedImage is being called by legacy code or gd is not set.",E_USER_WARNING);
		}else{
			return $gd->resize($width, $height);
		}
	}

	/**
	 * Generate a resized copy of this image with the given width & height, cropping to maintain aspect ratio.
	 * Use in templates with $CroppedImage
	 */
	function generateCroppedImage($gd, $width, $height) {
		return $gd->croppedResize($width, $height);
	}
	
	/**
	 * Remove all of the formatted cached images.
	 * Should be called by any method that updates the current image.
	 */
	public function deleteFormattedImages() {
		if($this->Filename) {
			$numDeleted = 0;
			$methodNames = $this->allMethodNames();
			$numDeleted = 0;
			foreach($methodNames as $methodName) {
				if(substr($methodName,0,8) == 'generate') {
					$format = substr($methodName,8);
					$cacheFile = $this->cacheFilename($format);
					if(Director::fileExists($cacheFile)) {
						unlink(Director::getAbsFile($cacheFile));
						$numDeleted++;
					}
				}
			}
			return $numDeleted;
		}
	}
	
	/**
	 * Get the dimensions of this Image.
	 * @param string $dim If this is equal to "string", return the dimensions in string form,
	 * if it is 0 return the height, if it is 1 return the width.
	 * @return string|int
	 */
	function getDimensions($dim = "string") {
		if($this->getField('Filename')) {
			$imagefile = Director::baseFolder() . '/' . $this->getField('Filename');
			if(file_exists($imagefile)) {
				$size = getimagesize($imagefile);
				return ($dim === "string") ? "$size[0]x$size[1]" : $size[$dim];
			} else {
				return ($dim === "string") ? "file '$imagefile' not found" : null;
			}
		}
	}

	/**
	 * Get the width of this image.
	 * @return int
	 */
	function getWidth() {
		return $this->getDimensions(0);
	}
	
	/**
	 * Get the height of this image.
	 * @return int
	 */
	function getHeight() {
		return $this->getDimensions(1);
	}
}

/**
 * Image not stored in the database, that is just a cached resampled image
 */
class Image_Cached extends Image {
	/**
	 * Create a new cached image.
	 * @param string $filename The filename of the image.
	 * @param boolean $isSingleton This this to true if this is a singleton() object, a stub for calling methods.  Singletons
	 * don't have their defaults set.
	 */
	public function __construct($filename = null, $isSingleton = false) {
		parent::__construct(array(), $isSingleton);
		$this->Filename = $filename;
	}
	
	public function getRelativePath() {
		return $this->getField('Filename');
	}
	
	// Prevent this from doing anything
	public function requireTable() {
		
	}
	
	public function debug() {
		return "Image_Cached object for $this->Filename";
	}
}

class Image_Saver extends DBField {
	function saveInto($record) {
		$image = $record->getComponent($this->name);
		if(!$image) {
			$image = $record->createComponent($this->name);
		}
		if($image) {
			$image->loadUploaded($this->value);
		} else {
			user_error("ImageSaver::saveInto() Image field '$this->name' note found", E_USER_ERROR);
		}
	}
	
	function requireField() {
		return null;
	}
}

/**
 * Uploader support for the uploading anything which is a File or subclass of 
 * File, eg Image.
 */
class Image_Uploader extends Controller {
	/**
	 * Ensures the css is loaded for the iframe.
	 */
	function iframe() {
		Requirements::css("cms/css/Image_iframe.css");
		return array();
	}
	
	/**
	 * Image object attached to this class.
	 * @var Image
	 */
	protected $imageObj;
	
	/**
	 * Associated parent object.
	 * @var DataObject
	 */
	protected $linkedObj;
	
	/**
	 * Finds the associated parent object from the urlParams.
	 * @return DataObject
	 */
	function linkedObj() {
		if(!$this->linkedObj) {
			$this->linkedObj = DataObject::get_by_id($this->urlParams['Class'], $this->urlParams['ID']);
			if(!$this->linkedObj) {
				user_error("Data object '{$this->urlParams['Class']}.{$this->urlParams['ID']}' couldn't be found", E_USER_ERROR);
			}
		}				
		return $this->linkedObj;
	}
	
	/**
	 * Returns the Image object attached to this class.
	 * @return Image
	 */
	function Image() {
		if(!$this->imageObj) {
			$funcName = $this->urlParams['Field'];
			$linked = $this->linkedObj();
			$this->imageObj = $linked->obj($funcName);
			if(!$this->imageObj) {$this->imageObj = new Image(null);}
		}
		
		return $this->imageObj;
	}
	
	/**
	 * Returns true if the file attachment is an image.
	 * Otherwise, it's a file.
	 * @return boolean
	 */
	function IsImage() {
		$className = $this->Image()->class;
		return $className == "Image" || is_subclass_of($className, "Image");
	}

	function UseSimpleForm() {
		if(!$this->useSimpleForm) {
			$this->useSimpleForm = false;
		}
		return $this->useSimpleForm;
	}
	
	/**
	 * Return a link to this uploader.
	 * @return string
	 */
	function Link($action = null) {
		return $this->RelativeLink($action);
	}
	
	/**
	 * Return the relative link to this uploader.
	 * @return string
	 */
	function RelativeLink($action = null) {
		if(!$action) {
			$action = "index";
		}
		return "images/$action/{$this->urlParams['Class']}/{$this->urlParams['ID']}/{$this->urlParams['Field']}";
	}
	
	/**
	 * Form to show the current image and allow you to upload another one.
	 * @return Form
	 */
	function EditImageForm() {
		$isImage = $this->IsImage();
		$type =  $isImage ? "Image" : "File";
		if($this->Image()->ID) {
			$title = "Replace " . $type;
			$fromYourPC = "With one from your computer";
			$fromTheDB = "With one from the file store";
		} else {
			$title = "Attach ". $type;
			$fromYourPC = "From your computer";
			$fromTheDB = "From the file store";
		}
		return new Form($this, 'EditImageForm', new FieldSet(
			new HiddenField("Class", null, $this->urlParams['Class']),
			new HiddenField("ID", null, $this->urlParams['ID']),
			new HiddenField("Field", null, $this->urlParams['Field']),
			new HeaderField($title),
			new SelectionGroup("ImageSource", array(
				"new//$fromYourPC" => new FieldGroup("",
					new FileField("Upload","")
				),
				"existing//$fromTheDB" => new FieldGroup("",
					new TreeDropdownField("ExistingFile", "","File")
				)
			))
		),
		
		new FieldSet(
			new FormAction("save",$title)
		));
	}
	
	/**
	 * A simple version of the upload form.
	 * @returns string
	 */
	function EditImageSimpleForm() {
		$isImage = $this->IsImage();
		$type =  $isImage ? "Image" : "File";
		if($this->Image()->ID) {
			$title = "Replace " . $type;
			$fromYourPC = "With one from your computer";
		} else {
			$title = "Attach". $type;
			$fromTheDB = "From the file store";
		}
		
		return new Form($this, 'EditImageSimpleForm', new FieldSet(
			new HiddenField("Class", null, $this->urlParams['Class']),
			new HiddenField("ID", null, $this->urlParams['ID']),
			new HiddenField("Field", null, $this->urlParams['Field']),
			new FileField("Upload","")
		),
		new FieldSet(
			new FormAction("save",$title)
		));
	}
	
	/**
	 * A form to delete this image.
	 * @return string
	 */
	function DeleteImageForm() {
		if($this->Image()->ID) {
			$isImage = $this->IsImage();
			$type =  $isImage ? "Image" : "File";
			$title = "Delete " . $type;
			return new Form($this,'DeleteImageForm', new FieldSet(
				new HiddenField("ID", null, $this->urlParams['ID']),
				new HeaderField($title),
				new LabelField("Click the button below to remove this $type.")
				),
				new FieldSet(
					new ConfirmedFormAction("delete",$title, "Do you really want to remove this $type?")
				)
			);
		}
	}
	
	/**
	 * Save the data in this form.
	 */
	function save($data, $form) {
		$owner = DataObject::get_by_id($data['Class'], $data['ID']);
		$fieldName = $data['Field'] . 'ID';

		if($data['ImageSource'] == 'existing') {
			$owner->$fieldName = $data['ExistingFile'];

			// Edit the class name, if applicable
			$existingFile = DataObject::get_by_id("File", $data['ExistingFile']);
			$desiredClass = $owner->has_one($data['Field']);
			if(!is_a($existingFile, $desiredClass)) {
				$existingFile->ClassName = $desiredClass;
				$existingFile->write();
			}
		} else {
			// TODO We need to replace this with a way to get the type of a field
			$imageClass = $owner->has_one($data['Field']);
		
			// If we can't find the relationship, assume its an Image.
			if( !$imageClass) {
				if(!is_subclass_of( $imageClass, 'Image' )){
					$imageClass = 'Image';	
				}
			}
			
			// Assuming its a decendant of File
			$image = new $imageClass();
			$image->loadUploaded($data['Upload']);
			$owner->$fieldName = $image->ID;
			
		    // store the owner id with the uploaded image
    		$member = Member::currentUser();
			$image->OwnerID = $member->ID;
			$image->write();
		}

		$owner->write();
		Director::redirectBack();
	}

	/**
	 * Delete the image referenced by this form.
	 */
	function delete($data, $form) {
		$image = $this->Image();
		$image->delete();
		Director::redirect($this->Link('iframe'));
	}

	/**
	 * Flush all of the generated images.
	 */
	function flush() {
		$images = DataObject::get("Image","");
		$numItems = 0;
		$num = 0;
		
		foreach($images as $image) {
			$numDeleted = $image->deleteFormattedImages();
			if($numDeleted) {
				$numItems++;
			}
			$num += $numDeleted;
		}
		echo $num . ' formatted images from ' . $numItems . ' items flushed';
	}


	/**
	 * Transfer all the content from the Image table into the File table.
	 * @deprecated This function is only used to migrate content from old databases.
	 */
	function transferlegacycontent() {
		$images = DB::query("SELECT * FROM _obsolete_Image");
		echo "<h3>Transferring images</h3>";
		foreach($images as $image) {
			if(($className = $image['ClassName']) && $image['Filename']) {
				echo "<li>Importing $image[Filename]";
				$folderName = str_replace('assets/','',dirname($image['Filename']));
				$name = basename($image['Filename']);
				$folderObj = Folder::findOrMake($folderName);
				$fileObj = new $className();
				$fileObj->Name = $name;
				$fileObj->ParentID = $folderObj->ID;
				$fileObj->write();
				$fileObj->destroy();
								
				echo " as $fileObj->ID";
				
				list($baseClass,$fieldName) = explode('_',$className,2);
				
				//if($baseClass == 'News') $baseClass = 'NewsHolder';
				//if($baseClass == 'FAQ') $baseClass = 'FaqHolder';
				
				$parentObj = DataObject::get_by_id($baseClass, $image['ParentID']);
				if($parentObj && $fieldName) {
					$fieldName .= "ID";
					echo "<li>$parentObj->class.$parentObj->ID.$fieldName = $fileObj->ID";
					$parentObj->$fieldName = $fileObj->ID;
					$parentObj->write();
					$parentObj->destroy();
				}

				// echo " and linking to $baseClass.$image[ParentID]->$fieldName";
			}
		}
	}
}

?>