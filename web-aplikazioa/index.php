<?php

require_once __DIR__ . "/config/config.php";
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . "/src/Infrastructure/database.php";
require_once __DIR__ . "/src/Core/security.php";

?>

<!doctype html>
<html lang="eu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ugarteburu Torlojuak</title>
<link rel=stylesheet type=text/css href=style.css>
</head>
<body>
<div align=center>
<!--<fieldset class=body_container id=body_container>-->
<table cellspacing=5 cellpadding=5>
<tr>
<td>
<img src=logo.png height=110 width=115 border=0 alt="Ugarteburu Torlojuak">
</td>
<td>
<hr>
<h1>Ugarteburu Torlojuak</h1><br>
<hr>
</td>
</tr>
</table>
<hr width=1000 size=5>

<?php
if(!empty($_SESSION['user_id'])){
  echo "<h3>Ongi Etorri ";

  echo e($_SESSION['username']);
  if (!empty($_SESSION['is_admin'])){
      echo " | <a href=".$_SERVER['PHP_SELF']."?action=account>Kontua</a> | <a href=".$_SERVER['PHP_SELF']."?action=updel>Igo/Ezabatu</a> | <a href=".$_SERVER['PHP_SELF']."?action=logout>Saioa itxi</a> | <a href=".$_SERVER['PHP_SELF'].">Hasiera</a></h3>";
  }
  else {
    echo " | <a href=".$_SERVER['PHP_SELF']."?action=account>Kontua</a> | <a href=".$_SERVER['PHP_SELF']."?action=logout>Saioa itxi</a> | <a href=".$_SERVER['PHP_SELF'].">Hasiera</a></h3>";
    /* karritoan zerbait badago, hemen ipini - karritoa ikusi*/
  }
}
else{
  echo "<h3><a href=".$_SERVER['PHP_SELF']."?action=register>Erregistratu</a> | <a href=".$_SERVER['PHP_SELF']."?action=login>Saioa hasi</a> | <a href=".$_SERVER['PHP_SELF'].">Hasiera</a></h3>";
}

?>
<form name="search" method="get" action="index.php" id="search">
    <input type="hidden" name="action" value="search"/>
    <input type="text" value="<?php echo e($_GET['keyword'] ?? ''); ?>" name="keyword"/>
    <input type="submit" name="search" value="Bilatu"/>
</form>

<hr width=1000 size=5>
<br>

<?php
if(isset($_GET['action'])){
    switch($_GET['action']){
      case("updel"):
        if(!empty($_SESSION['is_admin'])){
          include __DIR__ . "/src/Modules/Catalog/updel.php";
        }else{
          header("Location: ".$_SERVER['PHP_SELF']);
        }
      break;
      case("register"):
         include __DIR__ . "/src/Modules/Auth/register.php";
      break;
      case("login"):
        include __DIR__ . "/src/Modules/Auth/login.php";
      break;
      case("description"):
        include __DIR__ . "/src/Modules/Catalog/description.php";
      break;
      case("search"):
            include __DIR__ . "/src/Modules/Catalog/main.php";
            break;
      case("account"):
        if(!empty($_SESSION['user_id'])){
          include __DIR__ . "/src/Modules/Account/account.php";
        }else{
          header("Location: ".$_SERVER['PHP_SELF']);
        }
      break;
      case("logout"):
        if(!empty($_SESSION['user_id'])){
          session_unset();
          session_destroy();
          header("Location: index.php");
        }else{
          header("Location: ".$_SERVER['PHP_SELF']);
        }
      break;
      default:
          include __DIR__ . "/src/Modules/Catalog/main.php";
    }
}
else{
    include __DIR__ . "/src/Modules/Catalog/main.php";
}

?>
</div>
<br>
<hr width=1000 size=5>
<div align=center>
    <b>Ugarteburu Torlojuak &middot; <?php echo e($version); ?></b> <br>
<!--</fieldset>-->
</div>
</body>
</html>
