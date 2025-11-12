<?php
session_start();
session_unset();
session_destroy();

// منع الكاش هنا أيضا
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

header("Location: institutions_list.php");
exit();
?>
