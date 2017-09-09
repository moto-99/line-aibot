<?php

//Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';
require_once('lineBasicFuncions.php');
//POSTメソッドの取得
//$inputString = file_get_contents('php://input');
//error_log($inputString);

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv(
  'CHANNEL_ACCESS_TOKEN'
  )) ;
$bot = new \LINE\LINEBot($httpClient,['channelSecret' => getenv(
  'CHANNEL_SECRET'
  )]);
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
$events = $bot->parseEventRequest(file_get_contents('php://input'),$signature);

foreach ($events as $event) {
  # code...
  //$bot->replyText($event->getReplyToken(),'TextMessage');
  replyTextMessage($bot,$event->getReplyToken(),'TextMessage');
}




?>
