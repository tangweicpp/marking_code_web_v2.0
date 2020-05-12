<?php
include_once 'conn_db.php';

echo '<pre>';
print_r($_POST);
echo '</pre>';
echo '<pre>';
print_r($_FILES);
echo '</pre>';

$part_id = $_POST['part_id'];
$pds_file = $_FILES['pds_file'];
$wo_file = $_FILES['wo_file'];

// del
$dir = './uploads/' . $part_id . '/';
deldir($dir);

// upload pds
if ($pds_file['error'] === 0) {
  //把文件上传到./dir目录
  $up_root = './uploads/' . $part_id . '/PDS/';
  //该目录是否存在  || 不存在创建该目录
  is_dir($up_root) || mkdir($up_root, 0777, true);
  chmod($up_root, 0777);
  $ext = explode('.', $pds_file['name'])[1];
  // $save_name = $up_root . $part_id . '.' . $ext;
  $save_name = $up_root . $pds_file['name'];
  echo $save_name;
  if (move_uploaded_file($pds_file['tmp_name'], $save_name)) {
    $sql = "update tbl_markingcode_rep set PDS_DIR = '$save_name' where ht_pn = '$part_id'";
    my_query($sql, $result);
    echo ' pds保存成功';
  }
}

// wo pds
if ($wo_file['error'] === 0) {
  //把文件上传到./dir目录
  $up_root = './uploads/' . $part_id  . '/WO/';
  //该目录是否存在  || 不存在创建该目录
  is_dir($up_root) || mkdir($up_root, 0777, true);
  chmod($up_root, 0777);
  $ext = explode('.', $wo_file['name'])[1];
  // $save_name = $up_root . $part_id . '.' . $ext;
  $save_name = $up_root . $wo_file['name'];
  echo $save_name;
  if (move_uploaded_file($wo_file['tmp_name'], $save_name)) {
    $sql = "update tbl_markingcode_rep set WO_DIR = '$save_name' where ht_pn = '$part_id'";
    my_query($sql, $result);
    echo ' wo保存成功';
  }
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
