<?php
// I have no clue why should I define them and where should I use them
// Sorry
defined('HTTP_COOKIE_PARSE_RAW') or define('HTTP_COOKIE_PARSE_RAW', 1 << 0);
defined('HTTP_COOKIE_SECURE')    or define('HTTP_COOKIE_SECURE',    1 << 1);
defined('HTTP_COOKIE_HTTPONLY')  or define('HTTP_COOKIE_HTTPONLY',  1 << 2);

if(!function_exists('http_parse_cookie')) {
  function http_parse_cookie(string $cookie, ?int $flag = 0, array $extras = []) {
    $system = [
      'expires',
      'max-age',
      'domain',
      'path',
      'secure',
      'httponly',
      'samesite'
    ];
    $output = [
      'cookies' => [],
      'extras'  => []
    ];
    $extras = array_map('mb_strtolower', $extras);
    $parts = array_filter(explode(';', $cookie));
    for($i = 0; $i < sizeof($parts); $i++) {
      $part     = $parts[$i];
      $unpacked = explode('=', $part);
      $key      = trim(array_shift($unpacked));
      $lower    = mb_strtolower($key);
      if(!sizeof($unpacked)) {
        $value = true;
      } else {
        $value = ltrim(join('=', $unpacked));
        if(!empty($value) && $value[0] == '"') {
          // If value is a quoted string
          $tmp = str_replace('\\"', '', $value);
          $i++;
          while($i < sizeof($parts) && substr_count($tmp, '"') < 2) {
            $value .= ';' . $parts[$i];
            $tmp   .= ';' . str_replace('\\"', '', $parts[$i]);
          }
          $tmp = '';
          $value = trim($value, '"');
        } else {
          $value = rtrim($value);
        }
      }
      if($lower == 'expires') {
        // Wed, 21 Oct 2015 07:28:00 GMT
        if(!preg_match('/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun), (([1-9]|[12][0-9]|3[01]) (Jan|Mar|May|Jul|Aug|Oct|Dec)|([1-9]|[12][0-9]|30) (Apr|Jun|Sep|Nov)|([1-9]|1[0-9]|2[0-8]) Feb) \d{4,8} \d{2}:\d{2}:\d{2} GMT$/u', $value)) {
          return FALSE;
        }
        $output[$lower] = strtotime($value);
      } elseif($lower == 'max-age') {
        if(!is_numeric($lower) || $lower === 0) {
          return FALSE;
        }
        $output[$lower] = (int) $value;
      } elseif($lower == 'domain') {
        if(!preg_match('/^([a-z0-9\-а-яА-ЯёЁ]{1,63}\.)+[a-z0-9\-а-яА-ЯёЁ]{1,63}$/iu', $value)) {
          return FALSE;
        }
        $output[$lower] = $value;
      } elseif(in_array($lower, ['path','httponly','secure'])) {
        $output[$lower] = $value;
      } elseif($lower == 'samesite') {
        if($value !== 'Strict' && $value !== 'Lax') {
          return FALSE;
        }
        $output[$lower] = $value;
      } elseif(in_array($lower, $extras)) {
        $output['extras'][$key] = $value;
      } else {
        if(~$flag & HTTP_COOKIE_PARSE_RAW) $value = urldecode($value);
        $output['cookies'][$key] = $value;
      }
    }
    foreach($output['cookies'] as $key=>$value) {
      if(strpos($key, '__Secure-') !== FALSE) {
        if(!isset($output['secure']) || !$output['secure']) {
          return FALSE;
        }
      }
      if(strpos($key, '__Host-') !== FALSE) {
        if(!isset($output['secure']) || !$output['secure']) {
          return FALSE;
        }
        if(isset($output['domain']) || $output['path'] !== '/') {
          return FALSE;
        }
      }
    }
    return (object) $output;
  }
}
if(!function_exists('http_build_cookie')) {
  function http_build_cookie($cookie) {
    $cookie = (array) $cookie;
    $output = '';
    if(!isset($cookie['cookies'])) {
      return FALSE;
    }
    foreach($cookie['cookies'] as $key=>$value) {
      if(!empty($value) && preg_match('/[\(\)<>@,;:\\"\/\[\]\?=\{\}]/', $value)) {
        $value = '"' . addslashes($value) . '"';
      }
      $output .= $key . '=' . $value . '; ';
    }
    if(isset($cookies['extras']) && is_array($cookies['extras'])) {
      foreach($cookie['extras'] as $key=>$value) {
        if(!empty($value) && preg_match('/[\(\)<>@,;:\\"\/\[\]\?=\{\}]+/', $value)) {
          $value = '"' . addslashes($value) . '"';
        }
        $output .= $key . '=' . $value . '; ';
      }
    }
    if(isset($cookie['path']))     $output .= 'Path='.     $cookie['path'] . '; ';
    if(isset($cookie['domain']))   $output .= 'Domain='.   $cookie['domain'] . '; ';
    if(isset($cookie['expires']))  $output .= 'Expires='.  date('D, d M Y H:i:s', $cookie['expires']) . ' GMT; ';
    if(isset($cookie['max-age']))  $output .= 'Max-Age='.  $cookie['max-age'] . '; ';
    if(isset($cookie['samesite'])) $output .= 'SameSite='. $cookie['samesite'] . '; ';
    if(isset($cookie['secure']))   $output .= 'Secure; ';
    if(isset($cookie['httponly'])) $output .= 'HttpOnly; ';
    return trim($output);
  }
}
