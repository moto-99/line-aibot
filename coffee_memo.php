<?php
/**
 * コーヒーメモテーブルのデータベースの操作を行う。
 *
*/
//テーブル名の定義
define('TABLE_NAME_COFFEEMEMO','coffee_memo');

function coffeeMemoRegister($event,$bot){
  // MessageEventクラスのインスタンスでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    return;
  }
  // TextMessageクラスのインスタンスでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    return;
  }
  //登録
  registerMemo($event->getUserId(),$event->getText())
  $bot->replyText($event->getReplyToken(), $event->getText()+' を登録しました。');
}

function coffeeMemoSearch($event,$bot){
  // MessageEventクラスのインスタンスでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    return;
  }
  // TextMessageクラスのインスタンスでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    return;
  }
  //検索
  $bot->replyText($event->getReplyToken(), getMemoByUserId($event->getUserId()));
}


// メモをデータベースに登録する
function registerMemo($userId, $text) {
  $dbh = dbConnection::getConnection();
  $sql = 'insert into '. TABLE_NAME_COFFEEMEMO .' (userid, text) values (pgp_sym_encrypt(?, \'' . getenv('DB_ENCRYPT_PASS') . '\'), ?) ';
  $sth = $dbh->prepare($sql);
  $sth->execute(array($userId, $text));
}

// メモの情報をデータベースから削除
function deleteMemo($userId) {
  $dbh = dbConnection::getConnection();
  $sql = 'delete FROM ' . TABLE_NAME_COFFEEMEMO . ' where ? = pgp_sym_decrypt(userid, \'' . getenv('DB_ENCRYPT_PASS') . '\')';
  $sth = $dbh->prepare($sql);
  $flag = $sth->execute(array($userId));
}

// ユーザーIDを元にデータベースから情報を取得
function getMemoByUserId($userId) {
  $dbh = dbConnection::getConnection();
  $sql = 'select text from ' . TABLE_NAME_COFFEEMEMO . ' where ? = pgp_sym_decrypt(userid, \'' . getenv('DB_ENCRYPT_PASS') . '\')';
  $sth = $dbh->prepare($sql);
  $sth->execute(array($userId));
  // レコードが存在しなければNULL
  if (!($row = $sth->fetch())) {
    return PDO::PARAM_NULL;
  } else {
    // ユーザの状態を連想配列に変換し返す
    return json_decode($row['text']);
  }
}
?>
