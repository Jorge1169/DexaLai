<?php
@session_destroy();
logActivity('LOGOUT', 'Usuario cerró sesión');
redir("./");
?>