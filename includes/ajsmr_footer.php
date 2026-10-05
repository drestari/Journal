<?php
/*
 * AJSMR Master Footer Component
 * Source of truth: publicationethics.php
 */

if (!isset($ajsmr_root)) {
    $ajsmr_root = $GLOBALS['ajsmr_root'] ?? '';
}
$r = $ajsmr_root;

if (!isset($journal))   $journal   = 'The American Journal of Science and Medical Research';
if (!isset($publisher)) $publisher = 'Advaitha Innovative Research Association (AIRA)';
if (!isset($issn))      $issn      = '2377-6196';
if (!isset($frequency)) $frequency = 'Quarterly';

if (!function_exists('ajsmr_h')) {
    function ajsmr_h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}
?>
<footer>
  <div class="container footer-grid">
    <div class="footer-about">
      <div class="footer-logo">AJSMR</div>
      <h3><?=ajsmr_h($journal)?></h3>
      <p>Published by <?=ajsmr_h($publisher)?>.</p>
      <p>ISSN <?=ajsmr_h($issn)?> · <?=ajsmr_h($frequency)?> · Open Access</p>
    </div>
    <div><h4>Journal</h4><a href="<?=ajsmr_h($r)?>currentissue.php">Current Issue</a><a href="<?=ajsmr_h($r)?>archives.php">Archives</a><a href="<?=ajsmr_h($r)?>editorialboard.php">Editorial Board</a></div>
    <div><h4>Authors &amp; Reviewers</h4><a href="<?=ajsmr_h($r)?>authorguidelines.php">Author Guidelines</a><a href="<?=ajsmr_h($r)?>editorial/login.php">Submit Manuscript</a><a href="<?=ajsmr_h($r)?>peerreviewpolicy.php">Peer Review Policy</a></div>
    <div><h4>Policies</h4><a href="<?=ajsmr_h($r)?>policies.php">Policy Hub</a><a href="<?=ajsmr_h($r)?>publicationethics.php">Publication Ethics</a><a href="<?=ajsmr_h($r)?>openaccesscopyrightpolicy.php">Open Access &amp; Copyright</a><a href="<?=ajsmr_h($r)?>archivingdigitalpolicy.php">Digital Archiving</a></div>
  </div>
  <div class="footer-bottom"><div class="container">© <?=date('Y')?> <?=ajsmr_h($journal)?> · ISSN <?=ajsmr_h($issn)?> · All rights reserved.</div></div>
</footer>
