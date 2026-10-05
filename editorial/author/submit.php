<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$u = role_required(['author']);
$pageTitle = 'Submit Manuscript — AJSMR';
$msg = '';
$err = '';

$pdo = db();

// Fetch logged-in author profile info from users table
$stmtUser = $pdo->prepare('SELECT id, full_name, email, affiliation, phone, orcid FROM users WHERE id = ? LIMIT 1');
$stmtUser->execute([(int)$u['id']]);
$authorProfile = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];

$authorName        = (string)($authorProfile['full_name'] ?? $u['name'] ?? $u['full_name'] ?? 'Author');
$authorEmail       = (string)($authorProfile['email'] ?? $u['email'] ?? '');
$authorAffiliation = (string)($authorProfile['affiliation'] ?? '');
$authorOrcid       = (string)($authorProfile['orcid'] ?? '');

$articleTypes = [
    'Research Article',
    'Review Article',
    'Short Communication',
    'Case Report',
    'Case Series',
    'Methodology Article',
    'Editorial'
];

$researchAreas = [
    'Biomedical Sciences',
    'Clinical Medicine',
    'Biological Sciences',
    'Health Sciences & Public Health',
    'Pharmaceutical Sciences',
    'Biotechnology & Genetics',
    'Multidisciplinary Science',
    'Other'
];

// Check if opening an existing draft or redirected after submission
$draftId     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$submittedNo = trim((string)($_GET['no'] ?? ''));

$submittedMs = null;
$draftMs     = null;

if (isset($_GET['success']) && $submittedNo !== '') {
    $stmtSubmitted = $pdo->prepare('SELECT * FROM manuscripts WHERE manuscript_no = ? AND (corresponding_author_id = ? OR corresponding_email = ?) LIMIT 1');
    $stmtSubmitted->execute([$submittedNo, (int)$u['id'], $authorEmail]);
    $submittedMs = $stmtSubmitted->fetch(PDO::FETCH_ASSOC);
    $msg = "Your manuscript has been successfully submitted.";
}

// Load draft if manuscript ID is passed in GET
if ($draftId && !$submittedMs) {
    $stmtDraft = $pdo->prepare('SELECT * FROM manuscripts WHERE id = ? AND (corresponding_author_id = ? OR corresponding_email = ?) LIMIT 1');
    $stmtDraft->execute([$draftId, (int)$u['id'], $authorEmail]);
    $draftMs = $stmtDraft->fetch(PDO::FETCH_ASSOC);

    if (!$draftMs) {
        $err = "Draft manuscript not found or you do not have authorization to edit it.";
    } elseif ($draftMs['status'] !== null && strtoupper((string)$draftMs['status']) !== 'DRAFT') {
        redirect('author/submissions.php?msg=already_submitted');
    }
}

// Load rehydrated files & authors for draft if active
$draftAuthors = [];
$draftFiles   = [];
$draftResearchArea = '';
$draftResearchAreaOther = '';
$draftCleanKeywords = '';

if ($draftMs) {
    $mid = (int)$draftMs['id'];

    // Extract research area and clean keywords from manuscripts.keywords column
    $rawKw = (string)($draftMs['keywords'] ?? '');
    if (preg_match('/^\[Area:\s*(.*?)\]\s*(.*)$/s', $rawKw, $kwMatches)) {
        $parsedArea = trim($kwMatches[1]);
        $draftCleanKeywords = trim($kwMatches[2]);
        if (in_array($parsedArea, $researchAreas, true) && $parsedArea !== 'Other') {
            $draftResearchArea = $parsedArea;
        } else {
            $draftResearchArea = 'Other';
            $draftResearchAreaOther = $parsedArea;
        }
    } else {
        $draftCleanKeywords = $rawKw;
    }

    // Load authors
    $stA = $pdo->prepare('SELECT * FROM manuscript_authors WHERE manuscript_id = ? ORDER BY author_order ASC, id ASC');
    $stA->execute([$mid]);
    $draftAuthors = $stA->fetchAll(PDO::FETCH_ASSOC);

    // Load files
    $stV = $pdo->prepare("
        SELECT id, manuscript_id, version_no, file_path,
               COALESCE(NULLIF(author_response, ''), 'main_manuscript') AS file_type,
               SUBSTRING_INDEX(file_path, '/', -1) AS original_name,
               created_at
        FROM manuscript_versions
        WHERE manuscript_id = ?
        ORDER BY version_no ASC, id ASC
    ");
    $stV->execute([$mid]);
    $rawFiles = $stV->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawFiles as $rf) {
        $diskPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim((string)$rf['file_path'], '/\\'));
        $rf['file_size'] = is_file($diskPath) ? filesize($diskPath) : 0;
        $draftFiles[] = $rf;
    }
}

// -----------------------------------------------------------------------------
// POST HANDLING (SAVE DRAFT / REMOVE FILE / FINAL SUBMIT)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();

        $action = trim((string)($_POST['action'] ?? 'submit_final'));
        $msId   = filter_input(INPUT_POST, 'manuscript_id', FILTER_VALIDATE_INT);

        // Security ownership check if modifying an existing manuscript ID
        $existingMs = null;
        if ($msId) {
            $stCheck = $pdo->prepare('SELECT * FROM manuscripts WHERE id = ? AND (corresponding_author_id = ? OR corresponding_email = ?) LIMIT 1');
            $stCheck->execute([$msId, (int)$u['id'], $authorEmail]);
            $existingMs = $stCheck->fetch(PDO::FETCH_ASSOC);

            if (!$existingMs) {
                throw new RuntimeException('Unauthorized access or manuscript draft not found.');
            }
        }

        // Handle File Removal Action
        if ($action === 'remove_file') {
            $fileId = filter_input(INPUT_POST, 'file_id', FILTER_VALIDATE_INT);
            if ($fileId && $existingMs) {
                $stF = $pdo->prepare('SELECT * FROM manuscript_versions WHERE id = ? AND manuscript_id = ? LIMIT 1');
                $stF->execute([$fileId, (int)$existingMs['id']]);
                $fRow = $stF->fetch(PDO::FETCH_ASSOC);

                if ($fRow) {
                    $pdo->prepare('DELETE FROM manuscript_versions WHERE id = ?')->execute([(int)$fRow['id']]);
                    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim((string)$fRow['file_path'], '/\\'));
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }

                    if ((string)$existingMs['manuscript_file'] === (string)$fRow['file_path']) {
                        $pdo->prepare('UPDATE manuscripts SET manuscript_file = NULL WHERE id = ?')->execute([(int)$existingMs['id']]);
                    }
                }
            }
            $targetStep = (int)($_POST['current_step'] ?? 1);
            redirect('author/submit.php?id=' . (int)$existingMs['id'] . '&step=' . $targetStep . '&msg=file_removed');
        }

        // Gather Metadata & Form Fields
        $title             = trim((string)($_POST['title'] ?? ''));
        $runningTitle      = trim((string)($_POST['running_title'] ?? ''));
        $articleType       = trim((string)($_POST['article_type'] ?? 'Research Article'));
        $abstract          = trim((string)($_POST['abstract'] ?? ''));
        $keywords          = trim((string)($_POST['keywords'] ?? ''));
        $researchAreaSelect= trim((string)($_POST['research_area'] ?? ''));
        $researchAreaOther = trim((string)($_POST['research_area_other'] ?? ''));

        if (!in_array($articleType, $articleTypes, true)) {
            $articleType = 'Research Article';
        }

        $effectiveResearchArea = ($researchAreaSelect === 'Other') ? $researchAreaOther : $researchAreaSelect;

        // Compile keywords field with Research Area metadata prefix for schema-safe persistence
        $keywordsToStore = $keywords;
        if ($effectiveResearchArea !== '') {
            $keywordsToStore = '[Area: ' . $effectiveResearchArea . '] ' . $keywords;
        }

        // Gather First Author Affiliation override if provided
        $firstAuthorAff = trim((string)($_POST['author1_affiliation'] ?? $authorAffiliation));
        if ($firstAuthorAff !== '' && $authorAffiliation === '') {
            $authorAffiliation = $firstAuthorAff;
        }

        // Gather Authors
        $authorsData = [];
        $rawNames    = $_POST['author_names'] ?? [];
        $rawEmails   = $_POST['author_emails'] ?? [];
        $rawAffs     = $_POST['author_affiliations'] ?? [];
        $rawOrcids   = $_POST['author_orcids'] ?? [];

        // Author 1 is always logged-in user (Primary / First Author)
        $authorsData[] = [
            'user_id'          => (int)$u['id'],
            'name'             => $authorName,
            'email'            => $authorEmail,
            'affiliation'      => $authorAffiliation,
            'orcid'            => $authorOrcid,
            'is_corresponding' => false,
            'order'            => 1
        ];

        // Process Co-Authors
        if (is_array($rawNames)) {
            for ($i = 0; $i < count($rawNames); $i++) {
                $cName  = trim((string)($rawNames[$i] ?? ''));
                $cEmail = trim((string)($rawEmails[$i] ?? ''));
                $cAff   = trim((string)($rawAffs[$i] ?? ''));
                $cOrcid = trim((string)($rawOrcids[$i] ?? ''));

                if ($cName !== '' || $cEmail !== '' || $cAff !== '') {
                    if ($action === 'submit_final') {
                        if ($cName === '') {
                            throw new RuntimeException("Author #" . ($i + 2) . " requires a full name.");
                        }
                        if ($cEmail === '') {
                            throw new RuntimeException("Author #" . ($i + 2) . " requires an email address.");
                        }
                        if ($cAff === '') {
                            throw new RuntimeException("Author #" . ($i + 2) . " requires an institution/affiliation.");
                        }
                    }

                    $cUid = null;
                    if ($cEmail !== '') {
                        $stChk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                        $stChk->execute([$cEmail]);
                        $cUid = $stChk->fetchColumn() ?: null;
                    }

                    $authorsData[] = [
                        'user_id'          => $cUid ? (int)$cUid : null,
                        'name'             => $cName,
                        'email'            => $cEmail !== '' ? $cEmail : null,
                        'affiliation'      => $cAff !== '' ? $cAff : null,
                        'orcid'            => $cOrcid !== '' ? $cOrcid : null,
                        'is_corresponding' => false,
                        'order'            => count($authorsData) + 1
                    ];
                }
            }
        }

        // Corresponding Author Selection
        $caIndex = (int)($_POST['corresponding_author_index'] ?? 0);
        if (!isset($authorsData[$caIndex])) {
            $caIndex = 0;
        }

        foreach ($authorsData as $idx => &$aItem) {
            $aItem['is_corresponding'] = ($idx === $caIndex);
        }
        unset($aItem);

        $caAuthor = $authorsData[$caIndex];
        $caEmail  = $caAuthor['email'] ?: $authorEmail;
        $caUserId = $caAuthor['user_id'] ?: (int)$u['id'];

        // Gather Step 4 Declarations
        $policyAgreed      = !empty($_POST['policy_agreed']);
        $competingStatus   = trim((string)($_POST['competing_status'] ?? 'no'));
        $competingDetails  = trim((string)($_POST['competing_details'] ?? ''));
        $originalityStatus = trim((string)($_POST['originality_status'] ?? 'no'));
        $originalityDetails= trim((string)($_POST['originality_details'] ?? ''));
        $authorshipAgreed  = !empty($_POST['authorship_agreed']);
        $thirdpartyStatus  = trim((string)($_POST['thirdparty_status'] ?? 'no'));
        $thirdpartyAgreed  = !empty($_POST['thirdparty_agreed']);
        $dataStatus        = trim((string)($_POST['data_status'] ?? 'no'));
        $dataDetails       = trim((string)($_POST['data_details'] ?? ''));
        $acknowledgements  = trim((string)($_POST['acknowledgements'] ?? ''));
        $fundingStatus     = trim((string)($_POST['funding_status'] ?? 'no'));
        $fundingOrg        = trim((string)($_POST['funding_org'] ?? ''));
        $fundingGrant      = trim((string)($_POST['funding_grant'] ?? ''));
        $fundingDetails    = trim((string)($_POST['funding_details'] ?? ''));

        // Compile Statements
        $compiledFunding = "";
        if ($fundingStatus === 'yes') {
            $fParts = [];
            if ($fundingOrg !== '') $fParts[] = "Organization: " . $fundingOrg;
            if ($fundingGrant !== '') $fParts[] = "Grant #: " . $fundingGrant;
            if ($fundingDetails !== '') $fParts[] = "Details: " . $fundingDetails;
            $compiledFunding = implode(" | ", $fParts);
        } else {
            $compiledFunding = "The authors declare that no research funding was received for this work.";
        }

        $compiledConflict = "";
        if ($competingStatus === 'yes') {
            $compiledConflict = $competingDetails !== '' ? $competingDetails : "Competing interests declared.";
        } else {
            $compiledConflict = "The authors declare no competing financial or non-financial interests.";
        }

        $compiledEthicsList = [];
        if ($policyAgreed) $compiledEthicsList[] = "8.1 Publishing Policy: Agreed.";
        if ($authorshipAgreed) $compiledEthicsList[] = "8.4 Authorship Confirmation: Confirmed by all listed authors.";
        if ($originalityStatus === 'yes') $compiledEthicsList[] = "8.3 Dual Submission Details: " . $originalityDetails;
        else $compiledEthicsList[] = "8.3 Originality: Manuscript is original and not under review elsewhere.";

        if ($thirdpartyStatus === 'yes' && $thirdpartyAgreed) {
            $compiledEthicsList[] = "8.5 Third-Party Material: Permissions confirmed.";
        }
        if ($dataStatus === 'yes' && $dataDetails !== '') {
            $compiledEthicsList[] = "8.6 Data Availability Statement: " . $dataDetails;
        }
        if ($acknowledgements !== '') {
            $compiledEthicsList[] = "8.7 Acknowledgements: " . $acknowledgements;
        }
        $compiledEthicsText = implode("\n\n", $compiledEthicsList);

        // Final Submission Validation
        if ($action === 'submit_final') {
            if ($articleType === '') {
                throw new RuntimeException('Article Type is required.');
            }
            if ($title === '') {
                throw new RuntimeException('Manuscript title is required.');
            }
            if ($abstract === '') {
                throw new RuntimeException('Abstract is required.');
            }
            if ($keywords === '') {
                throw new RuntimeException('Keywords are required.');
            }
            if (!$policyAgreed) {
                throw new RuntimeException('You must agree to the Publishing Policy (8.1) before submitting.');
            }
            if ($competingStatus === 'yes' && $competingDetails === '') {
                throw new RuntimeException('Please provide Competing Interests Details because you selected Yes (8.2).');
            }
            if ($originalityStatus === 'yes' && $originalityDetails === '') {
                throw new RuntimeException('Please provide Dual Submission Details because you selected Yes (8.3).');
            }
            if (!$authorshipAgreed) {
                throw new RuntimeException('You must check the Authorship Confirmation checkbox (8.4).');
            }
            if ($thirdpartyStatus === 'yes' && !$thirdpartyAgreed) {
                throw new RuntimeException('Please confirm Third-Party Material permissions because you selected Yes (8.5).');
            }
            if ($dataStatus === 'yes' && $dataDetails === '') {
                throw new RuntimeException('Please provide a Data Availability Statement because you selected Yes (8.6).');
            }
            if ($fundingStatus === 'yes' && $fundingOrg === '') {
                throw new RuntimeException('Please specify the Funding Organization because you selected Yes (8.8).');
            }
            if (empty($_POST['confirm_accuracy'])) {
                throw new RuntimeException('You must check the author confirmation checkbox before submitting.');
            }
        }

        // Generate or Reuse Manuscript No
        $manuscriptNo = $existingMs ? (string)$existingMs['manuscript_no'] : '';
        if ($manuscriptNo === '') {
            for ($i = 0; $i < 20; $i++) {
                $candidate = 'AJSMR-' . date('Y') . '-' . str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
                $chk = $pdo->prepare('SELECT id FROM manuscripts WHERE manuscript_no = ? LIMIT 1');
                $chk->execute([$candidate]);
                if (!$chk->fetchColumn()) {
                    $manuscriptNo = $candidate;
                    break;
                }
            }
            if ($manuscriptNo === '') {
                $manuscriptNo = 'AJSMR-' . date('Y') . '-' . time();
            }
        }

        // Storage Directory
        $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'manuscripts';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('Failed to initialize manuscript storage directory.');
        }

        // Handle Main Manuscript Upload
        $fMain = $_FILES['manuscript'] ?? null;
        $mainRelPath = $existingMs ? (string)($existingMs['manuscript_file'] ?? '') : '';

        if ($fMain && (int)($fMain['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $mainExt = strtolower(pathinfo((string)$fMain['name'], PATHINFO_EXTENSION));
            if (!in_array($mainExt, ALLOWED_MANUSCRIPT_EXT, true)) {
                throw new RuntimeException('Main Manuscript must be a PDF, DOC, or DOCX document.');
            }
            if ((int)$fMain['size'] > MAX_UPLOAD_BYTES) {
                throw new RuntimeException('Main Manuscript file exceeds the 25 MB limit.');
            }
            if ((int)$fMain['size'] <= 0) {
                throw new RuntimeException('Main Manuscript file cannot be empty.');
            }

            $mainStoredName = $manuscriptNo . '_main_' . bin2hex(random_bytes(4)) . '.' . $mainExt;
            $mainDestPath   = $uploadDir . DIRECTORY_SEPARATOR . $mainStoredName;

            if (!move_uploaded_file((string)$fMain['tmp_name'], $mainDestPath)) {
                throw new RuntimeException('Failed to save the Main Manuscript file.');
            }
            $mainRelPath = 'uploads/manuscripts/' . $mainStoredName;
        }

        if ($action === 'submit_final' && $mainRelPath === '') {
            throw new RuntimeException('A Main Manuscript file (PDF/DOC/DOCX) must be uploaded before submitting.');
        }

        // Handle Additional Files Upload (Direct file upload without category dropdown requirement)
        $addFiles = $_FILES['additional_files'] ?? null;
        $uploadedAddFiles = [];

        if ($addFiles && is_array($addFiles['name'])) {
            foreach ($addFiles['name'] as $idx => $origName) {
                if ($origName !== '' && (int)($addFiles['error'][$idx] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $aSize = (int)($addFiles['size'][$idx] ?? 0);
                    $aTmp  = (string)($addFiles['tmp_name'][$idx] ?? '');
                    $aExt  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if ($aSize > 0 && $aSize <= MAX_UPLOAD_BYTES && $aTmp !== '') {
                        $fCategory = 'supplementary';
                        $aStoredName = $manuscriptNo . '_supp_' . bin2hex(random_bytes(4)) . '.' . $aExt;
                        $aDestPath   = $uploadDir . DIRECTORY_SEPARATOR . $aStoredName;

                        if (move_uploaded_file($aTmp, $aDestPath)) {
                            $uploadedAddFiles[] = [
                                'file_type'     => $fCategory,
                                'relative_path' => 'uploads/manuscripts/' . $aStoredName
                            ];
                        }
                    }
                }
            }
        }

        // ---------------------------------------------------------------------
        // Database Transaction (Save Draft OR Submit Final)
        // ---------------------------------------------------------------------
        $pdo->beginTransaction();

        try {
            $finalStatus = ($action === 'submit_final') ? 'SUBMITTED' : null;

            if ($existingMs) {
                $stmtM = $pdo->prepare("
                    UPDATE manuscripts SET
                        title = ?,
                        abstract = ?,
                        keywords = ?,
                        article_type = ?,
                        corresponding_author_id = ?,
                        corresponding_email = ?,
                        funding_statement = ?,
                        conflict_statement = ?,
                        ethics_statement = ?,
                        manuscript_file = COALESCE(NULLIF(?, ''), manuscript_file),
                        status = COALESCE(?, status),
                        updated_at = NOW(),
                        submitted_at = IF(? = 'SUBMITTED', NOW(), submitted_at)
                    WHERE id = ?
                ");
                $stmtM->execute([
                    $title !== '' ? $title : 'Untitled Draft',
                    $abstract !== '' ? $abstract : null,
                    $keywordsToStore !== '' ? $keywordsToStore : null,
                    $articleType,
                    $caUserId,
                    $caEmail,
                    $compiledFunding,
                    $compiledConflict,
                    $compiledEthicsText,
                    $mainRelPath,
                    $finalStatus,
                    $finalStatus,
                    (int)$existingMs['id']
                ]);
                $mid = (int)$existingMs['id'];
            } else {
                $stmtM = $pdo->prepare("
                    INSERT INTO manuscripts (
                        manuscript_no, title, abstract, keywords, article_type,
                        corresponding_author_id, corresponding_email, status, version_no,
                        funding_statement, conflict_statement, ethics_statement,
                        manuscript_file, submitted_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, IF(? = 'SUBMITTED', NOW(), NOW()), NOW())
                ");
                $stmtM->execute([
                    $manuscriptNo,
                    $title !== '' ? $title : 'Untitled Draft',
                    $abstract !== '' ? $abstract : null,
                    $keywordsToStore !== '' ? $keywordsToStore : null,
                    $articleType,
                    $caUserId,
                    $caEmail,
                    $finalStatus,
                    $compiledFunding,
                    $compiledConflict,
                    $compiledEthicsText,
                    $mainRelPath !== '' ? $mainRelPath : null,
                    $finalStatus
                ]);
                $mid = (int)$pdo->lastInsertId();
            }

            // Sync manuscript_authors
            $pdo->prepare('DELETE FROM manuscript_authors WHERE manuscript_id = ?')->execute([$mid]);
            $stmtA = $pdo->prepare("
                INSERT INTO manuscript_authors (
                    manuscript_id, author_user_id, author_name, affiliation, email, author_order
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($authorsData as $a) {
                $stmtA->execute([
                    $mid,
                    $a['user_id'],
                    $a['name'] !== '' ? $a['name'] : 'Author',
                    $a['affiliation'],
                    $a['email'],
                    $a['order']
                ]);
            }

            // Sync manuscript_versions for Main Manuscript if newly uploaded
            if ($mainRelPath !== '') {
                $chkVer = $pdo->prepare('SELECT id FROM manuscript_versions WHERE manuscript_id = ? AND file_path = ? LIMIT 1');
                $chkVer->execute([$mid, $mainRelPath]);
                if (!$chkVer->fetchColumn()) {
                    $stmtV = $pdo->prepare("
                        INSERT INTO manuscript_versions (
                            manuscript_id, version_no, file_path, author_response, uploaded_by, created_at
                        ) VALUES (?, 1, ?, 'main_manuscript', ?, NOW())
                    ");
                    $stmtV->execute([$mid, $mainRelPath, (int)$u['id']]);
                }
            }

            // Insert Additional Files into manuscript_versions
            if (!empty($uploadedAddFiles)) {
                $stmtV = $pdo->prepare("
                    INSERT INTO manuscript_versions (
                        manuscript_id, version_no, file_path, author_response, uploaded_by, created_at
                    ) VALUES (?, 1, ?, ?, ?, NOW())
                ");
                foreach ($uploadedAddFiles as $uFile) {
                    $stmtV->execute([$mid, $uFile['relative_path'], $uFile['file_type'], (int)$u['id']]);
                }
            }

            // Audit logging
            if (function_exists('audit')) {
                if ($action === 'submit_final') {
                    audit('SUBMIT', $mid, "Author submitted manuscript {$manuscriptNo}: {$title}");
                } else {
                    audit('SAVE_DRAFT', $mid, "Author saved draft manuscript {$manuscriptNo}");
                }
            }

            $pdo->commit();

            if ($action === 'submit_final') {
                sendWorkflowNotification($pdo, 'MANUSCRIPT_SUBMITTED', $mid);
                redirect('author/submit.php?success=1&no=' . rawurlencode($manuscriptNo));
            } else {
                $targetStep = (int)($_POST['current_step'] ?? 1);
                redirect('author/submit.php?id=' . $mid . '&step=' . $targetStep . '&msg=draft_saved');
            }

        } catch (Throwable $dbEx) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Author manuscript submission DB failure: ' . $dbEx->getMessage());
            throw new RuntimeException('We could not complete your request at this time (' . $dbEx->getMessage() . '). Please try again.');
        }

    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

// Determine current step from GET or default to 1
$activeStep = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT) ?: 1;
if ($activeStep < 1 || $activeStep > 5) {
    $activeStep = 1;
}

if (isset($_GET['msg']) && $_GET['msg'] === 'draft_saved') {
    $msg = "Draft saved successfully.";
} elseif (isset($_GET['msg']) && $_GET['msg'] === 'file_removed') {
    $msg = "File removed successfully.";
}

include __DIR__ . '/../includes/header.php';
?>

<style>
/* CSS Variables & Tokens */
:root {
  --primary: #0b5fa5;
  --primary-hover: #094d86;
  --primary-light: #dbeafe;
  --primary-bg: #eff6ff;
  --dark-heading: #092b5f;
  --text-main: #0f172a;
  --text-body: #334155;
  --text-muted: #64748b;
  --border-color: #cbd5e1;
  --border-light: #e2e8f0;
  --bg-subtle: #f8fafc;
  --danger: #dc2626;
  --danger-light: #fef2f2;
  --danger-border: #fecaca;
  --danger-hover: #b91c1c;
  --success: #16a34a;
  --success-light: #f0fdf4;
  --success-border: #bbf7d0;
  --radius-sm: 6px;
  --radius-md: 8px;
  --transition-fast: 0.2s ease;
}

/* Global Control Standards */
.form-input,
.form-select,
.form-textarea {
  width: 100%;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 10px 14px;
  font-family: inherit;
  font-size: 14px;
  color: var(--text-main);
  background-color: #ffffff;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
  box-sizing: border-box;
}

.form-input {
  height: 42px;
}

.form-select {
  height: 42px;
  appearance: auto;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  border-color: var(--primary);
  outline: none;
  box-shadow: 0 0 0 3px var(--primary-light);
}

.form-input[readonly] {
  background-color: var(--border-light);
  color: var(--text-body);
  cursor: not-allowed;
}

/* Common Interactive Accent Rules */
input[type="radio"],
input[type="checkbox"] {
  accent-color: var(--primary);
  width: 17px;
  height: 17px;
  cursor: pointer;
}

/* Modern Submission Wizard Styles */
.sub-header-card {
  background: #ffffff;
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  padding: 16px 20px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.sub-header-title {
  font-size: 20px;
  font-weight: 800;
  color: var(--dark-heading);
  margin: 0 0 4px 0;
}

.sub-header-meta {
  font-size: 13px;
  color: var(--text-muted);
  display: flex;
  gap: 16px;
  align-items: center;
}

.wizard-steps {
  display: flex;
  justify-content: space-between;
  margin-bottom: 24px;
  background: #ffffff;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  overflow-x: auto;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}

.wizard-step {
  flex: 1;
  min-width: 100px;
  text-align: center;
  cursor: pointer;
  padding: 6px 4px;
  user-select: none;
  transition: opacity var(--transition-fast);
}

.wizard-step:hover {
  opacity: 0.85;
}

.wizard-step .step-number {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--border-light);
  color: var(--text-muted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
  margin-bottom: 6px;
  transition: all var(--transition-fast);
}

.wizard-step.active .step-number {
  background: var(--primary);
  color: #ffffff;
  box-shadow: 0 0 0 4px var(--primary-light);
}

.wizard-step.completed .step-number {
  background: var(--success);
  color: #ffffff;
}

.wizard-step .step-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  display: block;
}

.wizard-step.active .step-label {
  color: var(--primary);
  font-weight: 700;
}

.step-content {
  display: none;
}

.step-content.active {
  display: block;
}

.guidelines-card {
  background: var(--bg-subtle);
  border: 1px solid var(--border-color);
  border-left: 5px solid var(--primary);
  border-radius: var(--radius-md);
  padding: 18px 22px;
  margin-bottom: 24px;
}

.guidelines-card h3 {
  margin-top: 0;
  margin-bottom: 12px;
  color: var(--dark-heading);
  font-size: 15px;
}

.guidelines-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 14px;
}

.guideline-item {
  background: #ffffff;
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
  padding: 12px 14px;
}

.guideline-item strong {
  color: #1e293b;
  display: block;
  margin-bottom: 4px;
  font-size: 13px;
}

.guideline-item p {
  margin: 0;
  font-size: 12px;
  color: #475569;
  line-height: 1.4;
}

.form-section-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-main);
  margin-bottom: 16px;
  padding-bottom: 8px;
  border-bottom: 2px solid #f1f5f9;
}

.file-card {
  background: #ffffff;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.file-card-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.file-card-icon {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  background: var(--primary-bg);
  color: #1d4ed8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 12px;
}

.author-card {
  background: var(--bg-subtle);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  padding: 16px;
  margin-bottom: 14px;
}

.author-card.first-author {
  border-left: 4px solid var(--primary);
  background: #f0f7ff;
}

.summary-block {
  background: var(--bg-subtle);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  padding: 16px;
  margin-bottom: 16px;
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  border-bottom: 1px solid var(--border-light);
  padding-bottom: 6px;
}

.summary-title {
  font-weight: 700;
  font-size: 14px;
  color: var(--text-main);
}

/* Polished Step 4 Declaration Styles */
.declaration-card {
  background: #ffffff;
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  padding: 18px 20px;
  margin-bottom: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.declaration-card:focus-within,
.declaration-card:hover {
  border-color: var(--border-color);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.declaration-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.declaration-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-main);
}

.req-badge {
  background: var(--danger-light);
  color: #b42318;
  border: 1px solid var(--danger-border);
  font-size: 11px;
  padding: 2px 8px;
  font-weight: 600;
  border-radius: 12px;
}

.opt-badge {
  background: #f1f5f9;
  color: var(--text-muted);
  border: 1px solid var(--border-light);
  font-size: 11px;
  padding: 2px 8px;
  font-weight: 600;
  border-radius: 12px;
}

.declaration-question {
  font-size: 13.5px;
  color: var(--text-body);
  margin-bottom: 10px;
  line-height: 1.45;
}

.declaration-options {
  display: flex;
  gap: 24px;
  font-size: 13.5px;
  margin-bottom: 4px;
}

.radio-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 500;
  color: #1e293b;
  user-select: none;
}

.declaration-checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13.5px;
  font-weight: 500;
  color: #1e293b;
  cursor: pointer;
  line-height: 1.45;
  user-select: none;
}

.conditional-block {
  display: none;
  margin-top: 12px;
  padding: 14px 16px;
  background: var(--bg-subtle);
  border: 1px solid var(--border-light);
  border-left: 4px solid var(--primary);
  border-radius: var(--radius-sm);
}

.conditional-label {
  display: block;
  font-weight: 600;
  font-size: 13px;
  color: #1e293b;
  margin-bottom: 6px;
}
</style>

<!-- Submission Header -->
<div class="sub-header-card">
  <div>
    <h1 class="sub-header-title">Submit Manuscript</h1>
    <div class="sub-header-meta">
      <span><strong>Manuscript ID:</strong> <?=e($draftMs ? $draftMs['manuscript_no'] : 'Creating Draft...')?></span>
      <span><strong>Status:</strong> <span class="badge" style="background:#e0f2fe;color:#0369a1;"><?=e($draftMs && $draftMs['status'] ? slabel($draftMs['status']) : 'Draft')?></span></span>
    </div>
  </div>
  <div>
    <a href="submissions.php" class="btn secondary">← My Manuscripts</a>
  </div>
</div>

<?php if ($msg !== ''): ?>
  <div class="alert success" style="margin-bottom:20px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:12px 16px;border-radius:6px;">
    ✓ <?=e($msg)?>
  </div>
<?php endif; ?>

<?php if ($submittedMs): ?>
  <div class="panel" style="background:#effaf3;border:1px solid #c9efd7;border-left:5px solid #16a34a;padding:22px;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
      <h2 style="margin:0;font-size:18px;color:#166534;">Submission Successful!</h2>
      <span class="badge" style="background:#dcfce7;color:#166534;font-size:13px;padding:3px 10px;font-weight:bold;">Submitted</span>
    </div>
    <p style="margin:0 0 14px 0;color:#334155;font-size:14px;line-height:1.5;">
      Your manuscript has been successfully submitted and entered into the AJSMR EIC editorial review pipeline.
    </p>
    <div style="background:#fff;border:1px solid #bbf7d0;border-radius:6px;padding:14px 18px;margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px;">
      <div>
        <span style="font-size:12px;font-weight:bold;color:#64748b;text-transform:uppercase;">Manuscript ID</span><br>
        <strong style="font-size:17px;color:#0b5fa5;"><?=e($submittedMs['manuscript_no'])?></strong>
      </div>
      <div>
        <span style="font-size:12px;font-weight:bold;color:#64748b;text-transform:uppercase;">Article Type</span><br>
        <span style="font-weight:bold;color:#1e293b;"><?=e($submittedMs['article_type'])?></span>
      </div>
      <div style="grid-column:1/-1;">
        <span style="font-size:12px;font-weight:bold;color:#64748b;text-transform:uppercase;">Title</span><br>
        <span style="color:#0f172a;font-weight:bold;"><?=e($submittedMs['title'])?></span>
      </div>
    </div>
    <div style="display:flex;gap:10px;">
      <a href="submissions.php" class="btn primary" style="padding:9px 16px;font-size:13px;">View My Manuscripts</a>
      <a href="../manuscript_view.php?id=<?= (int)$submittedMs['id'] ?>" class="btn secondary" style="padding:9px 16px;font-size:13px;">View Details ↗</a>
      <a href="submit.php" class="btn secondary" style="padding:9px 16px;font-size:13px;margin-left:auto;">+ Submit Another</a>
    </div>
  </div>
<?php endif; ?>

<?php if ($err !== ''): ?>
  <div class="alert err" style="margin-bottom:20px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:12px 16px;border-radius:6px;">
    <strong>Submission Error:</strong> <?=e($err)?>
  </div>
<?php endif; ?>

<!-- Informational Guidelines Panel (Before Step 1) -->
<div class="guidelines-card">
  <h3>📋 INITIAL MANUSCRIPT SUBMISSION GUIDELINES</h3>
  <div class="guidelines-grid">
    <div class="guideline-item">
      <strong>1. Scope and Originality</strong>
      <p>Submit your full-length article. Ensure it is an original manuscript not under review elsewhere and falls within the scope of AJSMR.</p>
    </div>
    <div class="guideline-item">
      <strong>2. Manuscript Quality</strong>
      <p>Ensure proper grammar, formatting, references, and academic presentation. Check the manuscript carefully before uploading.</p>
    </div>
    <div class="guideline-item">
      <strong>3. Authorship and Affiliation</strong>
      <p>Include full names, affiliations, and emails of all authors. Clearly designate the Corresponding Author.</p>
    </div>
    <div class="guideline-item">
      <strong>4. File Format and Submission</strong>
      <p>Submit the manuscript in MS Word (.doc / .docx) or PDF format. Maximum file size allowed: 25 MB.</p>
    </div>
  </div>
</div>

<!-- 5-Step Submission Stepper -->
<div class="wizard-steps">
  <div class="wizard-step <?=($activeStep===1?'active':'')?>" id="step-nav-1" onclick="goToStep(1)">
    <div class="step-number">01</div>
    <span class="step-label">Files &amp; Type</span>
  </div>
  <div class="wizard-step <?=($activeStep===2?'active':'')?>" id="step-nav-2" onclick="goToStep(2)">
    <div class="step-number">02</div>
    <span class="step-label">Details</span>
  </div>
  <div class="wizard-step <?=($activeStep===3?'active':'')?>" id="step-nav-3" onclick="goToStep(3)">
    <div class="step-number">03</div>
    <span class="step-label">Authors</span>
  </div>
  <div class="wizard-step <?=($activeStep===4?'active':'')?>" id="step-nav-4" onclick="goToStep(4)">
    <div class="step-number">04</div>
    <span class="step-label">Declarations</span>
  </div>
  <div class="wizard-step <?=($activeStep===5?'active':'')?>" id="step-nav-5" onclick="goToStep(5)">
    <div class="step-number">05</div>
    <span class="step-label">Review</span>
  </div>
</div>

<!-- Form Container -->
<div class="panel">
  <form method="post" enctype="multipart/form-data" id="submissionForm" onsubmit="return validateFinalSubmit(this);">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input type="hidden" name="action" id="formAction" value="submit_final">
    <input type="hidden" name="manuscript_id" value="<?=e($draftMs ? (string)$draftMs['id'] : '')?>">
    <input type="hidden" name="current_step" id="currentStepInput" value="<?=(int)$activeStep?>">

    <!-- =================================================================== -->
    <!-- STEP 1 — MANUSCRIPT FILES & ARTICLE TYPE                            -->
    <!-- =================================================================== -->
    <div class="step-content <?=($activeStep===1?'active':'')?>" id="step-content-1">
      <div class="form-section-title">Step 1 — Manuscript Files &amp; Article Type</div>
      <div class="muted" style="margin-bottom:16px;">Upload your manuscript file, any additional files, and select the Article Type for your submission.</div>

      <!-- Main Manuscript Section -->
      <div style="margin-bottom:20px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:16px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;font-size:14px;color:#0f172a;">
          Main Manuscript Upload *
        </label>

        <?php
        $mainFileRow = null;
        foreach ($draftFiles as $df) {
            if ($df['file_type'] === 'main_manuscript') {
                $mainFileRow = $df;
                break;
            }
        }
        ?>

        <?php if ($mainFileRow): ?>
          <div class="file-card" id="mainFileCard">
            <div class="file-card-info">
              <div class="file-card-icon">📄</div>
              <div>
                <strong style="color:#0f172a;font-size:14px;"><?=e($mainFileRow['original_name'])?></strong>
                <div style="font-size:12px;color:#64748b;">
                  <?=number_format(((int)$mainFileRow['file_size'])/1024/1024, 2)?> MB &bull; Main Manuscript &bull; Uploaded
                </div>
              </div>
            </div>
            <div style="display:flex;gap:8px;">
              <button type="button" class="btn secondary" style="padding:5px 12px;font-size:12px;" onclick="triggerFileReplace();">Replace File</button>
              <button type="button" class="btn err" style="padding:5px 12px;font-size:12px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:4px;" onclick="removeUploadedFile(<?=(int)$mainFileRow['id']?>);">Remove</button>
            </div>
          </div>
          <input type="file" name="manuscript" id="mainManuscriptInput" accept=".pdf,.doc,.docx" style="display:none;" onchange="updateSummaryPreview();">
        <?php else: ?>
          <input type="file" name="manuscript" id="mainManuscriptInput" accept=".pdf,.doc,.docx" style="display:block;margin-top:6px;" onchange="updateSummaryPreview();">
          <small class="muted" style="display:block;margin-top:6px;color:#64748b;">
            Required. Allowed formats: <strong>PDF, DOC, DOCX</strong>. Maximum file size: <strong>25 MB</strong>.
          </small>
        <?php endif; ?>
      </div>

      <!-- Additional Supporting Files (Simple Direct Upload - No Dropdown) -->
      <div style="margin-bottom:24px;background:#ffffff;border:1px solid #e2e8f0;border-radius:6px;padding:16px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;font-size:14px;color:#0f172a;">
          Additional Files (Optional)
        </label>
        <div class="muted" style="margin-bottom:14px;font-size:13px;color:#64748b;">
          Upload any figures, tables, supplementary files, or cover letters directly. No file category selection required.
        </div>

        <?php foreach ($draftFiles as $df): ?>
          <?php if ($df['file_type'] !== 'main_manuscript'): ?>
            <div class="file-card">
              <div class="file-card-info">
                <div class="file-card-icon" style="background:#f3e8ff;color:#7e22ce;">📎</div>
                <div>
                  <strong style="color:#0f172a;font-size:13px;"><?=e($df['original_name'])?></strong>
                  <div style="font-size:11px;color:#64748b;">
                    <?=number_format(((int)$df['file_size'])/1024, 1)?> KB &bull; Uploaded File
                  </div>
                </div>
              </div>
              <button type="button" class="btn light" style="padding:4px 10px;font-size:11px;color:#ef4444;" onclick="removeUploadedFile(<?=(int)$df['id']?>);">Remove</button>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>

        <div id="additionalFilesContainer"></div>
        <button type="button" class="btn secondary" style="font-size:13px;padding:7px 14px;margin-top:6px;" onclick="addAdditionalFileRow();">
          + Upload Additional File
        </button>
      </div>

      <!-- Article Type Field (MOVED FROM STEP 2 TO STEP 1) -->
      <div style="margin-bottom:20px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:16px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;font-size:14px;color:#0f172a;">
          Article Type *
        </label>
        <div class="muted" style="margin-bottom:10px;font-size:13px;color:#64748b;">
          Select the manuscript category that best matches your submission.
        </div>
        <select name="article_type" id="article_type" required class="form-select" onchange="updateSummaryPreview();">
          <?php
          $curType = $draftMs ? (string)$draftMs['article_type'] : ($_POST['article_type'] ?? 'Research Article');
          foreach ($articleTypes as $type):
          ?>
            <option value="<?=e($type)?>" <?=($curType === $type ? 'selected' : '')?>><?=e($type)?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="border-top:1px solid #edf0f4;padding-top:16px;display:flex;justify-content:space-between;align-items:center;">
        <button type="button" class="btn secondary" onclick="saveDraftAction();">Save Draft</button>
        <button type="button" class="btn primary" onclick="nextStep(1);">Next: Details →</button>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- STEP 2 — MANUSCRIPT DETAILS                                         -->
    <!-- =================================================================== -->
    <div class="step-content <?=($activeStep===2?'active':'')?>" id="step-content-2">
      <div class="form-section-title">Step 2 — Manuscript Details</div>
      <div class="muted" style="margin-bottom:16px;">Describe the subject area, manuscript title, abstract, and keywords.</div>

      <!-- Research Area / Subject with "Other" Support -->
      <div style="margin-bottom:18px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;">Research Area / Subject *</label>
        <?php
        $selectedArea = $_POST['research_area'] ?? $draftResearchArea;
        if ($selectedArea === '' && !empty($researchAreas)) {
            $selectedArea = $researchAreas[0];
        }
        $otherAreaVal = $_POST['research_area_other'] ?? $draftResearchAreaOther;
        ?>
        <select name="research_area" id="research_area" class="form-select" onchange="toggleResearchAreaOther(); updateSummaryPreview();">
          <?php foreach ($researchAreas as $area): ?>
            <option value="<?=e($area)?>" <?=($selectedArea === $area ? 'selected' : '')?>><?=e($area)?></option>
          <?php endforeach; ?>
        </select>
        
        <div id="researchAreaOtherBlock" style="display: <?=($selectedArea === 'Other' ? 'block' : 'none')?>; margin-top: 10px;">
          <label style="display:block;font-weight:600;margin-bottom:4px;font-size:13px;color:#1e293b;">Other Research Area / Subject *</label>
          <input type="text" name="research_area_other" id="research_area_other" class="form-input" value="<?=e($otherAreaVal)?>" placeholder="Enter custom research area" oninput="updateSummaryPreview();">
        </div>
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;">Manuscript Title *</label>
        <input type="text" name="title" id="title" class="form-input" value="<?=e($draftMs ? (string)$draftMs['title'] : ($_POST['title'] ?? ''))?>" required placeholder="Full title of the manuscript" oninput="updateSummaryPreview();">
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;">Short / Running Title</label>
        <input type="text" name="running_title" id="running_title" class="form-input" value="<?=e($_POST['running_title'] ?? '')?>" placeholder="Abbreviated title for header/running head">
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;">Abstract *</label>
        <textarea name="abstract" id="abstract" rows="6" required placeholder="Enter manuscript abstract..." class="form-textarea" oninput="updateSummaryPreview();"><?=e($draftMs ? (string)$draftMs['abstract'] : ($_POST['abstract'] ?? ''))?></textarea>
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block;font-weight:bold;margin-bottom:6px;">Keywords *</label>
        <input type="text" name="keywords" id="keywords" class="form-input" value="<?=e($draftMs ? $draftCleanKeywords : ($_POST['keywords'] ?? ''))?>" required placeholder="e.g. Biomedical Science, Oncology, Methodology (comma separated)" oninput="updateSummaryPreview();">
      </div>

      <div style="border-top:1px solid #edf0f4;padding-top:16px;display:flex;justify-content:space-between;align-items:center;">
        <button type="button" class="btn secondary" onclick="goToStep(1);">← Back</button>
        <div>
          <button type="button" class="btn secondary" onclick="saveDraftAction();" style="margin-right:8px;">Save Draft</button>
          <button type="button" class="btn primary" onclick="nextStep(2);">Next: Authors →</button>
        </div>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- STEP 3 — AUTHOR DETAILS                                             -->
    <!-- =================================================================== -->
    <div class="step-content <?=($activeStep===3?'active':'')?>" id="step-content-3">
      <div class="form-section-title">Step 3 — Author Details</div>
      <div class="muted" style="margin-bottom:16px;">Your account is automatically identified as the Primary/First Author. You may add co-authors and designate the Corresponding Author.</div>

      <!-- Step 3 Validation Error Alert -->
      <div id="step3ErrorBox" class="alert err" style="display:none;margin-bottom:16px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:6px;font-size:13px;"></div>

      <?php
      $caEmailInDraft = $draftMs ? (string)$draftMs['corresponding_email'] : $authorEmail;
      $hasCoAuthorsInDraft = count($draftAuthors) > 1;
      ?>

      <!-- Author 1 (First/Primary Author - Logged-in User) -->
      <div class="author-card first-author">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <div>
            <strong style="color:#0b5fa5;font-size:15px;"><?=e($authorName)?></strong>
            <span class="badge" style="background:#dbeafe;color:#1e40af;margin-left:8px;font-size:12px;padding:3px 8px;">Primary / First Author</span>
          </div>
          <label style="font-size:13px;font-weight:bold;color:#1e293b;cursor:pointer;display:flex;align-items:center;gap:6px;">
            <input type="radio" name="corresponding_author_index" value="0" <?=($caEmailInDraft === $authorEmail || !$hasCoAuthorsInDraft ? 'checked' : '')?> onchange="renderAuthorsSummary();">
            Corresponding Author
          </label>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
          <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#475569;">Author Name *</label>
            <input type="text" readonly value="<?=e($authorName)?>" class="form-input">
          </div>
          <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#475569;">Email Address *</label>
            <input type="text" readonly value="<?=e($authorEmail)?>" class="form-input">
          </div>
          <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#475569;">Primary Institution / Affiliation *</label>
            <input type="text" name="author1_affiliation" id="author1_affiliation" value="<?=e($authorAffiliation)?>" required class="form-input" placeholder="Institution / Affiliation" oninput="updateSummaryPreview();">
          </div>
          <div>
            <label style="display:block;font-weight:600;margin-bottom:4px;color:#475569;">ORCID <small class="muted">(Optional)</small></label>
            <input type="text" readonly value="<?=e($authorOrcid !== '' ? $authorOrcid : 'Not specified')?>" class="form-input">
          </div>
        </div>
      </div>

      <!-- Co-Authors List -->
      <div id="coAuthorsContainer">
        <?php
        if ($hasCoAuthorsInDraft) {
            foreach ($draftAuthors as $idx => $da) {
                if ($idx === 0) continue;
                $cIndex = $idx;
                $isCaThis = ((string)$da['email'] === $caEmailInDraft);
                ?>
                <div class="author-card" id="coAuthorCard_<?=$cIndex?>">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div>
                      <strong style="color:#1e293b;font-size:14px;">Author <?=$cIndex + 1?> (Co-Author)</strong>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                      <label style="font-size:13px;font-weight:bold;color:#1e293b;cursor:pointer;display:flex;align-items:center;gap:6px;">
                        <input type="radio" name="corresponding_author_index" value="<?=$cIndex?>" <?=($isCaThis ? 'checked' : '')?> onchange="renderAuthorsSummary();">
                        Corresponding Author
                      </label>
                      <button type="button" class="btn err" style="padding:3px 10px;font-size:12px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:4px;" onclick="removeCoAuthorCard(<?=$cIndex?>);">Remove</button>
                    </div>
                  </div>
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
                    <div>
                      <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">Author Name *</label>
                      <input type="text" name="author_names[]" required value="<?=e($da['author_name'])?>" class="form-input co-author-name" placeholder="Full name" oninput="updateSummaryPreview();">
                    </div>
                    <div>
                      <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">Email Address *</label>
                      <input type="email" name="author_emails[]" required value="<?=e($da['email'] ?? '')?>" class="form-input co-author-email" placeholder="author@institution.edu" oninput="updateSummaryPreview();">
                    </div>
                    <div>
                      <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">Primary Institution / Affiliation *</label>
                      <input type="text" name="author_affiliations[]" required value="<?=e($da['affiliation'] ?? '')?>" class="form-input co-author-aff" placeholder="University / Department" oninput="updateSummaryPreview();">
                    </div>
                    <div>
                      <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">ORCID <small class="muted">(Optional)</small></label>
                      <input type="text" name="author_orcids[]" value="" class="form-input co-author-orcid" placeholder="0000-0000-0000-0000">
                    </div>
                  </div>
                </div>
                <?php
            }
        }
        ?>
      </div>

      <button type="button" class="btn secondary" style="font-size:13px;padding:8px 16px;margin-bottom:20px;" onclick="addCoAuthorCard();">
        + Add Author
      </button>

      <div style="border-top:1px solid #edf0f4;padding-top:16px;display:flex;justify-content:space-between;align-items:center;">
        <button type="button" class="btn secondary" onclick="goToStep(2);">← Back</button>
        <div>
          <button type="button" class="btn secondary" onclick="saveDraftAction();" style="margin-right:8px;">Save Draft</button>
          <button type="button" class="btn primary" onclick="nextStep(3);">Next: Declarations →</button>
        </div>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- STEP 4 — DECLARATIONS & POLICIES                                   -->
    <!-- =================================================================== -->
    <div class="step-content <?=($activeStep===4?'active':'')?>" id="step-content-4">
      <div class="form-section-title">Step 4 — Declarations &amp; Policies</div>
      <div class="muted" style="margin-bottom:20px;font-size:13px;color:#64748b;">
        Review these declarations carefully. Required confirmations become part of the official submission record.
      </div>

      <!-- Step 4 Validation Error Box -->
      <div id="step4ErrorBox" class="alert err" style="display:none;margin-bottom:16px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:6px;font-size:13px;"></div>

      <!-- 8.1 Publishing Policy -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.1 Publishing Policy</span>
          <span class="req-badge">* Required</span>
        </div>
        <label class="declaration-checkbox-label">
          <input type="checkbox" name="policy_agreed" id="policy_agreed" value="1" checked required style="margin-top:2px;">
          <span>I have read and understood the journal's publishing policy and agree to submit this manuscript according to the policy.</span>
        </label>
      </div>

      <!-- 8.2 Competing Interests -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.2 Competing Interests</span>
          <span class="req-badge">* Required</span>
        </div>
        <div class="declaration-question">
          Do any authors have financial or non-financial interests that could reasonably be viewed as related to this work?
        </div>
        <div class="declaration-options">
          <label class="radio-label">
            <input type="radio" name="competing_status" value="no" checked onchange="toggleConditional('competingDetailsBlock', false);">
            <span>No</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="competing_status" value="yes" onchange="toggleConditional('competingDetailsBlock', true);">
            <span>Yes</span>
          </label>
        </div>
        <div class="conditional-block" id="competingDetailsBlock">
          <label class="conditional-label">Competing Interests Details *</label>
          <textarea name="competing_details" id="competing_details" rows="3" placeholder="Describe the relevant financial or non-financial interests." class="form-textarea"><?=e($_POST['competing_details'] ?? '')?></textarea>
        </div>
      </div>

      <!-- 8.3 Originality / Dual Submission -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.3 Originality / Dual Submission</span>
          <span class="req-badge">* Required</span>
        </div>
        <div class="declaration-question">
          Have the results, data, or figures in this manuscript been published elsewhere or submitted to another publisher?
        </div>
        <div class="declaration-options">
          <label class="radio-label">
            <input type="radio" name="originality_status" value="no" checked onchange="toggleConditional('originalityDetailsBlock', false);">
            <span>No</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="originality_status" value="yes" onchange="toggleConditional('originalityDetailsBlock', true);">
            <span>Yes</span>
          </label>
        </div>
        <div class="conditional-block" id="originalityDetailsBlock">
          <label class="conditional-label">Dual Submission Details *</label>
          <textarea name="originality_details" id="originality_details" rows="3" placeholder="Explain the related publication or submission and how it overlaps with this manuscript." class="form-textarea"><?=e($_POST['originality_details'] ?? '')?></textarea>
        </div>
      </div>

      <!-- 8.4 Authorship Confirmation -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.4 Authorship Confirmation</span>
          <span class="req-badge">* Required</span>
        </div>
        <label class="declaration-checkbox-label">
          <input type="checkbox" name="authorship_agreed" id="authorship_agreed" value="1" checked required style="margin-top:2px;">
          <span>I confirm that all listed authors have approved this manuscript and agree to its submission to the journal.</span>
        </label>
      </div>

      <!-- 8.5 Third-Party Material -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.5 Third-Party Material</span>
          <span class="req-badge">* Required</span>
        </div>
        <div class="declaration-question">
          Does the manuscript contain figures, images, tables, text, or data belonging to a third party?
        </div>
        <div class="declaration-options">
          <label class="radio-label">
            <input type="radio" name="thirdparty_status" value="no" checked onchange="toggleConditional('thirdpartyBlock', false);">
            <span>No</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="thirdparty_status" value="yes" onchange="toggleConditional('thirdpartyBlock', true);">
            <span>Yes</span>
          </label>
        </div>
        <div class="conditional-block" id="thirdpartyBlock">
          <label class="declaration-checkbox-label">
            <input type="checkbox" name="thirdparty_agreed" id="thirdparty_agreed" value="1" style="margin-top:2px;">
            <span>I confirm that the required permissions are available and can be provided to the journal upon request. *</span>
          </label>
        </div>
      </div>

      <!-- 8.6 Data Availability -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.6 Data Availability</span>
          <span class="req-badge">* Required</span>
        </div>
        <div class="declaration-question">
          Have you used or generated research data in this study?
        </div>
        <div class="declaration-options">
          <label class="radio-label">
            <input type="radio" name="data_status" value="no" checked onchange="toggleConditional('dataBlock', false);">
            <span>No / Not Applicable</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="data_status" value="yes" onchange="toggleConditional('dataBlock', true);">
            <span>Yes</span>
          </label>
        </div>
        <div class="conditional-block" id="dataBlock">
          <label class="conditional-label">Data Availability Statement *</label>
          <textarea name="data_details" id="data_details" rows="3" placeholder="Describe where the research data can be accessed, or explain any restrictions on availability." class="form-textarea"><?=e($_POST['data_details'] ?? '')?></textarea>
        </div>
      </div>

      <!-- 8.7 Acknowledgements -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.7 Acknowledgements</span>
          <span class="opt-badge">(Optional)</span>
        </div>
        <textarea name="acknowledgements" id="acknowledgements" rows="3" placeholder="Acknowledge contributors, facilities, technical assistance, or materials..." class="form-textarea"><?=e($_POST['acknowledgements'] ?? '')?></textarea>
      </div>

      <!-- 8.8 Research Funding -->
      <div class="declaration-card">
        <div class="declaration-header">
          <span class="declaration-title">8.8 Research Funding</span>
          <span class="req-badge">* Required</span>
        </div>
        <div class="declaration-question">
          Is the research supported by funding?
        </div>
        <div class="declaration-options">
          <label class="radio-label">
            <input type="radio" name="funding_status" value="no" checked onchange="toggleConditional('fundingBlock', false);">
            <span>No</span>
          </label>
          <label class="radio-label">
            <input type="radio" name="funding_status" value="yes" onchange="toggleConditional('fundingBlock', true);">
            <span>Yes</span>
          </label>
        </div>
        <div class="conditional-block" id="fundingBlock">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px;">
            <div>
              <label class="conditional-label">Funding Organization *</label>
              <input type="text" name="funding_org" id="funding_org" placeholder="Granting Agency / Sponsor" class="form-input" value="<?=e($_POST['funding_org'] ?? '')?>">
            </div>
            <div>
              <label class="conditional-label">Grant Number <small class="muted">(Optional)</small></label>
              <input type="text" name="funding_grant" id="funding_grant" placeholder="e.g. NIH R01-12345" class="form-input" value="<?=e($_POST['funding_grant'] ?? '')?>">
            </div>
          </div>
          <div>
            <label class="conditional-label">Funding Details <small class="muted">(Optional)</small></label>
            <textarea name="funding_details" id="funding_details" rows="2" placeholder="Additional funding details..." class="form-textarea"><?=e($_POST['funding_details'] ?? '')?></textarea>
          </div>
        </div>
      </div>

      <div style="border-top:1px solid #edf0f4;padding-top:16px;display:flex;justify-content:space-between;align-items:center;">
        <button type="button" class="btn secondary" onclick="goToStep(3);">← Back</button>
        <div>
          <button type="button" class="btn secondary" onclick="saveDraftAction();" style="margin-right:8px;">Save Draft</button>
          <button type="button" class="btn primary" onclick="nextStep(4);">Next: Review →</button>
        </div>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- STEP 5 — REVIEW & SUBMIT                                            -->
    <!-- =================================================================== -->
    <div class="step-content <?=($activeStep===5?'active':'')?>" id="step-content-5">
      <div class="form-section-title">Step 5 — Review &amp; Submit</div>
      <div class="muted" style="margin-bottom:16px;">Check every section carefully before submitting your manuscript.</div>

      <div class="summary-block">
        <div class="summary-header">
          <div class="summary-title">📄 MANUSCRIPT DETAILS &amp; TYPE</div>
          <button type="button" class="btn light" style="padding:3px 8px;font-size:11px;" onclick="goToStep(1);">[Edit]</button>
        </div>
        <div style="font-size:13px;line-height:1.5;">
          <strong>Article Type:</strong> <span id="sum_article_type">—</span><br>
          <strong>Research Area / Subject:</strong> <span id="sum_research_area">—</span><br>
          <strong>Title:</strong> <span id="sum_title">—</span><br>
          <strong>Abstract:</strong> <div id="sum_abstract" style="color:#475569;margin-top:4px;white-space:pre-wrap;">—</div>
          <strong>Keywords:</strong> <span id="sum_keywords">—</span>
        </div>
      </div>

      <div class="summary-block">
        <div class="summary-header">
          <div class="summary-title">📁 FILES</div>
          <button type="button" class="btn light" style="padding:3px 8px;font-size:11px;" onclick="goToStep(1);">[Edit]</button>
        </div>
        <div style="font-size:13px;" id="sum_files_list"></div>
      </div>

      <div class="summary-block">
        <div class="summary-header">
          <div class="summary-title">👥 AUTHORS</div>
          <button type="button" class="btn light" style="padding:3px 8px;font-size:11px;" onclick="goToStep(3);">[Edit]</button>
        </div>
        <div style="font-size:13px;" id="sum_authors_list"></div>
      </div>

      <div class="summary-block">
        <div class="summary-header">
          <div class="summary-title">📜 DECLARATIONS &amp; POLICIES</div>
          <button type="button" class="btn light" style="padding:3px 8px;font-size:11px;" onclick="goToStep(4);">[Edit]</button>
        </div>
        <div style="font-size:13px;line-height:1.6;" id="sum_declarations_list"></div>
      </div>

      <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;padding:14px;margin-bottom:20px;">
        <label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:#9a3412;cursor:pointer;">
          <input type="checkbox" name="confirm_accuracy" id="confirm_accuracy" value="1" required style="margin-top:3px;">
          <span>
            <strong>Author Confirmation:</strong> I confirm that the information provided in this submission is accurate, that I have reviewed the manuscript before submission, and that all co-authors have consented to this submission.
          </span>
        </label>
      </div>

      <div style="border-top:1px solid #edf0f4;padding-top:16px;display:flex;justify-content:space-between;align-items:center;">
        <button type="button" class="btn secondary" onclick="goToStep(4);">← Back</button>
        <div>
          <button type="button" class="btn secondary" onclick="saveDraftAction();" style="margin-right:8px;">Save Draft</button>
          <button type="submit" id="submitBtn" class="btn primary" style="padding:10px 28px;font-size:15px;font-weight:bold;">
            Submit Manuscript
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Hidden File Removal Form -->
<form id="removeFileForm" method="post" style="display:none;">
  <input type="hidden" name="csrf" value="<?=e(csrf())?>">
  <input type="hidden" name="action" value="remove_file">
  <input type="hidden" name="manuscript_id" value="<?=e($draftMs ? (string)$draftMs['id'] : '')?>">
  <input type="hidden" name="file_id" id="removeFileId" value="">
  <input type="hidden" name="current_step" id="removeFileStep" value="1">
</form>

<script>
let currentStep = <?=(int)$activeStep?>;
let coAuthorCount = <?=(count($draftAuthors) > 1 ? count($draftAuthors) - 1 : 0)?>;

function toggleResearchAreaOther() {
  const sel = document.getElementById('research_area').value;
  const block = document.getElementById('researchAreaOtherBlock');
  if (sel === 'Other') {
    block.style.display = 'block';
  } else {
    block.style.display = 'none';
  }
}

function goToStep(step) {
  if (step < 1 || step > 5) return;

  document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
  document.getElementById('step-content-' + step).classList.add('active');

  document.querySelectorAll('.wizard-step').forEach((el, idx) => {
    el.classList.remove('active');
    if (idx + 1 < step) el.classList.add('completed');
    else el.classList.remove('completed');
  });
  document.getElementById('step-nav-' + step).classList.add('active');

  currentStep = step;
  document.getElementById('currentStepInput').value = step;

  if (step === 5) {
    updateSummaryPreview();
  }
}

function nextStep(fromStep) {
  if (fromStep === 1) {
    const mainFile = document.getElementById('mainManuscriptInput');
    const existingCard = document.getElementById('mainFileCard');
    if (!existingCard && (!mainFile.files || mainFile.files.length === 0)) {
      alert('Please select a Main Manuscript file before proceeding.');
      return;
    }
    const artType = document.getElementById('article_type').value;
    if (!artType) {
      alert('Please select an Article Type before proceeding.');
      return;
    }
  } else if (fromStep === 2) {
    const title = document.getElementById('title').value.trim();
    const abstract = document.getElementById('abstract').value.trim();
    const keywords = document.getElementById('keywords').value.trim();
    const areaSel = document.getElementById('research_area').value;
    const areaOther = document.getElementById('research_area_other').value.trim();

    if (!title || !abstract || !keywords) {
      alert('Please complete all required fields (Title, Abstract, Keywords).');
      return;
    }
    if (areaSel === 'Other' && !areaOther) {
      alert('Please specify the custom Research Area / Subject.');
      document.getElementById('research_area_other').focus();
      return;
    }
  } else if (fromStep === 3) {
    const errBox = document.getElementById('step3ErrorBox');
    errBox.style.display = 'none';
    errBox.innerHTML = '';

    const aff1 = document.getElementById('author1_affiliation')?.value.trim();
    if (!aff1) {
      errBox.innerHTML = '<strong>Validation Error:</strong> Primary / First Author requires a Primary Institution / Affiliation.';
      errBox.style.display = 'block';
      errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    const coCards = document.querySelectorAll('#coAuthorsContainer .author-card');
    for (let i = 0; i < coCards.length; i++) {
      const card = coCards[i];
      const name = card.querySelector('.co-author-name')?.value.trim();
      const email = card.querySelector('.co-author-email')?.value.trim();
      const aff = card.querySelector('.co-author-aff')?.value.trim();

      if (!name) {
        errBox.innerHTML = `<strong>Validation Error:</strong> Co-Author #${i + 2} requires a Full Name.`;
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      if (!email) {
        errBox.innerHTML = `<strong>Validation Error:</strong> Co-Author #${i + 2} requires an Email Address.`;
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      if (!email.includes('@')) {
        errBox.innerHTML = `<strong>Validation Error:</strong> Co-Author #${i + 2} requires a valid Email Address.`;
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
      if (!aff) {
        errBox.innerHTML = `<strong>Validation Error:</strong> Co-Author #${i + 2} requires a Primary Institution / Affiliation.`;
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
    }
  } else if (fromStep === 4) {
    const errBox = document.getElementById('step4ErrorBox');
    errBox.style.display = 'none';
    errBox.innerHTML = '';

    const pol = document.getElementById('policy_agreed');
    if (!pol || !pol.checked) {
      errBox.innerHTML = '<strong>Validation Error:</strong> Please confirm that you have read and agreed to the Publishing Policy (8.1).';
      errBox.style.display = 'block';
      errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    const compVal = document.querySelector('input[name="competing_status"]:checked')?.value;
    if (compVal === 'yes') {
      const compDet = document.getElementById('competing_details').value.trim();
      if (!compDet) {
        errBox.innerHTML = '<strong>Validation Error:</strong> Please provide Competing Interests Details because you selected Yes (8.2).';
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
    }

    const origVal = document.querySelector('input[name="originality_status"]:checked')?.value;
    if (origVal === 'yes') {
      const origDet = document.getElementById('originality_details').value.trim();
      if (!origDet) {
        errBox.innerHTML = '<strong>Validation Error:</strong> Please provide Dual Submission Details because you selected Yes (8.3).';
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
    }

    const authAgreed = document.getElementById('authorship_agreed');
    if (!authAgreed || !authAgreed.checked) {
      errBox.innerHTML = '<strong>Validation Error:</strong> Please check the Authorship Confirmation checkbox (8.4).';
      errBox.style.display = 'block';
      errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    const tpVal = document.querySelector('input[name="thirdparty_status"]:checked')?.value;
    if (tpVal === 'yes') {
      const tpAgreed = document.getElementById('thirdparty_agreed');
      if (!tpAgreed || !tpAgreed.checked) {
        errBox.innerHTML = '<strong>Validation Error:</strong> Please confirm third-party material permissions (8.5).';
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
    }

    const dataVal = document.querySelector('input[name="data_status"]:checked')?.value;
    if (dataVal === 'yes') {
      const dataDet = document.getElementById('data_details').value.trim();
      if (!dataDet) {
        errBox.innerHTML = '<strong>Validation Error:</strong> Please provide a Data Availability Statement because you selected Yes (8.6).';
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
    }

    const fundVal = document.querySelector('input[name="funding_status"]:checked')?.value;
    if (fundVal === 'yes') {
      const fundOrg = document.getElementById('funding_org').value.trim();
      if (!fundOrg) {
        errBox.innerHTML = '<strong>Validation Error:</strong> Please specify the Funding Organization because you selected Yes (8.8).';
        errBox.style.display = 'block';
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }
    }
  }
  goToStep(fromStep + 1);
}

function saveDraftAction() {
  document.getElementById('formAction').value = 'save_draft';
  document.getElementById('submissionForm').submit();
}

function triggerFileReplace() {
  const input = document.getElementById('mainManuscriptInput');
  input.style.display = 'block';
  input.click();
}

function removeUploadedFile(fileId) {
  if (confirm('Are you sure you want to remove this file?')) {
    document.getElementById('removeFileId').value = fileId;
    document.getElementById('removeFileStep').value = currentStep;
    document.getElementById('removeFileForm').submit();
  }
}

function toggleConditional(elementId, show) {
  const el = document.getElementById(elementId);
  if (el) el.style.display = show ? 'block' : 'none';
}

function initDeclarationsUI() {
  toggleResearchAreaOther();

  const compRadio = document.querySelector('input[name="competing_status"]:checked');
  if (compRadio && compRadio.value === 'yes') toggleConditional('competingDetailsBlock', true);

  const origRadio = document.querySelector('input[name="originality_status"]:checked');
  if (origRadio && origRadio.value === 'yes') toggleConditional('originalityDetailsBlock', true);

  const tpRadio = document.querySelector('input[name="thirdparty_status"]:checked');
  if (tpRadio && tpRadio.value === 'yes') toggleConditional('thirdpartyBlock', true);

  const dataRadio = document.querySelector('input[name="data_status"]:checked');
  if (dataRadio && dataRadio.value === 'yes') toggleConditional('dataBlock', true);

  const fundRadio = document.querySelector('input[name="funding_status"]:checked');
  if (fundRadio && fundRadio.value === 'yes') toggleConditional('fundingBlock', true);
}

document.addEventListener('DOMContentLoaded', initDeclarationsUI);

// Dynamic Co-Author Rows
function addCoAuthorCard() {
  coAuthorCount++;
  const index = coAuthorCount;
  const container = document.getElementById('coAuthorsContainer');

  const card = document.createElement('div');
  card.className = 'author-card';
  card.id = 'coAuthorCard_' + index;

  card.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <strong style="color:#1e293b;font-size:14px;">Author ${index + 1} (Co-Author)</strong>
      <div style="display:flex;align-items:center;gap:12px;">
        <label style="font-size:13px;font-weight:bold;color:#1e293b;cursor:pointer;display:flex;align-items:center;gap:6px;">
          <input type="radio" name="corresponding_author_index" value="${index}" onchange="renderAuthorsSummary();">
          Corresponding Author
        </label>
        <button type="button" class="btn err" style="padding:3px 10px;font-size:12px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:4px;" onclick="removeCoAuthorCard(${index});">
          Remove
        </button>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;">
      <div>
        <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">Author Name *</label>
        <input type="text" name="author_names[]" required placeholder="e.g. Dr. Jane Doe" class="form-input co-author-name" oninput="updateSummaryPreview();">
      </div>
      <div>
        <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">Email Address *</label>
        <input type="email" name="author_emails[]" required placeholder="janedoe@university.edu" class="form-input co-author-email" oninput="updateSummaryPreview();">
      </div>
      <div>
        <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">Primary Institution / Affiliation *</label>
        <input type="text" name="author_affiliations[]" required placeholder="University / Department" class="form-input co-author-aff" oninput="updateSummaryPreview();">
      </div>
      <div>
        <label style="display:block;font-weight:600;margin-bottom:4px;color:#1e293b;">ORCID <small class="muted">(Optional)</small></label>
        <input type="text" name="author_orcids[]" placeholder="0000-0000-0000-0000" class="form-input co-author-orcid">
      </div>
    </div>
  `;
  container.appendChild(card);
  updateSummaryPreview();
}

function removeCoAuthorCard(index) {
  const card = document.getElementById('coAuthorCard_' + index);
  if (card) {
    card.remove();
    updateSummaryPreview();
  }
}

// Dynamic Additional Files Rows (Simple Direct Upload)
let addFileCount = 0;
function addAdditionalFileRow() {
  addFileCount++;
  const container = document.getElementById('additionalFilesContainer');
  const row = document.createElement('div');
  row.style.cssText = "display:flex;gap:10px;align-items:center;margin-bottom:10px;background:#f8fafc;padding:10px 14px;border:1px solid #e2e8f0;border-radius:6px;";
  row.id = 'addFileRow_' + addFileCount;

  row.innerHTML = `
    <span style="font-size:13px;font-weight:600;color:#334155;">Additional File:</span>
    <input type="file" name="additional_files[]" style="font-size:13px;" onchange="updateSummaryPreview();">
    <button type="button" class="btn light" style="padding:4px 10px;font-size:11px;color:#ef4444;margin-left:auto;" onclick="this.parentElement.remove();updateSummaryPreview();">Remove</button>
  `;
  container.appendChild(row);
}

// Render Review Summary
function updateSummaryPreview() {
  document.getElementById('sum_article_type').innerText = document.getElementById('article_type').value || '—';
  
  const areaSel = document.getElementById('research_area').value;
  const areaOther = document.getElementById('research_area_other').value.trim();
  document.getElementById('sum_research_area').innerText = (areaSel === 'Other' ? (areaOther ? 'Other (' + areaOther + ')' : 'Other') : areaSel) || '—';

  document.getElementById('sum_title').innerText = document.getElementById('title').value || '—';
  document.getElementById('sum_abstract').innerText = document.getElementById('abstract').value || '—';
  document.getElementById('sum_keywords').innerText = document.getElementById('keywords').value || '—';

  // Files summary
  let filesHTML = '';
  const mainFileInput = document.getElementById('mainManuscriptInput');
  const mainFileCard = document.getElementById('mainFileCard');

  if (mainFileCard) {
    filesHTML += '<div>📄 <strong>Main Manuscript:</strong> Uploaded on server</div>';
  } else if (mainFileInput && mainFileInput.files && mainFileInput.files[0]) {
    filesHTML += '<div>📄 <strong>Main Manuscript:</strong> ' + mainFileInput.files[0].name + ' (' + (mainFileInput.files[0].size / 1024 / 1024).toFixed(2) + ' MB)</div>';
  } else {
    filesHTML += '<div style="color:#ef4444;">No main manuscript selected</div>';
  }

  const addFileInputs = document.querySelectorAll('input[name="additional_files[]"]');
  addFileInputs.forEach((inp, i) => {
    if (inp.files && inp.files[0]) {
      filesHTML += '<div>📎 <strong>Additional File ' + (i + 1) + ':</strong> ' + inp.files[0].name + ' (' + (inp.files[0].size / 1024).toFixed(1) + ' KB)</div>';
    }
  });
  document.getElementById('sum_files_list').innerHTML = filesHTML;

  renderAuthorsSummary();

  // Declarations summary
  let decHTML = '';

  const polAgreed = document.getElementById('policy_agreed')?.checked;
  decHTML += '<div><strong>8.1 Publishing Policy:</strong> ' + (polAgreed ? '<span style="color:#166534;font-weight:600;">Confirmed</span>' : '<span style="color:#dc2626;">Not Confirmed</span>') + '</div>';

  const compVal = document.querySelector('input[name="competing_status"]:checked')?.value || 'no';
  const compDet = document.getElementById('competing_details')?.value.trim();
  decHTML += '<div><strong>8.2 Competing Interests:</strong> ' + (compVal === 'yes' ? ('Yes (' + (compDet || 'No details') + ')') : 'No') + '</div>';

  const origVal = document.querySelector('input[name="originality_status"]:checked')?.value || 'no';
  const origDet = document.getElementById('originality_details')?.value.trim();
  decHTML += '<div><strong>8.3 Originality / Dual Submission:</strong> ' + (origVal === 'yes' ? ('Yes (' + (origDet || 'No details') + ')') : 'No') + '</div>';

  const authAgreed = document.getElementById('authorship_agreed')?.checked;
  decHTML += '<div><strong>8.4 Authorship Confirmation:</strong> ' + (authAgreed ? '<span style="color:#166534;font-weight:600;">Confirmed</span>' : '<span style="color:#dc2626;">Not Confirmed</span>') + '</div>';

  const tpVal = document.querySelector('input[name="thirdparty_status"]:checked')?.value || 'no';
  const tpAgreed = document.getElementById('thirdparty_agreed')?.checked;
  decHTML += '<div><strong>8.5 Third-Party Material:</strong> ' + (tpVal === 'yes' ? (tpAgreed ? 'Yes (Permissions Confirmed)' : 'Yes (Permission Pending)') : 'No') + '</div>';

  const dataVal = document.querySelector('input[name="data_status"]:checked')?.value || 'no';
  const dataDet = document.getElementById('data_details')?.value.trim();
  decHTML += '<div><strong>8.6 Data Availability:</strong> ' + (dataVal === 'yes' ? ('Yes (' + (dataDet || 'No statement') + ')') : 'No / N/A') + '</div>';

  const ackVal = document.getElementById('acknowledgements')?.value.trim();
  decHTML += '<div><strong>8.7 Acknowledgements:</strong> ' + (ackVal ? ackVal : 'None') + '</div>';

  const fundVal = document.querySelector('input[name="funding_status"]:checked')?.value || 'no';
  const fundOrg = document.getElementById('funding_org')?.value.trim();
  const fundGrant = document.getElementById('funding_grant')?.value.trim();
  if (fundVal === 'yes') {
    let fStr = fundOrg || 'Yes';
    if (fundGrant) fStr += ' (Grant #' + fundGrant + ')';
    decHTML += '<div><strong>8.8 Research Funding:</strong> ' + fStr + '</div>';
  } else {
    decHTML += '<div><strong>8.8 Research Funding:</strong> No</div>';
  }

  document.getElementById('sum_declarations_list').innerHTML = decHTML;
}

function renderAuthorsSummary() {
  let authorsHTML = '';
  const caRadios = document.querySelectorAll('input[name="corresponding_author_index"]');
  let selectedCAIndex = 0;
  caRadios.forEach(r => {
    if (r.checked) selectedCAIndex = parseInt(r.value);
  });

  const aff1Val = document.getElementById('author1_affiliation')?.value.trim() || '<?=e($authorAffiliation)?>';

  // Author 1
  authorsHTML += '<div>1. <strong><?=e($authorName)?></strong> (Primary / First Author) ' +
    (selectedCAIndex === 0 ? '<span class="badge" style="background:#dbeafe;color:#1e40af;padding:2px 6px;">Corresponding Author</span>' : '') +
    '<br><small style="color:#64748b;"><?=e($authorEmail)?> \vert ' + (aff1Val || 'No affiliation specified') + ' <?=($authorOrcid ? ' \vert ORCID: ' . e($authorOrcid) : '')?></small></div>';

  // Co-authors
  const coNameInputs = document.querySelectorAll('#coAuthorsContainer .co-author-name');
  const coEmailInputs = document.querySelectorAll('#coAuthorsContainer .co-author-email');
  const coAffInputs = document.querySelectorAll('#coAuthorsContainer .co-author-aff');
  const coOrcidInputs = document.querySelectorAll('#coAuthorsContainer .co-author-orcid');

  coNameInputs.forEach((inp, idx) => {
    const val = inp.value.trim();
    if (val) {
      const email = coEmailInputs[idx] ? coEmailInputs[idx].value.trim() : '';
      const aff = coAffInputs[idx] ? coAffInputs[idx].value.trim() : '';
      const orcid = coOrcidInputs[idx] ? coOrcidInputs[idx].value.trim() : '';
      const isCA = (selectedCAIndex === (idx + 1));
      authorsHTML += '<div style="margin-top:6px;">' + (idx + 2) + '. <strong>' + val + '</strong> ' +
        (isCA ? '<span class="badge" style="background:#dbeafe;color:#1e40af;padding:2px 6px;">Corresponding Author</span>' : '') +
        '<br><small style="color:#64748b;">' + (email || 'No email') + ' | ' + (aff || 'No affiliation') + (orcid ? ' | ORCID: ' + orcid : '') + '</small></div>';
    }
  });

  document.getElementById('sum_authors_list').innerHTML = authorsHTML;
}

function validateFinalSubmit(form) {
  const btn = document.getElementById('submitBtn');
  if (document.getElementById('formAction').value === 'save_draft') {
    return true;
  }
  if (btn.disabled) return false;

  btn.disabled = true;
  btn.innerText = 'Submitting Manuscript...';
  return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>