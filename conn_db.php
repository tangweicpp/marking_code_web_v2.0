<?php
function my_query($sql, &$result)
{
  $conn = oci_connect('INSITEQT2', 'KsMesDB_ht89', '10.160.2.19:1521/mesora', 'utf8');
  if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
  }


  $stmt = oci_parse($conn, $sql);
  oci_execute($stmt);

  $rows = oci_fetch_all($stmt, $result);
  oci_free_statement($stmt);
  oci_close($conn);

  return $rows;
}
