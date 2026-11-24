<?php
declare(strict_types=1);

/**
 * simplysichtbar.at – DSGVO-konformes Kontaktformular-Backend für Hostinger
 * - Kein Drittanbieter, keine externen Bibliotheken (kein Composer nötig)
 * - 4-stufiger unsichtbarer Spam-Schutz: Honeypot, Zeitprüfung, Origin-Check, IP-Rate-Limit
 * - Unterstützt sowohl Hostinger PHP mail() (Standard) als auch optional direktes SMTP (TLS/SSL)
 */

// ============================================================================
// KONFIGURATION (bei Bedarf anpassen)
// ============================================================================
$TO_EMAIL       = 'hallo@simplysichtbar.at';
$FROM_EMAIL     = 'hallo@simplysichtbar.at'; // Sollte ein echtes Postfach deiner Domain bei Hostinger sein
$FROM_NAME      = 'simplysichtbar.at Kontaktformular';
$ALLOWED_HOSTS  = ['simplysichtbar.at', 'www.simplysichtbar.at', 'localhost', '127.0.0.1'];

// Optional: Authentifizierter SMTP-Versand über Hostinger (falls PHP mail() nicht gewünscht ist)
$USE_SMTP       = false;
$SMTP_HOST      = 'smtp.hostinger.com';
$SMTP_PORT      = 465; // SSL
$SMTP_USER      = 'hallo@simplysichtbar.at';
$SMTP_PASSWORD  = '';  // Hier nur eintragen, wenn $USE_SMTP = true
// ============================================================================

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

function respond(bool $ok, string $message, int $status = 200): never {
    global $isAjax;
    if ($isAjax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $param = $ok ? 'sent=1' : 'error=1';
    header('Location: /?' . $param . '#kontakt', true, 303);
    exit;
}

function clean_header_value(string $value): string {
    return trim(str_replace(["\r", "\n", "\0"], '', $value));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Methode nicht erlaubt.', 405);
}

// 1. Origin- / Referer-Prüfung gegen direkte Bot-POSTs von außen
$originHeader = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
if ($originHeader !== '') {
    $host = strtolower((string) parse_url($originHeader, PHP_URL_HOST));
    if ($host !== '' && !in_array($host, $ALLOWED_HOSTS, true)) {
        respond(false, 'Ungültige Anfragequelle.', 403);
    }
}

// 2. Honeypot-Prüfung (Bots füllen unsichtbare Felder aus -> still erfolgreich tun)
$honeypot = trim((string) ($_POST['website'] ?? ''));
if ($honeypot !== '') {
    respond(true, 'Vielen Dank! Deine Anfrage wurde übermittelt.');
}

// 3. Zeitprüfung (mind. 3 Sekunden zwischen Seitenaufruf und Absenden, max. 24 Stunden)
$formTs = (int) ($_POST['form_ts'] ?? 0);
$now    = time();
if ($formTs > 0) {
    $elapsed = $now - $formTs;
    if ($elapsed < 3) {
        respond(false, 'Bitte nimm dir kurz einen Moment Zeit und sende das Formular erneut.', 429);
    }
    if ($elapsed > 86400) {
        respond(false, 'Das Formular ist abgelaufen. Bitte lade die Seite kurz neu.', 400);
    }
}

// 4. Einfaches IP-Rate-Limit (max. 5 Anfragen pro 15 Minuten pro IP)
$ipHash    = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$rateFile  = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ss_rate_' . $ipHash . '.json';
$windowSec = 900;
$maxReqs   = 5;
$timestamps = [];
if (is_file($rateFile)) {
    $decoded = json_decode((string) @file_get_contents($rateFile), true);
    if (is_array($decoded)) {
        $timestamps = array_values(array_filter($decoded, static fn($t) => is_int($t) && ($now - $t) < $windowSec));
    }
}
if (count($timestamps) >= $maxReqs) {
    respond(false, 'Zu viele Anfragen in kurzer Zeit. Bitte versuche es in einigen Minuten erneut oder ruf uns direkt an.', 429);
}
$timestamps[] = $now;
@file_put_contents($rateFile, json_encode($timestamps), LOCK_EX);

// 5. Eingaben prüfen & säubern
$name    = clean_header_value((string) ($_POST['name'] ?? ''));
$contact = clean_header_value((string) ($_POST['contact'] ?? ''));
$message = trim(str_replace("\0", '', (string) ($_POST['message'] ?? '')));

if ($name === '' || mb_strlen($name) > 160) {
    respond(false, 'Bitte gib deinen Namen bzw. Betrieb an.', 400);
}
if ($contact === '' || mb_strlen($contact) > 160) {
    respond(false, 'Bitte gib deine E-Mail-Adresse oder Telefonnummer an.', 400);
}
if (mb_strlen($message) > 4000) {
    respond(false, 'Die Nachricht ist zu lang (maximal 4.000 Zeichen).', 400);
}

// Spam-Link-Filter (mehr als 2 URLs in einer Kurznachricht sind fast immer Spam)
if (preg_match_all('/https?:\/\/|www\./i', $message) > 2) {
    respond(false, 'Bitte füge maximal zwei Links in deine Nachricht ein.', 400);
}

// Reply-To setzen, falls der Nutzer eine gültige E-Mail-Adresse eingegeben hat
$replyTo = filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : $FROM_EMAIL;

$subjectRaw = 'Neue Website-Anfrage über simplysichtbar.at: ' . $name;
$subject    = '=?UTF-8?B?' . base64_encode($subjectRaw) . '?=';
$body       = implode("\r\n", [
    'Neue Anfrage über das Kontaktformular auf simplysichtbar.at',
    '------------------------------------------------------------',
    'Name / Betrieb:       ' . $name,
    'E-Mail / Telefon:     ' . $contact,
    'Zeitpunkt:            ' . date('d.m.Y H:i:s'),
    '',
    'Nachricht:',
    $message !== '' ? $message : '(Keine Nachricht angegeben)',
    '------------------------------------------------------------',
]);

$encodedFromName = '=?UTF-8?B?' . base64_encode($FROM_NAME) . '?=';
$headers = [
    'From: ' . $encodedFromName . ' <' . $FROM_EMAIL . '>',
    'Reply-To: ' . $replyTo,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'X-Mailer: PHP/' . phpversion(),
];

function send_via_smtp(
    string $host,
    int $port,
    string $user,
    string $pass,
    string $from,
    string $to,
    string $subject,
    string $body,
    array $headers
): bool {
    $fp = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 10);
    if (!$fp) {
        return false;
    }
    stream_set_timeout($fp, 10);

    $read = static function () use ($fp): string {
        $resp = '';
        while (($line = fgets($fp, 515)) !== false) {
            $resp .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $resp;
    };

    $cmd = static function (string $command, int $expect) use ($fp, $read): bool {
        fwrite($fp, $command . "\r\n");
        $resp = $read();
        return (int) substr($resp, 0, 3) === $expect;
    };

    $banner = $read();
    if ((int) substr($banner, 0, 3) !== 220) {
        fclose($fp);
        return false;
    }

    $ok = $cmd('EHLO simplysichtbar.at', 250)
        && $cmd('AUTH LOGIN', 334)
        && $cmd(base64_encode($user), 334)
        && $cmd(base64_encode($pass), 235)
        && $cmd('MAIL FROM:<' . $from . '>', 250)
        && $cmd('RCPT TO:<' . $to . '>', 250)
        && $cmd('DATA', 354);

    if (!$ok) {
        fclose($fp);
        return false;
    }

    $dataHeaders = array_merge(
        ['To: <' . $to . '>', 'Subject: ' . $subject, 'Date: ' . date('r')],
        $headers
    );
    $safeBody = preg_replace('/^\./m', '..', $body);
    fwrite($fp, implode("\r\n", $dataHeaders) . "\r\n\r\n" . $safeBody . "\r\n.\r\n");
    $sent = (int) substr($read(), 0, 3) === 250;
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $sent;
}

$sent = $USE_SMTP && $SMTP_PASSWORD !== ''
    ? send_via_smtp($SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASSWORD, $FROM_EMAIL, $TO_EMAIL, $subject, $body, $headers)
    : @mail($TO_EMAIL, $subject, $body, implode("\r\n", $headers), '-f ' . $FROM_EMAIL);

if ($sent) {
    respond(true, 'Vielen Dank! Deine Anfrage ist bei uns eingegangen – wir melden uns innerhalb von 24 Stunden.');
}

respond(false, 'Beim Senden ist leider ein technischer Fehler aufgetreten. Bitte schreib uns direkt an hallo@simplysichtbar.at oder ruf uns an.', 500);
