<?php


require "../vendor/autoload.php";
use Goutte\Client;

$url_to_traverse = 'https://play.google.com/store/search?q=pub%3ANordcurrent';

$goutte = new Client();
$crawler = $goutte->request('GET', $url_to_traverse);

$status_code = $goutte->getResponse()->getStatus(); d($status_code);
$res = [];
if($status_code==200){

    foreach ( $crawler->filter('a[class="title"]')->extract(array('_text', 'href')) as $elem ){
      $res[]= [ $elem[0] => $elem[1] ];
      }
}


echo json_encode($res);
