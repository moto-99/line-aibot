<?php

//Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lineBasicFuncions.php';
require_once __DIR__ . '/dbConnection.php';
require_once __DIR__ . '/userdb.php';
require_once __DIR__ . '/oumu.php';

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
  // ユーザーの情報がデータベースに存在しない時
  if(getStateByUserId($event->getUserId()) === PDO::PARAM_NULL) {
    error_log('debag:first user');
    $state = array('talkMode' => 'normal');
    // ユーザーをデータベースに登録
    registerUser($event->getUserId(), json_encode($state));
    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
      $bot->replyText($event->getReplyToken(), '初めまして。今の会話は通常モードです。今は[通常,オウム]モードがあります。');
      continue;
    }
  }else{
    error_log('debag:user' . $event->getText());
    //指定の言葉でトークモードの変更
    if(strpos($event->getText(),'変え') !== False){
      error_log('debag:mode change');
      if(strpos($event->getText(),'通常') !== False){
        error_log('debag:user change normal');
        updateUser($event->getUserId(), 'normal');
        $bot->replyText($event->getReplyToken(), '[通常]モードに変更しました。');
        continue;//ブレイクがまずいかも
      }
      if(strpos($event->getText(),'オウム') !== False){
        error_log('debag:user change oumu');
        updateUser($event->getUserId(), 'oumu');
        $bot->replyText($event->getReplyToken(), '[オウム]モードに変更しました。');
        continue;//ブレイクがまずいかも
      }
    }
  }
  $state = getStateByUserId($event->getUserId());
  //オウム返し
  if(strpos($state->{'talkMode'},'oumu') !== False){
    error_log('debag:oumu mode run');
    oumu($event,$bot);
    continue;
  }



}


?>
