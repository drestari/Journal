<?php
declare(strict_types=1);

/**
 * AJSMR Centralized Workflow Notification Service
 *
 * Orchestrates event-driven email notifications for Authors, Reviewers, and Editors.
 * Ensures proper recipient resolution, confidentiality checks, audit logging,
 * and robust non-blocking error handling.
 */

require_once __DIR__ . '/email_service.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Main Centralized Notification Entry Point
 *
 * @param PDO $db
 * @param string $event
 * @param int $manuscriptId
 * @param array $extraData
 * @return bool
 */
function sendWorkflowNotification(PDO $db, string $event, int $manuscriptId, array $extraData = []): bool {
    $event = strtoupper(trim($event));
    if ($manuscriptId <= 0) {
        return false;
    }

    $m = ajsmr_get_manuscript_details($db, $manuscriptId);
    if (!$m) {
        return false;
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = defined('BASE_URL') ? BASE_URL : '/editorial/';
    $siteRootUrl = $protocol . $host . $baseUrl;

    $authorLink   = $siteRootUrl . "manuscript_view.php?id=" . $manuscriptId;
    $reviewerLink = $siteRootUrl . "reviewer/review_manuscript.php?id=" . $manuscriptId;
    $eicLink      = $siteRootUrl . "manuscript_view.php?id=" . $manuscriptId;

    $success = false;

    try {
        switch ($event) {

            // -----------------------------------------------------------------
            // 1. MANUSCRIPT SUBMITTED (Author)
            // -----------------------------------------------------------------
            case 'MANUSCRIPT_SUBMITTED':
            case 'SUBMISSION_RECEIVED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $tpl = [
                    'title'          => 'Manuscript Submission Received',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "Thank you for submitting your manuscript to the American Journal of Science and Medical Research (AJSMR). Your manuscript has been assigned a unique Manuscript ID and will now undergo initial technical screening.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Article Type'   => $m['article_type'] ?? 'Research Article',
                        'Current Status' => 'Submitted',
                        'Submitted Date' => date('d M Y, H:i', strtotime($m['submitted_at'] ?? 'now')),
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Manuscript Portal'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Manuscript Submission Received ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 2. TECHNICAL CHECK PASSED (Author)
            // -----------------------------------------------------------------
            case 'TECHNICAL_CHECK_COMPLETED':
            case 'TECHNICAL_CHECK_PASSED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $tpl = [
                    'title'          => 'Technical Check Completed',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "Your manuscript has successfully passed the initial Technical Check and screening. It is now proceeding to section editor assignment and peer-review evaluation.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Technical Check Completed',
                        'Updated Date'   => date('d M Y')
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Manuscript Portal'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Technical Check Passed ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 3. TECHNICAL CHECK CORRECTIONS REQUIRED (Author)
            // -----------------------------------------------------------------
            case 'TECHNICAL_CHECK_CORRECTION':
            case 'TECHNICAL_CORRECTIONS_REQUIRED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $comments = trim((string)($extraData['comments'] ?? 'Please check manuscript formatting and required declarations.'));

                $tpl = [
                    'title'          => 'Technical Corrections Required',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "Your manuscript has been screened. Minor technical corrections are required before proceeding to peer review.\n\nRequired Corrections:\n" . $comments,
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Revision Required (Technical)'
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'Upload Corrected Manuscript'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Technical Check Update: Corrections Required ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 4. TECHNICAL CHECK FAILED / REJECTED (Author)
            // -----------------------------------------------------------------
            case 'TECHNICAL_CHECK_FAILED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $comments = trim((string)($extraData['comments'] ?? 'Manuscript does not meet basic submission requirements.'));

                $tpl = [
                    'title'          => 'Technical Check Outcome',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "Your manuscript was evaluated during initial screening and did not pass the required technical check.\n\nTechnical Screening Notes:\n" . $comments,
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Rejected (Technical Check)'
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Manuscript Details'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Technical Check Outcome ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 5. EDITOR ASSIGNED (Author)
            // -----------------------------------------------------------------
            case 'EDITOR_ASSIGNED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $tpl = [
                    'title'          => 'Section Editor Assigned',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "A Section Editor has been assigned to oversee the peer review and evaluation of your manuscript.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Assigned to Editor'
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Author Portal'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Section Editor Assigned ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 6. REVIEW STARTED / PEER REVIEW INITIATED (Author)
            // -----------------------------------------------------------------
            case 'REVIEW_STARTED':
            case 'UNDER_REVIEW':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $tpl = [
                    'title'          => 'Peer Review Started',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "Your manuscript has been sent for peer review. Expert peer reviewers are currently evaluating your manuscript.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Under Review'
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'Track Manuscript Status'
                ];
                // NO reviewer identity exposed!
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Peer Review Process Initiated ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 7. REVIEWER INVITED (Reviewer)
            // -----------------------------------------------------------------
            case 'REVIEWER_INVITED':
                $reviewerId = (int)($extraData['reviewer_id'] ?? 0);
                $reviewer   = ajsmr_get_reviewer_recipient($db, $reviewerId);
                if (empty($reviewer['email'])) break;

                $dueDateStr = !empty($extraData['due_at']) ? date('d M Y', strtotime((string)$extraData['due_at'])) : 'As specified in portal';

                $tpl = [
                    'title'          => 'Invitation to Review Manuscript',
                    'greeting'       => "Dear " . ($reviewer['name'] ?: 'Reviewer') . ",",
                    'message'        => "You are cordially invited to serve as a peer reviewer for the following manuscript submitted to AJSMR.\n\nAbstract:\n" . ($m['abstract'] ?? 'Available in Reviewer Portal'),
                    'manuscriptInfo' => [
                        'Manuscript ID'   => $m['manuscript_no'],
                        'Title'           => $m['title'],
                        'Article Type'    => $m['article_type'] ?? 'Research Article',
                        'Review Deadline' => $dueDateStr
                    ],
                    'actionUrl'      => $reviewerLink,
                    'actionText'     => 'Open Reviewer Portal'
                ];
                // NO other reviewers exposed!
                $success = ajsmr_dispatch_email($db, $reviewer['email'], "AJSMR — Reviewer Invitation ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 8. REVIEWER ASSIGNED / CONFIRMED (Reviewer)
            // -----------------------------------------------------------------
            case 'REVIEWER_ASSIGNED':
                $reviewerId = (int)($extraData['reviewer_id'] ?? 0);
                $reviewer   = ajsmr_get_reviewer_recipient($db, $reviewerId);
                if (empty($reviewer['email'])) break;

                $tpl = [
                    'title'          => 'Review Assignment Confirmation',
                    'greeting'       => "Dear " . ($reviewer['name'] ?: 'Reviewer') . ",",
                    'message'        => "Thank you for accepting the review assignment for manuscript {$m['manuscript_no']}. You may download the manuscript file and submit your evaluation report directly through the Reviewer Portal.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Due Date'       => !empty($extraData['due_at']) ? date('d M Y', strtotime((string)$extraData['due_at'])) : 'Standard Period'
                    ],
                    'actionUrl'      => $reviewerLink,
                    'actionText'     => 'Open Reviewer Portal'
                ];
                $success = ajsmr_dispatch_email($db, $reviewer['email'], "AJSMR — Review Assignment ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 9. REVIEW SUBMITTED (Reviewer + EIC)
            // -----------------------------------------------------------------
            case 'REVIEW_SUBMITTED':
                $reviewerId = (int)($extraData['reviewer_id'] ?? 0);
                $reviewer   = ajsmr_get_reviewer_recipient($db, $reviewerId);

                // Reviewer confirmation
                if (!empty($reviewer['email'])) {
                    $tplRev = [
                        'title'          => 'Review Submitted Successfully',
                        'greeting'       => "Dear " . ($reviewer['name'] ?: 'Reviewer') . ",",
                        'message'        => "Thank you for completing and submitting your peer-review report for manuscript {$m['manuscript_no']} (\"{$m['title']}\"). Your expert contribution is greatly appreciated.",
                        'manuscriptInfo' => [
                            'Manuscript ID'   => $m['manuscript_no'],
                            'Title'           => $m['title'],
                            'Submitted Date'  => date('d M Y, H:i')
                        ],
                        'actionUrl'      => $reviewerLink,
                        'actionText'     => 'View Reviewer Portal'
                    ];
                    ajsmr_dispatch_email($db, $reviewer['email'], "AJSMR — Review Submitted Successfully ({$m['manuscript_no']})", $tplRev, $event, $manuscriptId);
                }

                // EIC notification
                $eic = ajsmr_get_eic_recipient($db);
                if (!empty($eic['email'])) {
                    $tplEic = [
                        'title'          => 'Peer Review Report Received',
                        'greeting'       => 'Dear Editor-in-Chief,',
                        'message'        => "A peer-review report has been submitted for manuscript {$m['manuscript_no']} by reviewer " . ($reviewer['name'] ?? 'Assigned Reviewer') . ".",
                        'manuscriptInfo' => [
                            'Manuscript ID'   => $m['manuscript_no'],
                            'Title'           => $m['title'],
                            'Recommendation'  => ucwords(str_replace('_', ' ', (string)($extraData['recommendation'] ?? 'Submitted')))
                        ],
                        'actionUrl'      => $eicLink,
                        'actionText'     => 'Review Report on EIC Dashboard'
                    ];
                    $success = ajsmr_dispatch_email($db, $eic['email'], "AJSMR — Review Report Received ({$m['manuscript_no']})", $tplEic, $event, $manuscriptId);
                }
                break;

            // -----------------------------------------------------------------
            // 10. REVIEW COMPLETED (Author)
            // -----------------------------------------------------------------
            case 'REVIEW_COMPLETED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $tpl = [
                    'title'          => 'Peer Review Evaluation Completed',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "The peer review process for your manuscript has been completed. The Editor-in-Chief is evaluating the reports to render an editorial decision.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Under Editorial Evaluation'
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Author Portal'
                ];
                // NO confidential reviewer identity or internal notes exposed!
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Peer Review Completed ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 11. EDITORIAL DECISION / REVISION REQUESTED (Author)
            // -----------------------------------------------------------------
            case 'EDITORIAL_DECISION':
            case 'REVISION_REQUESTED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $decision = (string)($extraData['decision'] ?? $extraData['revision_type'] ?? 'revision');
                $letter   = trim((string)($extraData['letter'] ?? $extraData['comments'] ?? $extraData['response_text'] ?? ''));

                $decisionTitle = 'Editorial Decision';
                $statusLabel   = 'Revision Required';

                if (in_array($decision, ['accept', 'ACCEPTED'], true)) {
                    $decisionTitle = 'Manuscript Accepted';
                    $statusLabel   = 'Accepted';
                } elseif (in_array($decision, ['reject', 'REJECTED'], true)) {
                    $decisionTitle = 'Manuscript Rejected';
                    $statusLabel   = 'Rejected';
                } elseif (str_contains(strtolower($decision), 'minor')) {
                    $decisionTitle = 'Minor Revision Requested';
                    $statusLabel   = 'Minor Revision Required';
                } elseif (str_contains(strtolower($decision), 'major')) {
                    $decisionTitle = 'Major Revision Requested';
                    $statusLabel   = 'Major Revision Required';
                }

                $messageText = "An editorial decision has been rendered for your manuscript: " . ucwords(str_replace('_', ' ', $decision)) . ".";
                if (!empty($letter)) {
                    $messageText .= "\n\nEditorial Letter / Comments:\n" . $letter;
                }

                $tpl = [
                    'title'          => $decisionTitle,
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => $messageText,
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => $statusLabel
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'Open Author Portal'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Editorial Decision ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 12. REVISION SUBMITTED / RECEIVED (Author + EIC)
            // -----------------------------------------------------------------
            case 'REVISION_SUBMITTED':
            case 'REVISION_RECEIVED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (!empty($author['email'])) {
                    $tplAuth = [
                        'title'          => 'Revised Manuscript Received',
                        'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                        'message'        => "Your revised manuscript has been successfully submitted to the editorial office and is now being evaluated by the editor.",
                        'manuscriptInfo' => [
                            'Manuscript ID'  => $m['manuscript_no'],
                            'Title'          => $m['title'],
                            'Version'        => 'Version ' . ($m['version_no'] ?? '2'),
                            'Received Date'  => date('d M Y, H:i')
                        ],
                        'actionUrl'      => $authorLink,
                        'actionText'     => 'View Manuscript Portal'
                    ];
                    ajsmr_dispatch_email($db, $author['email'], "AJSMR — Revision Received ({$m['manuscript_no']})", $tplAuth, $event, $manuscriptId);
                }

                $eic = ajsmr_get_eic_recipient($db);
                if (!empty($eic['email'])) {
                    $tplEic = [
                        'title'          => 'Revised Manuscript Submitted',
                        'greeting'       => 'Dear Editor-in-Chief,',
                        'message'        => "The author has uploaded a revised version for manuscript {$m['manuscript_no']} (\"{$m['title']}\").",
                        'manuscriptInfo' => [
                            'Manuscript ID'  => $m['manuscript_no'],
                            'Title'          => $m['title'],
                            'Version'        => 'Version ' . ($m['version_no'] ?? '2')
                        ],
                        'actionUrl'      => $eicLink,
                        'actionText'     => 'Evaluate Revised Manuscript'
                    ];
                    $success = ajsmr_dispatch_email($db, $eic['email'], "AJSMR — Revised Manuscript Received ({$m['manuscript_no']})", $tplEic, $event, $manuscriptId);
                }
                break;

            // -----------------------------------------------------------------
            // 13. MANUSCRIPT ACCEPTED (Author)
            // -----------------------------------------------------------------
            case 'MANUSCRIPT_ACCEPTED':
            case 'ACCEPTANCE':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $tpl = [
                    'title'          => 'Manuscript Formally Accepted',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "We are delighted to inform you that your manuscript has been formally accepted for publication in the American Journal of Science and Medical Research (AJSMR). Your article will now proceed to copyediting, typesetting, and production.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Accepted for Publication',
                        'Accepted Date'  => date('d M Y')
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Author Portal'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Manuscript Accepted ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 14. MANUSCRIPT REJECTED (Author)
            // -----------------------------------------------------------------
            case 'MANUSCRIPT_REJECTED':
            case 'REJECTION':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $letter = trim((string)($extraData['letter'] ?? $extraData['comments'] ?? ''));

                $tpl = [
                    'title'          => 'Editorial Decision: Rejected',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "Thank you for submitting your work to AJSMR. After peer review and editorial evaluation, we regret to inform you that we are unable to accept your manuscript for publication.\n\nEditorial Notes:\n" . ($letter ?: 'See portal for details.'),
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Rejected'
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Manuscript Portal'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Editorial Decision: Rejected ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 15. GALLERY PROOF SENT (Author)
            // -----------------------------------------------------------------
            case 'GALLERY_PROOF_SENT':
            case 'PROOF_SENT':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $version = (string)($extraData['proof_version'] ?? '1');
                $notes   = trim((string)($extraData['notes'] ?? $extraData['proof_comments'] ?? 'Please review the formatted galley proof file.'));

                $proofLink = $siteRootUrl . "author_proof.php?id=" . $manuscriptId;

                $tpl = [
                    'title'          => 'Gallery Proof Available for Review',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "The galley proof (Version {$version}) for your accepted manuscript is now available. Please inspect the formatted article proof carefully and submit your approval or required corrections within the portal.\n\nInstructions:\n" . $notes,
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Proof Version'  => 'Version ' . $version,
                        'Action Required'=> 'Author Proof Approval'
                    ],
                    'actionUrl'      => $proofLink,
                    'actionText'     => 'View & Approve Gallery Proof'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Gallery Proof Available ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 16. GALLERY PROOF RESPONSE (Author + EIC)
            // -----------------------------------------------------------------
            case 'GALLERY_PROOF_RESPONSE':
            case 'GALLERY_PROOF_APPROVED':
            case 'GALLERY_PROOF_CORRECTIONS':
                $author = ajsmr_get_author_recipient($db, $m);
                $decision = (string)($extraData['decision'] ?? 'approved');
                $comments = trim((string)($extraData['comments'] ?? ''));

                if (!empty($author['email'])) {
                    $tplAuth = [
                        'title'          => 'Gallery Proof Response Recorded',
                        'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                        'message'        => "Your author proof decision (" . ucwords(str_replace('_', ' ', $decision)) . ") has been successfully recorded in the production system.",
                        'manuscriptInfo' => [
                            'Manuscript ID'  => $m['manuscript_no'],
                            'Title'          => $m['title'],
                            'Decision'       => ucwords(str_replace('_', ' ', $decision))
                        ],
                        'actionUrl'      => $authorLink,
                        'actionText'     => 'View Author Portal'
                    ];
                    ajsmr_dispatch_email($db, $author['email'], "AJSMR — Gallery Proof Response ({$m['manuscript_no']})", $tplAuth, $event, $manuscriptId);
                }

                $eic = ajsmr_get_eic_recipient($db);
                if (!empty($eic['email'])) {
                    $tplEic = [
                        'title'          => 'Author Proof Response Received',
                        'greeting'       => 'Dear Production Editor,',
                        'message'        => "The author has submitted a proof decision: " . ucwords(str_replace('_', ' ', $decision)) . ($comments ? "\n\nComments:\n" . $comments : ''),
                        'manuscriptInfo' => [
                            'Manuscript ID'  => $m['manuscript_no'],
                            'Title'          => $m['title'],
                            'Decision'       => ucwords(str_replace('_', ' ', $decision))
                        ],
                        'actionUrl'      => $siteRootUrl . "production.php",
                        'actionText'     => 'View Production Dashboard'
                    ];
                    $success = ajsmr_dispatch_email($db, $eic['email'], "AJSMR — Author Proof Decision ({$m['manuscript_no']})", $tplEic, $event, $manuscriptId);
                }
                break;

            // -----------------------------------------------------------------
            // 17. IN_PRESS (Author)
            // -----------------------------------------------------------------
            case 'IN_PRESS':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $tpl = [
                    'title'          => 'Manuscript In-Press (Online First)',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "Your article has completed production and is now placed In-Press (Online First). It will be assigned to a final issue upon publication.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'In-Press'
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Article Status'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Manuscript In-Press ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 18. PUBLISHED (Author)
            // -----------------------------------------------------------------
            case 'PUBLISHED':
                $author = ajsmr_get_author_recipient($db, $m);
                if (empty($author['email'])) break;

                $doi = (string)($extraData['doi'] ?? $m['doi'] ?? '');
                $volIssue = (string)($extraData['volume_issue'] ?? '');

                $tpl = [
                    'title'          => 'Manuscript Officially Published',
                    'greeting'       => "Dear " . ($author['name'] ?: 'Author') . ",",
                    'message'        => "We are delighted to announce that your article has been officially published in the American Journal of Science and Medical Research (AJSMR). Congratulations!",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => 'Published',
                        'DOI'            => $doi ?: 'Registered',
                        'Volume/Issue'   => $volIssue ?: 'Assigned Issue',
                        'Publication Date' => date('d M Y')
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Published Article'
                ];
                $success = ajsmr_dispatch_email($db, $author['email'], "AJSMR — Manuscript Published ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 19. REVIEWER CANCELLED / REMOVED (Reviewer)
            // -----------------------------------------------------------------
            case 'REVIEWER_CANCELLED':
                $reviewerId = (int)($extraData['reviewer_id'] ?? 0);
                $reviewer   = ajsmr_get_reviewer_recipient($db, $reviewerId);
                if (empty($reviewer['email'])) break;

                $tpl = [
                    'title'          => 'Review Assignment Update',
                    'greeting'       => "Dear " . ($reviewer['name'] ?: 'Reviewer') . ",",
                    'message'        => "Please be advised that your review assignment for manuscript {$m['manuscript_no']} (\"{$m['title']}\") has been updated or unassigned. Thank you for your willingness to assist AJSMR.",
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title']
                    ],
                    'actionUrl'      => $reviewerLink,
                    'actionText'     => 'View Reviewer Portal'
                ];
                $success = ajsmr_dispatch_email($db, $reviewer['email'], "AJSMR — Review Assignment Update ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 20. REVIEWER DECLINED (EIC)
            // -----------------------------------------------------------------
            case 'REVIEWER_DECLINED':
                $eic = ajsmr_get_eic_recipient($db);
                if (empty($eic['email'])) break;

                $reviewerName = (string)($extraData['reviewer_name'] ?? 'Assigned Reviewer');
                $reason       = (string)($extraData['reason'] ?? 'Not specified');
                $comments     = (string)($extraData['comments'] ?? '');

                $tpl = [
                    'title'          => 'Review Invitation Declined',
                    'greeting'       => 'Dear Editor-in-Chief,',
                    'message'        => "Reviewer {$reviewerName} has declined the review invitation for manuscript {$m['manuscript_no']}.\n\nReason: " . ucwords(str_replace('_', ' ', $reason)) . ($comments ? "\nComments: {$comments}" : ''),
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title']
                    ],
                    'actionUrl'      => $eicLink,
                    'actionText'     => 'Assign Alternate Reviewer'
                ];
                $success = ajsmr_dispatch_email($db, $eic['email'], "AJSMR — Review Invitation Declined ({$m['manuscript_no']})", $tpl, $event, $manuscriptId);
                break;

            // -----------------------------------------------------------------
            // 21. CUSTOM EMAIL
            // -----------------------------------------------------------------
            case 'CUSTOM_EMAIL':
                $recipientEmail = trim((string)($extraData['to'] ?? ''));
                if (empty($recipientEmail)) {
                    $author = ajsmr_get_author_recipient($db, $m);
                    $recipientEmail = $author['email'];
                }
                if (empty($recipientEmail)) break;

                $customSubject = trim((string)($extraData['subject'] ?? "AJSMR — Editorial Message: {$m['manuscript_no']}"));
                $customBody    = trim((string)($extraData['message'] ?? $extraData['body'] ?? ''));

                $tpl = [
                    'title'          => 'Editorial Communication',
                    'greeting'       => "Dear Recipient,",
                    'message'        => $customBody,
                    'manuscriptInfo' => [
                        'Manuscript ID'  => $m['manuscript_no'],
                        'Title'          => $m['title'],
                        'Current Status' => $m['status']
                    ],
                    'actionUrl'      => $authorLink,
                    'actionText'     => 'View Manuscript Portal'
                ];
                $success = ajsmr_dispatch_email($db, $recipientEmail, $customSubject, $tpl, $event, $manuscriptId);
                break;

            default:
                error_log("[AJSMR Notification Service] Unknown workflow event: {$event}");
                break;
        }
    } catch (Throwable $e) {
        error_log("[AJSMR Notification Service Error] Event: {$event} | Error: " . $e->getMessage());
        $success = false;
    }

    return $success;
}

/**
 * Dispatch Email & Audit Log safely.
 */
function ajsmr_dispatch_email(PDO $db, string $toEmail, string $subject, array $templateParams, string $event, ?int $manuscriptId = null): bool {
    $htmlBody = renderHtmlEmailTemplate($templateParams);
    $textBody = renderTextEmailTemplate($templateParams);

    $res = sendEmail($toEmail, $subject, $htmlBody, $textBody);
    $success = $res['success'];
    $err = $res['error'];

    ajsmr_log_email_event($db, $event, $toEmail, $manuscriptId, $success, $err);

    return $success;
}

/**
 * Safely log email event without exposing secrets.
 */
function ajsmr_log_email_event(PDO $db, string $event, string $recipientEmail, ?int $manuscriptId = null, bool $success = true, string $errorMsg = ''): void {
    try {
        $details = "Event: {$event} | Recipient: {$recipientEmail} | Status: " . ($success ? 'SUCCESS' : 'FAILED');
        if (!$success && !empty($errorMsg)) {
            $details .= " | Error: " . substr(strip_tags($errorMsg), 0, 200);
        }

        $userId = $_SESSION['user']['id'] ?? null;
        if ($userId !== null) {
            $userId = (int)$userId;
        }

        // Try writing to ew_audit_log
        $stmt = $db->prepare("INSERT INTO ew_audit_log (manuscript_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $manuscriptId,
            $userId,
            'email_' . strtolower($event),
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Throwable $e) {
        // Fallback: log to PHP error log if DB audit insert fails
        error_log("[AJSMR Email Audit Log Error] Event: {$event} | To: {$recipientEmail} | " . $e->getMessage());
    }
}

/**
 * Helper: Query manuscript details.
 */
function ajsmr_get_manuscript_details(PDO $db, int $mid): ?array {
    try {
        $stmt = $db->prepare("SELECT * FROM manuscripts WHERE id = ? LIMIT 1");
        $stmt->execute([$mid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Helper: Resolve Corresponding Author email & name.
 */
function ajsmr_get_author_recipient(PDO $db, array $m): array {
    $email = trim((string)($m['corresponding_email'] ?? ''));
    $name  = '';

    if (!empty($m['corresponding_author_id'])) {
        $stmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$m['corresponding_author_id']]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $name = (string)($u['full_name'] ?? '');
            if (empty($email)) {
                $email = (string)($u['email'] ?? '');
            }
        }
    }

    if (empty($email)) {
        $stmt = $db->prepare("SELECT author_name, email FROM manuscript_authors WHERE manuscript_id = ? AND email IS NOT NULL AND email != '' ORDER BY author_order ASC, id ASC LIMIT 1");
        $stmt->execute([(int)$m['id']]);
        $ma = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ma) {
            $email = (string)$ma['email'];
            if (empty($name)) {
                $name = (string)$ma['author_name'];
            }
        }
    }

    return [
        'name'  => $name ?: 'Author',
        'email' => $email
    ];
}

/**
 * Helper: Resolve Reviewer email & name.
 */
function ajsmr_get_reviewer_recipient(PDO $db, int $reviewerId): array {
    if ($reviewerId <= 0) {
        return ['name' => 'Reviewer', 'email' => ''];
    }

    // Try ew_reviewer_pool first
    try {
        $stmt = $db->prepare("SELECT full_name, email FROM ew_reviewer_pool WHERE id = ? OR user_id = ? LIMIT 1");
        $stmt->execute([$reviewerId, $reviewerId]);
        $rp = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rp && !empty($rp['email'])) {
            return [
                'name'  => (string)$rp['full_name'],
                'email' => (string)$rp['email']
            ];
        }
    } catch (Throwable $e) {}

    // Try users table
    try {
        $stmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$reviewerId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u && !empty($u['email'])) {
            return [
                'name'  => (string)$u['full_name'],
                'email' => (string)$u['email']
            ];
        }
    } catch (Throwable $e) {}

    return ['name' => 'Reviewer', 'email' => ''];
}

/**
 * Helper: Resolve EIC / Managing Editor email & name.
 */
function ajsmr_get_eic_recipient(PDO $db): array {
    try {
        $stmt = $db->query("SELECT full_name, email FROM users WHERE role IN ('editor_in_chief', 'admin') AND active = 1 ORDER BY id ASC LIMIT 1");
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u && !empty($u['email'])) {
            return [
                'name'  => (string)($u['full_name'] ?: 'Editor-in-Chief'),
                'email' => (string)$u['email']
            ];
        }
    } catch (Throwable $e) {}

    return [
        'name'  => 'Editor-in-Chief',
        'email' => 'editorajsmr@gmail.com'
    ];
}

// -----------------------------------------------------------------------------
// BACKWARD-COMPATIBLE WRAPPERS FOR EXISTING CODE
// -----------------------------------------------------------------------------

if (!function_exists('sendAuthorNotification')) {
    /**
     * Backward-compatible wrapper for sendAuthorNotification.
     */
    function sendAuthorNotification($param1, $param2 = null, $param3 = null): bool {
        static $globalDb = null;
        if ($globalDb === null) {
            $globalDb = db();
        }

        // Case A: sendAuthorNotification(PDO $db, string $event, int $mid, array $extraData = [])
        if ($param1 instanceof PDO && is_string($param2) && is_int($param3)) {
            return sendWorkflowNotification($param1, $param2, $param3, is_array(func_get_arg(3) ?? null) ? func_get_arg(3) : []);
        }

        // Case B: Legacy call sendAuthorNotification(array $m, string $subject, string $body)
        if (is_array($param1) && is_string($param2) && is_string($param3)) {
            $m = $param1;
            $mid = (int)($m['id'] ?? 0);
            if ($mid <= 0) return false;
            return sendWorkflowNotification($globalDb, 'CUSTOM_EMAIL', $mid, [
                'subject' => $param2,
                'message' => $param3
            ]);
        }

        // Case C: sendAuthorNotification(int $mid, string $event, array $extraData = [])
        if (is_int($param1) && is_string($param2)) {
            return sendWorkflowNotification($globalDb, $param2, $param1, is_array($param3) ? $param3 : []);
        }

        return false;
    }
}

if (!function_exists('sendReviewerNotification')) {
    /**
     * Backward-compatible wrapper for sendReviewerNotification.
     */
    function sendReviewerNotification($param1, $param2 = null, $param3 = null): bool {
        static $globalDb = null;
        if ($globalDb === null) {
            $globalDb = db();
        }

        // Case A: sendReviewerNotification(PDO $db, string $event, int $mid, int $reviewerId, array $extraData = [])
        if ($param1 instanceof PDO && is_string($param2) && is_int($param3)) {
            $reviewerId = is_int(func_get_arg(3) ?? null) ? func_get_arg(3) : 0;
            $extra = is_array(func_get_arg(4) ?? null) ? func_get_arg(4) : [];
            $extra['reviewer_id'] = $reviewerId;
            return sendWorkflowNotification($param1, $param2, $param3, $extra);
        }

        // Case B: Legacy call sendReviewerNotification(string $toEmail, string $subject, string $body)
        if (is_string($param1) && is_string($param2) && is_string($param3)) {
            $tpl = [
                'title'    => 'Reviewer Communication',
                'greeting' => 'Dear Reviewer,',
                'message'  => $param3
            ];
            $html = renderHtmlEmailTemplate($tpl);
            $text = renderTextEmailTemplate($tpl);
            $res = sendEmail($param1, $param2, $html, $text);
            return $res['success'];
        }

        return false;
    }
}

if (!function_exists('sendEICNotification')) {
    function sendEICNotification(PDO $db, string $event, int $manuscriptId, array $extraData = []): bool {
        return sendWorkflowNotification($db, $event, $manuscriptId, $extraData);
    }
}

if (!function_exists('ajsmr_send_reviewer_registration_invitation')) {
    /**
     * Dispatches secure Reviewer Registration Invitation email with token link.
     */
    function ajsmr_send_reviewer_registration_invitation(PDO $db, int $reviewerPoolId): bool {
        $stmt = $db->prepare("SELECT * FROM ew_reviewer_pool WHERE id = ? LIMIT 1");
        $stmt->execute([$reviewerPoolId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r || empty($r['email']) || empty($r['invitation_token'])) {
            return false;
        }

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/editorial/';
        $regLink = $protocol . $host . $baseUrl . "reviewer/register.php?token=" . urlencode((string)$r['invitation_token']);

        $tpl = [
            'title'          => 'Reviewer Registration Invitation',
            'greeting'       => "Dear " . ($r['full_name'] ?: 'Reviewer') . ",",
            'message'        => "You have been invited to register as a reviewer for The American Journal of Science and Medical Research (AJSMR).\n\nPlease complete your reviewer registration using the link below.\n\nReviewer email: {$r['email']}\n(This email address is associated with your reviewer invitation and cannot be changed during registration.)",
            'actionUrl'      => $regLink,
            'actionText'     => 'Complete Reviewer Registration'
        ];

        return ajsmr_dispatch_email($db, $r['email'], "AJSMR — Reviewer Registration Invitation", $tpl, 'REVIEWER_REGISTRATION_INVITATION', null);
    }
}
