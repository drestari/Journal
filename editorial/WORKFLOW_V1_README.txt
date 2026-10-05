
AJSMR EDITORIAL WORKFLOW V1 - LOCAL TESTING

1. Import workflow_v1_migration.sql into database: ajsmr_editorial.
2. Copy the PHP files into:
   E:\ajsmr_testing\editorial\
3. Keep the existing:
   editorial\config\config.php
4. Open:
   http://localhost/ajsmr_testing/editorial/workflow_v1.php

V1 flow:
Technical Check -> Editor Assignment -> Reviewer Pool/Assignment -> Peer Review
-> Editorial Decision -> Revision -> Acceptance

Reviewer pool:
http://localhost/ajsmr_testing/editorial/reviewer_manage.php

Reviewer portal:
http://localhost/ajsmr_testing/editorial/reviewer_portal.php

IMPORTANT:
- This V1 does not send real email. It records workflow states locally.
- It does not replace legacy submissions.php.
- It does not alter the legacy submitmanuscripts table.
- It does not delete existing editorial data.
- Test with AJSMR-2026-91690 first.
- Before production deployment, add mail delivery, secure file permissions, invitation tokens, reviewer conflict checks, decision-letter templates, revision file upload, and full audit/security review.

ROLE NOTE:
The current local users table uses the role value 'editor_in_chief' (underscore).
Workflow V1 has been corrected to recognize this exact existing role. Do not change
the existing user's role just for Workflow V1.


FIXED_ALL NOTE:
All Workflow V1 management pages have been updated to use wf_require_roles()
and the actual database role value editor_in_chief. This fixes the undefined
function wf_user() error and keeps authorization consistent across:
technical check, editor assignment, reviewer management, decision, revision,
and acceptance.
