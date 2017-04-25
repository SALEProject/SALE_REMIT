<?php

	class TImage extends TWidget
	{
		var $Picture;
		
		function setProperty($name, $value)
		{
			switch ($name)
			{
				case 'picture':
					$this->Picture = $value;
					break;
				default:
					parent::setProperty($name, $value);
					break;
			}
		}

		function generateHTML()
		{
			$class = '';
			if ($this->Theme != '') $class = $this->Theme.'_'.$this->ClassName;
							
			$style =	'display: block; '.
						'position: absolute; '.
						'left: '.$this->Left.'px; '.
						'top: '.$this->Top.'px; ';
			
			switch ($this->FVisible)
			{
				case false:
					$style .= 'visibility: hidden; ';
					break;
				case true:
					$style .= 'visibility: visible; ';
					break;
			}
				
			$id = $this->Name;
			if ($this->Parent != null) $id = $this->Parent->Name.'.'.$id;
			else $id = '%parent%.'.$id;
			
			$html = '<img id="'.$id.'" class="'.$class.'" style="'.$style.'" src="%workdir%/'.$this->Picture.'" width="'.$this->Width.'" height="'.$this->Height.'"></img>';
			$this->st_rendered = true;
			return $html;
		}
		
		
	}

	registerWidget('TImage', 'TImage');
	
?>