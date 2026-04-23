<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/api.php';

$endpointUrl = rtrim($config['base_url'], '/') . $config['change_pwd_path'];
$timeout = (int) ($config['request_timeout_seconds'] ?? 10);

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function validate_reset_token(string $endpointUrl, string $token, int $timeout): array
{
    $response = api_patch_json($endpointUrl, ['reset_pwd_token' => $token], $timeout);

    if ($response['error']) {
        return [
            'valid' => null,
            'transport_error' => $response['error'],
            'status' => $response['status'],
            'message' => 'Ezin izan da esteka orain berrikusi.',
        ];
    }

    $status = (int) ($response['status'] ?? 0);
    $data = $response['data'] ?? [];

    if ($status === 200 && array_key_exists('valid', $data)) {
        return ['valid' => (bool) $data['valid'], 'transport_error' => null, 'status' => $status, 'message' => null];
    }

    if ($status === 401) {
        return ['valid' => false, 'transport_error' => null, 'status' => $status, 'message' => $data['message'] ?? 'Token baliogabea edo iraungita'];
    }

    return ['valid' => null, 'transport_error' => null, 'status' => $status, 'message' => $data['message'] ?? 'Ezin izan da esteka orain berrikusi.'];
}

function change_password(string $endpointUrl, string $token, string $password, int $timeout): array
{
    $response = api_patch_json($endpointUrl, ['reset_pwd_token' => $token, 'new_password' => $password], $timeout);

    if ($response['error']) {
        return ['success' => false, 'expired' => false, 'message' => 'Ezin izan da APIrekin konektatu. Egiaztatu oinarri-URLa edo zerbitzaria.', 'status' => 0];
    }

    $status = (int) ($response['status'] ?? 0);
    $data = $response['data'] ?? [];

    if ($status === 200) {
        return ['success' => true, 'expired' => false, 'message' => $data['message'] ?? 'Pasahitza behar bezala aldatu da', 'status' => $status];
    }

    if ($status === 401) {
        return ['success' => false, 'expired' => true, 'message' => $data['message'] ?? 'Token baliogabea edo iraungita', 'status' => $status];
    }

    return ['success' => false, 'expired' => false, 'message' => $data['message'] ?? 'Ezin izan da pasahitza eguneratu.', 'status' => $status];
}

$scene = 'form';
$errors = [];
$pageHttpCode = 200;
$formPassword = '';
$formConfirm = '';
$successMessage = null;
$token = trim((string) ($_GET['token'] ?? $_GET['reset_pwd_token'] ?? ''));
$tokenPreview = $token !== ''
    ? (strlen($token) > 20 ? substr($token, 0, 20) . '…' : $token)
    : 'token gabe';

if ($token === '') {
    $scene = 'expired';
    $pageHttpCode = 404;
} else {
    $tokenCheck = validate_reset_token($endpointUrl, $token, $timeout);
    if ($tokenCheck['valid'] === false) {
        $scene = 'expired';
        $pageHttpCode = 404;
    } elseif ($tokenCheck['valid'] === null) {
        $scene = 'error';
        $errors[] = $tokenCheck['message'];
        $pageHttpCode = 503;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $scene === 'form') {
    $formPassword = (string) ($_POST['new_password'] ?? '');
    $formConfirm = (string) ($_POST['confirm_password'] ?? '');

    if ($formPassword === '') $errors[] = 'Pasahitz berria nahitaezkoa da.';
    if (strlen($formPassword) < 6) $errors[] = 'Pasahitzak gutxienez 6 karaktere izan behar ditu.';
    if ($formPassword !== $formConfirm) $errors[] = 'Pasahitzak ez datoz bat.';

    if (empty($errors)) {
        $changeResult = change_password($endpointUrl, $token, $formPassword, $timeout);
        if ($changeResult['success']) {
            $scene = 'success';
            $successMessage = $changeResult['message'];
        } elseif ($changeResult['expired']) {
            $scene = 'expired';
            $pageHttpCode = 404;
        } else {
            $errors[] = $changeResult['message'];
        }
    }
}

http_response_code($pageHttpCode);
?><!doctype html>
<html lang="eu">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?php echo match ($scene) {
    'success' => 'Pasahitza eguneratuta — pakAG',
    'expired' => '404 — Esteka baliogabea — pakAG',
    'error'   => 'Errorea — pakAG',
    default   => 'Pasahitza aldatu — pakAG',
}; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="assets/css/styles.css"/>
</head>
<body>
<div class="layout">

  <!-- ── LEFT PANEL ─────────────────────────────────────── -->
  <div class="left-panel">
    <div class="ambient-glow"></div>

    <!-- Logo -->
    <div class="panel-logo">
      <div class="logo">
        <div class="logo-badge">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
            <path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
          </svg>
        </div>
        <div class="logo-text">pak<span>AG</span></div>
      </div>
    </div>

    <!-- Content -->
    <div class="panel-content fade-up">

      <?php if ($scene === 'form'): ?>

        <div class="form-header">
          <p class="form-eyebrow">· PASAHITZA ALDATU</p>
          <h1 class="form-title">Pasahitz berria sortu</h1>
          <p class="form-desc">Aukeratu pasahitz seguru bat zure pakAG konturako.</p>
        </div>

        <?php foreach ($errors as $error): ?>
          <div class="alert fade-in" role="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
            </svg>
            <span><?php echo h($error); ?></span>
          </div>
        <?php endforeach; ?>

        <form data-reset-form data-form-card method="post" novalidate>

          <div class="field">
            <label class="field-label" for="new_password">Pasahitz berria</label>
            <div class="input-wrap">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input id="new_password" name="new_password" type="password" minlength="6" placeholder="Gutxienez 6 karaktere" value="<?php echo h($formPassword); ?>" autocomplete="new-password" required data-password/>
              <button type="button" class="toggle-visibility" data-toggle-visibility data-target="new_password" aria-label="Pasahitza erakutsi edo ezkutatu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>

            <div class="strength fade-in <?php echo $formPassword !== '' ? 'is-visible' : ''; ?>" data-strength>
              <div class="strength-head">
                <span data-strength-label>Oso ahula</span>
                <span data-strength-value>0/5</span>
              </div>
              <div class="strength-track">
                <div class="strength-bar" data-strength-bar></div>
              </div>
              <div class="criteria">
                <div class="criterion" data-criterion="length"><span class="criterion-bullet"></span>Gutxienez 6 karaktere</div>
                <div class="criterion" data-criterion="upper"><span class="criterion-bullet"></span>Maiuskula bat</div>
                <div class="criterion" data-criterion="number"><span class="criterion-bullet"></span>Zenbaki bat</div>
                <div class="criterion" data-criterion="symbol"><span class="criterion-bullet"></span>Sinbolo bat (!@#…)</div>
              </div>
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="confirm_password">Pasahitza berretsi</label>
            <div class="input-wrap <?php echo ($formConfirm !== '' && $formPassword !== $formConfirm) ? 'has-error' : ''; ?>">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              <input id="confirm_password" name="confirm_password" type="password" minlength="6" placeholder="Errepikatu pasahitza" value="<?php echo h($formConfirm); ?>" autocomplete="new-password" required data-confirm-password/>
              <button type="button" class="toggle-visibility" data-toggle-visibility data-target="confirm_password" aria-label="Pasahitza erakutsi edo ezkutatu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <div class="inline-message error <?php echo ($formConfirm !== '' && $formPassword !== $formConfirm) ? 'is-visible' : ''; ?>" data-mismatch-error>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
              Pasahitzak ez datoz bat
            </div>
            <div class="inline-message success <?php echo ($formConfirm !== '' && $formPassword === $formConfirm) ? 'is-visible' : ''; ?>" data-match-success>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              Pasahitzak bat datoz
            </div>
          </div>

          <button class="primary-btn" type="submit" data-submit>
            Pasahitza aldatu
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </form>

        <div class="token-hint">Token: <code><?php echo h($tokenPreview); ?></code></div>

        <div class="card-center fade-in hidden" data-loading-state style="margin-top:20px">
          <div class="loader-ring"></div>
          <div style="font-size:16px;font-weight:600;margin-bottom:6px">Pasahitza eguneratzen…</div>
          <div style="font-size:13px;color:var(--text-secondary)">Zure aldaketak modu seguruan gordetzen ari gara.</div>
        </div>

      <?php elseif ($scene === 'success'): ?>

        <div class="card-center fade-up">
          <div class="state-icon success">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          </div>
          <h2>Pasahitza eguneratuta!</h2>
          <p>Zure pasahitza behar bezala aldatu da.<br/>Orain saioa has dezakezu zure kredentzial berriekin.</p>
          <div class="center-actions">
            <a class="primary-btn" href="<?php echo h($config['login_url']); ?>">
              Saioa hasi
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
          </div>
          <div class="status-note"><?php echo h($successMessage ?? 'Segurtasunagatik, gainerako saio guztiak itxi dira.'); ?></div>
        </div>

      <?php elseif ($scene === 'expired'): ?>

        <div class="card-center fade-up">
          <div class="state-icon warning">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <h2>404 · Esteka baliogabea</h2>
          <p>Berreskuratze-esteka hau ez da existitzen, iraungitu da edo dagoeneko erabili zen.<br/>Berri bat eskatu behar duzu.</p>
          <div class="center-actions">
            <a class="primary-btn" href="mailto:<?php echo h($config['support_email']); ?>">Laguntza-taldearekin harremanetan jarri</a>
          </div>
          <div class="status-note">Jasotako tokena: <code><?php echo h($tokenPreview); ?></code></div>
        </div>

      <?php else: ?>

        <div class="card-center fade-up">
          <div class="state-icon error">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
          </div>
          <h2>Zerbait gaizki joan da</h2>
          <p>Ezin izan dugu zure esteka berrikusi edo APIrekin konektatu.<br/>Egiaztatu konfigurazioa edo saiatu geroago.</p>
          <?php foreach ($errors as $error): ?>
            <div class="alert fade-in" role="alert" style="margin-top:18px;text-align:left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
              </svg>
              <span><?php echo h($error); ?></span>
            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>

    <p class="panel-footer">pakAG © 2026 · Elduaien / Aduna</p>
  </div>

  <!-- ── RIGHT PANEL ─────────────────────────────────────── -->
  <div class="right-panel">

    <!-- Grid background -->
    <svg class="map-grid" style="width:100%;height:100%" xmlns="http://www.w3.org/2000/svg">
      <?php for($i=0;$i<20;$i++): ?>
        <line x1="0" y1="<?php echo $i*60; ?>" x2="100%" y2="<?php echo $i*60; ?>" stroke="var(--border-normal)" stroke-width="1"/>
        <line x1="<?php echo $i*80; ?>" y1="0" x2="<?php echo $i*80; ?>" y2="100%" stroke="var(--border-normal)" stroke-width="1"/>
      <?php endfor; ?>
    </svg>

    <!-- Card 1: Segurtasun-aholkuak -->
    <div class="float-card fc-1">
      <div class="shield-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
      </div>
      <div class="fc-title">Pasahitz seguruaren gomendioak</div>
      <div class="fc-criteria">
        <div class="fc-crit-row"><span class="fc-dot ok"></span><span style="color:var(--text-secondary)">Gutxienez 8 karaktere</span></div>
        <div class="fc-crit-row"><span class="fc-dot ok"></span><span style="color:var(--text-secondary)">Maiuskula eta minuskula</span></div>
        <div class="fc-crit-row"><span class="fc-dot ok"></span><span style="color:var(--text-secondary)">Zenbakiak eta sinboloak</span></div>
        <div class="fc-crit-row"><span class="fc-dot warn"></span><span style="color:var(--text-secondary)">Ez erabili datu pertsonalik</span></div>
      </div>
    </div>

    <!-- Card 2: Indarra -->
    <div class="float-card fc-2">
      <div class="fc-title" style="margin-bottom:6px">Pasahitzaren indarra</div>
      <div class="fc-sub">Karaktere mota desberdinak konbinatzeak zure kontua babesteko aukera hobetzen du.</div>
      <div class="fc-bar-wrap">
        <div class="fc-bar-label">
          <span>Indarra</span>
          <span style="color:var(--accent-light);font-weight:600">Ona</span>
        </div>
        <div class="fc-track"><div class="fc-bar"></div></div>
      </div>
    </div>

    <!-- Card 3: Saioak -->
    <div class="float-card fc-3">
      <div class="fc-title" style="margin-bottom:4px">Saio aktiboei buruz</div>
      <div class="fc-sub">Pasahitza aldatzean, segurtasunagatik beste saio guztiak automatikoki itxiko dira.</div>
      <div class="sessions-row">
        <span class="session-dot"></span>
        <span class="session-txt">Saio aktiboak itxita egongo dira</span>
      </div>
    </div>

    <!-- Headline -->
    <div class="panel-headline">
      <h2>Zure kontua,<br><span>babestuta.</span></h2>
      <p>pakAG plataformaren segurtasun-sistema — zure datuak eta ibilbideak beti seguru.</p>
    </div>

  </div>

</div>
<script src="assets/js/app.js"></script>
</body>
</html>
