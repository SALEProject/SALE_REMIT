<?php

	class TScrollBox extends TWidget
	{

		function setProperty($name, $value)
		{
			switch ($name)
			{
				default:
					parent::setProperty($name, $value);
					break;
			}
		}

		function generateHTML()
		{
			$s = $this->innerHTML();
			
			$class = '';
			if ($this->Theme != '') $class = $this->Theme.'_'.$this->ClassName;
						
			$style = 	'display: block; '.
						'position: absolute; '.
						'left: '.$this->Left.'px; '.
						'top: '.$this->Top.'px; '.
						'width: '.$this->Width.'px; '.
						'height: '.$this->Height.'px; '.
						'overflow: scroll;';
			
			return '<div id="%parent%_'.$this->Name.'" class="'.$class.'" style="'.$style.'">'.$s.'</div>';
		}
		
	} 
	
	registerWidget('TScrollBox', 'TScrollBox');
	
?>