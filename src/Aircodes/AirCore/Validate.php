<?php
namespace Aircodes\AirCore;

class Validate {

  const LEVEL_LOW  = 1;
  const LEVEL_MID  = 2;
  const LEVEL_HIGH = 4;
  const ANY        = 8;

  const REGEX_DOMAIN = '/^(?!\-)(?:[a-z\d\-]{0,62}[a-z\d]\.){1,126}(?!\d+)[a-z\d]{1,63}$/iD';
  const REGEX_EMAIL = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';


    /**
    * Validates string as domain
    * @access public
    *
    * @param string $domain   Domain to be validated
    * @param ?int   $level    Validation level LEVEL_LOW | LEVEL_MID | LEVEL_HIGH
    *
    * @return bool
    */
  public static function domain(string $domain, int $level = null) : bool {
    if($level === null) $level = self::LEVEL_LOW;
    // As domain ain't a url we won't check for https:// being correct
    $x = sizeof(str_split($domain));
    if($domain == 'localhost') return true;
    if($level & self::ANY) {
      if($domain == '*' || $domain == '*.*') return true;
    }
    if($level & self::LEVEL_LOW) {
      if($x < 4 || $x > 255 || false === strpos($domain, '.')) return false;
    }
    if($level & self::LEVEL_MID) {
      if(!preg_match('/^[a-z0-9\-\.]+\.[a-z\d]{1,63}$/iD', $domain)) return false;
    }
    if($level & self::LEVEL_HIGH) {
      if(!preg_match(self::REGEX_DOMAIN, $domain)) return false;
    }
    return true;
  }


    /**
    * Validates string as email
    * @access public
    *
    * @param string $email    Email to be validated
    * @param ?int   $level    Validation level LEVEL_LOW | LEVEL_MID | LEVEL_HIGH
    *
    * @return bool
    */

  public static function email(string $email, int $level = null) : bool {
    if($level === null) $level = self::LEVEL_LOW;
    if($level & self::LEVEL_LOW) {
      if(substr_count($email, '@') < 1 || strlen($email) < 6) return false;
    }
    if($level & self::LEVEL_MID) {
      if(!filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) return false;
    }
    if($level & self::LEVEL_HIGH) {
      if(!preg_match(self::REGEX_EMAIL, $email)) return false;
    }
    return true;
  }


    /**
    * Validates string as ip
    * @access public
    *
    * @param string $ip       IP to be validated
    * @param ?int   $level    Validation level LEVEL_LOW | LEVEL_MID | LEVEL_HIGH
    *
    * @return bool
    */
  public static function ip(string $ip, int $level = null) : bool {
    return self::ipv4($ip, $level) || self::ipv6($ip, $level);
  }


    /**
    * Validates string as IPv4
    * @access public
    *
    * @param string $ip       IP to be validated
    * @param ?int   $level    Validation level LEVEL_LOW | LEVEL_MID | LEVEL_HIGH
    *
    * @return bool
    */
  public static function ipv4(string $ip, int $level = null) : bool {
    if($level & self::LEVEL_LOW) {
      if(substr_count($ip, '.') !== 3 ) return false;
    }
    if($level & self::LEVEL_MID) {
      if(!preg_match('/^(\d+\.){3}\d+$/')) return false;
    }
    if($level & self::LEVEL_HIGH) {
      if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    }
    return true;
  }


    /**
    * Validates string as IPv6
    * @access public
    *
    * @param string $ip       IP to be validated
    * @param ?int   $level    Validation level LEVEL_LOW | LEVEL_MID | LEVEL_HIGH
    *
    * @return bool
    */
  public static function ipv6(string $ip, int $level = null) : bool {
    if($level & self::LEVEL_LOW) {
      if(substr_count($ip, ':') < 2) return false;
    }
    if($level & self::LEVEL_MID) {
      if(!preg_match('/^([0-9a-f]{4}:)+[0-9a-f]{4}$/')) return false;
    }
    if($level & self::LEVEL_HIGH) {
      if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return false;
    }
    return true;
  }


    /**
    * Validates integer as port
    * @access public
    *
    * @param int  $port      Port to be validated
    * @ignore ?int $level    Validation level LEVEL_LOW | LEVEL_MID | LEVEL_HIGH
    *
    * @return bool
    */
  public static function port(int $port, int $level = null) : bool {
    return ($port > 0 and $port < 65536);
  }

}
