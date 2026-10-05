<?php
/*
 * AJSMR Master Right Sidebar Component
 * Source of truth: publicationethics.php
 *
 * Includes:
 *   - Journal at a Glance
 *   - Policies navigation
 */

if (!isset($ajsmr_root)) {
    $ajsmr_root = $GLOBALS['ajsmr_root'] ?? '';
}
$r = $ajsmr_root;
?>
<aside class="policy-sidebar">
  <h3>Journal at a Glance</h3>
  <ul>
    <li><strong>Journal:</strong> The American Journal of Science and Medical Research (AJSMR)</li>
    <li><strong>ISSN:</strong> 2377-6196</li>
    <li><strong>Publisher:</strong> Advaitha Innovative Research Association (AIRA)</li>
    <li><strong>Frequency:</strong> Quarterly</li>
    <li><strong>Access:</strong> Open Access</li>
    <li><strong>Peer Review:</strong> Double-blind</li>
    <li><strong>Licence:</strong> CC BY 4.0</li>
    <li><strong>First Published:</strong> 2015</li>
  </ul>

  <div class="policy-nav">
    <div class="policy-nav-title">Policies</div>
    <a href="<?=htmlspecialchars($r, ENT_QUOTES, 'UTF-8')?>publicationethics.php">Publication Ethics &amp; Editorial Policy</a>
    <a href="<?=htmlspecialchars($r, ENT_QUOTES, 'UTF-8')?>peerreviewpolicy.php">Peer Review Policy</a>
    <a href="<?=htmlspecialchars($r, ENT_QUOTES, 'UTF-8')?>openaccesscopyrightpolicy.php">Open Access, Copyright &amp; Licensing Policy</a>
    <a href="<?=htmlspecialchars($r, ENT_QUOTES, 'UTF-8')?>plagiarismresearchintegritypolicy.php">Plagiarism, Research Integrity &amp; Retraction Policy</a>
    <a href="<?=htmlspecialchars($r, ENT_QUOTES, 'UTF-8')?>complaintsappealspolicy.php">Complaints &amp; Appeals Policy</a>
    <a href="<?=htmlspecialchars($r, ENT_QUOTES, 'UTF-8')?>archivingdigitalpolicy.php">Archiving &amp; Digital Preservation Policy</a>
    <a href="<?=htmlspecialchars($r, ENT_QUOTES, 'UTF-8')?>privacydataprotectionpolicy.php">Privacy &amp; Data Protection Policy</a>
  </div>
</aside>
