<?php

/**
 * YPP Loger Class
 *
 * It just log somethings to a file.
 *
 * @author      zhangy<Young@yeahmobi.com>
 * @package     ym
 * @category    Libraries
 * @since       Version 1.0.1
 * @copyright   Copyright (c) 2014, Yeahmobi, Inc.
 */
class Ym_Logger
{

    /**
     * date famat
     */
    const DATEFAT = 'Y-m-d H:i:s';

    /**
     * log levels
     * @var array
     */
    protected static $levels = array('debug'=>1, 'info'=>2, 'notice'=>3, 'warning'=>4, 'error'=>5, 'fatal'=>6, 'alert'=>7, 'emergency'=>8);

    /**
     * the server hostname
     * @var string
     */
    protected static $hostname;

    /**
     * log file name
     * @var string
     */
    protected static $logFile;

    /**
     * log file path
     * @var string
     */
    protected static $logPath;

    /**
     * log handler 
     * the log type or method
     * 
     * @var string
     */
    protected static $handler;

    /**
     * log handler cache
     * 
     * @var source
     */
    protected static $handlerCache = NULL;

    protected static $isCacheHandler;

    //-----------------------------------------------------------------------

    /**
     * init the log object
     * 
     * @param  array  $logConfig 
     * @return object
     */
    public static function init($level='', array $logConfig = array()) {

        $fileLevel = 'info';
        if (static::$levels[$level] >= 5) $fileLevel = 'error';

        // need to add log config by $logConfig = Config::getItem('log');
        $logConfig = array('logPath'=>'./', 'logFile'=>'yeahmobi_'.$fileLevel, 'handler'=>'file', 'isCacheHandler'=>TRUE);

        // get and set the hostname
        static::getHostname();

        // configure log
        static::doConfigure($logConfig);
	}

    /**
     * the real place of logging
     * 
     * @param  string $msg     
     * @param  string $level   
     * @param  string $from    
     * @param  string $extends 
     * @return boolean
     */
    public static function log($msg='', $level='info', $from='', $extends='') {

        $time = static::getTime();
		$logLevel = static::getLevelName($level);
		if ( ! $logLevel) return FALSE; //throw error
        
        // Init the log service
        static::init($level);

		// get from Info START>>>
        if (empty($from)) {

    		$traces=debug_backtrace();
    		$count=0;
    		foreach($traces as $trace) {

    			if ($count==0) {
    				if (isset($trace['file'],$trace['line'])) {
    					$from = $trace['file'].':'.$trace['line'];
    				}
    			}

    			if ($count==1) {
    				if (isset($trace['class'],$trace['function'])) {
    					$from = ':'.$trace['class'].':'.$trace['function'];
    				}
    			}

    			if(++$count>=1)
    				break;
    		}
        }
		// END<<<

		$data = array(
				'time' 		=> $time,
				'logLevel' 	=> $logLevel,
				'from' 		=> $from,
				'extends' 	=> $extends,
				'msg'		=> $msg,
			);

		$message = static::dataFormat($data);
		if ($message === FALSE) return FALSE;

		// Start log to somewhere
		if (static::logToResource($message)) {
			return TRUE;
		}
		return FALSE;
	}

    /**
     * info log
     * 
     * @param  string $msg     
     * @param  string $extends 
     */
    public static function info($msg='', $extends='') {

        static::log($msg, __FUNCTION__, $from='', $extends);
    }

    /**
     * debug log
     * 
     * @param  string $msg     
     * @param  string $extends 
     */
    public static function debug($msg='', $extends='') {

        static::log($msg, __FUNCTION__, $from='', $extends);
    }

    /**
     * warning log
     * 
     * @param  string $msg     
     * @param  string $extends 
     */
    public static function warning($msg='', $extends='') {

        static::log($msg, __FUNCTION__, $from='', $extends);
    }

    /**
     * error log
     * 
     * @param  string $msg     
     * @param  string $extends 
     */
    public static function error($msg='', $extends='') {

        static::log($msg, __FUNCTION__, $from='', $extends);
    }

    /**
     * fatal log
     * 
     * @param  string $msg     
     * @param  string $extends 
     */
    public static function fatal($msg='', $extends='') {

        static::log($msg, __FUNCTION__, $from='', $extends);
    }

    /**
     * log to the resource
     * 
     * @param  string $message 
     * @return boolean
     */
	protected static function logToResource($message = '') {

        if (empty($message)) return FALSE;

		switch (static::$handler) {
			case 'file':
				$resource = static::getFile();
				static::logToFile($message, $resource);
				break;

            case 'socket':
                break;
			
			default:
				$resource = static::getFile();
				static::logToFile($message, $resource);
				break;
		}

		return TRUE;
	}

    /**
     * parse the config
     * 
     * @param  array  $logConfig
     */
	protected static function doConfigure(array $logConfig = array()) {

		if (empty($logConfig)) {
			return FALSE;
		}

		if (isset($logConfig['logFile']) AND isset($logConfig['logPath'])) {

			// set the default handler to file
			if ( ! isset($logConfig['handler'])) $logConfig['handler'] = 'file';
            if ( ! isset($logConfig['isCacheHandler'])) $logConfig['isCacheHandler'] = TRUE;
			foreach ($logConfig as $key=>$item) {
				static::$$key = $item;
			}
		}
	}

    /**
     * formate the log data
     * 
     * @param  array $data
     * @return string
     */
	protected static function dataFormat(array $data = array()) {
		//Need to transfer spacial string
        if (empty($data['extends'])) {
            unset($data['extends']);
        } else {
            if (is_array($data['extends'])) {
                $data['extends'] = implode(':', $data['extends']);
            }
        }

		foreach ($data as $key => $value) {
            $data[$key] = '['.str_replace(array('[',']'), array('\[','\]'), $value).']';
        }

		return implode($data)."\n";
	}

    /**
     * log to a file
     * 
     * @param  string $message
     * @param  mixed $file    
     * @return boolean
     */
	protected static function logToFile($message='', $file=NULL) {

        $fp = NULL;

        if (static::$isCacheHandler) { //If set to cache the handler
            if (static::$handlerCache === NULL) {
                if ( ! static::$handlerCache = @fopen($file, 'a+')) {
                    return FALSE; 
                }
            }
            $fp = static::$handlerCache;

        } else { // If don't cache the handler

            if ( ! $fp = @fopen($file, 'a+')) {
                    return FALSE; 
                }
        }

		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		
        if (static::$isCacheHandler === FALSE) {
            fclose($fp);
        }

		@chmod($file, 0666);
		return TRUE;
	}

    /**
     * get the file resource
     * 
     * @return string
     */
	protected static function getFile() {

		if (static::$logFile AND static::$logPath) {

			return rtrim(static::$logPath,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.static::$logFile.'.log';
		}
		return FALSE;//throw error
	}

    /**
     * get the log level name 
     * 
     * @param  string $level
     * @return string
     */
	protected static function getLevelName($level) {

		return isset(static::$levels[$level]) ? $level : NULL;
	}

    /**
     * get the log time
     * 
     * @return string
     */
	protected static function getTime() {

		date_default_timezone_set('PRC');
		return date(static::DATEFAT, time());
	}

    /**
     * get the hostname
     * @return string
     */
	protected static function getHostname() {

		if (static::$hostname) return static::$hostname;
		return static::$hostname = isset($_SERVER['HOSTNAME']) ? $_SERVER['HOSTNAME'] : NULL;
	}
}
// END Ym_Loger class

/* End of file Loger.php */
/* Location: ./ypp/ym/Loger.php */

class Logger_test {
    
    public function __construct() {
        $msg = 'test log!';             // log message
        //$extends = array('key_extends','value_extends'); // optional
        //$extends = 'key:value';
        
        echo 'mem:'.memory_get_usage(),'<br/>';
        echo 'time:'.microtime(true),'<br/>';
        for($i=50000; $i>0; $i--)
            Ym_Logger::info($msg);
        echo 'mem:'.memory_get_usage(),'<br/>';
        echo 'time:'.microtime(true),'<br/>';
        echo 'Peak_mem:'.memory_get_peak_usage(),'<br/>';
        // Ym_Logger::debug($msg);
        // Ym_Logger::warning($msg);
        // Ym_Logger::error($msg);
        // Ym_Logger::fatal($msg);
         
        /*
        cache the handler
        mem:443648
        time:1396253955.7691
        mem:444496
        time:1396253968.5525
        Peak_mem:454504*/

        /*
        mem:443648
        time:1396253902.9877
        mem:444184
        time:1396253924.1252
        Peak_mem:454504
        */
    }
}
new Logger_test;
