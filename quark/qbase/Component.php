<?php

	require_once 'PropertyClass.php';

	class TComponent extends TPropertyClass
	{		
		protected $FCodeFile = '';
		var $ClassName; //  will self autocomplete
		var $Name = ''; //  it is set by the loader		
		var $Parent = null;
		var $Controls = Array();
		
		function __construct($AParent)
		{
			$this->ClassName = get_class($this);
			$reflector = new ReflectionClass($this->ClassName);
			$this->FCodeFile = $reflector->getFileName();
			if (isset($AParent)) $this->Parent = $AParent;
		}
		
		function get_CodeFile()
		{
			return $this->FCodeFile;
		}
		
		function getParentForm()
		{
			if ($this instanceof TForm) return $this;
			else 
			{
				if ($this->Parent == null) return null;
				if (!is_object($this->Parent)) return null;
				if (!($this->Parent instanceof TWidget)) return null;
				
				if ($this->Parent instanceof TForm) return $this->Parent;
				else return $this->Parent->getParentForm();
			}
		}		
		
		function get_id()
		{
			$id = $this->Name;
			
			if ($this->Parent == null) return $id;
			if (!is_object($this->Parent)) return $id;
			if (!($this->Parent instanceof TComponent)) return $id;
			
			return $this->Parent->id.'.'.$id;
		}
		
		function getControlbyID($id)
		{
			$a = split('\.', $id);
			if (count($a) == 0) return null;
			if ($a[0] == $this->Name) array_splice($a, 0, 1);
			
			if (count($a) == 0) return $this;
			else if (isset($this->Controls[$a[0]])) 
				return $this->Controls[$a[0]]->getControlbyID(implode('.', $a));
		}
		
		function getCount()
		{
			return count($this->Controls);
		}
		
		var $Properties = Array();
		
		function setProperty($name, $value)
		{
			$this->__set($name, $value);				
		}
		
		function addControl($ctrl)
		{
			if (!($ctrl instanceof TWidget)) return;
			
			$this->Controls[$ctrl->Name] = $ctrl;
			$ctrl->Parent = $this;
		}

		function setControlProperty($control, $name, $value)
		{
			$ctrl = null;
			if (isset($this->$control)) $ctrl = $this->$control;
			else $ctrl = $this->Controls[$control];
			if ($ctrl == null) return;
			
			if (TQuark::instance()->debugJS) TQuark::instance()->traceCallStack($ctrl, 'setProperty');
			$ctrl->setProperty($name, $value);			
		}
		
		function loadProperties($str)
		{
			$xml = simplexml_load_string($str);
			foreach ($xml->children() as $xml_node)
			{
				$type = $xml_node->getName();

				if (isWidget($type))
				{
					$widget = createWidget($type);
					$widget->Theme = $this->Theme;
					$widget->loadProperties($xml_node->asXML());
					
					$this->addControl($widget);
					/*$widget->Parent = $this;
					$this->Controls[$widget->Name] = $widget;*/
				}
				else
				{
					$name = trim(strtolower($xml_node->getName()));
					$value = $xml_node->__toString();

					if ($name == 'name') $this->Name = $value;
					if ($name == 'theme') $this->Theme = $value;
					if ($name == 'visible')
					{
						if (trim(strtolower($value)) == 'false') $this->FVisible = false;
						else $this->FVisible = true;
					}
					if ($name == 'cssclass') $this->CSSClass = $value;
					if ($name == 'style') $this->Style = $value;
					$this->setProperty($name, $value);
					
					$this->Properties[$name] = $value;
				}
			}
		}
		
		
	}

?>