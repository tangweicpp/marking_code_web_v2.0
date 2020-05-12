<?php
include_once 'conn_db.php';
$part_id = $_GET['part_id'];
$action = $_GET['action'];
$query_type = $_GET['query_type'];
$code = $_GET['code'];
$desc = $_GET['desc'];
$username = $_GET['username'];

switch ($action) {
  case 'query':
    on_query_rule($part_id, $query_type);
    break;
  case 'add':
    on_add_rule($part_id, $code, $desc, $username);
    break;
  case 'update':
    on_update_rule($part_id, $code, $desc, $username);
    break;
  case 'remove':
    on_remove_rule($part_id, $username);
    break;
  case 'created':
    on_created_rule($part_id, $username);
    break;
  case 'buyoff':
    on_buyoff_rule($part_id, $username);
    break;
  default:
}

function on_query_rule($part_id, $query_type)
{
  if ($query_type === 'this') {
    $sql = "select ID,HT_PN,to_char(CREATE_DATE,'yyyy-MM-dd HH24:mi:ss') ,CREATE_BY,ESTABLISH_FLAG,BUY_OFF_FLAG,REMARK,DESCRIBE,PDS_DIR as PDS,WO_DIR as WO from tbl_markingcode_rep where ht_pn='$part_id' order by id desc ";
  } elseif ($query_type === 'all') {
    $sql = "select ID,HT_PN,to_char(CREATE_DATE,'yyyy-MM-dd HH24:mi:ss'),CREATE_BY,ESTABLISH_FLAG,BUY_OFF_FLAG,REMARK,DESCRIBE,PDS_DIR as PDS,WO_DIR as WO from tbl_markingcode_rep order by id desc";
  } elseif ($query_type === 'wait_create') {
    $sql = "select ID,HT_PN,to_char(CREATE_DATE,'yyyy-MM-dd HH24:mi:ss'),CREATE_BY,ESTABLISH_FLAG,BUY_OFF_FLAG,REMARK,DESCRIBE,PDS_DIR as PDS,WO_DIR as WO from tbl_markingcode_rep where establish_flag = 'N' order by id desc ";
  } elseif ($query_type === 'wait_buy_off') {
    $sql = "select ID,HT_PN,to_char(CREATE_DATE,'yyyy-MM-dd HH24:mi:ss'),CREATE_BY,ESTABLISH_FLAG,BUY_OFF_FLAG,REMARK,DESCRIBE,PDS_DIR as PDS,WO_DIR as WO from tbl_markingcode_rep where buy_off_flag = 'N' order by id desc ";
  }

  $rows = my_query($sql, $result);
  if ($rows === 0) {
    echo '{}';
    return false;
  }

  echo json_encode($result);
}

function on_add_rule($part_id, $code, $desc, $username)
{
  $sql = "select * from tbltsvnpiproduct where qtechptno = '$part_id'";
  $rows = my_query($sql, $result);
  if ($rows == 0) {
    echo '您输入的机种还没有维护NPI对照表,请先维护';
    exit;
  }

  $sql = "select * from tbl_markingcode_rep where ht_pn='$part_id' ";

  $rows = my_query($sql, $result);
  if ($rows > 0) {
    echo '已经存在该机种的打标规则,不可再次新建';
    exit;
  }

  $sql = "select (max(ID) + 1) as ID from tbl_markingcode_rep";
  $rows = my_query($sql, $result);
  $id = $result['ID'][0];
  $sql = "insert into tbl_markingcode_rep(ID,HT_PN,CREATE_DATE,CREATE_BY,ESTABLISH_FLAG,BUY_OFF_FLAG,REMARK,DESCRIBE,APPLY_FLAG,CARD_CONTROL_FLAG) values($id, '$part_id',sysdate, '$username','N','N','$code','$desc','Y','Y') ";
  my_query($sql, $result);


  // send email
  $sql = "insert into tbl_Sent_Mail(MAIL_ID,SENT_FROM,SENT_TIME,SENT_TO,SENT_TO_CC,MAIL_TITLE,MAIL_BODY,MAIL_ATTACHMENT,FLAG) "
    . " values(MAILID_SEQ.Nextval,(select username from tbl_mail_name where userid = '$username'),sysdate, (select emlname from tbl_mail_name where userid = '$username') ,'wei.tang_ks@ht-tech.com','机种'||'$part_id'||'打标码规则申请新增通知','机种'||'$part_id'||'打标码规则已申请','','0')  ";
  my_query($sql, $result);
  echo 'success';
}

function on_update_rule($part_id, $code, $desc, $username)
{
  $sql = "select * from tbltsvnpiproduct where qtechptno = '$part_id'";
  $rows = my_query($sql, $result);
  if ($rows == 0) {
    echo '您输入的机种还没有维护NPI对照表,请先维护';
    exit;
  }

  $sql = "select * from tbl_markingcode_rep where ht_pn='$part_id' ";

  $rows = my_query($sql, $result);
  if ($rows == 0) {
    echo '不存在该机种的打标规则,无法修改';
    exit;
  }

  $sql = "update tbl_markingcode_rep set CREATE_DATE = sysdate,ESTABLISH_FLAG = 'N',BUY_OFF_FLAG = 'N',REMARK = '$code',DESCRIBE = '$desc'  where ht_pn = '$part_id' and CREATE_BY = '$username' ";
  my_query($sql, $result);

  //send mail
  $sql = "insert into tbl_Sent_Mail(MAIL_ID,SENT_FROM,SENT_TIME,SENT_TO,SENT_TO_CC,MAIL_TITLE,MAIL_BODY,MAIL_ATTACHMENT,FLAG) "
    . " values(MAILID_SEQ.Nextval,(select username from tbl_mail_name where userid = '$username'),sysdate,(select emlname from tbl_mail_name where userid = '$username'),'wei.tang_ks@ht-tech.com','机种'||'$part_id'||'打标码规则申请修改通知','机种'||'$part_id'||'打标码规则已经修改','','0') ";
  my_query($sql, $result);
  echo 'success';
}

function on_created_rule($part_id_array, $username)
{
  foreach ($part_id_array as $part_id) {

    $sql = "update tbl_markingcode_rep set ESTABLISH_FLAG = 'Y' where id = '$part_id' ";
    my_query($sql, $result);
    $sql = "select ht_pn as PN from tbl_markingcode_rep where id = '$part_id' ";
    my_query($sql, $result);
    $part_id_name = $result['PN'][0];
    //send mail
    $sql = "insert into tbl_Sent_Mail(MAIL_ID,SENT_FROM,SENT_TIME,SENT_TO,SENT_TO_CC,MAIL_TITLE,MAIL_BODY,MAIL_ATTACHMENT,FLAG) "
      . " values(MAILID_SEQ.Nextval,(select bb.username from tbl_markingcode_rep aa,tbl_mail_name bb 
  where aa.create_by = bb.userid and aa.id = '$part_id'),sysdate,(select bb.emlname from tbl_markingcode_rep aa,tbl_mail_name bb 
  where aa.create_by = bb.userid and aa.id = '$part_id'),'wei.tang_ks@ht-tech.com','机种'||'$part_id_name'||'打标码规则建立完成','机种'||'$part_id_name'||'打标码规则已建立','','0') ";
    my_query($sql, $result);
  }

  echo 'success';
}

function on_buyoff_rule($part_id_array, $username)
{
  foreach ($part_id_array as $part_id) {

    $sql = "update tbl_markingcode_rep set BUY_OFF_FLAG = 'Y' where id = '$part_id' and CREATE_BY = '$username' and ESTABLISH_FLAG = 'Y'";
    my_query($sql, $result);
    $sql = "select ht_pn as PN from tbl_markingcode_rep where id = '$part_id' ";
    my_query($sql, $result);
    $part_id_name = $result['PN'][0];
    //send mail
    $sql = "insert into tbl_Sent_Mail(MAIL_ID,SENT_FROM,SENT_TIME,SENT_TO,SENT_TO_CC,MAIL_TITLE,MAIL_BODY,MAIL_ATTACHMENT,FLAG) "
      . " values(MAILID_SEQ.Nextval,(select bb.username from tbl_markingcode_rep aa,tbl_mail_name bb 
  where aa.create_by = bb.userid and aa.id = '$part_id'),sysdate,(select bb.emlname from tbl_markingcode_rep aa,tbl_mail_name bb 
  where aa.create_by = bb.userid and aa.id = '$part_id'),'wei.tang_ks@ht-tech.com','机种'||'$part_id_name'||'打标码规则验收完成','机种'||'$part_id_name'||'打标码规则已验收','','0') ";
    my_query($sql, $result);
  }

  echo 'success';
}

function on_remove_rule($part_id, $username)
{
  if ($username != '07885') {
    echo '你没有权限删除,请联系IT进行删除';
    exit;
  }

  $sql = "select * from tbltsvnpiproduct where qtechptno = '$part_id'";
  $rows = my_query($sql, $result);
  if ($rows == 0) {
    echo '您输入的机种还没有维护NPI对照表,请先维护';
    exit;
  }

  $sql = "select * from tbl_markingcode_rep where ht_pn='$part_id' ";

  $rows = my_query($sql, $result);
  if ($rows == 0) {
    echo '不存在该机种的打标规则,无法删除';
    exit;
  }

  $sql = "delete from tbl_markingcode_rep where ht_pn='$part_id' ";
  my_query($sql, $result);

  $dir = './uploads/' . $part_id . '/';
  deldir($dir);

  $sql = "insert into tbl_Sent_Mail(MAIL_ID,SENT_FROM,SENT_TIME,SENT_TO,SENT_TO_CC,MAIL_TITLE,MAIL_BODY,MAIL_ATTACHMENT,FLAG) "
    . " values(MAILID_SEQ.Nextval,(select username from tbl_mail_name where userid = '$username'),sysdate,(select emlname from tbl_mail_name where userid = '$username'),'wei.tang_ks@ht-tech.com','机种'||'$part_id'||'打标码规则申请删除通知','机种'||'$part_id'||'打标码规则已经删除','','0') ";
  my_query($sql, $result);

  echo 'success';
}

function deldir($dir)
{
  //先删除目录下的文件：
  $dh = opendir($dir);
  while ($file = readdir($dh)) {
    if ($file != "." && $file != "..") {
      $fullpath = $dir . "/" . $file;
      if (!is_dir($fullpath)) {
        unlink($fullpath);
      } else {
        deldir($fullpath);
      }
    }
  }

  closedir($dh);
  //删除当前文件夹：
  if (rmdir($dir)) {
    return true;
  } else {
    return false;
  }
}
