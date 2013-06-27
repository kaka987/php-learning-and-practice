<?php
/**
 * -----------------------------------------------------
 * Hello Class
 *
 * @package  	  Hello
 * @subpackage	
 * @category	  Hello
 * @author		  AndyZhang 
 * @since       2013-06-05 version 1.0       
 * @link		    http://www.16nn.com
 * -----------------------------------------------------	
 */
 
 class Hello {
  public $test;
  
  public function __construct($test){
    $this->test = $test;
    echo "Hello world,".$this->test;
  }
 }
 
 $this = new Hello('kaka987');

/* End of file helloworld.php */ 
/* Location: ./helloworld.php */
