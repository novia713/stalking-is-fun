<?php
  require "vendor/autoload.php";
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

  $GH_SECRET_KEY = "4f3c2c4368c3e7dc7588d93efd5d66ea2ad90585";
  $GH_ID_KEY     = "a182edbb05e1757dadd9";


  $guzzle  = new GuzzleHttp\Client();
  $goutte  = new Goutte\Client();
  $whoops  = new \Whoops\Run;

  // for routing
  $query_string = null;
  $verb = null;
  $noun = null;



  /**
   *
   *  ===================================================
   *  ================ START CLOSURES  ==================
   *  ===================================================
   *
   *
   */

  $routing_management = function () use( &$query_string, &$verb, &$noun ) {

    $query_string = explode("/", $_SERVER['QUERY_STRING']);
    $verb = @$query_string[1];
    $noun = @$query_string[2];

    if (!$noun) {
        http_response_code(400);
        echo json_encode([ "status-code" => "400", "response" => "No username found"]);
        die();
    }
  };

  $spit = function($value) {
    header('Content-Type', 'application/json');
    header("Access-Control-Allow-Origin: *"); //CORS
    echo $value;
  };


  $req_with_goutte = function($url) use ($goutte){

    $crawler = $goutte->request('GET', $url);
    $status_code = $goutte->getResponse()->getStatus();

    if ($status_code==200) {
      $res = [];
      foreach ($crawler->filter('a[class="title"]')->extract(array('_text', 'href')) as $elem) {
        $res[]= [ trim($elem[0]) => trim($elem[1]) ];
      }
    }
    if (empty($res)) $res = json_encode( ['status-code' => 404, "response" => "No user found"] );
    return $res;
  };

  // aux lambda for doing the url call with Guzzle ==
  /**
   * mode: 0 gh, 1 android-market
   *
   *
   */
  $do_req = function ($url, $mode=0) use ($spit, $guzzle, $req_with_goutte) {

    if ($mode == 1) {

        //@TODO: $spit
        echo json_encode( $req_with_goutte($url) );

    } else {

        try {
            $req = @$guzzle->request('GET', $url);
            if ($req->getBody()) {
                $spit( $req->getBody() );
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            echo json_encode(["status-code" => $e->getResponse()->getStatusCode(), "response" => "No user found"]);
        }
    }

  };


  $set_mode_req = function() use ( &$verb ) {
    return ($verb != "android-market")? 0: 1;
  };


  $get_url = function () use (&$verb, &$noun, $GH_SECRET_KEY, $GH_ID_KEY ) {

    switch ($verb) {

     /**
      * http://mysite.com/followers_gh/trufae
      *
      * gets followers in Github
      *
      */
     case "followers_gh":
      $url = 'https://api.github.com/users/' . $noun . '/followers?client_id='.$GH_ID_KEY.'&client_secret='.$GH_SECRET_KEY;
      break;
     /**
      * http://mysite.com/search_gh/rickycode
      *
      * searchs users in Github
      *
      */
     case "search_gh":
      $url = 'https://api.github.com/search/users?client_id='.$GH_ID_KEY.'&client_secret='.$GH_SECRET_KEY.'&q='  . $noun;
      break;

     /**
      * http://mysite.com/user/fabpot
      *
      * basic info about a user in Github
      *
      */
    case "user":
      $url = 'https://api.github.com/users/'.$noun.'?client_id='.$GH_ID_KEY.'&client_secret='.$GH_SECRET_KEY;
      break;

     /**
      * http://mysite.com/android-market/Nordcurrent
      *
      * apps of the user in Android Market
      *
      */
    case "android-market":
      $url = "https://play.google.com/store/search?q=pub:". $noun;
      break;

    }

    return $url;
  };

  $handle_errors_with_whoops = function() use ($whoops){

    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
  };

  /**
   *
   *  ===================================================
   *  ================= END CLOSURES  ===================
   *  ===================================================
   *
   *
   */

  //-----------------------------------------------------//

  /**
   *
   *  ===================================================
   *  ========= MAIN PROGRAM STARTS HERE ================
   *  ===================================================
   *
   *
   */

  $handle_errors_with_whoops();
  $routing_management();
  $do_req( $get_url(), $set_mode_req() );
