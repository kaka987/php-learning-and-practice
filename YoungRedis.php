<?php
/**
 *  Young Redis Class
 *
 *  PHP redis load balance drived class
 *
 *  By Young<young@1988de.com> @151204 v1.0.2
 *  1.1 change the lb method type
 *  1.2 add or remove server features
 *
 */
class YoungRedis {


    // redis obj pool
    public static $handler=array();

    // redis connect
    public static $connected = NULL;

    // real server node
    public $node = '';

    // virtual server shards
    public $virtualShards = 24;

    //---------------------------------------------------------------------------------------

    public function __construct($options=array(), $key='') {

        $this->_init($options,$key);
    }

    // init class
    private function _init($options=array(), $key='') {

        if ( !extension_loaded('redis') ) {
            echo "No redis extension!";
            exit(1);
        }

        $options = array (
            'host'    => isset($options['host']) ? $options['host'] : array('127.0.0.1:6379'),
            'shards'  => isset($options['shards']) ? $options['shards'] : array(),
            'timeout' => isset($options['timeout']) ? $options['timeout'] : false,
            'persistent' => isset($options['persistent']) ? $options['persistent'] : false,
            'expire'  => isset($options['expire']) ? $options['expire'] : 86400
        );
        $this->options =  $options;
    }

    // connect redis
    public function connect($key='') {

        $this->getRedisObj($key);
        $func = $this->options['persistent'] ? 'pconnect' : 'connect';
        
        $hostArr = explode(':', $this->node);
        $host = isset($hostArr[0]) ? $hostArr[0] : '127.0.0.1';
        $port = isset($hostArr[1]) ? $hostArr[1] : '6379';

        if (!isset(static::$connected[$this->node])) {
            static::$connected[$this->node] = $this->options['timeout'] === false ?
                static::$handler[$this->node]->$func($host, $port) :
                static::$handler[$this->node]->$func($host, $port, $this->options['timeout']);
        }
    }

    // get redis obj
    public function getRedisObj($key='') {

        $this->node = $this->getRedisNode($key);

        if (isset(static::$handler[$this->node]) AND static::$handler[$this->node]!==NULL) 
            return static::$handler[$this->node];
        else 
            return static::$handler[$this->node] = new Redis;
    }

    // get redis node
    public function getRedisNode($key='') {

        if(empty($key)) {
            echo "Key is empty, Error!";
            exit;
        }

        $shards = $this->options['shards'];
        $host   = $this->options['host'];
        $s = md5($key);
        $intKey = ord($s[0])*100+ord($s[15])*10+ord($s[31]); //need to change 
        $shardIndex = $intKey % $this->virtualShards;
        
        return isset($host[$shards[$shardIndex]]) ? $host[$shards[$shardIndex]] : array();
    }

    // get value
    public function get($name) {

        $node = $this->getRedisNode($name);
        return static::$handler[$node]->get($name);
    }

    // set key
    public function set($name, $value, $expire = null) {

        $node = $this->getRedisNode($name);
        if(is_array($value)) $value = serialize($value);
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        if(is_int($expire)) {
            $result = static::$handler[$node]->setex($name, $expire, $value);
        }else{
            $result = static::$handler[$node]->set($name, $value);
        }
        return $result;
    }

    // del key
    public function rm($name) {
        $node = $this->getRedisNode($name);
        return static::$handler[$node]->delete($name);
    }

    // close resource
    public function __destruct(){

        foreach (static::$handler as $key => $value) {
           echo "close : ",$key,PHP_EOL;
           static::$handler[$key]->close();
        }
    }
}



// test
$option = array(
    'host'   => array('s1'=>'192.168.1.11:6379', 's2'=>'192.168.1.12:6379'),
    'shards' => array(
             0 => 's1',  1 => 's1',  2 => 's1',  3 => 's1',  4 => 's1',  5 => 's1',  6 => 's1',  7 => 's1',
             8 => 's1',  9 => 's1', 10 => 's1', 11 => 's1', 12 => 's1', 13 => 's2', 14 => 's2', 15 => 's2',  
            16 => 's2', 17 => 's2', 18 => 's2', 19 => 's2', 20 => 's2', 21 => 's2', 22 => 's2', 23 => 's2'
        )
);
$redis = new YoungRedis($option);
for($i=1;$i<=10000;$i++) {

    $v = md5($i);
    $key = $v;
    $value = str_repeat($v,rand(5,10));
    //var_dump($value);

    $redis->connect($key);
    $redis->set($key, $value, 600);

    $r = $redis->get($key);
    if(empty($r)) echo "Empty:",$key,PHP_EOL;

    //$redis->rm($key);
}

/*register_shutdown_function(function () {
    global $redis;
    var_dump($redis)
    unset($redis);
});*/
