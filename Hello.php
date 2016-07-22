<?php
/**
 * -----------------------------------------------------
 * Hello Class
 * This is a test php!
 *  
 * @package  	Hello
 * @subpackage	
 * @category	Hello
 * @author	Young
 * @since       2013-06-05 version 1.0.1
 * @v1.0.2      2016-07-22 修改注释
 * @link        http://blog.1988de.com
 * -----------------------------------------------------	
 */
 
class Hello 
{
  public $test;
  
  public function __construct($test){
    $this->test = $test;
    echo "Hello world,".$this->test;
  }
}
 
$this = new Hello('kaka987');

/* End of file Hello.php */ 
/* Location: ./Hello.php */
