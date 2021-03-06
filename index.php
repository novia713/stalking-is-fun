<?php
  require "vendor/autoload.php";

  /**
   *
   *  The MIT License (MIT)
   *  Copyright (c) 20160317 leandro713 <leandro@leandro.org>
   *
   *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
   *
   *  The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
   *
   *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
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
  $parser  = new League\Uri\UriParser();


  // handle routing context as functor;

  class RouteContext
  {
    public $context = [];
      public function __construct($v)
      {
          $this->context = $v;
      }
  }


  /**
   *
   *  ===================================================
   *  ================ START CLOSURES  ==================
   *  ===================================================
   *
   *
   */

  $guard_params = function ($params) {
    if (array_filter($params, function ($p) { return empty($p); })) {
        http_response_code(400);
        echo json_encode([ "status-code" => "400", "response" => "No username found"]);
        die();
    } else {
        return $params;
    }
  };

  $routing_management = function () use ($_SERVER, $parser) {
    $uri = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $uri_chunks = explode("/", $parser->parse($uri)["path"]);

    //nginx
    if (strpos($_SERVER["SERVER_SOFTWARE"], "nginx") !== false) {
        $_['verb'] = @$uri_chunks[1];
        $_['noun'] = @$uri_chunks[2];
    } else { //apache
      $_['verb'] = @$uri_chunks[2];
        $_['noun'] = @$uri_chunks[3];
    }

    return $_;

  };

  $spit = function ($value) {

    header("Access-Control-Allow-Origin: *"); //CORS
    header('Content-Type', 'application/json;charset=utf-8');

    echo $value;
  };


  $req_with_goutte = function ($url) use ($goutte) {

    $crawler = $goutte->request('GET', $url);
    $status_code = $goutte->getResponse()->getStatus();

    if ($status_code==200) {
        $res = [];
        foreach ($crawler->filter('a[class="title"]')->extract(array('_text', 'href')) as $elem) {
            $res[]= [ trim($elem[0]) => trim($elem[1]) ];
        }
    }
    if (empty($res)) {
        $res = json_encode(['status-code' => 404, "response" => "No user found"]);
    }
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
        $spit(json_encode($req_with_goutte($url)));
    } else {
        try {
            $req = @$guzzle->request('GET', $url);

            if ($req->getBody()) {
                $spit($req->getBody());
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            echo json_encode(["status-code" => $e->getResponse()->getStatusCode(), "response" => "No user found"]);
        }
    }

  };


  $set_mode_req = function () use (&$RC) {
    return ($RC->context['verb'] != "android-market")? 0: 1;
  };


  $get_url = function () use ($GH_SECRET_KEY, $GH_ID_KEY, $spit, &$RC) {

    $url = false;

    switch ($RC->context['verb']) {

     /**
      * http://mysite.com/followers_gh/trufae
      *
      * gets followers in Github
      *
      */
     case "followers_gh":
      $url = 'https://api.github.com/users/' . $RC->context['noun'] . '/followers?client_id='.$GH_ID_KEY.'&client_secret='.$GH_SECRET_KEY;
      break;
     /**
      * http://mysite.com/search_gh/rickycode
      *
      * searchs users in Github
      *
      */
     case "search_gh":
      $url = 'https://api.github.com/search/users?client_id='.$GH_ID_KEY.'&client_secret='.$GH_SECRET_KEY.'&q='  . $RC->context['noun'] ;
      break;

     /**
      * http://mysite.com/user/fabpot
      *
      * basic info about a user in Github
      *
      */
    case "user":
      $url = 'https://api.github.com/users/'. $RC->context['noun']  .'?client_id='.$GH_ID_KEY.'&client_secret='.$GH_SECRET_KEY;
      break;

     /**
      * http://mysite.com/android-market/Nordcurrent
      *
      * apps of the user in Android Market
      *
      */
    case "android-market":
      $url = "https://play.google.com/store/search?q=pub:". $RC->context['noun'] ;
      break;

    default:
      break;

    }


    // no url found!
    if (!$url) {
        $spit(json_encode(['status-code' => 404, "response" => "No url found"])); //FIXME:
      return false;
    }



    return $url;
  };

  $handle_errors_with_whoops = function () use ($whoops) {

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
  $RC = new RouteContext($guard_params ($routing_management()));
  $do_req($get_url(), $set_mode_req());
