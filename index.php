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
      $bot->replyText($event->getReplyToken(), '初めまして。今の会話は通常モードです。今は[通常,オウム,コーヒーメモ]モードがあります。');
      continue;
    }
  }else{
    error_log('debag:user' . $event->getText());
    //指定の言葉でトークモードの変更
    if(strpos($event->getText(),'変え') !== False){
      error_log('debag:mode change');
      if(strpos($event->getText(),'通常') !== False){
        error_log('debag:user change normal');
        updateUser($event->getUserId(), json_encode(array('talkMode' => 'normal')));
        $bot->replyText($event->getReplyToken(), '[通常]モードに変更しました。');
        continue;
      }
      //オウムモードに変更
      if(strpos($event->getText(),'オウム') !== False){
        error_log('debag:user change oumu');
        updateUser($event->getUserId(), json_encode(array('talkMode' => 'oumu')));
        $bot->replyText($event->getReplyToken(), '[オウム]モードに変更しました。');
        continue;
      }
      //コーヒーメモモードに変更
      if(strpos($event->getText(),'コーヒーメモ') !== False){
        if(strpos($event->getText(),'検索') !== False){
          error_log('debag:user change coffee_memo_search');
          updateUser($event->getUserId(), json_encode(array('talkMode' => 'coffee_memo_search')));
          $bot->replyText($event->getReplyToken(), '[コーヒーメモ - 検索]モードに変更しました。');
          continue;
        } else {
          error_log('debag:user change coffee_memo_register');
          updateUser($event->getUserId(), json_encode(array('talkMode' => 'coffee_memo_register')));
          $bot->replyText($event->getReplyToken(), '[コーヒーメモ - 登録]モードに変更しました。');
          continue;
        }
      }
    }
    //python lib check 隠しコマンド
    if(strpos($event->getText(),'python') !== False){
      error_log('debag:user change python lib check');
      exec('python ' . __DIR__ . '/pythonLib_checker.py', $outpara);//python 呼び出し
      error_log( $outpara[0]);//print した順に入っている。今回はprint一個。
      $bot->replyText($event->getReplyToken(), 'pythonライブラリをログに表示します。字数制限で送信はできません。');
      continue;
    }
  }

  /* stateによって機能の切り替え*/
  $state = getStateByUserId($event->getUserId());
  // 通常モード
  if(strpos($state->{'talkMode'},'normal') !== False){
    if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
      $bot->replyText($event->getReplyToken(), '今の会話は通常モードです。今は[通常,オウム,コーヒーメモ]モードがあります。');
      continue;
    }
  }

  //オウム返し
  if(strpos($state->{'talkMode'},'oumu') !== False){
    error_log('debag:oumu mode run');
    oumu($event,$bot);
    continue;
  }

  //コーヒーメモモード
  if(strpos($state->{'talkMode'},'coffee_memo_register') !== False){
    error_log('debag:coffee_memo mode run');
    coffeeMemoRegister($event,$bot);
    continue;
  }
  //コーヒーメモモード
  if(strpos($state->{'talkMode'},'coffee_memo_search') !== False){
    error_log('debag:coffee_memo mode run');
    coffeeMemoSearch($event,$bot);
    continue;
  }

}


?>
