<?php
namespace Aircodes\AirCore;

use Exception;
use RuntimeException;
use Aircodes\AirCore\Ratchet\App;
use Symfony\Component\HttpFoundation\Session\Storage\Handler;
use Ratchet\WebSocket\WsServer as WsWrapper;
use Ratchet\Http\HttpServer as HttpWrapper;
use Ratchet\Session\SessionProvider as WsSessionProvider;
use React\EventLoop\Factory as LoopFactory;

class Core {

  protected $_app,                       // Ratchet/App Instance
            $_loop,                      // ReactPHP EventLoop
            $_server_ip,                 // IP the server run on
            $_ws_server,                 // WebSocket\Server Instance
            $_http_server,               // Http\Server Instance
            $_server_port,               // Port the server run on
            $_SessionHandler,            // SessionHandler Instance

            $_domains        = [],       // List of domains being using
            $_ws_handlers    = [],       // WebSocket Handlers list
            $_http_handlers  = [],       // HTTP Handlers list
            $_current_domain = false;    // Default domain for ->on() function

  const VERSION = 'AirCore v1.0.1';  // Showable in headers core version
  const SESSION_NULL      = 1 << 0;  // Flag: Sessions are null (default)
  const SESSION_MEMCACHED = 1 << 1;  // Flag: Sessions stores in memcached
  const IP_DEFAULT        = 1 << 2;  // Flag: Parse IP from REMOTE_ADDR (mixable, default)
  const IP_FORWARDED      = 1 << 3;  // Flag: Parse IP from X-Forwarded-For (mixable)
  const IP_CLOUDFLARE     = 1 << 4;  // Flag: Parse IP from Cloudflare (mixable)

  public function __construct(string $ip, int $port, int $flags = null) {
    // Setting the default $flags value to Not handle session, Parse IP from REMOTE_ADDR
    if($flags == null)
      $flags = self::SESSION_NULL | self::IP_DEFAULT;
    $conf = Settings::getInstance();

    echo "Trying to create server at $ip:$port\n";

    // Checking for IP and Port be valid
    if(!Validate::ip($ip))     throw new RuntimeException("Invalid ip $ip\n");
    if(!Validate::port($port)) throw new RuntimeException("Invalid port $port\n");

    // Remember IP and Port the server run on
    $this->_server_ip   = $ip;
    $this->_server_port = $port;

    // Creating Ratchet App to work with
    $this->_loop = LoopFactory::create();
    $this->_app = new App('localhost', $port, $ip, $this->_loop);

    // Preparing the headers from which should be parsed connections IP.
    // Sort in descending priority
    $ipHeaders = [];
    // header using by cloudflare.com
    if($flags & self::IP_CLOUDFLARE)
      $ipHeaders[] = 'CF-Connecting-IP';
    // use if proxy enabled
    if($flags & self::IP_FORWARDED)
      $ipHeaders[] = 'X-Forwarded-For';
    if(!sizeof($ipHeaders) || ($flags & self::IP_DEFAULT))
      // Default. Could be not used if only other flags are set.
      $ipHeaders[] = 'REMOTE_ADDR';

    // Set IP, Port, ipHeaders in public settings registry
    $conf->set('ip', $ip)
         ->set('port', $port)
         ->set('ipHeaders', $ipHeaders);

    // Set the session handler and clear default domain which is using in ->on() methods
    $this->enableSessions($flags)->clearWith();
  }

  /**
  *  Setting handler for HTTP Server
  *
  *  @param string $path - klein\klein URL path string
  *  @param callable $handler - the function which will be called when path matches
  *  @param string $domain - the domain on which the url works (optional)
  *
  *  @return Core instance $this
  */
  public function on(string $path, callable $handlers, ?string $domain = null) : Core {
    return $this->addHandler('http', $path, $handlers, $domain);
  }


  /**
  *  Setting handler for WS\Http Server
  *
  *  @param string $type - 'http' or 'ws'
  *  @param string $path - klein\klein URL path string
  *  @param callable $handler - the function which will be called when path matches
  *  @param string $domain - the domain on which the url works (optional)
  *
  *  @return Core instance $this
  */
  public function addHandler(string $type, string $path, callable $handler, ?string $domain = null) : Core {
    if($domain == null) {
      if(!$this->_current_domain) {
        throw new RuntimeException("Invalid domain ($domain) sent by ravens");
      } else {
        $domain = $this->_current_domain;
      }
    } else {
      if(!Validate::domain($domain))
        throw new RuntimeException("Invalid domain ($domain) sent by ravens");
      $this->_domains[$domain] = true;
    }
    if($type == 'http') {
      $this->_http_handlers[$path] = (object) ['func'=>$handler,'path'=>$path,'domain'=>$domain];
    } elseif($type == 'ws') {
      $this->_ws_handlers[$path]   = (object) ['func'=>$handler,'path'=>$path,'domain'=>$domain];
    } else {
      throw new RuntimeException("Invalid handler type ($type)");
    }
    return $this;
  }


  /**
  * Set the default domain for further using in addHandler method
  *
  * @param string $domain - the domain to set as a default
  *
  * @return Core instance $this
  */
  public function with(string $domain) : Core {
    if(!Validate::domain($domain))
      throw new RuntimeException("Invalid domain ($domain) sent by ravens");
    $this->_current_domain = $domain;
    $this->_domains[$domain] = true;
    return $this;
  }


  /**
  *
  *
  * @param
  *
  * @return
  */
  public function clearWith() : Core {
    $this->_cuurent_domain = false;
    return $this;
  }

  public function enableSessions(int $option) : Core {

    if($option & self::SESSION_NULL) {

      ini_set('session.save_handler', 'files');
      ini_set('session.save_path', '');
      $this->_SessionHandler = new Handler\NullSessionHandler();
      Settings::getInstance()->set('sessionType', self::SESSION_NULL);

    } elseif($option & self::SESSION_MEMCACHED) {

      ini_set('session.save_handler', 'memcached');
      ini_set('session.save_path', 'localhost:11211');
      $md = new \Memcached;
      $md->addServer('localhost', 11211);
      $this->_SessionHandler = new Handler\MemcachedSessionHandler($md);
      Settings::getInstance()->set('sessionType', self::SESSION_NULL);

    }
    return $this;
  }

  public function run() : void {

    // Server instances
    $SocketServer = new WebSocket\Server($this->_loop);
    $HttpServer   = new Http\Server($this->_loop);

    // Wrapping sessions
    $SocketSessionServer = new WsSessionProvider($SocketServer, $this->_SessionHandler);
    $HttpSessionServer   = new Http\CookieSessionProvider($HttpServer, $this->_SessionHandler);

    // Wrapping WS as Http server to be apply apply it to router
    $WebSocketSessionServer     = new WsWrapper($SocketSessionServer);
    $HttpWebSocketSessionServer = new HttpWrapper($WebSocketSessionServer);

    // Saving variables to be able work with them in cause of...
    $this->_ws_server   = $HttpWebSocketSessionServer;
    $this->_http_server = $HttpSessionServer;

    // Applying routes to Ratchet/App
    foreach(array_keys($this->_domains) as $domain) {
      $this->_app->addAllowedAccess($domain, $this->_server_port);
    }
    foreach($this->_http_handlers as $handler) {
      $this->_app->route($handler->path, $this->_http_server, ['*'], $handler->domain);
      // $this->_http_server->on($handler->path, $handler->func);
    }
    foreach($this->_ws_handlers as $handler) {
      $this->_app->route($handler->path, $this->_ws_server, ['*'], $handler->domain);
      // $this->_ws_server->on($handler->path, $handler->func);
    }

    // Starting server
    echo "Server started at {$this->_server_ip}:{$this->_server_port}\n\n";
    $this->_app->run();
  }

}
