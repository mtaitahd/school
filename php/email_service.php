<?php
/**
 * Simple SMTP Email Service for Kona Ya Hisabati
 * Uses PHP fsockopen to connect directly to SMTP server.
 */

class EmailService {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private string $encryption;

    public function __construct() {
        $envPath = __DIR__ . '/../.env';
        $env = [];
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || substr($line, 0, 1) === '#') continue;
                if (strpos($line, '=') !== false) {
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v);
                }
            }
        }

        $this->host       = $env['SMTP_HOST'] ?? 'mail.smartmathconner.co.tz';
        $this->port       = (int) ($env['SMTP_PORT'] ?? 465);
        $this->username   = $env['SMTP_USERNAME'] ?? 'info@smartmathconner.co.tz';
        $this->password   = $env['SMTP_PASSWORD'] ?? 'kona2026$';
        $this->fromEmail  = $env['SMTP_FROM_EMAIL'] ?? 'info@smartmathconner.co.tz';
        $this->fromName   = $env['SMTP_FROM_NAME'] ?? 'Kona Ya Hisabati';
        $this->encryption = $env['SMTP_ENCRYPTION'] ?? 'ssl';
    }

    /**
     * Send an email via SMTP
     */
    public function send(string $toEmail, string $subject, string $htmlBody, string $toName = ''): bool {
        $errno = 0;
        $errstr = '';

        $protocol = ($this->encryption === 'ssl') ? 'ssl://' : 'tls://';
        $connectHost = ($this->encryption === 'ssl') ? $this->host : $this->host;

        if ($this->encryption === 'ssl') {
            $fp = fsockopen('ssl://' . $this->host, $this->port, $errno, $errstr, 30);
        } else {
            $fp = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        }

        if (!$fp) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }

        $response = $this->smtpRead($fp);
        $this->smtpCommand($fp, "EHLO " . gethostname());
        $this->smtpCommand($fp, "AUTH LOGIN");
        $this->smtpCommand($fp, base64_encode($this->username));
        $this->smtpCommand($fp, base64_encode($this->password));
        $this->smtpCommand($fp, "MAIL FROM:<{$this->fromEmail}>");
        $this->smtpCommand($fp, "RCPT TO:<{$toEmail}>");
        $this->smtpCommand($fp, "DATA");

        $headers  = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "To: " . ($toName ? "{$toName} <{$toEmail}>" : $toEmail) . "\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";

        $body = $headers . $htmlBody . "\r\n.\r\n";
        fwrite($fp, $body);
        $this->smtpRead($fp);

        $this->smtpCommand($fp, "QUIT");
        fclose($fp);

        return true;
    }

    private function smtpRead($fp): string {
        $response = '';
        while (true) {
            $line = fgets($fp, 512);
            if ($line === false) break;
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    }

    private function smtpCommand($fp, string $command): string {
        fwrite($fp, $command . "\r\n");
        return $this->smtpRead($fp);
    }

    /**
     * Send a password reset code email
     */
    public function sendPasswordResetCode(string $toEmail, string $userName, string $code): bool {
        $subject = "Your Reset Code - Kona Ya Hisabati";

        $html = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,sans-serif;">
            <div style="max-width:500px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:30px;text-align:center;">
                    <h1 style="color:#fff;margin:0;font-size:22px;">Kona Ya Hisabati</h1>
                    <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:14px;">Password Reset Code</p>
                </div>
                <div style="padding:30px;text-align:center;">
                    <p style="color:#333;font-size:15px;line-height:1.6;">Hello <strong>' . htmlspecialchars($userName) . '</strong>,</p>
                    <p style="color:#555;font-size:14px;line-height:1.6;">Use the code below to reset your password:</p>
                    <div style="margin:28px 0;">
                        <span style="display:inline-block;background:#f4f4ff;border:2px dashed #4f46e5;border-radius:10px;padding:16px 40px;font-size:28px;font-weight:800;letter-spacing:6px;color:#4f46e5;font-family:\'Courier New\',monospace;">' . htmlspecialchars($code) . '</span>
                    </div>
                    <p style="color:#888;font-size:13px;line-height:1.5;">This code will expire in <strong>1 hour</strong>. If you did not request a password reset, please ignore this email.</p>
                    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
                    <p style="color:#aaa;font-size:12px;text-align:center;">Kona Ya Hisabati &mdash; Jifunze, Furahia, Fanikiwa</p>
                </div>
            </div>
        </body>
        </html>';

        return $this->send($toEmail, $subject, $html, $userName);
    }
}
