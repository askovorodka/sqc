<?php

  define("DB_HOST","localhost");
  define("DB_NAME","sqc");
  define("DB_USER","root");
  define("DB_PASS","password");

  define("ROOT", dirname(__FILE__) . "/../");
  define("DOMAIN", 'http://'.$_SERVER['SERVER_NAME'].'/');
  define("SHOP_IMAGE", DOMAIN . 'uploaded_files/shop_images/');
  define("TPL_PATH", ROOT . "templates/");
  define("TPL_SHOP_PATH", ROOT . "modules/shop/front/templates/");
  define('PROPERTY_COLOR_ID', 9);
  
?>
