<?php
require_once __DIR__.'/config/config.php';
if(session_status()!==PHP_SESSION_ACTIVE) session_start();

function prod_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function prod_user(PDO $db):array{
 if(empty($_SESSION['user']['id'])){http_response_code(403);exit('Access denied. Please login again.');}
 $s=$db->prepare("SELECT id,email,role,active FROM users WHERE id=? LIMIT 1");$s->execute([(int)$_SESSION['user']['id']]);$u=$s->fetch(PDO::FETCH_ASSOC);
 if(!$u||(int)$u['active']!==1) {http_response_code(403);exit('Access denied. User account is inactive or not found.');}
 if(!in_array((string)$u['role'],['admin','editor_in_chief','editor'],true)){http_response_code(403);exit('Access denied. Production management is restricted to editorial management roles.');}
 return $u;
}
function prod_csrf():string{if(empty($_SESSION['production_csrf']))$_SESSION['production_csrf']=bin2hex(random_bytes(32));return $_SESSION['production_csrf'];}
function prod_check_csrf():void{if(empty($_POST['csrf'])||empty($_SESSION['production_csrf'])||!hash_equals($_SESSION['production_csrf'],$_POST['csrf'])){http_response_code(403);exit('Invalid CSRF token.');}}
// prod_log writes to ew_audit_log (exists)
function prod_log(PDO $db,int $mid,int $uid,string $action,string $details=''):void{$s=$db->prepare("INSERT INTO ew_audit_log(manuscript_id,user_id,action,details,ip_address) VALUES(?,?,?,?,?)");$s->execute([$mid,$uid,$action,$details,$_SERVER['REMOTE_ADDR']??null]);}
function prod_ms(PDO $db,int $id):array{$s=$db->prepare("SELECT * FROM manuscripts WHERE id=? LIMIT 1");$s->execute([$id]);$m=$s->fetch(PDO::FETCH_ASSOC);if(!$m)exit('Manuscript not found.');return $m;}
// prod_ensure: use existing `production` table (not ew_production which does not exist)
function prod_ensure(PDO $db,int $mid,int $uid):void{$s=$db->prepare("SELECT id FROM production WHERE manuscript_id=?");$s->execute([$mid]);if(!$s->fetch()){$db->prepare("INSERT INTO production(manuscript_id) VALUES(?)")->execute([$mid]);}}
function prod_header(string $title,array $u):void{
    require_once __DIR__ . '/includes/eic_layout.php';
    eic_render_header($title, 'Production & Publication Stream');
    echo '<style>a.button,button{display:inline-block;padding:9px 13px;border:1px solid #999;border-radius:4px;background:#fafafa;color:#222;text-decoration:none;cursor:pointer}input,textarea,select{box-sizing:border-box;width:100%;max-width:700px;padding:9px;margin:5px 0 12px}textarea{min-height:130px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px;text-align:left}.ok{background:#f0fff0;border:1px solid #9b9;padding:12px;margin-bottom:15px}.err{background:#fff0f0;border:1px solid #c99;padding:12px;margin-bottom:15px}</style>';
    echo '<div class="panel" style="margin-bottom:18px;"><strong>AJSMR Production V1</strong> | '.prod_h($u['email']).' ('.prod_h($u['role']).')</div>';
}
function prod_footer():void{
    require_once __DIR__ . '/includes/eic_layout.php';
    eic_render_footer();
}
