<?php

require_once "conn_db.php";
$username = $_GET['username'];
$password = $_GET['password'];

$sql = "select username from tbl_erpuser where username='$username' and password='$password'  ";

$rows = my_query($sql, $result);
if ($rows === 0) {
  echo '请输入正确的用户名和密码';
} else {
  echo 'success';
}
