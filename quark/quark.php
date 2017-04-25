<?php

	header('Content-Type: text/html; charset=utf-8');
	use phpbrowscap\Browscap;
	
	//-------------------------------------------------------------------------------------------------------	
	//  determine working directory
	$qPath = __DIR__;
	$s = dirname($_SERVER['SCRIPT_FILENAME']);
	if ($s != '\\' || $s != '/') $s .= DIRECTORY_SEPARATOR;
	if (strpos($qPath, $s) >= 0) $qPath = substr($qPath, strlen($s));
	define('qPath', $qPath);
	
	//-------------------------------------------------------------------------------------------------------
	//  Set Exception handling
	//  note: check if qOS already loaded its own exception handling routine	
	error_reporting(0);
	register_shutdown_function('quark_shutdown');
	function quark_shutdown()
	{
		if (!is_null($e = error_get_last()))
		{
			switch ($e['type'])
			{
				case E_ERROR:
				case E_COMPILE_ERROR:
					TQuark::instance()->addAjaxStack('', 'alert', print_r($e, true));
					TQuark::instance()->sendAjaxResponse();
					break;
			}
		}
	}
	
	//-------------------------------------------------------------------------------------------------------
	//  load base classes, qcl and widgets	
	require_once 'qbase/PropertyClass.php';
	require_once 'qbase/Component.php';
	require_once 'qbase/Widget.php';
	require_once 'qbase/ThemeBroker.php';
	
	//  Quark Component Library
	$str_dir_qcl = dirname(__FILE__).DIRECTORY_SEPARATOR.'qcl';
	if (file_exists($str_dir_qcl))
	{
		$dir_qcl = new DirectoryIterator($str_dir_qcl);
		foreach ($dir_qcl as $file_qcl)
			if (!$file_qcl->isDot())
			{
				require_once $str_dir_qcl.DIRECTORY_SEPARATOR.$file_qcl->getFilename();
			}
	}
	
	//  Quark Widgets
	$str_dir_widgets = dirname(__FILE__).DIRECTORY_SEPARATOR.'widgets';
	if (file_exists($str_dir_qcl))
	{
		$dir_widgets = new DirectoryIterator($str_dir_widgets);
		foreach ($dir_widgets as $file_widget)
			if (!$file_widget->isDot() && $file_widget->getExtension() == 'php')
			{
				require_once $str_dir_widgets.DIRECTORY_SEPARATOR.$file_widget->getFilename();						
			}
	}

	//-------------------------------------------------------------------------------------------------------
	//  declare the TQuark class
	class TQuark extends TPropertyClass
	{
		static private $Finstance = null;
		
		static function instance()
		{
			if (self::$Finstance != null) return self::$Finstance;
			
			self::$Finstance = new TQuark();
			return self::$Finstance;
		}
		
		var $debugJS = false;
		var $debugForm = null;
		var $IsCallBack = false;		
		var $browser_info = null;
		var $clientScreen = null;
		var $themes = Array();
		var $desktop = null;
		var $viewports = Array();
		var $currentViewport = '';
		protected $forms = Array();		
		protected $idxForm = 0;
		protected $AjaxStack = Array();
		protected $handlers = Array();		
		protected $timers = Array();
		protected $files = Array();
		
		//  Context Manager vars
		private $FcontextManager = null;
		private $FcontextMain = null;
		private $FcontextCallBack = null;
		
		function __construct()
		{
			if (session_id() == '') session_start();

			$debugJS = false;
			if (isset($_REQUEST['callBack']))
				if ($_REQUEST['callBack'] == 'true') $this->IsCallBack = true;
				
			//$this->addTheme('qDebug', 'themes/qDebug/qDebug.css');			
		}
		
		protected function get_contextManager()
		{
			return $this->FcontextManager;
		}
		
		protected function get_contextMain()
		{
			return $this->FcontextMain;
		}
		
		protected function get_contextCallBack()
		{
			return $this->FcontextCallBack;
		}

		protected function sendDebugFlag()
		{
			if ($this->debugJS) 
			{
				if (!$this->IsCallBack) echo '<script type="text/javascript">debugJS = true;</script>';
				if (!$this->IsCallBack) echo '<link rel="stylesheet" type="text/css" href="themes/qDebug/qDebug.css">';
				
				$w = 320; $h = 400;
				$this->debugForm = new TDebugForm($this->clientScreen->Width - $w - 8, 32, $w, $h, 'qDebug');
				if (!$this->IsCallBack) $this->debugForm->show();
			}
		}
		
		protected function checkCache()
		{
			if (!is_dir('cache')) mkdir('cache');
		}
		
		protected function checkBrowser()
		{
			$browser = new Browscap('browscap_cache');
			$browser->localFile = 'browscap_ini/php_browscap.ini';
			$browser->doAutoUpdate = false;
			$this->browser_info = $browser->getBrowser();			
		}
		
		function addTheme($ThemeName, $ThemeURL)
		{
			if (count($this->themes) == 0) $this->themes['default'] = new TThemeBroker('default', '');
			$this->themes[$ThemeName] = new TThemeBroker($ThemeName, $ThemeURL);
		}
		
		protected function sendTheme($ThemeName)
		{
			if (!isset($this->themes[$ThemeName])) return false;
			
			echo '<style id="theme_'.$ThemeName.'" type="text/css">'."\n";
			echo $this->themes[$ThemeName]->Style;
			echo '</style>';				
		}

		protected function initDesktop($SettingsFile = null)
		{
			echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" />';
			echo '<meta name="viewport" content="width-device-width, initial-scale=1.0" />';
			
			//  obtain the default css for widgets
			if (count($this->themes) == 0) $this->themes['default'] = new TThemeBroker('default', '');
			foreach ($this->themes as $key => $value) $this->sendTheme($key);
			
			$s = qPath.DIRECTORY_SEPARATOR.'quark.js';
			$v = md5_file($s);
			echo '<script type="text/javascript" src="'.qPath.'/js/q.js"></script>';
			echo '<script type="text/javascript" src="'.qPath.'/js/base64.js"></script>';
			echo '<script type="text/javascript" src="'.qPath.'/js/json2.js"></script>';
			echo '<script type="text/javascript" src="'.qPath.'/js/upclick.js"></script>';
			echo '<script type="text/javascript" src="'.qPath.'/quark.js?v='.$v.'"></script>';
			echo '<script type="text/javascript" src="'.qPath.'/dojo/dojo.js"></script>';
			//echo '<link rel="stylesheet" type="text/css" href="dijit/themes/claro/claro.css">';
						
			if (!isset($SettingsFile)) return;
		
			$codefile = dirname($SettingsFile).DIRECTORY_SEPARATOR.basename($SettingsFile, '.xml');
			$codefile .= '.php';
		
			if (!file_exists($codefile)) return null;
		
			require_once $codefile;
		
			$s = file_get_contents($SettingsFile);
			$xml = simplexml_load_string($s);
		
			$type = $xml->getName();
			$dsk = new $type();
			if (!($dsk instanceof TDesktop)) return;
			$dsk->loadProperties($s);
			$dsk->ClassName = $type;
			$dsk->CodeFile = $codefile;
			$this->setFormVars($dsk);
			$dsk->OnLoad();
			
			$this->desktop = $dsk;
			//$this->forms[$dsk->Name] = $dsk;

			$this->desktop->show();			
		}
	
		protected function setFormVars(TForm $frm, TWidget $parent = null)
		{
			if (!($frm instanceof TForm)) return;
			if ($parent == null) $parent = $frm;
			if (!($parent instanceof TWidget)) return;
			
			foreach($parent->Controls as $ctrl)
			{
				eval('$frm->'.$ctrl->Name.' = $ctrl;');
				$this->setFormVars($frm, $ctrl);
			}
		}
	
		function loadForm($url, $contextID = 0)
		{
			if (!file_exists($url)) return null;
		
			$codefile = dirname($url).DIRECTORY_SEPARATOR.basename($url, '.xml');
			$codefile .= '.php';
		
			if (!file_exists($codefile)) return null;
		
			require_once $codefile;
		
			$s = file_get_contents($url);
			$xml = simplexml_load_string($s);
		
			$form = null;
			try
			{
				$type = $xml->getName();
				$form = new $type();
				$form->contextID = $contextID;
				$form->loadProperties($s);
				$form->ClassName = $type;
				$form->CodeFile = $codefile;
				$this->setFormVars($form);
				$form->OnLoad();
			}
			catch (Exception $e)
			{
				$this->addAjaxStack('', 'alert', 'An error occured while loading form.');
			}
		
			//global $forms;
			$this->forms[$form->Name] = $form;
		
			return $form;
		}

		function removeForm($form_name)
		{
			if ($this->forms[$form_name] != null) unset($this->forms[$form_name]);
		}	
		
		function firstForm()
		{
			$forms = array_values($this->forms);
			if (count($forms) == 0) return null;
			$frm = $forms[0];			
			if (!isset($frm)) return null;
			
			$this->idxForm = 0;
			return $forms[0];
		}
		
		function countForms()
		{
			return count($this->forms);
		}
		
		function getForm($index)
		{
			if (count($this->forms) == 0) return null;
			
			if (is_numeric($index))
			{
				if ($index < 0 || $index >= count($this->forms)) return null;			
				$forms = array_values($this->forms);
				return $forms[index];
			}
			else if (isset($this->forms[$index])) return $this->forms[$index];
			
			return null;
		}
		
		function nextForm()
		{
			if (count($this->forms) == 0) return null;
			if ($this->idxForm >= count($this->forms) - 1) return null;
			
			$this->idxForm++;
			$forms = array_values($this->forms);
			return $forms[$this->idxForm];
		}
		
		function prevForm()
		{
			
		}
		
		function lastForm()
		{
			
		}
		
		
		
		protected function serializeForms()
		{
			//global $forms;
			$this->checkCache();
			$sid = session_id();
		
			$s =	'<cache>'."\n";
			
			if ($this->clientScreen != null)
			{
				$s .=	'	<clientScreen>'."\n".
						'		<Orientation>'.$this->clientScreen->Orientation.'</Orientation>'."\n".
						'		<Width>'.$this->clientScreen->Width.'</Width>'."\n".
						'		<Height>'.$this->clientScreen->Height.'</Height>'."\n".
						'		<AvailWidth>'.$this->clientScreen->AvailWidth.'</AvailWidth>'."\n".
						'		<AvailHeight>'.$this->clientScreen->AvailHeight.'</AvailHeight>'."\n";
				if ($this->clientScreen->TouchCapable)
					$s .= 	'		<TouchCapable>true</TouchCapable>'."\n";
				else
					$s .= 	'		<TouchCapable>false</TouchCapable>'."\n";
				$s .=	'	</clientScreen>'."\n";
			}	
					
			$dsk_codefile = '';
			$dsk_properties = '';
			if ($this->desktop != null)
				if ($this->desktop instanceof TDesktop)
				{
					$dsk_codefile = $this->desktop->CodeFile;
					$dsk_properties = base64_encode(serialize($this->desktop));
				}
				
			$s .=	'	<desktop>'."\n";
			$s .=	'		<codefile>'.$dsk_codefile.'</codefile>'."\n";
			$s .=	'		<properties>'.$dsk_properties.'</properties>'."\n";
			$s .=	'		<currentViewport>'.$this->currentViewport.'</currentViewport>'."\n";
			$s .=	'	</desktop>'."\n";
		
			$s .=	'	<forms>'."\n";
			foreach($this->forms as $frm)
			{
				$s .=	'		<'.$frm->Name.'>'."\n".
						'			<codefile>'.$frm->CodeFile.'</codefile>'."\n".
						'			<properties>'.base64_encode(serialize($frm)).'</properties>'."\n".
						'		</'.$frm->Name.'>'."\n";
			}
			$s .=	'	</forms>'."\n";
			
			$s .=	'	<handlers>'.base64_encode(serialize($this->handlers)).'</handlers>'."\n";
			
			$s .=	'	<files>'.serialize($this->files).'</files>'."\n";
			
			$s .=	'	<timers>'."\n";
			foreach ($this->timers as $timer)
			{
				$s .=	'	<timer>'."\n".
						'		<form>'.$timer['form'].'</form>'."\n".
						'		<method>'.$timer['method'].'</method>'."\n".
						'		<interval>'.$timer['interval'].'</interval>'."\n".
						'	</timer>'."\n";
			}
			$s .=	'	</timers>'."\n";
			$s .=	'</cache>'."\n";

			//  to do
			//  error handling when there can't be written the information
			$res_count = file_put_contents('cache'.DIRECTORY_SEPARATOR.$sid, $s);
		}
	
		protected function createFromCache()
		{
			//global $forms;
		
			$this->checkCache();
			$sid = session_id();
		
			if (!file_exists('cache'.DIRECTORY_SEPARATOR.$sid)) return false;
		
			$xml = simplexml_load_file('cache'.DIRECTORY_SEPARATOR.$sid);
			
			foreach ($xml->children() as $xml_node)
			{
				switch ($xml_node->getName())
				{
					case 'clientScreen':
						$this->clientScreen->Orientation = (string)$xml_node->Orientation;
						$this->clientScreen->Width = (int)$xml_node->Width;
						$this->clientScreen->Height = (int)$xml_node->Height;
						$this->clientScreen->AvailWidth = (int)$xml_node->AvailWidth;
						$this->clientScreen->AvailHeight = (int)$xml_node->AvailHeight;
						$this->clientScreen->TouchCapable = false;
						if ((string)$xml_node->TouchCapable == 'true') $this->clientScreen->TouchCapable = true;
						break;
					case 'desktop':
						$this->currentViewport = (string)$xml_node->currentViewport;
						$dsk_codefile = (string)$xml_node->codefile;
						$dsk_properties = (string)$xml_node->properties;
						
						if ($dsk_codefile != '')
						{
							require_once $dsk_codefile;
							$dsk = unserialize(base64_decode($dsk_properties));
							if ($dsk != null)
							{
								$this->desktop = $dsk;
							}
						}
						
						break;
					case 'forms':
						foreach($xml_node->children() as $xml_frm)
						{							
							$codefile = $xml_frm->codefile;
							$properties = $xml_frm->properties;
						
							require_once $codefile;
							$frm = unserialize(base64_decode($properties));
							if ($frm != null)
							{
								$this->forms[$frm->Name] = $frm;
							}
						}
						break;
					case 'handlers':
						$b64_handlers = (string)$xml_node;
						$this->handlers = unserialize(base64_decode($b64_handlers));
						break;
					case 'files':
						$this->files = unserialize((string)$xml_node);
						break;
					case 'timers':
						foreach ($xml_node->children() as $xml_timer)
						{
							$formname = (string)$xml_timer->form;
							$method = (string)$xml_timer->method;
							$interval = (int)$xml_timer->interval;
							
							$frm = $this->forms[$formname];
							if ($frm != null)
								$this->registerTimer($frm, $method, $interval);
							
							/*$timer = Array
							(
								'form' => $formname,
								'method' => $method,
								'interval' => $interval
							);
							$this->timers[] = $timer;*/
						}
						break;
				}
			}		
		
			return true;
		}
	
		var $callStack = '';
		function traceCallStack($object = null, $method = null)
		{
			$this->callStack = '';
			
			$dbg = debug_backtrace();
			
			for ($i = count($dbg) - 1; $i > 0; $i--)
			{
				$item = $dbg[$i];
				
				$file = $item['file'];
				$class = $item['class'];
				$line = $item['line'];
				$function = $item['function'];
				$type = $item['type'];
				
				$this->callStack .= '<span class="debugBlock">'.$class.$type.$function.'</span><br/>';
			}
			
			if (isset($object) && isset($method))
			{
				if ($object == null) $this->callStack .= $method;
				else if (is_object($object)) $this->callStack .= '<span class="debugBlock">'.get_class($object).'->'.$method.'</span><br/>'; 
			}
		}
		
		//-------------------------------------------------------------------------------------------------------
		//  Ajax Stack functions
		function addAjaxStack($target, $action, $content)
		{
			//global $AjaxStack;
		
			$a = Array();
			$a['target'] = $target;
			$a['action'] = $action;
			$a['content'] = base64_encode($content);
		
			$this->AjaxStack[] = $a;
		}
		
		//  specific ajax stack functions
		function browserAlert($message)
		{
			$this->addAjaxStack('', 'alert', $message);
		}
		
		function browserAppend($htmlID, $content)
		{
			$this->addAjaxStack($htmlID, 'append', $content);
		}
		
		function browserDelete($htmlID)
		{
			$this->addAjaxStack($htmlID, 'delete', '');
		}
		
		function browserUpdate($htmlID, $content)
		{
			$this->addAjaxStack($htmlID, 'update', $content);
		}
		
		function browserReplace($htmlID, $content)
		{
			$this->addAjaxStack($htmlID, 'replace', $content);
		}
		
		function browserAddClass($htmlID, $className)
		{
			$this->addAjaxStack($htmlID, 'addClass', $className);
		}
		
		function browserRemoveClass($htmlID, $className)
		{
			$this->addAjaxStack($htmlID, 'removeClass', $className);
		}
		
		function browserSetStyle($htmlID, $content)
		{
			$this->addAjaxStack($htmlID, 'setStyle', $content);
		}
		
		function browserScript($script)
		{
			$this->addAjaxStack('', 'script', $script);
		}
		
		function browserDownload($filename, $content)
		{
			file_put_contents('cache/'.$filename, $content);
			$this->addAjaxStack('', 'download', qPath.'/download.php?filename='.$filename);
		}
		
		protected function sendAjaxResponse()
		{
			echo json_encode($this->AjaxStack);
		}
		
		function MessageDlg($Text, $Caption, $Buttons, $CallbackObject, $CallbackMethod)
		{
			try
			{
				$codefile = dirname(__FILE__).DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'MessageDlg.php';
				if (file_exists($codefile))
				{
					//  create the handle
					$CallbackHandle = $this->registerHandler($CallbackObject, $CallbackMethod);
					
					$msgdlg = new TMessageDialog($Text, $Caption, $Buttons, $CallbackHandle, 'sale');
					$msgdlg->contextID = 0;
					$msgdlg->ClassName = 'TMessageDlg';
					$msgdlg->CodeFile = $codefile;
					$msgdlg->OnLoad();
					
					if ($msgdlg != null) 
					{
						$this->forms[$form->Name] = $msgdlg;
						$msgdlg->showModal();
					}
				}
			}
			catch (Exception $e)
			{
				$this->addAjaxStack('', 'alert', 'An error occured while loading message dialog.');
			}			
		}
				
		//-------------------------------------------------------------------------------------------------------
		//  Handlers management
		function registerHandler(TComponent &$obj, $method_name)
		{
			if (!isset($obj)) return '';
			if (!($obj instanceof TComponent)) return '';			
			if (!method_exists($obj, $method_name)) return '';
			
			//  create handle id
			$handleid = '$handle_'.count($this->handlers);
			
			$this->handlers[$handleid] = Array();
			$this->handlers[$handleid]['form'] = $obj->getParentForm()->Name;
			$this->handlers[$handleid]['object_ref'] = $obj->id;			
			$this->handlers[$handleid]['method_name'] = $method_name;			
			$this->handlers[$handleid]['handle'] = $handleid;			
			
			return $handleid;
		}
		
		function callHandler($handle, $sender, $varName, $varValue)
		{
			if (!isset($this->handlers[$handle])) return false;
			
			$frm_name = $this->handlers[$handle]['form'];
			$frm = $this->getForm($frm_name);
			if ($frm != null)
			{
				$object = $frm->getControlbyID($this->handlers[$handle]['object_ref']);
				$method_name = $this->handlers[$handle]['method_name'];
				
				if (!method_exists($object, $method_name)) return false;
				
				//  call the event
				if ($this->debugJS) $this->traceCallStack($frm, $event);
				if ($sender !== null && $varName !== null && $varValue !== null) $object->$method_name($sender, $varName, $varValue);
				else if ($varName !== null && $varValue !== null) $object->$method_name($varName, $varValue);
				else if ($sender !== null) $object->$method_name($sender);
				else $object->$method_name();				
			}			
		}
		
		protected function handleFileUpload()
		{
			if (!isset($_FILES['Filedata'])) return false;
			
			$tmp_file_name = $_FILES['Filedata']['tmp_name'];
			$filename = basename($tmp_file_name);
			$ok = move_uploaded_file($tmp_file_name, 'cache/'.$filename);
			
			if ($ok)
			{
				$name = $_FILES['Filedata']['name'];
				$type = $_FILES['Filedata']['type'];
				$tmp_name = 'cache/'.$filename;
				$size = $_FILES['Filedata']['size'];
				$this->files[] = array('name' => $name, 'type' => $type, 'tmp_name' => $tmp_name, 'size' => $size);
			}
			
			// This message will be passed to 'oncomplete' function
			echo $ok ? "OK" : "FAIL";
			
			return true;
		}
		
		function retrieveUploadedFile()
		{
			if (count($this->files) > 0) 
			{
				$file = $this->files[0];
				unset($this->files[0]);
				$this->files = array_values($this->files);
				
				return $file;
			}
		}
		
		protected function handleFormEvent($event, $frm_name, $sender, $varName, $varValue)
		{
			//  retrieve the form object
			$frm = null;
			if ($frm_name == "frm_DebugForm") $frm = $this->debugForm;
			if ($frm_name == $this->desktop->Name) $frm = $this->desktop;
			if (isset($this->forms[$frm_name])) $frm = $this->forms[$frm_name];
			if ($frm == null) return;
			
			//  update form fields
			foreach($_REQUEST as $key => $value)
			{
				if ($key != 'callBack' && $key != 'form' && $key != 'event' &&
				$key != 'sender' && $key != 'varName' && $key != 'varValue')
				{
					if (isset($frm->$key) && method_exists($frm->$key, 'setValue')) $frm->$key->setValue($value);
				}
			}
			
			$object = null;
			$method_name = '';
				
			//  check handlers
			if (isset($this->handlers[$event]) && $this->handlers[$event]['form'] == $frm_name)
			{
				$object = $frm->getControlbyID($this->handlers[$event]['object_ref']);
				$method_name = $this->handlers[$event]['method_name'];
			}
			else 
			{
				$object = $frm;
				$method_name = $event;
			}
			
			if (!method_exists($object, $method_name)) return;

			//  call the event
			if ($this->debugJS) $this->traceCallStack($frm, $event);
			if ($sender !== null && $varName !== null && $varValue !== null) $object->$method_name($sender, $varName, $varValue);
			else if ($varName !== null && $varValue !== null) $object->$method_name($varName, $varValue);
			else if ($sender !== null) $object->$method_name($sender);
			else $object->$method_name();
		}
		
		function run($contextManager = null, $contextMain = null, $contextCallBack = null, $desktopFile = null)
		{
			$this->FcontextManager = $contextManager;
			$this->FcontextMain = $contextMain;
			$this->FcontextCallBack = $contextCallBack;
			
			switch ($this->createFromCache())
			{
				case false:
					require_once 'qbase/browscap.php';
					$this->checkBrowser();
					
					$this->initDesktop($desktopFile);
					$this->sendDebugFlag();

					if (isset($contextManager) && isset($contextMain))
					{
						if (is_object($contextManager) && method_exists($contextManager, $contextMain)) $contextManager->$contextMain();
					}
					else if (isset($contextMain))
					{
						if (function_exists($contextMain)) $contextMain();						
					}
					break;
				case true:
					switch ($this->IsCallBack)
					{
						case false:
							if ($this->handleFileUpload()) break;

							$this->initDesktop($desktopFile);
							$this->sendDebugFlag();
								
							foreach($this->forms as $frm)
							{
								if (!$frm->Visible) continue;
								
								switch ($frm->IsModal)
								{
									case false:
										echo $frm->show();
										break;
									case true:
										echo $frm->showModal();
										break;
								}
							}
								
							break;
						case true:
							$this->sendDebugFlag();
								
							//  retrieve callback arguments
							$frm_name = $_REQUEST['form'];
							$event = $_REQUEST['event'];
							
							$sender = null; 
							if (isset($_REQUEST['sender'])) 
							{
								$sender = $_REQUEST['sender'];
								$a = split('\.', $sender);
								if(count($a) >= 2)
								{
									//$parent = $a[0];
									$sender = $a[1];		
								}									
							}
							
							$varName = null; if (isset($_REQUEST['varName'])) $varName = $_REQUEST['varName'];
							$varValue = null; if (isset($_REQUEST['varValue'])) $varValue = $_REQUEST['varValue'];
								
							switch ($event)
							{
								case 'systemTimer':
									$this->systemTimer();
									break;
								case 'setClientScreen':
									$post_body = file_get_contents('php://input');
									$obj = json_decode($post_body);
									$this->clientScreen = $obj;
									break;
								default:									
									if ($frm_name == '') break;
									$this->handleFormEvent($event, $frm_name, $sender, $varName, $varValue);									
									if ($this->debugJS) $this->addAjaxStack('', 'debugStack', $this->callStack);									
									break;
							}
								
							$this->sendAjaxResponse();
								
							break;
					}
						
					break;
			}
			
			$this->serializeForms();
				
		}
		
		function getContext($contextID)
		{
			$obj = $this->contextManager;
			$proc = $this->contextCallBack;
			
			if (isset($obj) && isset($proc))
			{
				if (is_object($obj) && method_exists($obj, $proc))
				{
					return $obj->$proc($contextID);
				}
			}
			else if (isset($proc))
			{
				if (function_exists($proc)) return $proc(contextID);
			}
			
			return null;
		}
		
		//-------------------------------------------------------------------------------------------------------
		//  Timers management  
		protected function systemTimer()
		{
			//  until timer registrations will be possible, this will explicitly call refreshing on the desktop
			
			if ($this->desktop != null)
				if ($this->desktop instanceof TDesktop)
					$this->desktop->refreshThumbs();
				
			foreach ($this->timers as $timer)
			{
				try
				{
					$formname = $timer['form'];
					$method = $timer['method'];
					
					$frm = $this->forms[$formname];
					if ($frm != null)
						$frm->$method();
				}
				catch (Exception $exc)
				{
					
				}
			}
		}
		
		function registerTimer($form, $method, $interval)
		{
			$timer = Array
			(
				'form' => $form->Name,
				'method' => $method,
				'interval' => $interval
			);
			
			$this->timers[] = $timer;
		}
		
	}
	

?>
