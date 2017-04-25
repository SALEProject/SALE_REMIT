<?php

	require_once 'registry.php';
	require_once 'application.php';
	require_once 'context.php';

	class TQuarkOS
	{
		static private $Finstance = null;
		
		static function instance()
		{
			if (self::$Finstance != null) return self::$Finstance;
				
			self::$Finstance = new TQuarkOS();
			return self::$Finstance;
		}
		
		private $loginName = '';
		private $registry = null;
		private $contexts = Array();
		
		function getValidContextID()
		{
			$contextID = 0;
			$found = false;

			do 
			{
				$found = true;
				$contextID++;
				foreach ($this->contexts as $context)
				{
					if ($context->contextID == $contextID) $found = false;
				}	
			} while (!$found);
			
			return $contextID;
		}
		
		function launchApplication($url)
		{
			$filename = $url;
			if (is_dir($url)) 
			{
				if ($url[strlen($url) - 1] != DIRECTORY_SEPARATOR) $filename .= DIRECTORY_SEPARATOR;
				$filename .= 'application.xml';
			}	
			
			$codefile = dirname($filename).DIRECTORY_SEPARATOR.basename($filename, '.xml');
			$codefile .= '.php';
			
			if (file_exists($filename) && file_exists($codefile))
			{
				require_once $codefile;

				$app = null;
				$context = null;
				try
				{
					$s = file_get_contents($filename);
					$xml = simplexml_load_string($s);

					$type = $xml->getName();
					$app = new $type();
					$app->contextID = $this->getValidContextID();
					$app->WorkingDirectory = dirname($filename);
					
					//  the application is initialized, add it to the contexts
					$context = new TContext();
					$context->contextID = $app->contextID;
					$context->application = $app;
					$context->loginName = $this->loginName;
					$context->WorkingDirectory = $app->WorkingDirectory;
					$this->contexts[] = $context; 
					
					$app->main();
				}
				catch (Exception $e)
				{
					$this->addAjaxStack('', 'alert', 'An error occured while loading form.');
				}
			}
		}

		function checkCache()
		{
			if (!is_dir('cache')) mkdir('cache');
		}
		
		function serializeContexts()
		{
			$this->checkCache();
			$sid = session_id();
		
			$s =	'<cache>'."\n";
		
			$s .=	'	<contexts>'."\n";
			foreach($this->contexts as $context)
			{
				$s .=	'		<context>'."\n".
						'			<contextID>'.$context->contextID.'</contextID>'."\n".
						'			<WorkingDirectory>'.$context->WorkingDirectory.'</WorkingDirectory>'."\n".
						'			<loginName>'.$context->loginName.'</loginName>'."\n".
						'			<application>'.base64_encode(serialize($context->application)).'</application>'."\n".
						'		</context>'."\n";
			}
			$s .=	'	</contexts>'."\n";
				
			$s .=	'</cache>'."\n";
		
			$res_count = file_put_contents('cache'.DIRECTORY_SEPARATOR.$sid.'_qos', $s);
		}
		
		function createFromCache()
		{
			$this->checkCache();
			$sid = session_id();
		
			if (!file_exists('cache'.DIRECTORY_SEPARATOR.$sid.'_qos')) return false;
		
			$xml = simplexml_load_file('cache'.DIRECTORY_SEPARATOR.$sid.'_qos');
				
			foreach ($xml->children() as $xml_node)
			{
				switch ($xml_node->getName())
				{
					case 'contexts':
						foreach($xml_node->children() as $xml_context)
						{
							$contextID = (int)$xml_context->contextID;
							$WorkingDirectory = (string)$xml_context->WorkingDirectory;
							$loginName = (string)$xml_context->loginName;
							$app = (string)$xml_context->application;
		
							$codefile = $WorkingDirectory.DIRECTORY_SEPARATOR.'application.php';
							require_once $codefile;
							$application = unserialize(base64_decode($app));
							if ($application != null)
							{
								$context = new TContext();
								$context->contextID = $contextID;
								$context->loginName = $loginName;
								$context->WorkingDirectory = $WorkingDirectory;
								$context->application = $application;
								
								$this->contexts[] = $context;
							}
						}
						break;
				}
			}
		
			return true;
		}
		
		function run()
		{			
			//return QOS_ERR_FAIL;
			session_start();						
			ob_start();
				
			if (file_exists('quark'.DIRECTORY_SEPARATOR.'quark.php'))
			{
				require_once 'quark/quark.php';
				
				TQuark::instance()->debugJS = false;
				TQuark::instance()->addTheme('sale', 'themes/sale');
			}
			
			$this->createFromCache();
			if ($this->registry == null) $this->registry = new TRegistry();
			if (file_exists('quark'.DIRECTORY_SEPARATOR.'quark.php'))
			{
				TQuark::instance()->run($this, 'main', 'getContext', 'desktops/sale.xml');			
			}			
						
			ob_end_flush();
			
			$this->serializeContexts();
			
			return QOS_ERR_OK;
		}
		
		function main()
		{
			$this->launchApplication('applications/remit');
			//if ($this->loginName == '') $this->launchApplication('applications/login');
		}
		
		function getContext($contextID)
		{
			$b = false;
			$i = -1;
			$context = null;
			while (!$b && $i < count($this->contexts) - 1)
			{
				$i++;
				$context = $this->contexts[$i];
				if ($context->contextID == $contextID) $b = true;	
			}
			
			if ($b) return $context; 
			return null;
		}
		
		function sendMessage($contextID, $msg)
		{
			if ($contextID == 0) 
			{
				//  broadcast
				foreach ($this->contexts as $context)
				{
					if ($context != null) $context->application->processMessage($msg);
				}
			}
			else 
			{			
				$context = $this->getContext($contextID);
				if ($context != null) $context->application->processMessage($msg);
			}
		}
		
	}

?>
