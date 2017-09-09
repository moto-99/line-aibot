<?php

//Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lineBasicFuncions.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/userdb.php';

/* 最初のおまじない */
// アクセストークンを使いCurlHTTPClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
// CurlHTTPClientとシークレットを使いLINEBotをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
// LINE Messaging APIがリクエストに付与した署名を取得
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
// 署名が正当かチェック。正当であればリクエストをパースし配列へ
// 不正であれば例外の内容を出力
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log('parseEventRequest failed. InvalidSignatureException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log('parseEventRequest failed. UnknownEventTypeException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log('parseEventRequest failed. UnknownMessageTypeException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log('parseEventRequest failed. InvalidEventRequestException => '.var_export($e, true));
}


/*  ユーザへのアクション */
// 配列に格納された各イベントをループで処理
foreach ($events as $event) {
  $state = getStateByUserId($event->getUserId());
  // ユーザーの情報がデータベースに存在しない時
  if($state === PDO::PARAM_NULL) {
    $state = array('talkMode' => 'normal');
    // ユーザーをデータベースに登録
    registerUser($event->getUserId(), json_encode($state));
    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
      $bot->replyText($event->getReplyToken(), '初めまして。今の会話は通常モードです。今は[オウム]モードがあります。');
      continue;
    }
  }else{
    //指定の言葉でトークモードの変更
    if(strpos($event->getText(),'変え')){
      if(strpos($event->getText(),'オウム')){
        updateUser($event->getUserId(), 'oumu');
        $bot->replyText($event->getReplyToken(), '[オウム]モードに変更しました。');
        break;//ブレイクがまずいかも
      }
    }
  }
  //オウム返し
  if(strpos($state['talkMode'],'oumu')){
    oumu($event,$bot);
    continue;
  }


}


?>
