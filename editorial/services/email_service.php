<?php
declare(strict_types=1);

/**
 * AJSMR Centralized Email Transport & Template Service
 * 
 * Provides mail configuration management, socket SMTP transport with fallbacks,
 * responsive HTML email template generation, and plain-text fallback formatting.
 */

if (!function_exists('ajsmr_mail_config')) {
    function ajsmr_mail_config(string $key, $default = null) {
        static $config = null;
        if ($config === null) {
            $config = [
                'enabled'      => filter_var(getenv('MAIL_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
                'host'         => getenv('MAIL_HOST') ?: 'smtp.ajsmrjournal.com',
                'port'         => (int)(getenv('MAIL_PORT') ?: 465),
                'username'     => getenv('MAIL_USERNAME') ?: 'no-reply@ajsmrjournal.com',
                'password'     => getenv('MAIL_PASSWORD') ?: '',
                'encryption'   => strtolower((string)(getenv('MAIL_ENCRYPTION') ?: 'ssl')),
                'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ajsmrjournal.com',
                'from_name'    => getenv('MAIL_FROM_NAME') ?: 'AJSMR Editorial Team',
            ];
        }
        return $config[$key] ?? $default;
    }
}

/**
 * Send an email using SMTP (if configured) or native PHP mail() as fallback.
 *
 * @param string $toEmail
 * @param string $subject
 * @param string $htmlBody
 * @param string $textBody
 * @param array $extraHeaders
 * @return array ['success' => bool, 'error' => string]
 */
function sendEmail(string $toEmail, string $subject, string $htmlBody, string $textBody = '', array $extraHeaders = []): array {
    $toEmail = trim($toEmail);
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid or empty recipient email address.'];
    }

    $enabled = (bool)ajsmr_mail_config('enabled', true);
    if (!$enabled) {
        // Development / Safe test mode
        error_log("[AJSMR Mail Service - Dev Mode Log] To: {$toEmail} | Subject: {$subject}");
        return ['success' => true, 'error' => ''];
    }

    $fromAddress = (string)ajsmr_mail_config('from_address');
    $fromName    = (string)ajsmr_mail_config('from_name');
    $host        = (string)ajsmr_mail_config('host');
    $username    = (string)ajsmr_mail_config('username');
    $password    = (string)ajsmr_mail_config('password');
    $port        = (int)ajsmr_mail_config('port');
    $encryption  = (string)ajsmr_mail_config('encryption');

    if (empty($textBody)) {
        $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], $htmlBody));
    }

    // Try socket SMTP transport if SMTP host and username are configured
    if (!empty($host) && !empty($username)) {
        $smtpResult = sendEmailSmtp($host, $port, $encryption, $username, $password, $fromAddress, $fromName, $toEmail, $subject, $htmlBody, $textBody);
        if ($smtpResult['success']) {
            return $smtpResult;
        }
        error_log("[AJSMR Mail Service - SMTP Warning] SMTP failed: " . $smtpResult['error'] . " - Falling back to mail()");
    }

    // Fallback: Native PHP mail() with multipart/alternative MIME boundary
    return sendEmailNativeMail($fromAddress, $fromName, $toEmail, $subject, $htmlBody, $textBody, $extraHeaders);
}

/**
 * Socket-based SMTP mail transport supporting STARTTLS and SSL.
 */
function sendEmailSmtp(string $host, int $port, string $encryption, string $username, string $password, string $fromAddr, string $fromName, string $toAddr, string $subject, string $htmlBody, string $textBody): array {
    $timeout = 10;
    $boundary = "==Multipart_Boundary_x" . md5((string)time()) . "x";

    $rawSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers  = "From: {$encodedFromName} <{$fromAddr}>\r\n";
    $headers .= "To: <{$toAddr}>\r\n";
    $headers .= "Subject: {$rawSubject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: AJSMR Editorial Mailer/1.0\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $textBody . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--{$boundary}--";

    $isSsl = ($encryption === 'ssl' || $port === 465);
    $remoteHost = ($isSsl && strpos($host, 'ssl://') !== 0 ? 'ssl://' : '') . $host;

    $socket = @stream_socket_client($remoteHost . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        return ['success' => false, 'error' => "SMTP Connection failed to {$remoteHost}:{$port} - {$errstr} ({$errno})"];
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function() use ($socket): string {
        $res = '';
        while ($line = fgets($socket, 512)) {
            $res .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $res;
    };

    $sendCommand = function(string $cmd) use ($socket, $readResponse): string {
        fputs($socket, $cmd . "\r\n");
        return $readResponse();
    };

    $greeting = $readResponse();
    if (substr($greeting, 0, 3) !== '220') {
        fclose($socket);
        return ['success' => false, 'error' => "SMTP Server error: {$greeting}"];
    }

    $ehlo = $sendCommand("EHLO " . (gethostname() ?: 'localhost'));

    if ($encryption === 'tls' && $port !== 465) {
        $startTls = $sendCommand("STARTTLS");
        if (substr($startTls, 0, 3) === '220') {
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                fclose($socket);
                return ['success' => false, 'error' => "SMTP TLS encryption handshake failed."];
            }
            $ehlo = $sendCommand("EHLO " . (gethostname() ?: 'localhost'));
        }
    }

    if (!empty($username)) {
        if (empty($password)) {
            fclose($socket);
            return ['success' => false, 'error' => "SMTP Authentication requires a non-empty password (set MAIL_PASSWORD env variable)."];
        }

        $authRes = $sendCommand("AUTH LOGIN");
        if (substr($authRes, 0, 3) === '334') {
            $uRes = $sendCommand(base64_encode($username));
            if (substr($uRes, 0, 3) === '334') {
                $pRes = $sendCommand(base64_encode($password));
                if (substr($pRes, 0, 3) !== '235') {
                    fclose($socket);
                    return ['success' => false, 'error' => "SMTP Authentication failed for user {$username}."];
                }
            } else {
                fclose($socket);
                return ['success' => false, 'error' => "SMTP Username rejected."];
            }
        } else {
            fclose($socket);
            return ['success' => false, 'error' => "SMTP AUTH LOGIN rejected by server: {$authRes}"];
        }
    }

    $mFrom = $sendCommand("MAIL FROM:<{$fromAddr}>");
    if (substr($mFrom, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'error' => "SMTP MAIL FROM rejected: {$mFrom}"];
    }

    $rTo = $sendCommand("RCPT TO:<{$toAddr}>");
    if (substr($rTo, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'error' => "SMTP RCPT TO rejected: {$rTo}"];
    }

    $dRes = $sendCommand("DATA");
    if (substr($dRes, 0, 3) !== '354') {
        fclose($socket);
        return ['success' => false, 'error' => "SMTP DATA rejected: {$dRes}"];
    }

    $fullData = $headers . "\r\n" . $body . "\r\n.";
    $sendRes = $sendCommand($fullData);
    $sendCommand("QUIT");
    fclose($socket);

    if (substr($sendRes, 0, 3) === '250') {
        return ['success' => true, 'error' => ''];
    }

    return ['success' => false, 'error' => "SMTP Transmission error: {$sendRes}"];
}

/**
 * Native PHP mail() helper function with structured MIME multipart headers.
 */
function sendEmailNativeMail(string $fromAddr, string $fromName, string $toAddr, string $subject, string $htmlBody, string $textBody, array $extraHeaders = []): array {
    $boundary = "==Multipart_Boundary_x" . md5((string)time()) . "x";

    $rawSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers  = "From: {$encodedFromName} <{$fromAddr}>\r\n";
    $headers .= "Reply-To: {$fromAddr}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    foreach ($extraHeaders as $k => $v) {
        $headers .= "{$k}: {$v}\r\n";
    }

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $textBody . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--{$boundary}--";

    $ok = @mail($toAddr, $rawSubject, $body, $headers);
    if ($ok) {
        return ['success' => true, 'error' => ''];
    }

    return ['success' => false, 'error' => 'Native mail() function call returned false.'];
}

/**
 * Render responsive HTML email template for AJSMR notifications.
 */
function renderHtmlEmailTemplate(array $p): string {
    $title           = htmlspecialchars((string)($p['title'] ?? 'Manuscript Notification'), ENT_QUOTES, 'UTF-8');
    $greeting        = htmlspecialchars((string)($p['greeting'] ?? 'Dear User,'), ENT_QUOTES, 'UTF-8');
    $messageHtml     = nl2br(htmlspecialchars((string)($p['message'] ?? ''), ENT_QUOTES, 'UTF-8'));
    $manuscriptInfo  = (array)($p['manuscriptInfo'] ?? []);
    $actionUrl       = (string)($p['actionUrl'] ?? '');
    $actionText      = htmlspecialchars((string)($p['actionText'] ?? 'Access Portal'), ENT_QUOTES, 'UTF-8');
    $footerNotes     = htmlspecialchars((string)($p['footerNotes'] ?? ''), ENT_QUOTES, 'UTF-8');

    $infoRowsHtml = '';
    if (!empty($manuscriptInfo)) {
        foreach ($manuscriptInfo as $label => $val) {
            if ($val === null || $val === '') continue;
            $lblHtml = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
            $valHtml = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
            $infoRowsHtml .= "
            <tr>
              <td style=\"padding: 8px 12px; font-weight: 600; color: #475569; width: 35%; border-bottom: 1px solid #e2e8f0; vertical-align: top;\">{$lblHtml}:</td>
              <td style=\"padding: 8px 12px; color: #0f172a; border-bottom: 1px solid #e2e8f0; vertical-align: top;\">{$valHtml}</td>
            </tr>";
        }
    }

    $infoBoxHtml = '';
    if ($infoRowsHtml !== '') {
        $infoBoxHtml = "
        <table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; margin: 20px 0; border-collapse: separate;\">
          {$infoRowsHtml}
        </table>";
    }

    $buttonHtml = '';
    if (!empty($actionUrl)) {
        $safeUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
        $buttonHtml = "
        <div style=\"text-align: center; margin: 28px 0 20px 0;\">
          <a href=\"{$safeUrl}\" style=\"background-color: #0b5fa5; color: #ffffff; padding: 12px 26px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1);\">{$actionText}</a>
        </div>";
    }

    $footerExtra = $footerNotes !== '' ? "<p style=\"margin: 12px 0 0 0; color: #94a3b8; font-size: 11px;\">{$footerNotes}</p>" : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1e293b;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 620px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
          
          <!-- Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 24px 30px; text-align: left;">
              <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.5px;">AJSMR</h1>
              <p style="color: #94a3b8; margin: 4px 0 0 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 500;">International Journal of Science and Research</p>
            </td>
          </tr>

          <!-- Banner Title -->
          <tr>
            <td style="background-color: #f8fafc; padding: 14px 30px; border-bottom: 1px solid #e2e8f0;">
              <h2 style="margin: 0; color: #0b5fa5; font-size: 16px; font-weight: 600;">{$title}</h2>
            </td>
          </tr>

          <!-- Main Content -->
          <tr>
            <td style="padding: 28px 30px; font-size: 14px; line-height: 1.6; color: #334155;">
              <p style="margin-top: 0; font-weight: 600; font-size: 15px; color: #0f172a;">{$greeting}</p>
              
              <div style="margin: 16px 0;">
                {$messageHtml}
              </div>

              {$infoBoxHtml}

              {$buttonHtml}

              <p style="margin-bottom: 0; color: #475569;">
                Regards,<br>
                <strong style="color: #0f172a;">AJSMR Editorial Team</strong><br>
                <span style="font-size: 12px; color: #64748b;">American Journal of Science and Medical Research</span>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; padding: 18px 30px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; text-align: center;">
              <p style="margin: 0;">This is an automated notification from the AJSMR Editorial Management System.</p>
              {$footerExtra}
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Render plain-text fallback version of email.
 */
function renderTextEmailTemplate(array $p): string {
    $title          = (string)($p['title'] ?? 'Manuscript Notification');
    $greeting       = (string)($p['greeting'] ?? 'Dear User,');
    $message        = (string)($p['message'] ?? '');
    $manuscriptInfo = (array)($p['manuscriptInfo'] ?? []);
    $actionUrl      = (string)($p['actionUrl'] ?? '');

    $out  = "==================================================\n";
    $out .= "AJSMR — International Journal of Science and Research\n";
    $out .= "{$title}\n";
    $out .= "==================================================\n\n";

    $out .= "{$greeting}\n\n";
    $out .= "{$message}\n\n";

    if (!empty($manuscriptInfo)) {
        $out .= "--------------------------------------------------\n";
        $out .= "MANUSCRIPT INFORMATION:\n";
        $out .= "--------------------------------------------------\n";
        foreach ($manuscriptInfo as $k => $v) {
            if ($v === null || $v === '') continue;
            $out .= sprintf("%-20s: %s\n", $k, $v);
        }
        $out .= "--------------------------------------------------\n\n";
    }

    if (!empty($actionUrl)) {
        $out .= "Access Portal / Action Link:\n";
        $out .= "{$actionUrl}\n\n";
    }

    $out .= "Regards,\n";
    $out .= "AJSMR Editorial Team\n";
    $out .= "American Journal of Science and Medical Research\n";
    $out .= "http://localhost/editorial/\n";

    return $out;
}
