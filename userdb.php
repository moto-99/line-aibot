<?php
/**
 * ユーザテーブルのデータベースの操作を行う。
 *
*/
//テーブル名の定義
define('TABLE_NAME_USERS','users');
// ユーザーをデータベースに登録する
function registerUser($userId, $state) {
  $dbh = dbConnection::getConnection();
  $sql = 'insert into '. TABLE_NAME_USERS .' (userid, state) values (pgp_sym_encrypt(?, \'' . getenv('DB_ENCRYPT_PASS') . '\'), ?) ';
  $sth = $dbh->prepare($sql);
  $sth->execute(array($userId, $state));
}

// ユーザーの情報を更新
function updateUser($userId, $state) {
  $dbh = dbConnection::getConnection();
  $sql = 'update ' . TABLE_NAME_USERS . ' set state = ? where ? = pgp_sym_decrypt(userid, \'' . getenv('DB_ENCRYPT_PASS') . '\')';
  $sth = $dbh->prepare($sql);
  $sth->execute(array($state, $userId));
}

// ユーザーの情報をデータベースから削除
function deleteUser($userId) {
  $dbh = dbConnection::getConnection();
  $sql = 'delete FROM ' . TABLE_NAME_USERS . ' where ? = pgp_sym_decrypt(userid, \'' . getenv('DB_ENCRYPT_PASS') . '\')';
  $sth = $dbh->prepare($sql);
  $flag = $sth->execute(array($userId));
}

// ユーザーIDを元にデータベースから情報を取得
function getStateByUserId($userId) {
  $dbh = dbConnection::getConnection();
  $sql = 'select state from ' . TABLE_NAME_USERS . ' where ? = pgp_sym_decrypt(userid, \'' . getenv('DB_ENCRYPT_PASS') . '\')';
  $sth = $dbh->prepare($sql);
  $sth->execute(array($userId));
  // レコードが存在しなければNULL
  if (!($row = $sth->fetch())) {
    return PDO::PARAM_NULL;
  } else {
    // ユーザの状態を連想配列に変換し返す
    return json_decode($row['state']);
  }
}
?>
