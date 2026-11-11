<?php
require __DIR__ . '/includes/auth.php';
redirect('login.php?email_confirmation_not_required=1');
