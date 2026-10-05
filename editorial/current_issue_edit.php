<?php
declare(strict_types=1);

require_once __DIR__ . '/current_issue_common.php';
$u = current_issue_eic();

$pageTitle = 'Edit Current Issue Article';
$error = '';
$success = '';

$jdb = journal_db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: current_issue_list.php');
    exit;
}

// Fetch article
$stmt = $jdb->prepare("SELECT * FROM ajsmr_issuecontent WHERE contentid = ? LIMIT 1");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: current_issue_list.php?msg=' . urlencode('Article not found.'));
    exit;
}

$issues = get_all_issues();
$articleTypes = current_issue_article_types();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        check_csrf();

        $catid = isset($_POST['catid']) ? (int)$_POST['catid'] : (int)$article['catid'];
        $type = trim((string)($_POST['type'] ?? $article['type']));
        $conttitle = trim((string)($_POST['conttitle'] ?? $article['conttitle']));
        $authors = trim((string)($_POST['authors'] ?? $article['authors']));
        $journal = trim((string)($_POST['journal'] ?? $article['journal']));
        $recevied = trim((string)($_POST['recevied'] ?? $article['recevied']));
        $accepted = trim((string)($_POST['accepted'] ?? $article['accepted']));
        $published = trim((string)($_POST['published'] ?? $article['published']));
        $doi = trim((string)($_POST['doi'] ?? $article['doi']));
        $absurl = trim((string)($_POST['absurl'] ?? $article['absurl']));
        $description = trim((string)($_POST['description'] ?? $article['description']));
        $status = (isset($_POST['status']) && (int)$_POST['status'] === 1) ? 1 : 0;

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

        // File uploads: replace if new file uploaded, else keep existing
        $abstractPdf = (string)$article['abstract'];
        if (!empty($_FILES['abstract_pdf']['name'])) {
            $abstractPdf = (string)handle_secure_upload($_FILES['abstract_pdf'], 'pdf', 'pdffiles');
        }

        $fullpaperPdf = (string)$article['fullpaper'];
        if (!empty($_FILES['fullpaper_pdf']['name'])) {
            $fullpaperPdf = (string)handle_secure_upload($_FILES['fullpaper_pdf'], 'pdf', 'pdffiles');
        }

        $photoImg = (string)$article['photopath'];
        if (!empty($_FILES['photo_img']['name'])) {
            $photoImg = (string)handle_secure_upload($_FILES['photo_img'], 'image', 'issuesimgs');
        }

        if ($absurl === '' && $doi !== '' && preg_match('~^https?://~i', $doi)) {
            $absurl = $doi;
        }

        $programdate = ($published !== '' && strtotime($published)) ? date('Y-m-d', strtotime($published)) : date('Y-m-d');

        // Update ajsmr_issuecontent
        $upStmt = $jdb->prepare(
            "UPDATE ajsmr_issuecontent SET
                catid = ?,
                type = ?,
                conttitle = ?,
                authors = ?,
                journal = ?,
                recevied = ?,
                accepted = ?,
                published = ?,
                doi = ?,
                abstract = ?,
                absurl = ?,
                fullpaper = ?,
                photopath = ?,
                description = ?,
                programdate = ?,
                status = ?
            WHERE contentid = ?"
        );

        $upStmt->execute([
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
            $status,
            $id
        ]);

        // Sync to ajsmr_abstracts
        try {
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
                $id,
                $conttitle,
                $issName,
                $authors,
                $journal,
                $doi,
                $fullpaperPdf ?: $abstractPdf,
                $description,
                $status
            ]);
        } catch (Throwable $eSync) {}

        // Audit Log
        audit('CURRENT_ISSUE_ARTICLE_UPDATED', null, "Updated article #{$id} '{$conttitle}' in issue #{$catid}");

        header('Location: current_issue_list.php?msg=' . urlencode('Current Issue article updated successfully.'));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <div>
    <h1 class="title" style="margin-bottom:4px;">Edit Current Issue Article</h1>
    <div class="muted">Editing: <strong><?=e($article['conttitle'])?></strong> (ID: #<?=e($article['contentid'])?>)</div>
  </div>
  <div>
    <a href="current_issue_list.php" class="btn secondary">← Back to Current Issue List</a>
    <a href="../abstracts_details.php?id=<?=e($article['contentid'])?>" target="_blank" class="btn secondary" style="margin-left:8px;">View Public Article ↗</a>
  </div>
</div>

<?php if ($error !== ''): ?>
  <div class="alert err"><strong>Error:</strong> <?=e($error)?></div>
<?php endif; ?>

<div class="panel">
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">

    <div class="grid">
      <div>
        <label>Select Year and Issue <span style="color:#b42318;">*</span></label>
        <select name="catid" required>
          <?php foreach ($issues as $iss): ?>
            <option value="<?=e($iss['catid'])?>" <?=( (int)$article['catid'] === (int)$iss['catid'] ? 'selected' : '' )?>>
              <?=e(format_issue_label($iss))?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Article Type <span style="color:#b42318;">*</span></label>
        <select name="type" required>
          <?php foreach ($articleTypes as $t): ?>
            <option value="<?=e($t)?>" <?=( $article['type'] === $t ? 'selected' : '' )?>><?=e($t)?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="margin-top:14px;">
      <label>Article Title <span style="color:#b42318;">*</span></label>
      <input type="text" name="conttitle" value="<?=e($article['conttitle'])?>" required>
    </div>

    <div style="margin-top:14px;">
      <label>Authors <span style="color:#b42318;">*</span></label>
      <input type="text" name="authors" value="<?=e($article['authors'])?>" required>
      <small class="muted" style="display:block;margin-top:4px;">Comma-separated author names. Use * to designate corresponding author.</small>
    </div>

    <div style="margin-top:14px;">
      <label>Journal Citation / Section</label>
      <input type="text" name="journal" value="<?=e($article['journal'])?>">
    </div>

    <div class="grid" style="margin-top:14px;">
      <div>
        <label>Received Date</label>
        <input type="date" name="recevied" value="<?=e($article['recevied'])?>">
      </div>
      <div>
        <label>Accepted Date</label>
        <input type="date" name="accepted" value="<?=e($article['accepted'])?>">
      </div>
    </div>

    <div class="grid" style="margin-top:14px;">
      <div>
        <label>Published Date</label>
        <input type="date" name="published" value="<?=e($article['published'])?>">
      </div>
      <div>
        <label>DOI</label>
        <input type="text" name="doi" value="<?=e($article['doi'])?>">
      </div>
    </div>

    <div style="margin-top:14px;">
      <label>Abstract URL</label>
      <input type="url" name="absurl" value="<?=e($article['absurl'])?>">
    </div>

    <div style="margin-top:14px;">
      <label>Abstract Text / Summary</label>
      <textarea name="description"><?=e($article['description'])?></textarea>
    </div>

    <!-- File Management and Replacement Section -->
    <div style="margin-top:20px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
      <h3 style="margin-top:0;font-size:15px;color:#1e293b;">Files &amp; Media Management</h3>

      <div class="grid" style="margin-top:10px;">
        <div>
          <label>Abstract PDF</label>
          <?php if (!empty($article['abstract'])): ?>
            <div style="font-size:12px;margin-bottom:6px;">
              Current Abstract PDF:
              <a href="<?=e(current_issue_file_url($article['abstract']))?>" target="_blank" style="color:#0b5fa5;font-weight:bold;">View File ↗</a>
            </div>
          <?php else: ?>
            <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Current Abstract PDF: None</div>
          <?php endif; ?>
          <input type="file" name="abstract_pdf" accept=".pdf,application/pdf">
          <small class="muted" style="display:block;margin-top:4px;">Upload to replace existing file. Leave blank to keep current.</small>
        </div>

        <div>
          <label>Full Article PDF</label>
          <?php if (!empty($article['fullpaper'])): ?>
            <div style="font-size:12px;margin-bottom:6px;">
              Current Full Article PDF:
              <a href="<?=e(current_issue_file_url($article['fullpaper']))?>" target="_blank" style="color:#0b5fa5;font-weight:bold;">View File ↗</a>
            </div>
          <?php else: ?>
            <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Current Full Article PDF: None</div>
          <?php endif; ?>
          <input type="file" name="fullpaper_pdf" accept=".pdf,application/pdf">
          <small class="muted" style="display:block;margin-top:4px;">Upload to replace existing file. Leave blank to keep current.</small>
        </div>
      </div>

      <div class="grid" style="margin-top:14px;">
        <div>
          <label>Article Image</label>
          <?php if (!empty($article['photopath'])): ?>
            <div style="font-size:12px;margin-bottom:6px;display:flex;align-items:center;gap:10px;">
              <span>Current Image:</span>
              <img src="<?=e(current_issue_file_url($article['photopath']))?>" alt="Thumbnail" style="height:36px;border-radius:4px;border:1px solid #cbd5e1;">
              <a href="<?=e(current_issue_file_url($article['photopath']))?>" target="_blank" style="color:#0b5fa5;font-weight:bold;">Preview ↗</a>
            </div>
          <?php else: ?>
            <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Current Image: None</div>
          <?php endif; ?>
          <input type="file" name="photo_img" accept=".jpg,.jpeg,.png,.webp,image/*">
          <small class="muted" style="display:block;margin-top:4px;">Upload to replace existing image. Leave blank to keep current.</small>
        </div>

        <div>
          <label>Publication Status <span style="color:#b42318;">*</span></label>
          <select name="status">
            <option value="1" <?=( (int)$article['status'] === 1 ? 'selected' : '' )?>>Published (Visible on Public Website)</option>
            <option value="0" <?=( (int)$article['status'] === 0 ? 'selected' : '' )?>>Draft (Hidden from Public)</option>
          </select>
        </div>
      </div>
    </div>

    <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
      <button type="submit" class="btn primary" style="padding:11px 22px;font-size:14px;">Update Article</button>
      <a href="current_issue_list.php" class="btn secondary">Cancel</a>
    </div>

  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
