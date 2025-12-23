<?php
// mod/sudo_logout.php

$result = sudoLogout();

if ($result['success']) {
    alert($result['message'], 1, "inicio");
} else {
    alert($result['message'], 0, "inicio");
}
?>