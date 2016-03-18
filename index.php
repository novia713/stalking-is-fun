<?php

// only for developing, disable when prod
error_reporting(-1);
ini_set('display_errors', 'On');

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
$climate = new League\CLImate\CLImate;
$client = new GuzzleHttp\Client();


// for console only ==
$out = function ($type, $txt) use ($climate){
  if ("info" == $type )
    $climate->backgroundBlue()->out('Usage: php folowers_gh.php username');

};

// aux lambda for doing the url call with Guzzle ==
$do_req = function ($url) use ($client){
  $req = $client->request( 'GET', $url );

  if ($req->getBody()) {
    header("Access-Control-Allow-Origin: *"); //CORS
    echo $req->getBody();
  }

};
//==
#

$query_string = explode("/", $_SERVER['QUERY_STRING']);
$verb = @$query_string[1];
$noun = @$query_string[2];

if (!$noun) {
  http_response_code(400);
  echo json_encode( [ "error" => "400", "response" => "No username found"]);
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

  }

  return $url;
};


// 3, 2, 1 ...
$do_req($get_url());
