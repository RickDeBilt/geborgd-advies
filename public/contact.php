<?php
declare(strict_types=1);

/**
 * Geborgd Advies — contactformulier-handler.
 *
 * Verwerkt de POST van het contactformulier en verstuurt het bericht via SMTP
 * (SSL, poort 465) naar de ontvanger uit private/contact-config.php.
 *
 * Geen externe libraries nodig: praat rechtstreeks met de mailserver.
 *
 * PLAATSING:
 *   public_html/contact.php          (dit bestand, in de webroot)
 *   ../private/contact-config.php    (config met inloggegevens, BUITEN de webroot)
 */

// ── Alleen POST toestaan ────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /contact');
    exit;
}

// Bepaal of we JSON teruggeven (fetch/JS) of redirecten (zonder JS).
$wantsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

/**
 * Antwoord teruggeven en stoppen.
 */
function respond(bool $ok, string $message, bool $wantsJson): void
{
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 400);
        echo json_encode(['success' => $ok, 'message' => $message]);
    } elseif ($ok) {
        header('Location: /bedankt');
    } else {
        header('Location: /contact?verzonden=mislukt');
    }
    exit;
}

// ── Config laden (buiten webroot) ───────────────────────────────────────
$configPaths = [
    __DIR__ . '/../private/contact-config.php',
    dirname(__DIR__) . '/private/contact-config.php',
    dirname(__DIR__, 2) . '/private/contact-config.php',
];
$config = null;
foreach ($configPaths as $path) {
    if (is_file($path)) {
        $config = require $path;
        break;
    }
}
if (!is_array($config)) {
    respond(false, 'Serverconfiguratie ontbreekt. Neem direct contact op via edwin@geborgdadvies.nl.', $wantsJson);
}

// ── Honeypot: bots vullen dit verborgen veld vaak in ────────────────────
if (!empty($_POST['website'])) {
    // Stil "success" teruggeven zodat bots geen signaal krijgen.
    respond(true, 'Bedankt voor uw bericht.', $wantsJson);
}

// ── Invoer ophalen en schoonmaken ───────────────────────────────────────
function clean(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}
/** Verwijder regeleindes tegen header-injectie. */
function oneLine(string $value): string
{
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $value));
}

$naam      = oneLine(clean('naam'));
$bedrijf   = oneLine(clean('bedrijf'));
$email     = oneLine(clean('email'));
$telefoon  = oneLine(clean('telefoon'));
$onderwerp = oneLine(clean('onderwerp'));
$bericht   = clean('bericht');
$akkoord   = !empty($_POST['akkoord']);

// ── Validatie ───────────────────────────────────────────────────────────
$errors = [];
if ($naam === '')                                        $errors[] = 'naam';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'e-mailadres';
if ($bericht === '')                                     $errors[] = 'bericht';
if (!$akkoord)                                           $errors[] = 'akkoord';

if ($errors) {
    respond(false, 'Controleer de volgende velden: ' . implode(', ', $errors) . '.', $wantsJson);
}

// ── Bericht opbouwen ────────────────────────────────────────────────────
$subject = 'Contactformulier: ' . ($onderwerp !== '' ? $onderwerp : 'nieuw bericht');

$lines = [
    'Nieuw bericht via het contactformulier van geborgdadvies.nl',
    str_repeat('-', 52),
    'Naam:      ' . $naam,
    'Bedrijf:   ' . ($bedrijf !== '' ? $bedrijf : '-'),
    'E-mail:    ' . $email,
    'Telefoon:  ' . ($telefoon !== '' ? $telefoon : '-'),
    'Onderwerp: ' . ($onderwerp !== '' ? $onderwerp : '-'),
    str_repeat('-', 52),
    '',
    $bericht,
    '',
    str_repeat('-', 52),
    'Verzonden op ' . date('d-m-Y H:i') . ' vanaf ' . ($_SERVER['REMOTE_ADDR'] ?? 'onbekend'),
];
$body = implode("\r\n", $lines);

// ── Versturen ───────────────────────────────────────────────────────────
$error = '';
$ok = smtp_send($config, $subject, $body, $email, $naam, $error);

if ($ok) {
    respond(true, 'Bedankt voor uw bericht. Ik neem zo snel mogelijk contact met u op.', $wantsJson);
}
respond(false, 'Het bericht kon niet worden verzonden. Probeer het later opnieuw of mail direct naar edwin@geborgdadvies.nl.', $wantsJson);


/**
 * Verstuurt een e-mail via SMTP over een SSL-verbinding (poort 465).
 * Retourneert true bij succes; bij fout wordt $error gevuld.
 */
function smtp_send(array $cfg, string $subject, string $body, string $replyEmail, string $replyName, string &$error): bool
{
    $host = (string) ($cfg['smtp_host'] ?? '');
    $port = (int) ($cfg['smtp_port'] ?? 465);
    $user = (string) ($cfg['smtp_user'] ?? '');
    $pass = (string) ($cfg['smtp_password'] ?? '');
    $to   = (string) ($cfg['recipient'] ?? '');

    $transport = ($port === 465 ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $context = stream_context_create();
    $fp = @stream_socket_client($transport, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        $error = "verbinding mislukt ($errno $errstr)";
        return false;
    }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // Laatste regel van een SMTP-antwoord heeft een spatie op positie 4.
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $send = static function (string $cmd) use ($fp, $read): string {
        fwrite($fp, $cmd . "\r\n");
        return $read();
    };
    $expect = static function (string $response, string $code) use (&$error): bool {
        if (strncmp($response, $code, 3) !== 0) {
            $error = 'onverwacht antwoord: ' . trim($response);
            return false;
        }
        return true;
    };

    $ehlo = $_SERVER['SERVER_NAME'] ?? 'localhost';

    try {
        if (!$expect($read(), '220')) return false;                       // begroeting
        if (!$expect($send('EHLO ' . $ehlo), '250')) return false;
        if (!$expect($send('AUTH LOGIN'), '334')) return false;
        if (!$expect($send(base64_encode($user)), '334')) return false;
        if (!$expect($send(base64_encode($pass)), '235')) { $error = 'inloggen mislukt'; return false; }
        if (!$expect($send('MAIL FROM:<' . $user . '>'), '250')) return false;
        if (!$expect($send('RCPT TO:<' . $to . '>'), '250')) return false;
        if (!$expect($send('DATA'), '354')) return false;

        $headers = [
            'From: Geborgd Advies (website) <' . $user . '>',
            'Reply-To: ' . encode_name($replyName) . ' <' . $replyEmail . '>',
            'To: <' . $to . '>',
            'Subject: ' . encode_header($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . date('r'),
        ];

        // Normaliseer naar CRLF en pas dot-stuffing toe (regels die met "." beginnen).
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        $message = str_replace(["\r\n", "\r", "\n"], "\n", $message);
        $message = str_replace("\n", "\r\n", $message);
        $message = preg_replace('/^\./m', '..', $message);

        fwrite($fp, $message . "\r\n.\r\n");
        if (!$expect($read(), '250')) return false;

        $send('QUIT');
        return true;
    } finally {
        fclose($fp);
    }
}

/** Codeer een headerwaarde als UTF-8 encoded-word wanneer nodig. */
function encode_header(string $text): string
{
    if (preg_match('/[^\x20-\x7E]/', $text)) {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }
    return $text;
}

/** Codeer een weergavenaam veilig voor gebruik in een adresheader. */
function encode_name(string $name): string
{
    if ($name === '') {
        return '';
    }
    if (preg_match('/[^\x20-\x7E]/', $name)) {
        return encode_header($name);
    }
    return '"' . str_replace('"', '', $name) . '"';
}
