<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/lib/api.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function track_package(string $baseUrl, string $trackingToken, int $timeout): array
{
    $url = rtrim($baseUrl, '/') . '/api/tracking/' . urlencode($trackingToken);
    $response = api_get_json($url, $timeout);

    if (!empty($response['error'])) {
        return ['success' => false, 'message' => 'Konexio-errorea: ' . $response['error']];
    }

    $status = (int) ($response['status'] ?? 0);
    $data = $response['data'] ?? [];

    if ($status === 200) return ['success' => true, 'data' => $data];
    if ($status === 404) return ['success' => false, 'message' => 'Jarraipen-tokena baliogabea edo iraungita dago.'];

    return ['success' => false, 'message' => $data['message'] ?? 'Errorea paketea bilatzean (Errorea ' . $status . ').'];
}

$scene = 'search';
$packageData = null;
$errors = [];
$trackingToken = trim((string) ($_GET['token'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trackingToken = trim((string) ($_POST['tracking_token'] ?? ''));
    if (empty($trackingToken)) {
        $errors[] = 'Mesedez, sartu zure jarraipen-tokena.';
    } else {
        $result = track_package($config['base_url'], $trackingToken, $config['request_timeout_seconds']);
        if ($result['success']) { $scene = 'tracking'; $packageData = $result['data']; }
        else { $errors[] = $result['message']; }
    }
}

if ($trackingToken !== '' && $scene === 'search') {
    $result = track_package($config['base_url'], $trackingToken, $config['request_timeout_seconds']);
    if ($result['success']) { $scene = 'tracking'; $packageData = $result['data']; }
    else { $errors[] = $result['message']; }
}

$statusMap = [
    'pending'          => ['label' => 'Zain',              'icon' => '📋', 'class' => 'pending'],
    'processing'       => ['label' => 'Prozesatzen',       'icon' => '⚙️', 'class' => 'pending'],
    'in_warehouse'     => ['label' => 'Biltegian',         'icon' => '🏢', 'class' => 'pending'],
    'in_transit'       => ['label' => 'Garraioan',         'icon' => '🚚', 'class' => 'pending'],
    'out_for_delivery' => ['label' => 'Banaketara irten',  'icon' => '📦', 'class' => 'pending'],
    'in_delivery'      => ['label' => 'Banatzen',          'icon' => '📦', 'class' => 'pending'],
    'delivered'        => ['label' => 'Entregatua',        'icon' => '✓',  'class' => 'delivered'],
    'failed'           => ['label' => 'Entrega huts egin', 'icon' => '✗',  'class' => 'failed'],
    'returned'         => ['label' => 'Itzulita',          'icon' => '↩️', 'class' => 'failed'],
];
?><!doctype html>
<html lang="eu">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Paketea jarraitu — pakAG</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets/css/styles.css"/>
</head>
<body>
<div class="layout">

  <!-- ── LEFT PANEL ─────────────────────────────────── -->
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

    <!-- Form area -->
    <div class="panel-content fade-up">

      <?php if ($scene === 'search'): ?>

        <div class="form-header">
          <p class="form-eyebrow">· PAKETEAK JARRAITU</p>
          <h1 class="form-title">Zure bidalketa kokatu</h1>
          <p class="form-desc">Sartu jarraipen-kodea egoera eta kokapena denbora errealean ikusteko.</p>
        </div>

        <?php foreach ($errors as $error): ?>
          <div class="alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php echo h($error); ?>
          </div>
        <?php endforeach; ?>

        <form method="post" novalidate>
          <div class="fields">
            <div>
              <label class="field-label">Jarraipen-tokena</label>
              <div class="input-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <input name="tracking_token" type="text" placeholder="Itsatsi tokena baieztatze-emailetik" autofocus autocomplete="off" value="<?php echo h($trackingToken); ?>"/>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-primary">
            Paketea jarraitu
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
            </svg>
          </button>
        </form>

        <div class="hint">
          Begiratu eskaeraren baieztatze-emaila<br>
          <span style="margin-top:4px;display:block">Zure jarraipen-token bakarrarekin esteka bat bidali dizugu</span>
        </div>

      <?php elseif ($scene === 'tracking' && $packageData): ?>
        <?php
          $status = $packageData['status'] ?? 'unknown';
          $statusInfo = $statusMap[$status] ?? ['label' => 'Estado desconocido', 'icon' => '?', 'class' => 'pending'];
        ?>

        <div class="form-header">
          <p class="form-eyebrow">· BIDALKETA EGOERA</p>
          <h1 class="form-title" style="font-size:22px"><?php echo h($packageData['tracking_code'] ?? 'Zure paketea'); ?></h1>
        </div>

        <div class="tracking-header">
          <div>
            <div class="status-label"><?php echo h($statusInfo['label']); ?></div>
          </div>
          <div class="status-icon"><?php echo $statusInfo['icon']; ?></div>
        </div>

        <?php if (!empty($packageData['recipient_name'])): ?>
          <div class="pkg-info">
            <div class="pkg-info-label">Hartzailea</div>
            <div class="pkg-info-value" style="font-weight:500"><?php echo h($packageData['recipient_name']); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($packageData['address'])): ?>
          <div class="pkg-info">
            <div class="pkg-info-label">Entrega helbidea</div>
            <div class="pkg-info-value">
              <?php echo h($packageData['address']['street'] ?? ''); ?>
              <?php if (!empty($packageData['address']['city'])): ?><br><?php echo h($packageData['address']['city']); ?><?php endif; ?>
              <?php if (!empty($packageData['address']['postal_code'])): ?>, <?php echo h($packageData['address']['postal_code']); ?><?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($packageData['estimated_delivery'])): ?>
          <div class="pkg-info">
            <div class="pkg-info-label">Entrega estimatua</div>
            <div class="pkg-info-value"><?php echo h($packageData['estimated_delivery']); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($packageData['last_update'])): ?>
          <div class="pkg-info">
            <div class="pkg-info-label">Azken eguneratzea</div>
            <div class="pkg-info-value" style="font-size:12px;color:var(--text-disabled)"><?php echo h($packageData['last_update']); ?></div>
          </div>
        <?php endif; ?>

        <div class="tracking-footer">
          <form method="post">
            <button type="submit" class="btn-secondary">Beste pakete bat bilatu</button>
          </form>
        </div>

      <?php endif; ?>
    </div>

    <p class="panel-footer">pakAG © 2026 — v2.4.1</p>
  </div>

  <!-- ── RIGHT PANEL ────────────────────────────────── -->
  <div class="right-panel">

    <!-- Subtle grid -->
    <svg class="map-grid" style="position:absolute;inset:0;width:100%;height:100%" xmlns="http://www.w3.org/2000/svg">
      <?php for($i=0;$i<20;$i++): ?>
        <line x1="0" y1="<?php echo $i*60; ?>" x2="100%" y2="<?php echo $i*60; ?>" stroke="var(--border-normal)" stroke-width="1"/>
      <?php endfor; ?>
      <?php for($i=0;$i<20;$i++): ?>
        <line x1="<?php echo $i*80; ?>" y1="0" x2="<?php echo $i*80; ?>" y2="100%" stroke="var(--border-normal)" stroke-width="1"/>
      <?php endfor; ?>
    </svg>

    <!-- Route line + dots -->
    <svg class="map-route" style="position:absolute;inset:0;width:100%;height:100%;opacity:.4" xmlns="http://www.w3.org/2000/svg">
      <polyline points="120,280 240,160 360,240 480,120" stroke="var(--accent-primary)" stroke-width="2" fill="none" stroke-dasharray="6 4"/>
      <circle cx="120" cy="280" r="6" fill="var(--st-delivered-fg)"/>
      <circle cx="240" cy="160" r="6" fill="var(--st-delivered-fg)"/>
      <circle cx="360" cy="240" r="8" fill="var(--accent-light)"/>
      <circle cx="480" cy="120" r="6" fill="var(--st-assigned-fg)"/>
    </svg>

    <!-- Card 1: Package in transit -->
    <div class="float-card fc-1">
      <div class="fc-meta">
        <span class="fc-code">PKG-261042</span>
        <span class="transit-badge"><span class="transit-dot"></span> Garraioan</span>
      </div>
      <div class="fc-name">Itziar Etxeberria</div>
      <div class="fc-addr">Kale Nagusia 12, Tolosa</div>
      <div class="fc-sep"></div>
      <div class="fc-bottom">
        <span>Geldialdia #3</span>
        <span>ETA 10:20</span>
      </div>
    </div>

    <!-- Card 2: Route progress -->
    <div class="float-card fc-2">
      <div class="fc-route-head">
        <div class="fc-truck-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
        </div>
        <div>
          <div class="fc-route-label">Gaurko ibilbidea</div>
          <div class="fc-route-title">8 geldialdia · 24 km</div>
        </div>
      </div>
      <div class="progress-track"><div class="progress-bar"></div></div>
      <div class="fc-progress-label"><strong>8tik 5</strong> osatuta</div>
    </div>

    <!-- Card 3: Fake map -->
    <div class="float-card fc-3" style="padding:0">
      <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" style="background:var(--bg-darkest)">
        <?php for($i=0;$i<6;$i++): ?>
          <line x1="0" y1="<?php echo $i*34; ?>" x2="320" y2="<?php echo $i*34; ?>" stroke="var(--border-normal)" stroke-width="1" opacity=".4"/>
          <line x1="<?php echo $i*64; ?>" y1="0" x2="<?php echo $i*64; ?>" y2="170" stroke="var(--border-normal)" stroke-width="1" opacity=".4"/>
        <?php endfor; ?>
        <polyline points="60,140 140,80 220,120 300,60" stroke="var(--accent-primary)" stroke-width="2" fill="none" stroke-dasharray="5 3"/>
        <circle cx="60" cy="140" r="5" fill="var(--st-delivered-fg)"/>
        <circle cx="140" cy="80" r="5" fill="var(--st-delivered-fg)"/>
        <circle cx="220" cy="120" r="7" fill="var(--accent-light)"/>
        <circle cx="300" cy="60" r="5" fill="var(--st-assigned-fg)"/>
      </svg>
    </div>

    <!-- Headline -->
    <div class="panel-headline">
      <h2>Pakete bakoitza,<br><span>bere lekuan.</span></h2>
      <p>Adunako banaketa-flotarako operazio-zentroa — ibilbidea, egoera eta trazabilitatea pantaila bakarrean.</p>
    </div>
  </div>

</div>
</body>
</html>
