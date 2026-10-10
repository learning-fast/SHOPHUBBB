<?php
$new_password = 'Admin@123';
$new_hash = password_hash($new_password, PASSWORD_BCRYPT);
echo $new_hash;
?>