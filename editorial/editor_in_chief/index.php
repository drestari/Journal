<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
role_required(['editor_in_chief']);
redirect('dashboard.php');
