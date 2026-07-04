<?php
declare(strict_types=1);

/**
 * Geborgd Advies — contactformulier-endpoint.
 *
 * Verstuurt berichten via geauthenticeerde SMTP (SMTPS, poort 465) met PHPMailer.
 * Geen PHP mail(). Inloggegevens komen uit een config BUITEN de webroot.
 *
 * Plaatsing op de host (Vimexx / DirectAdmin):
 *   /domains/geborgdadvies.nl/public_html/api/contact.php   (dit bestand)
 *   /domains/geborgdadvies.nl/private/contact-config.php     (inloggegevens)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ── PHPMailer laden (meegeleverd, geen Composer nodig) ──────────────────
require __DIR__ . '/lib/PHPMailer/Exception.php';
require __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/SMTP.php';

// Detecteer of we JSON teruggeven (fetch/JS) of redirecten (zonder JS).
$wantsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

/** Stuur een antwoord en stop. Foutmeldingen bevatten nooit technische details. */
function respond(bool $ok, string $message, bool $wantsJson, int $status = 200): void
{
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : $status);
        echo json_encode(['success' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    } elseif ($ok) {
        header('Location: /bedankt/');
    } else {
        header('Location: /contact/?verzonden=mislukt');
    }
    exit;
}

// ── Alleen POST ──────────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respond(false, 'Ongeldige aanvraag.', $wantsJson, 405);
}

// ── Config laden (buiten webroot) ───────────────────────────────────────
$config = null;
foreach ([
    __DIR__ . '/../../private/contact-config.php',
    dirname(__DIR__, 2) . '/private/contact-config.php',
    dirname(__DIR__, 3) . '/private/contact-config.php',
] as $path) {
    if (is_file($path)) {
        $config = require $path;
        break;
    }
}
if (!is_array($config)) {
    error_log('[contact] configbestand niet gevonden');
    respond(false, 'Er is een technische storing. Mail gerust direct naar edwin@geborgdadvies.nl.', $wantsJson, 500);
}

// ── Honeypot: door mensen onzichtbaar, door bots vaak ingevuld ──────────
if (!empty($_POST['website'])) {
    // Stil "succes" teruggeven zodat bots geen signaal krijgen.
    respond(true, 'Bedankt voor uw bericht.', $wantsJson);
}

// ── Minimale invultijd (anti-spam): 3 seconden ──────────────────────────
// De frontend zet 'elapsed' (in ms) op de tijd tussen laden en verzenden.
if (isset($_POST['elapsed']) && $_POST['elapsed'] !== '') {
    $elapsed = (int) $_POST['elapsed'];
    if ($elapsed < 3000) {
        respond(false, 'Het formulier werd te snel verzonden. Probeer het opnieuw.', $wantsJson, 429);
    }
}

// ── Rate limiting per IP-adres ──────────────────────────────────────────
$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$storageDir = rtrim((string) ($config['storage_dir'] ?? ''), '/\\') ?: sys_get_temp_dir();
[$allowed, $limitMessage] = rate_limit($storageDir, $ip, 5, 3600, 20);
if (!$allowed) {
    respond(false, $limitMessage, $wantsJson, 429);
}

// ── Invoer ophalen en normaliseren ──────────────────────────────────────
function field(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}
/** Verwijder regeleindes tegen header-injectie in koptekstvelden. */
function one_line(string $v): string
{
    return trim(preg_replace('/[\r\n\t]+/', ' ', $v) ?? '');
}

$naam      = one_line(field('naam'));
$bedrijf   = one_line(field('bedrijf'));
$email     = one_line(field('email'));
$telefoon  = one_line(field('telefoon'));
$onderwerp = one_line(field('onderwerp'));
$bericht   = field('bericht');
$akkoord   = !empty($_POST['akkoord']);

// ── Server-side validatie ───────────────────────────────────────────────
$errors = [];

if (mb_strlen($naam) < 2 || mb_strlen($naam) > 100) {
    $errors[] = 'Vul een geldige naam in.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    $errors[] = 'Vul een geldig e-mailadres in.';
}
$telefoonDigits = preg_replace('/\D+/', '', $telefoon);
if (strlen($telefoonDigits) < 9 || strlen($telefoonDigits) > 15 || !preg_match('/^[0-9+()\/\s.-]{9,25}$/', $telefoon)) {
    $errors[] = 'Vul een geldig telefoonnummer in.';
}
if (mb_strlen($bericht) < 10 || mb_strlen($bericht) > 5000) {
    $errors[] = 'Vul een bericht in van minimaal 10 tekens.';
}
if (!$akkoord) {
    $errors[] = 'U dient akkoord te gaan met het gebruik van uw gegevens.';
}

if ($errors) {
    respond(false, implode(' ', $errors), $wantsJson, 422);
}

// ── Bericht opbouwen: HTML + platte tekst ───────────────────────────────
$onderwerpLabel = $onderwerp !== '' ? $onderwerp : 'Nieuw bericht';
$subject = 'Contactformulier: ' . $onderwerpLabel;

$rows = [
    'Naam'      => $naam,
    'Bedrijf'   => $bedrijf !== '' ? $bedrijf : '—',
    'E-mail'    => $email,
    'Telefoon'  => $telefoon !== '' ? $telefoon : '—',
    'Onderwerp' => $onderwerpLabel,
];

// Platte tekst
$plainLines = ['Nieuw bericht via het contactformulier van geborgdadvies.nl', str_repeat('-', 52)];
foreach ($rows as $label => $value) {
    $plainLines[] = sprintf('%-10s %s', $label . ':', $value);
}
$plainLines[] = str_repeat('-', 52);
$plainLines[] = '';
$plainLines[] = $bericht;
$plainBody = implode("\r\n", $plainLines);

// HTML
$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$htmlRows = '';
foreach ($rows as $label => $value) {
    $htmlRows .= '<tr>'
        . '<td style="padding:6px 12px 6px 0;color:#667085;font-size:14px;vertical-align:top;white-space:nowrap;">' . $esc($label) . '</td>'
        . '<td style="padding:6px 0;color:#1f2933;font-size:14px;">' . $esc($value) . '</td>'
        . '</tr>';
}
$htmlBericht = nl2br($esc($bericht));
$htmlBody = '<!doctype html><html lang="nl"><body style="margin:0;background:#f7f4ee;padding:24px;font-family:Arial,Helvetica,sans-serif;">'
    . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e5e0d6;border-radius:12px;overflow:hidden;">'
    . '<div style="background:#172033;padding:20px 24px;"><h1 style="margin:0;color:#ffffff;font-size:18px;">Nieuw bericht via de website</h1></div>'
    . '<div style="padding:24px;">'
    . '<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">' . $htmlRows . '</table>'
    . '<div style="border-top:1px solid #e5e0d6;padding-top:16px;color:#1f2933;font-size:15px;line-height:1.6;">' . $htmlBericht . '</div>'
    . '</div>'
    . '<div style="background:#f7f4ee;padding:12px 24px;color:#667085;font-size:12px;">Verzonden op ' . $esc(date('d-m-Y H:i')) . '</div>'
    . '</div></body></html>';

// ── Versturen met PHPMailer ─────────────────────────────────────────────
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host          = (string) $config['smtp_host'];
    $mail->SMTPAuth      = true;
    $mail->Username      = (string) $config['smtp_user'];
    $mail->Password      = (string) $config['smtp_password'];
    $mail->SMTPSecure    = PHPMailer::ENCRYPTION_SMTPS; // SSL / poort 465
    $mail->Port          = (int) $config['smtp_port'];
    $mail->CharSet       = PHPMailer::CHARSET_UTF8;
    $mail->Encoding      = PHPMailer::ENCODING_BASE64;
    $mail->Timeout       = 20;

    // Vast afzenderadres + naam
    $mail->setFrom((string) $config['from_email'], (string) ($config['from_name'] ?? 'Geborgd Advies'));
    // Ontvanger
    $mail->addAddress((string) $config['recipient'], (string) ($config['recipient_name'] ?? ''));
    // Reply-To = de bezoeker (naam + e-mail)
    $mail->addReplyTo($email, $naam);

    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body    = $htmlBody;
    $mail->AltBody = $plainBody;

    $mail->send();

    record_submission($storageDir, $ip);
    respond(true, 'Bedankt voor uw bericht. Ik neem zo snel mogelijk contact met u op.', $wantsJson);
} catch (MailException $e) {
    // Technische details alleen in het serverlog, nooit naar de bezoeker.
    error_log('[contact] verzenden mislukt: ' . $mail->ErrorInfo);
    respond(false, 'Het bericht kon niet worden verzonden. Probeer het later opnieuw of mail direct naar edwin@geborgdadvies.nl.', $wantsJson, 502);
}


/**
 * Eenvoudige rate limiting per IP op basis van bestanden.
 * @return array{0:bool,1:string} [toegestaan, foutmelding]
 */
function rate_limit(string $dir, string $ip, int $maxPerWindow, int $windowSeconds, int $minInterval): array
{
    $store = $dir . DIRECTORY_SEPARATOR . 'geborgd_rl';
    if (!is_dir($store)) {
        @mkdir($store, 0700, true);
    }
    if (!is_dir($store) || !is_writable($store)) {
        // Kan niet limiteren → niet blokkeren (honeypot + timing vangen nog af).
        return [true, ''];
    }

    $file = $store . DIRECTORY_SEPARATOR . 'rl_' . sha1($ip) . '.json';
    $now = time();

    $timestamps = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $timestamps = array_filter($decoded, static fn($t) => is_int($t) && ($now - $t) < $windowSeconds);
        }
    }

    if ($timestamps) {
        $last = max($timestamps);
        if (($now - $last) < $minInterval) {
            return [false, 'U heeft zojuist al een bericht verstuurd. Wacht even voordat u het opnieuw probeert.'];
        }
    }
    if (count($timestamps) >= $maxPerWindow) {
        return [false, 'U heeft het maximale aantal berichten voor nu bereikt. Probeer het later opnieuw of bel gerust.'];
    }

    return [true, ''];
}

/** Registreer een geslaagde inzending voor de rate limiting. */
function record_submission(string $dir, string $ip): void
{
    $store = $dir . DIRECTORY_SEPARATOR . 'geborgd_rl';
    $file = $store . DIRECTORY_SEPARATOR . 'rl_' . sha1($ip) . '.json';
    if (!is_dir($store) || !is_writable($store)) {
        return;
    }
    $now = time();
    $timestamps = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $timestamps = array_filter($decoded, static fn($t) => is_int($t) && ($now - $t) < 3600);
        }
    }
    $timestamps[] = $now;
    @file_put_contents($file, json_encode(array_values($timestamps)), LOCK_EX);
}
