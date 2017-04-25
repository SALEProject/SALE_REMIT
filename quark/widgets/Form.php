<?php

class TForm extends TWidget
{
	static $DefaultStyle = "
div.default_TForm
{
	background-color: #dddddd;
	border-style: solid;
	border-color: white;
	border-width: 1px;
	border-radius: 10px;
	box-shadow: 0px 0px 10px #888888;	
	
	-webkit-touch-callout: none;
	-webkit-user-select: none;
	-khtml-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}

div.default_TForm.morph
{
	opacity: 0.5;
	filter: alpha(opacity=50); /* For IE8 and earlier */	
}

div.default_TForm_modal
{
	background-color: #888888;
	opacity: 0.5;
	filter: alpha(opacity=50); /* For IE8 and earlier */
}

div.default_TForm div.default_TForm_caption
{
	background-color: #ccccff;
	height: 22px;
	border-style: none;
	border-top-color: white;
	border-top-style: solid;
	border-top-width: 1px;
	border-left-color: white;
	border-left-style: solid;
	border-left-width: 1px;
	border-right-color: white;
	border-right-style: solid;
	border-right-width: 1px;
	border-radius: 10px 10px 0px 0px;
	box-shadow: 0px 0px 10px #888888;	
	text-shadow: 0 0 3px #999999;
}

div.default_TForm a.default_TForm_close
{
	display: block;
	position: absolute;
	/*background-color: red;*/
	background: -webkit-linear-gradient(darkorange, orangered);
	background: -o-linear-gradient(darkorange, orangered);
	background: -moz-linear-gradient(darkorange, orangered);
	background: linear-gradient(darkorange, orangered);
	right: 12px;
	top: 0px;
	width: 24px;
	height: 16px;
	border-bottom-right-radius: 5px;
	border-bottom-left-radius: 5px;	
	border-bottom: 1px solid white;
	border-right: 1px solid white;
	border-left: 1px solid white;
	cursor: pointer;
	opacity: 0.5;
}

div.default_TForm a.default_TForm_resize
{
	display: block;
	position: absolute;
	background-color: transparent;
	/*border: 1px solid black;*/	
	width: 16px;
	height: 16px;
}

div.default_TForm a.default_TForm_close:hover
{
	opacity: 1;
}

div.default_TForm span.default_TForm_caption
{
	display: block;
	position: absolute;
	left: 8px;
	top: 4px;
	font-weight: bold;
	color: white;
}
			";
	var $contextID = 0;
	var $CodeFile;
	var $Caption = '';
	var $BorderStyle;
	var $Position;
	var $IsModal = false;
	var $Visible = false;
	var $ThumbCSSClass;
	var $ThumbVisible = true;
	
	function __construct($AParent)
	{
		parent::__construct($AParent);
		
		$this->Left = 0;
		$this->Top = 0;
		$this->Width = 200;
		$this->Height = 100;
	}

	function setProperty($name, $value)
	{
		switch (strtolower($name))
		{
			case 'codefile':
				$this->CodeFile = $value;
			case 'caption':
				$this->Caption = $value;
				break;
			case 'borderstyle':
				$this->BorderStyle = $value;
				break;
			case 'position':
				$this->Position = $value;
				break;
			case 'thumbcssclass':
				$this->ThumbCSSClass = $value;
				break;
			case 'thumbvisible':
				if (trim(strtolower($value)) == 'true') $this->ThumbVisible = true;
				else $this->ThumbVisible = false;
				break;
			default:
				parent::setProperty($name, $value);
				break;
		}
	}

	function generateHTML()
	{
		$s = $this->innerHTML();
		$s = str_replace('%parent%', $this->Name, $s);
		
		$workdir = '';
		$context = TQuark::instance()->getContext($this->contextID);
		if ($context != null) $workdir = $context->WorkingDirectory;
		$s = str_replace('%workdir%', $workdir, $s);
			
		$class = '';
		if ($this->Theme != '') $class = $this->Theme.'_TForm';
		
		$style = 'display: block; overflow: hidden; ';
		switch (trim(strtolower($this->Position)))
		{
			case 'podesigned':
				$style = 	'position: absolute; '.
							'left: '.$this->Left.'px; '.
							'top: '.$this->Top.'px; '.
							'width: '.$this->Width.'px; '.
							'height: '.$this->Height.'px;';
				break;
			case 'poscreencenter':
				$style = 	'position: absolute; '.
							'left: 50%; '.
							'top: 50%; '.
							'width: '.$this->Width.'px; '.
							'height: '.$this->Height.'px;'.
							'margin-left: -'.($this->Width / 2).'; '.
							'margin-top: -'.($this->Height / 2).';';
				break;
			case 'pofillcontainer':
				$style = 	'position: relative; '.
							//'left: 0px;'.
							//'top: 0px;'.
							//'width: 100%'.
							'min-height: '.$this->Height.'px;';
				break;
		}

		$html = '';

		switch (trim(strtolower($this->BorderStyle)))
		{
			case "bsnone":
				$html = $s;
				break;
			case "bssingle":
				$html = $s;
				break;
			case "bsdialog":
				$str_onmousedown = 'onmousedown="getJSform(\''.$this->Name.'\').mouseDown(event);"';
				$str_onmousemove = 'onmousemove="getJSform(\''.$this->Name.'\').mouseMove(event);"';
				$str_onmouseup = 'onmouseup="getJSform(\''.$this->Name.'\').mouseUp(event);"';
				if (TQuark::instance()->clientScreen->TouchCapable)
				{
					$str_onmousedown = 	'ontouchstart="getJSform(\''.$this->Name.'\').mouseDown(event);"';
					$str_onmousemove = 	'ontouchmove="getJSform(\''.$this->Name.'\').mouseMove(event);"';
					$str_onmouseup = 	'ontouchend="getJSform(\''.$this->Name.'\').mouseUp(event);" '.
										'onmouseup="getJSform(\''.$this->Name.'\').mouseUp(event);"';	
				}
				
				$html = '<div class="'.$class.'_caption" style="display: block; position: relative; width: 100%" '.
						'onclick="getJSform(\''.$this->Name.'\').captionClick();" '.
						$str_onmousedown.' '.$str_onmousemove.' '.$str_onmouseup.' '.
						'onmouseout="getJSform(\''.$this->Name.'\').mouseExit(event);"> '.
						'<span class="'.$class.'_caption">'.$this->Caption.'</span> '.
						'<a id="'.$this->Name.'_close" class="'.$class.'_close" onclick="getJSform(\''.$this->Name.'\').callBack(\'close\')"></a>'.
						'</div> '.
						'<div class="'.$class.'_content" style="display: block; position: relative; width: 100%; height: 100%;">'.$s.'</div>';
				break;
			case "bssizeable":
				$str_onmousedown = 'onmousedown="getJSform(\''.$this->Name.'\').mouseDown(event);"';
				$str_onmousemove = 'onmousemove="getJSform(\''.$this->Name.'\').mouseMove(event);"';
				$str_onmouseup = 'onmouseup="getJSform(\''.$this->Name.'\').mouseUp(event);"';
				if (TQuark::instance()->clientScreen->TouchCapable)
				{
					$str_onmousedown = 	'ontouchstart="getJSform(\''.$this->Name.'\').mouseDown(event);"';
					$str_onmousemove = 	'ontouchmove="getJSform(\''.$this->Name.'\').mouseMove(event);"';
					$str_onmouseup = 	'ontouchend="getJSform(\''.$this->Name.'\').mouseUp(event);" '.
										'onmouseup="getJSform(\''.$this->Name.'\').mouseUp(event);"';	
				}
				
				$html = '<div class="'.$class.'_caption" style="display: block; position: relative; width: 100%" '.
						'onclick="getJSform(\''.$this->Name.'\').captionClick();" '.
						$str_onmousedown.' '.$str_onmousemove.' '.$str_onmouseup.' '.
						'onmouseout="getJSform(\''.$this->Name.'\').mouseExit(event);"> '.
						'<span class="'.$class.'_caption">'.$this->Caption.'</span> '.
						'<a id="'.$this->Name.'_close" class="'.$class.'_close" onclick="getJSform(\''.$this->Name.'\').callBack(\'close\')"></a>'.
						'</div> '.
						'<div class="'.$class.'_content" style="display: block; position: relative; width: 100%; height: 100%;">'.$s.'</div>';
				
				$str_onmousedown = 'onmousedown="getJSform(\''.$this->Name.'\').resize_mouseDown(event, \'%dir%\');"';
				$str_onmousemove = 'onmousemove="getJSform(\''.$this->Name.'\').resize_mouseMove(event, \'%dir%\');"';
				$str_onmouseup = 'onmouseup="getJSform(\''.$this->Name.'\').resize_mouseUp(event, \'%dir%\');"';
				if (TQuark::instance()->clientScreen->TouchCapable)
				{
					$str_onmousedown = 	'ontouchstart="getJSform(\''.$this->Name.'\').resize_mouseDown(event, \'%dir%\');"';
					$str_onmousemove = 	'ontouchmove="getJSform(\''.$this->Name.'\').resize_mouseMove(event, \'%dir%\');"';
					$str_onmouseup = 	'ontouchend="getJSform(\''.$this->Name.'\').resize_mouseUp(event, \'%dir%\');" '.
										'onmouseup="getJSform(\''.$this->Name.'\').resize_mouseUp(event, \'%dir%\');"';
				}				
				$html.=	'<a class="'.$class.'_resize" style="cursor: nw-resize; left: -8px; top: -8px;" '.str_replace('%dir%', 'nw', $str_onmousedown).' '.str_replace('%dir%', 'nw', $str_onmousemove).' '.str_replace('%dir%', 'nw', $str_onmouseup).'></a>'.
						'<a class="'.$class.'_resize" style="cursor: n-resize; left: 50%; top: -8px; margin-left: -8px;" '.str_replace('%dir%', 'n', $str_onmousedown).' '.str_replace('%dir%', 'n', $str_onmousemove).' '.str_replace('%dir%', 'n', $str_onmouseup).'></a>'.
						'<a class="'.$class.'_resize" style="cursor: ne-resize; right: -8px; top: -8px;" '.str_replace('%dir%', 'ne', $str_onmousedown).' '.str_replace('%dir%', 'ne', $str_onmousemove).' '.str_replace('%dir%', 'ne', $str_onmouseup).'></a>'.
						'<a class="'.$class.'_resize" style="cursor: e-resize; right: -8px; top: 50%; margin-top: -8px;" '.str_replace('%dir%', 'e', $str_onmousedown).' '.str_replace('%dir%', 'e', $str_onmousemove).' '.str_replace('%dir%', 'e', $str_onmouseup).'></a>'.
						'<a class="'.$class.'_resize" style="cursor: se-resize; right: -8px; bottom: -8px;" '.str_replace('%dir%', 'se', $str_onmousedown).' '.str_replace('%dir%', 'se', $str_onmousemove).' '.str_replace('%dir%', 'se', $str_onmouseup).'></a>'.
						'<a class="'.$class.'_resize" style="cursor: s-resize; left: 50%; bottom: -8px; margin-left: -8px;" '.str_replace('%dir%', 's', $str_onmousedown).' '.str_replace('%dir%', 's', $str_onmousemove).' '.str_replace('%dir%', 's', $str_onmouseup).'></a>'.
						'<a class="'.$class.'_resize" style="cursor: sw-resize; left: -8px; bottom: -8px;" '.str_replace('%dir%', 'sw', $str_onmousedown).' '.str_replace('%dir%', 'sw', $str_onmousemove).' '.str_replace('%dir%', 'sw', $str_onmouseup).'></a>'.
						'<a class="'.$class.'_resize" style="cursor: w-resize; left: -8px; top: 50%; margin-top: -8px;" '.str_replace('%dir%', 'w', $str_onmousedown).' '.str_replace('%dir%', 'w', $str_onmousemove).' '.str_replace('%dir%', 'w', $str_onmouseup).'></a>';
				break;
			default:
				$html = $s;
				break;
		}
		
		return	'<form id="'.$this->Name.'" action="" method=""> '.
				'	<div id="'.$this->Name.'_wrapper" class="'.$class.'" style="'.$style.'"> '.
				$html.
				'	</div> '.
				//'	<script type="text/javascript">addFormID("'.$this->Name.'");</script> '.
				'</form> ';
	}
	
	function generateJS()
	{
		$s = $this->innerJS();
		$s = str_replace('%parent%', $this->Name, $s);
		
		/*
		$js =	'function '.$this->ClassName.'()'.
				'{ '.
				$s.
				'} '.
				'var '.$this->Name.' = new '.$this->ClassName.'();';
		*/
		$js =	$s.
				'getJSform("'.$this->Name.'").captionClick = function() {this.focus();};'."\n".
				'getJSform("'.$this->Name.'").callBack = callBack;';
				//'$("'.$this->Name.'").callBack = callBack;';
		
		return $js;
	}

	function show()
	{
		//TQuark::instance()->addAjaxStack('', 'alert', $this->Name);
		//$html = $this->generateHTML();
		//$js = $this->generateJS();

		//if (!$this->Visible)
		{
			//  append html
			
			switch (TQuark::instance()->IsCallBack)
			{
				case false:
					echo '<script type="text/javascript">addFormID("'.$this->Name.'");</script> ';
					break;
				case true:
					TQuark::instance()->addAjaxStack('', 'script', 'addFormID("'.$this->Name.'");');
					break;
			}
			
			$this->Visible = true;
		}
		//else
		{
		  //  update instead ???
		}
		
	}
	
	function showModal()
	{
		switch (TQuark::instance()->IsCallBack)
		{
			case false:
				echo '<script type="text/javascript">addFormID("'.$this->Name.'");</script> ';
				break;
			case true:
				TQuark::instance()->addAjaxStack('', 'script', 'addFormID("'.$this->Name.'");');
				break;
		}
		
		$this->Visible = true;
		$this->IsModal = true;
		
		/*
		$class = '';
		if ($this->Theme != '') $class = $this->Theme.'_TForm_modal';
		
		$s = '<div id="'.$this->Name.'_modal" class="'.$class.'" style="display: block; position: absolute; left: 0px; top: 0px; width: 100%; height: 100%"></div>';//.$this->generateOutput();
		$html = $this->generateHTML();
		$js = $this->generateJS();
		
		$this->IsModal = true;
		
		//global $IsCallBack;
		switch (TQuark::instance()->IsCallBack)
		{
			case false:
				echo $s;
				echo $html;
				echo '<script type="text/javascript"> '.$js.'</script>';
				
				break;
			case true:
				//addAjaxStack('', 'alert', strlen($js));				
				TQuark::instance()->addAjaxStack($target, 'append', $s.$html);
				TQuark::instance()->addAjaxStack('', 'script', 'addFormID("'.$this->Name.'");');
				//addAjaxStack('', 'script', $js);
				break;
		}
		*/
	}

	function renderHTML()
	{
		//if (!$this->Visible) return;
		
		$html = $this->generateHTML();
		$target = TQuark::instance()->currentViewport;
		
		//addAjaxStack('', 'alert', strlen($js));
		switch ($this->IsModal)
		{
			case false:
				TQuark::instance()->addAjaxStack($target, 'append', $html);
				break;
			case true:
				$class = '';
				if ($this->Theme != '') $class = $this->Theme.'_TForm_modal';
				$s_modal = '<div id="'.$this->Name.'_modal" class="'.$class.'" style="display: block; position: fixed; left: 0px; top: 0px; width: 100%; height: 100%"></div>';//.$this->generateOutput();
				TQuark::instance()->addAjaxStack('', 'append', $s_modal);
				TQuark::instance()->addAjaxStack('', 'append', $html);
				break;
		}
		
		//TQuark::instance()->addAjaxStack('', 'script', 'addFormID("'.$this->Name.'");');
		//addAjaxStack('', 'script', $js);
	}
	
	function loadJS()
	{
		//if (!$this->Visible) return;
		
		$js = $this->generateJS();
		//addAjaxStack('', 'alert', $js);
		TQuark::instance()->addAjaxStack('', 'script', $js);
	}
	
	function render()
	{
		$this->renderHTML();
		$this->loadJS();	
	}
	
	function generateThumbHTML()
	{
		$html = '<span>'.$this->Caption.'</span>';
		return $html;
	}
	
	function generateThumbJS()
	{
		
	}
	
	function hide()
	{
		//TQuark::instance()->removeForm($this->Name);
		TQuark::instance()->addAjaxStack($this->Name, 'delete', '');
		if ($this->IsModal)
			TQuark::instance()->addAjaxStack($this->Name.'_modal', 'delete', '');
		TQuark::instance()->addAjaxStack('', 'script', 'removeFormID("'.$this->Name.'")');
		
		$this->Visible = false;
	}
	
	function release()
	{
		foreach ($this->Controls as $ctrl)
		{
			if ($ctrl instanceof TWidget)
				$ctrl->release();
		}
	}
	
	function close()
	{
		$this->release();
		
		TQuark::instance()->removeForm($this->Name);
		TQuark::instance()->addAjaxStack($this->Name, 'delete', '');
		if ($this->IsModal)
			TQuark::instance()->addAjaxStack($this->Name.'_modal', 'delete', '');
		TQuark::instance()->addAjaxStack('', 'script', 'removeFormID("'.$this->Name.'")');
	}
	
	function minimize()
	{
		
	}
	
	function maximize()
	{
		
	}
	
	function restore()
	{
		
	}
	
	function getContext()
	{
		return TQuark::instance()->getContext($this->contextID);
	}
	
}

registerWidget('TForm', 'TForm');

?>
