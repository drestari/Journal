<?php
declare(strict_types=1);

require_once __DIR__ . '/current_issue_common.php';
$u = current_issue_eic();

$pageTitle = 'Add Current Issue Article';
$error = '';
$success = '';

$issues = get_all_issues();
$articleTypes = current_issue_article_types();

$defaults = [
    'catid' => $issues[0]['catid'] ?? 0,
    'type' => 'Research Article',
    'conttitle' => '',
    'authors' => '',
    'journal' => 'The American Journal of Science and Medical Research',
    'recevied' => '',
    'accepted' => '',
    'published' => date('Y-m-d'),
    'doi' => '',
    'absurl' => ''
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        check_csrf();

        $catid = isset($_POST['catid']) ? (int)$_POST['catid'] : 0;
        $type = trim((string)($_POST['type'] ?? ''));
        $conttitle = trim((string)($_POST['conttitle'] ?? ''));
        $authors = trim((string)($_POST['authors'] ?? ''));
        $journal = trim((string)($_POST['journal'] ?? ''));
        $recevied = trim((string)($_POST['recevied'] ?? ''));
        $accepted = trim((string)($_POST['accepted'] ?? ''));
        $published = trim((string)($_POST['published'] ?? ''));
        $doi = trim((string)($_POST['doi'] ?? ''));
        $absurl = trim((string)($_POST['absurl'] ?? ''));
        $description = '';
        $status = 1; // Default to published internally

        // Validation
        if ($catid <= 0) {
            throw new RuntimeException('Please select a valid Issue / Year.');
        }
        if ($conttitle === '') {
            throw new RuntimeException('Article Title is required.');
        }
        if ($authors === '') {
            throw new RuntimeException('Authors information is required.');
        }
        if ($type === '') {
            $type = 'Research Article';
        }

        // Duplicate prevention check
        $jdb = journal_db();
        $dupCheck = $jdb->prepare("SELECT contentid FROM ajsmr_issuecontent WHERE catid = ? AND conttitle = ? LIMIT 1");
        $dupCheck->execute([$catid, $conttitle]);
        if ($dupCheck->fetch()) {
            throw new RuntimeException('An article with this title is already registered in the selected issue.');
        }

        // Handle File Uploads securely
        $abstractPdf = '';

        $fullpaperPdf = '';
        if (!empty($_FILES['fullpaper_pdf']['name'])) {
            $fullpaperPdf = (string)handle_secure_upload($_FILES['fullpaper_pdf'], 'pdf', 'pdffiles');
        }

        $photoImg = '';
        if (!empty($_FILES['photo_img']['name'])) {
            $photoImg = (string)handle_secure_upload($_FILES['photo_img'], 'image', 'issuesimgs');
        }

        // Default DOI auto-link if absent but abstract URL provided
        if ($absurl === '' && $doi !== '' && preg_match('~^https?://~i', $doi)) {
            $absurl = $doi;
        }

        $programdate = ($published !== '' && strtotime($published)) ? date('Y-m-d', strtotime($published)) : date('Y-m-d');

        // Insert into ajsmr_issuecontent
        $stmt = $jdb->prepare(
            "INSERT INTO ajsmr_issuecontent
            (catid, type, conttitle, authors, journal, recevied, accepted, published, doi, abstract, absurl, fullpaper, photopath, description, programdate, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $catid,
            $type,
            $conttitle,
            $authors,
            $journal,
            $recevied,
            $accepted,
            $published,
            $doi,
            $abstractPdf,
            $absurl,
            $fullpaperPdf,
            $photoImg,
            $description,
            $programdate,
            $status
        ]);

        $newId = (int)$jdb->lastInsertId();

        // Sync to ajsmr_abstracts if abstracts table exists
        try {
            // Find issue label
            $issStmt = $jdb->prepare("SELECT catename FROM ajsmr_issueyears WHERE catid = ? LIMIT 1");
            $issStmt->execute([$catid]);
            $issName = (string)$issStmt->fetchColumn();

            $absStmt = $jdb->prepare(
                "INSERT INTO ajsmr_abstracts
                (contentid, conttitle, issuedetails, authors, journal, doi, abstract, abstractsdesc, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                conttitle = VALUES(conttitle),
                issuedetails = VALUES(issuedetails),
                authors = VALUES(authors),
                journal = VALUES(journal),
                doi = VALUES(doi),
                abstract = VALUES(abstract),
                abstractsdesc = VALUES(abstractsdesc),
                status = VALUES(status)"
            );
            $absStmt->execute([
                $newId,
                $conttitle,
                $issName,
                $authors,
                $journal,
                $doi,
                $fullpaperPdf ?: $abstractPdf,
                $description,
                $status
            ]);
        } catch (Throwable $eSync) {
            // non-fatal abstract sync
        }

        // Audit Log
        audit('CURRENT_ISSUE_ARTICLE_ADDED', null, "Added article #{$newId} '{$conttitle}' to issue #{$catid}");

        $success = 'Current Issue article added successfully.';

        // Reset defaults
        $defaults['conttitle'] = '';
        $defaults['authors'] = '';
        $defaults['doi'] = '';
        $defaults['absurl'] = '';
        $defaults['recevied'] = '';
        $defaults['accepted'] = '';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
  <div>
    <h1 class="title" style="margin-bottom:4px;">Add Current Issue Article</h1>
    <div class="muted">Add a new article into the journal issues and current issue publication stream.</div>
  </div>
  <div>
    <a href="current_issue_list.php" class="btn secondary">← Back to Current Issue List</a>
    <a href="../currentissue.php" target="_blank" class="btn secondary" style="margin-left:8px;">View Public Issue ↗</a>
  </div>
</div>

<?php if ($error !== ''): ?>
  <div class="alert err"><strong>Error:</strong> <?=e($error)?></div>
<?php endif; ?>

<?php if ($success !== ''): ?>
  <div class="alert">
    <strong>Success!</strong> <?=e($success)?>
    <span style="margin-left:15px;">
      <a href="current_issue_list.php" style="color:#2f6f9f;font-weight:bold;">View in List</a> &middot;
      <a href="../currentissue.php" target="_blank" style="color:#2f6f9f;font-weight:bold;">Open Public Current Issue</a>
    </span>
  </div>
<?php endif; ?>

<div class="panel">
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">

    <div class="grid">
      <div>
        <label>Select Year and Issue <span style="color:#b42318;">*</span></label>
        <select name="catid" required>
          <?php foreach ($issues as $iss): ?>
            <option value="<?=e($iss['catid'])?>" <?=( (int)$defaults['catid'] === (int)$iss['catid'] ? 'selected' : '' )?>>
              <?=e(format_issue_label($iss))?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="muted" style="display:block;margin-top:4px;">Select the target issue where this article will be published.</small>
      </div>

      <div>
        <label>Article Type <span style="color:#b42318;">*</span></label>
        <select name="type" required>
          <?php foreach ($articleTypes as $t): ?>
            <option value="<?=e($t)?>" <?=( $defaults['type'] === $t ? 'selected' : '' )?>><?=e($t)?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="margin-top:14px;">
      <label>Article Title <span style="color:#b42318;">*</span></label>
      <input type="text" name="conttitle" value="<?=e($defaults['conttitle'])?>" placeholder="Full manuscript / article title" required>
    </div>

    <div style="margin-top:14px;">
      <label>Authors <span style="color:#b42318;">*</span></label>
      <input type="text" name="authors" value="<?=e($defaults['authors'])?>" placeholder="e.g. John Doe*, Jane Smith, Alex Johnson" required>
      <small class="muted" style="display:block;margin-top:4px;">Comma-separated author names. Use * to designate corresponding author.</small>
    </div>

    <div style="margin-top:14px;">
      <label>Journal Citation / Section</label>
      <input type="text" name="journal" value="<?=e($defaults['journal'])?>" placeholder="e.g. The American Journal of Science and Medical Research, 12(3), 1-10">
    </div>

    <div class="grid" style="margin-top:14px;">
      <div>
        <label>Received Date</label>
        <input type="date" name="recevied" value="<?=e($defaults['recevied'])?>">
      </div>
      <div>
        <label>Accepted Date</label>
        <input type="date" name="accepted" value="<?=e($defaults['accepted'])?>">
      </div>
    </div>

    <div class="grid" style="margin-top:14px;">
      <div>
        <label>Published Date</label>
        <input type="date" name="published" value="<?=e($defaults['published'])?>">
      </div>
      <div>
        <label>DOI</label>
        <input type="text" name="doi" value="<?=e($defaults['doi'])?>" placeholder="e.g. https://doi.org/10.5281/zenodo.18489357">
      </div>
    </div>

    <div style="margin-top:14px;">
      <label>Abstract URL</label>
      <input type="url" name="absurl" value="<?=e($defaults['absurl'])?>" placeholder="https://doi.org/... or external abstract link">
    </div>

    <div class="grid" style="margin-top:14px;">
      <div>
        <label>Full Article PDF (Recommended)</label>
        <input type="file" name="fullpaper_pdf" accept=".pdf,application/pdf">
        <small class="muted" style="display:block;margin-top:4px;">Allowed: .pdf (Max 25MB)</small>
      </div>
      <div>
        <label>Article Image (Optional)</label>
        <input type="file" name="photo_img" accept=".jpg,.jpeg,.png,.webp,image/*">
        <small class="muted" style="display:block;margin-top:4px;">Allowed: JPG, PNG, WEBP (Max 10MB)</small>
      </div>
    </div>

    <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
      <button type="submit" class="btn primary" style="padding:11px 22px;font-size:14px;">Save Article</button>
      <button type="reset" class="btn secondary">Reset</button>
      <a href="current_issue_list.php" class="btn secondary" style="margin-left:auto;">Cancel</a>
    </div>

  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
