<?php

/**
 *
 *  (c) 20160317 leandro713 <leandro@leandro.org>
 *  these are my personal keys, please use your own keys
 *
 *  ===================================================
 *  ========= PUT YOUR OWN GITHUB KEYS HERE ===========
 *  ===================================================
 *
 */
 $secret = "4f3c2c4368c3e7dc7588d93efd5d66ea2ad90585";
 $id ="a182edbb05e1757dadd9";

require "vendor/autoload.php";
use Goutte\Client;

$climate = new League\CLImate\CLImate;
$guzzle  = new GuzzleHttp\Client();

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// for console only ==
$out = function ($type, $txt) use ($climate) {
  if ("info" == $type) {
      $climate->backgroundBlue()->out('Usage: php folowers_gh.php username');
  }

};

// aux lambda for doing the url call with Guzzle ==
/**
 * mode: 0 gh, 1 android-market
 *
 *
 */
$do_req = function ($url, $mode=0) use ($guzzle) {

  if ($mode == 1) { //@TODO: refactor this in lambda

    $goutte = new Client();
      $crawler = $goutte->request('GET', $url);

      $status_code = $goutte->getResponse()->getStatus();
      $res = [];
      if ($status_code==200) {
          foreach ($crawler->filter('a[class="title"]')->extract(array('_text', 'href')) as $elem) {
              $res[]= [ trim($elem[0]) => trim($elem[1]) ];
          }
      }

      echo json_encode($res);
  } else {
      try {
          $req = @$guzzle->request('GET', $url);
          if ($req->getBody()) {
              header("Access-Control-Allow-Origin: *"); //CORS //@TODO: decorator for enveloping headers & echo
        echo $req->getBody();
          }
      } catch (\GuzzleHttp\Exception\ClientException $e) {
          echo json_encode(["status-code" => $e->getResponse()->getStatusCode()]);
      }
  }



};


//==
#

$query_string = explode("/", $_SERVER['QUERY_STRING']);
$verb = @$query_string[1];
$noun = @$query_string[2];

if (!$noun) {
    http_response_code(400);
    echo json_encode([ "error" => "400", "response" => "No username found"]);
    die();
}

$get_url = function () use ($verb, $noun, $secret, $id) {

  switch ($verb) {

   /**
    * http://mysite.com/followers_gh/trufae
    *
    * gets followers in Github
    *
    */
   case "followers_gh":
    $url = 'https://api.github.com/users/' . $noun . '/followers?client_id='.$id.'&client_secret='.$secret;
    break;
   /**
    * http://mysite.com/search_gh/trufae
    *
    * searchs users in Github
    *
    */
   case "search_gh":
    $url = 'https://api.github.com/search/users?client_id='.$id.'&client_secret='.$secret.'&q='  . $noun;
    break;

   /**
    * http://mysite.com/user/trufae
    *
    * basic info about a user in Github
    *
    */
  case "user":
    $url = 'https://api.github.com/users/'.$noun.'?client_id='.$id.'&client_secret='.$secret;
    break;

   /**
    * http://mysite.com/android-market/Nordcurrent
    *
    * apps of the user in Android Market
    *
    */
  case "android-market":
    $url = "https://play.google.com/store/search?q=pub:".$noun;
    break;

  }

  return $url;
};


// 3, 2, 1 ...
if ($verb != "android-market") { //@TODO: refactor this in decorator pattern!
  $do_req($get_url(), 0);
} else {
    $do_req($get_url(), 1);
}
