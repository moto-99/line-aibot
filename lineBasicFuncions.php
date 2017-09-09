<?
//テキストの返信
function replyTextMessage($bot,$replyToken,$text){
  $response = $bot->replyText($replyToken,
  new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
  //error
  if(!$response -> isSucceeded()){
    //エラー表示
    eroor_log('Failed! '. $response->getHTTPStatus) . ' ' . $response->getRawBody());
  }
}

?>
