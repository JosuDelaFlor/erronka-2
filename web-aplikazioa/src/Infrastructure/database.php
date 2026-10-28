<?php

$conx = mysqli_connect($mysql_host, $mysql_user, $mysql_pass);
if (!$conx || !mysqli_select_db($conx, $mysql_db)) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    exit('Servicio temporalmente no disponible.');
}
mysqli_set_charset($conx, 'utf8mb4');

?>