<?php
if (!isset($_SESSION["mikhmon"])) {
    header("Location:../admin.php?id=login");
  } else {
        $_SESSION["v"] = 'Di modifikasi oleh <a href="https://dianrp.com" target="_blank">dianrp.com</a>';
    
    }
