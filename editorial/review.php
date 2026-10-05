<?php
require_once __DIR__.'/workflow_v1_common.php';
$aid = (int)($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0);
if ($aid > 0) {
    wf_redirect('reviewer/review_manuscript.php?assignment_id=' . $aid);
} else {
    wf_redirect('reviewer/index.php');
}
