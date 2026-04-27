<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html" encoding="UTF-8" indent="yes"
    doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN"/>

  <!-- ═══════════════════════════════════════════
       ROOT — generates the full HTML document
       ═══════════════════════════════════════════ -->
  <xsl:template match="/landing">
    <html lang="{meta/lang}">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title><xsl:value-of select="meta/title"/></title>
        <meta name="description" content="{meta/description}"/>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&amp;family=Inter:ital,wght@0,300;0,400;0,500;1,300&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet"/>
        <style>
          /* ── RESET ── */
          *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

          /* ── CSS VARIABLES (dark purple palette) ── */
          :root {
            --bg-darkest:  #0e0b16;
            --bg-dark:     #1a1626;
            --bg-surface:  #231d35;
            --bg-elevated: #2d2545;

            --accent-primary: #7c3aed;
            --accent-hover:   #6d28d9;
            --accent-light:   #a78bfa;
            --accent-subtle:  #3d2960;

            --text-primary:   #f5f3ff;
            --text-secondary: #a89dc4;
            --text-disabled:  #6b5b8a;

            --border-normal: #3d3358;
            --border-focus:  #7c3aed;

            --sh-sm:      0 1px 3px rgba(0,0,0,0.4);
            --sh-md:      0 4px 16px rgba(0,0,0,0.5);
            --sh-glow:    0 8px 32px rgba(124,58,237,0.25);
            --sh-glow-sm: 0 0 0 3px rgba(124,58,237,0.18);

            --r-sm:  6px;
            --r-md:  8px;
            --r-lg:  12px;
            --r-xl:  16px;
            --r-pill: 9999px;

            --ff-sans:    'Inter', system-ui, -apple-system, sans-serif;
            --ff-display: 'Syne', 'Inter', system-ui, sans-serif;
            --ff-mono:    'JetBrains Mono', ui-monospace, monospace;
          }

          /* ── BASE ── */
          html { scroll-behavior: smooth; }

          body {
            background: var(--bg-dark);
            color: var(--text-primary);
            font-family: var(--ff-sans);
            font-size: 16px;
            line-height: 1.6;
            overflow-x: hidden;
          }

          /* ── KEYFRAME ANIMATIONS ── */
          @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
          }
          @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .4; transform: scale(1.5); }
          }
          @keyframes heroGlow {
            0%   { opacity: .5; }
            50%  { opacity: 1; }
            100% { opacity: .5; }
          }
          @keyframes gridDrift {
            from { transform: translateY(0); }
            to   { transform: translateY(60px); }
          }
          @keyframes textShimmer {
            0%   { background-position: 0% center; }
            100% { background-position: 200% center; }
          }
          @keyframes cardShimmer {
            0%   { left: -100%; }
            60%  { left: 120%; }
            100% { left: 120%; }
          }
          @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
          }
          @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
          }
          @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(124,58,237,.2); }
            50%       { box-shadow: 0 0 0 10px rgba(124,58,237,.4); }
          }
          @keyframes borderGlow {
            0%, 100% { border-color: var(--accent-subtle); }
            50%       { border-color: var(--accent-primary); }
          }
          @keyframes statCount {
            from { opacity: 0; transform: translateY(10px) scale(.9); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
          }

          /* ── NAV ── */
          nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.2rem 5%;
            background: rgba(14,11,22,.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-normal);
          }
          .logo {
            font-family: var(--ff-display);
            font-weight: 800; font-size: 1.6rem;
            letter-spacing: -.03em; color: var(--text-primary);
            text-decoration: none;
          }
          .logo span { color: var(--accent-light); }

          nav ul { list-style: none; display: flex; gap: 2.5rem; }
          nav ul a {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: .9rem; font-weight: 500;
            transition: color .2s;
          }
          nav ul a:hover { color: var(--text-primary); }

          .nav-cta {
            background: var(--accent-primary) !important;
            color: var(--text-primary) !important;
            padding: .55rem 1.3rem;
            border-radius: var(--r-pill);
            font-size: .85rem !important;
            transition: background .2s, box-shadow .2s !important;
            box-shadow: var(--sh-glow);
          }
          .nav-cta:hover {
            background: var(--accent-hover) !important;
            box-shadow: 0 8px 24px rgba(124,58,237,.45) !important;
          }

          /* ── HERO ── */
          .hero {
            min-height: 100vh;
            display: flex; flex-direction: column;
            justify-content: flex-end;
            padding: 0 5% 6rem;
            position: relative;
            overflow: hidden;
          }
          .hero-bg {
            position: absolute; inset: 0;
            background: var(--bg-darkest);
            z-index: 0;
          }
          .hero-bg::before {
            content: '';
            position: absolute; inset: 0;
            background:
              radial-gradient(ellipse at 15% 60%, rgba(124,58,237,.22) 0%, transparent 55%),
              radial-gradient(ellipse at 85% 15%, rgba(167,139,250,.12) 0%, transparent 45%),
              radial-gradient(ellipse at 65% 85%, rgba(61,41,96,.4) 0%, transparent 50%);
            animation: heroGlow 8s ease-in-out infinite;
          }
          .hero-bg::after {
            content: '';
            position: absolute; inset: 0;
            background-image:
              linear-gradient(rgba(124,58,237,.035) 1px, transparent 1px),
              linear-gradient(90deg, rgba(124,58,237,.035) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridDrift 25s linear infinite;
          }

          .hero-tag {
            position: relative; z-index: 1;
            display: inline-flex; align-items: center; gap: .5rem;
            background: var(--accent-subtle);
            border: 1px solid var(--accent-primary);
            color: var(--accent-light);
            font-size: .75rem; font-weight: 600; letter-spacing: .1em;
            text-transform: uppercase;
            padding: .4rem 1rem;
            border-radius: var(--r-pill);
            margin-bottom: 1.5rem;
            width: fit-content;
            animation: fadeUp .8s ease both;
          }
          .hero-tag::before {
            content: '';
            display: block; width: 6px; height: 6px;
            border-radius: 50%; background: var(--accent-light);
            animation: pulse 2s infinite;
          }

          .hero h1 {
            position: relative; z-index: 1;
            font-family: var(--ff-display);
            font-weight: 800;
            font-size: clamp(3.5rem, 9vw, 9rem);
            line-height: .95;
            letter-spacing: -.04em;
            color: var(--text-primary);
            margin-bottom: 2rem;
            animation: fadeUp .8s .1s ease both;
          }
          .hero h1 em {
            font-style: normal;
            background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-light) 50%, var(--accent-primary) 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            animation: textShimmer 4s linear infinite;
          }

          .hero-sub {
            position: relative; z-index: 1;
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 2rem;
            animation: fadeUp .8s .2s ease both;
          }
          .hero-desc {
            max-width: 420px;
            color: rgba(245,243,255,.55);
            font-size: 1.05rem; font-weight: 300; line-height: 1.7;
          }
          .hero-actions { display: flex; gap: 1rem; align-items: center; flex-shrink: 0; }

          .btn-primary {
            display: inline-flex; align-items: center; gap: .5rem;
            background: var(--accent-primary);
            color: var(--text-primary);
            text-decoration: none;
            padding: .9rem 2rem;
            border-radius: var(--r-pill);
            font-weight: 500; font-size: .95rem;
            transition: transform .2s, background .2s, box-shadow .2s;
            box-shadow: var(--sh-glow);
          }
          .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(124,58,237,.45);
          }
          .btn-ghost {
            display: inline-flex; align-items: center; gap: .5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: .9rem; font-weight: 400;
            transition: color .2s;
          }
          .btn-ghost:hover { color: var(--text-primary); }

          .hero-scroll {
            position: absolute; right: 5%; bottom: 2rem; z-index: 1;
            writing-mode: vertical-lr;
            font-size: .7rem; letter-spacing: .15em; text-transform: uppercase;
            color: rgba(245,243,255,.2);
            animation: fadeUp .8s .4s ease both;
          }

          /* ── STATS ── */
          .stats {
            background: var(--accent-primary);
            padding: 2rem 5%;
            display: flex; gap: 0; justify-content: space-between;
            align-items: center;
            position: relative; overflow: hidden;
          }
          .stats::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent 40%, rgba(255,255,255,.07) 100%);
          }
          .stat { text-align: center; flex: 1; position: relative; z-index: 1; }
          .stat:not(:last-child)::after {
            content: '';
            position: absolute; right: 0; top: 10%; bottom: 10%;
            width: 1px; background: rgba(255,255,255,.2);
          }
          .stat-num {
            display: block;
            font-family: var(--ff-display);
            font-size: 2.2rem; font-weight: 800;
            color: #fff; line-height: 1;
          }
          .stat.visible .stat-num { animation: statCount .5s ease both; }
          .stat-label {
            font-size: .8rem; color: rgba(255,255,255,.7);
            text-transform: uppercase; letter-spacing: .08em; margin-top: .25rem;
          }

          /* ── SECTION COMMONS ── */
          section { padding: 7rem 5%; }
          .section-label {
            font-size: .75rem; font-weight: 600; letter-spacing: .15em;
            text-transform: uppercase; color: var(--accent-light); margin-bottom: 1rem;
          }
          .section-title {
            font-family: var(--ff-display);
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 800; letter-spacing: -.03em;
            line-height: 1.1; color: var(--text-primary);
          }

          /* ── APPS ── */
          .apps { background: var(--bg-dark); }
          .apps-header {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-bottom: 4rem;
          }
          .apps-header .desc {
            max-width: 320px; color: var(--text-secondary); font-size: .95rem;
          }
          .apps-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 1.5rem;
          }
          .app-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-normal);
            border-radius: var(--r-xl);
            padding: 2.5rem;
            position: relative; overflow: hidden;
            transition: transform .3s, box-shadow .3s, border-color .3s;
          }
          .app-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--sh-glow);
            border-color: var(--accent-primary);
          }
          .app-card.featured {
            background: var(--bg-darkest);
            border-color: var(--accent-subtle);
            box-shadow: var(--sh-glow);
            animation: borderGlow 4s ease-in-out infinite;
          }
          .app-card.featured:hover {
            border-color: var(--accent-light);
            box-shadow: 0 16px 56px rgba(124,58,237,.45);
          }
          .app-card.featured::before {
            content: '';
            position: absolute;
            top: 0; width: 45%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(124,58,237,.07), transparent);
            animation: cardShimmer 5s ease-in-out infinite;
          }
          .app-icon {
            width: 52px; height: 52px;
            border-radius: var(--r-lg);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1.8rem;
            background: var(--bg-elevated);
            border: 1px solid var(--border-normal);
          }
          .app-card.featured .app-icon {
            background: var(--accent-subtle);
            border-color: var(--accent-primary);
          }
          .app-card h3 {
            font-family: var(--ff-display);
            font-size: 1.3rem; font-weight: 700; margin-bottom: .6rem;
            color: var(--text-primary);
          }
          .app-card p { font-size: .9rem; line-height: 1.7; color: var(--text-secondary); }
          .app-badge {
            display: inline-block; margin-top: 1.5rem;
            font-size: .75rem; font-weight: 600;
            letter-spacing: .08em; text-transform: uppercase;
            padding: .35rem .9rem; border-radius: var(--r-pill);
            background: var(--bg-elevated); color: var(--accent-light);
            border: 1px solid var(--border-normal);
            font-family: var(--ff-mono);
          }
          .app-card.featured .app-badge {
            background: var(--accent-subtle);
            color: var(--accent-light);
            border-color: var(--accent-primary);
          }
          .app-card::after {
            content: '';
            position: absolute; top: -40px; right: -40px;
            width: 120px; height: 120px; border-radius: 50%;
            border: 1px solid var(--border-normal); pointer-events: none;
          }
          .app-card.featured::after { border-color: var(--accent-subtle); }

          /* ── HOW IT WORKS ── */
          .how { background: var(--bg-darkest); }
          .how-header { margin-bottom: 4rem; }
          .steps {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 0; position: relative;
          }
          .steps::before {
            content: '';
            position: absolute; top: 28px; left: 5%; right: 5%;
            height: 1px; background: var(--border-normal); z-index: 0;
          }
          .step { padding: 0 1.5rem; position: relative; z-index: 1; }
          .step-num {
            width: 56px; height: 56px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: var(--ff-display);
            font-weight: 800; font-size: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-normal);
            background: var(--bg-darkest);
            color: var(--text-secondary);
            transition: background .3s, color .3s, border-color .3s, box-shadow .3s;
          }
          .step.accent .step-num {
            background: var(--accent-primary); color: #fff;
            border-color: var(--accent-primary);
            box-shadow: var(--sh-glow-sm);
          }
          .step h4 {
            font-family: var(--ff-display);
            font-size: 1rem; font-weight: 700; margin-bottom: .5rem;
            color: var(--text-primary);
          }
          .step p { font-size: .875rem; color: var(--text-secondary); line-height: 1.65; }

          /* ── TRACKING ── */
          .tracking {
            background: var(--bg-surface);
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 6rem; align-items: center;
            padding: 7rem 5%;
          }
          .tracking-text .section-label { color: var(--accent-light); }
          .tracking-text p.tdesc {
            color: var(--text-secondary);
            margin-top: 1.2rem; font-size: .95rem; line-height: 1.8;
          }
          .tracking-widget {
            background: var(--bg-elevated);
            border: 1px solid var(--border-normal);
            border-radius: var(--r-xl);
            padding: 2.5rem;
            box-shadow: var(--sh-md);
            animation: float 6s ease-in-out infinite;
          }
          .tracking-widget h4 {
            font-family: var(--ff-display);
            font-size: .85rem; font-weight: 700;
            color: var(--text-disabled);
            letter-spacing: .1em; text-transform: uppercase;
            margin-bottom: 1.5rem;
          }
          .tracking-id {
            font-family: var(--ff-mono);
            font-size: .8rem; color: var(--accent-light);
            margin-bottom: 2rem;
            padding: .75rem 1rem;
            background: var(--accent-subtle);
            border: 1px solid var(--border-focus);
            border-radius: var(--r-sm);
            letter-spacing: .05em;
          }
          .track-steps { display: flex; flex-direction: column; gap: 0; }
          .track-step {
            display: flex; gap: 1rem; align-items: flex-start;
            padding-bottom: 1.5rem; position: relative;
          }
          .track-step:not(:last-child)::before {
            content: '';
            position: absolute; left: 15px; top: 32px; bottom: 0;
            width: 1px; background: var(--border-normal);
          }
          .track-dot {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; flex-shrink: 0; margin-top: .1rem;
          }
          .track-dot.done  { background: var(--accent-primary); color: #fff; }
          .track-dot.active {
            background: var(--accent-light); color: var(--bg-darkest);
            animation: glowPulse 2s ease-in-out infinite;
            font-size: .7rem;
          }
          .track-dot.active span { display: inline-block; animation: spin 1.5s linear infinite; }
          .track-dot.pending {
            background: var(--bg-surface);
            border: 1px solid var(--border-normal);
          }
          .track-info strong {
            display: block; font-size: .875rem; font-weight: 500;
            color: var(--text-primary);
          }
          .track-info span.ttime {
            font-size: .78rem; color: var(--text-disabled);
            font-family: var(--ff-mono);
          }

          /* ── ROLES ── */
          .roles { background: var(--bg-dark); }
          .roles-header { margin-bottom: 4rem; }
          .roles-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.5rem; }
          .role-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-normal);
            border-radius: var(--r-xl);
            padding: 2rem;
            transition: border-color .2s, transform .3s, box-shadow .3s;
          }
          .role-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-4px);
            box-shadow: var(--sh-glow);
          }
          .role-emoji { font-size: 2rem; margin-bottom: 1rem; }
          .role-card h3 {
            font-family: var(--ff-display);
            font-size: 1.1rem; font-weight: 700; margin-bottom: .75rem;
            color: var(--text-primary);
          }
          .role-features { list-style: none; display: flex; flex-direction: column; gap: .5rem; }
          .role-features li {
            font-size: .875rem; color: var(--text-secondary);
            display: flex; align-items: center; gap: .5rem;
          }
          .role-features li::before { content: '→'; color: var(--accent-primary); font-size: .75rem; }

          /* ── FOOTER ── */
          footer {
            background: var(--bg-darkest);
            padding: 4rem 5%;
            display: flex; justify-content: space-between; align-items: center;
            border-top: 1px solid var(--border-normal);
          }
          .footer-logo {
            font-family: var(--ff-display);
            font-weight: 800; font-size: 1.4rem;
            color: var(--text-primary); letter-spacing: -.03em;
          }
          .footer-logo span { color: var(--accent-light); }
          .footer-copy { font-size: .8rem; color: rgba(245,243,255,.3); }
          .footer-domain {
            font-size: .85rem; color: var(--accent-light);
            font-family: var(--ff-mono); letter-spacing: .05em;
          }

          /* ── SCROLL REVEAL ── */
          .reveal {
            opacity: 0; transform: translateY(24px);
            transition: opacity .7s ease, transform .7s ease;
          }
          .reveal.visible { opacity: 1; transform: translateY(0); }

          /* ── RESPONSIVE ── */
          @media (max-width: 900px) {
            .apps-grid { grid-template-columns: 1fr; }
            .apps-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .steps { grid-template-columns: 1fr 1fr; gap: 2rem; }
            .steps::before { display: none; }
            .tracking { grid-template-columns: 1fr; gap: 3rem; }
            .roles-grid { grid-template-columns: 1fr; }
            .hero-sub { flex-direction: column; align-items: flex-start; }
            .stats { flex-wrap: wrap; gap: 2rem; }
            footer { flex-direction: column; gap: 1rem; text-align: center; }
            nav ul { display: none; }
          }
        </style>
      </head>
      <body>
        <xsl:apply-templates select="nav"/>
        <xsl:apply-templates select="hero"/>
        <xsl:apply-templates select="stats"/>
        <xsl:apply-templates select="apps"/>
        <xsl:apply-templates select="howItWorks"/>
        <xsl:apply-templates select="tracking"/>
        <xsl:apply-templates select="roles"/>
        <xsl:apply-templates select="footer"/>
        <script>
          (function() {
            var observer = new IntersectionObserver(function(entries) {
              entries.forEach(function(e, i) {
                if (e.isIntersecting) {
                  setTimeout(function() { e.target.classList.add('visible'); }, i * 80);
                  observer.unobserve(e.target);
                }
              });
            }, { threshold: 0.12 });
            document.querySelectorAll('.reveal').forEach(function(el) { observer.observe(el); });
          })();
        </script>
      </body>
    </html>
  </xsl:template>

  <!-- ═══════════════════
       NAV
       ═══════════════════ -->
  <xsl:template match="nav">
    <nav>
      <a href="#" class="logo">
        <xsl:value-of select="logo/brand"/>
        <span><xsl:value-of select="logo/highlight"/></span>
      </a>
      <ul>
        <xsl:for-each select="links/link">
          <li>
            <xsl:choose>
              <xsl:when test="@cta='true'">
                <a href="{@href}" class="nav-cta"><xsl:value-of select="."/></a>
              </xsl:when>
              <xsl:otherwise>
                <a href="{@href}"><xsl:value-of select="."/></a>
              </xsl:otherwise>
            </xsl:choose>
          </li>
        </xsl:for-each>
      </ul>
    </nav>
  </xsl:template>

  <!-- ═══════════════════
       HERO
       ═══════════════════ -->
  <xsl:template match="hero">
    <section class="hero">
      <div class="hero-bg"></div>
      <span class="hero-tag"><xsl:value-of select="tag"/></span>
      <h1>
        <xsl:value-of select="heading"/><br/>
        <em><xsl:value-of select="headingEm"/></em>,<br/>
        modu sinplean.
      </h1>
      <div class="hero-sub">
        <p class="hero-desc"><xsl:value-of select="description"/></p>
        <div class="hero-actions">
          <a href="{actions/primaryBtn/@href}" class="btn-primary">
            <xsl:value-of select="actions/primaryBtn"/>
          </a>
          <a href="{actions/ghostBtn/@href}" class="btn-ghost">
            <xsl:value-of select="actions/ghostBtn"/>
          </a>
        </div>
      </div>
      <span class="hero-scroll"><xsl:value-of select="scrollHint"/></span>
    </section>
  </xsl:template>

  <!-- ═══════════════════
       STATS
       ═══════════════════ -->
  <xsl:template match="stats">
    <div class="stats reveal">
      <xsl:for-each select="stat">
        <div class="stat">
          <span class="stat-num"><xsl:value-of select="num"/></span>
          <span class="stat-label"><xsl:value-of select="label"/></span>
        </div>
      </xsl:for-each>
    </div>
  </xsl:template>

  <!-- ═══════════════════
       APPS
       ═══════════════════ -->
  <xsl:template match="apps">
    <section class="apps" id="apps">
      <div class="apps-header reveal">
        <div>
          <p class="section-label"><xsl:value-of select="sectionLabel"/></p>
          <h2 class="section-title"><xsl:value-of select="sectionTitle"/></h2>
        </div>
        <p class="desc"><xsl:value-of select="sectionDesc"/></p>
      </div>
      <div class="apps-grid">
        <xsl:for-each select="appsGrid/appCard">
          <xsl:choose>
            <xsl:when test="@featured='true'">
              <div class="app-card featured reveal">
                <div class="app-icon"><xsl:value-of select="icon"/></div>
                <h3><xsl:value-of select="cardTitle"/></h3>
                <p><xsl:value-of select="cardDesc"/></p>
                <span class="app-badge"><xsl:value-of select="badge"/></span>
              </div>
            </xsl:when>
            <xsl:otherwise>
              <div class="app-card reveal">
                <div class="app-icon"><xsl:value-of select="icon"/></div>
                <h3><xsl:value-of select="cardTitle"/></h3>
                <p><xsl:value-of select="cardDesc"/></p>
                <span class="app-badge"><xsl:value-of select="badge"/></span>
              </div>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:for-each>
      </div>
    </section>
  </xsl:template>

  <!-- ═══════════════════
       HOW IT WORKS
       ═══════════════════ -->
  <xsl:template match="howItWorks">
    <section class="how" id="nola">
      <div class="how-header reveal">
        <p class="section-label"><xsl:value-of select="sectionLabel"/></p>
        <h2 class="section-title"><xsl:value-of select="sectionTitle"/></h2>
      </div>
      <div class="steps">
        <xsl:for-each select="steps/step">
          <xsl:choose>
            <xsl:when test="@accent='true'">
              <div class="step accent reveal">
                <div class="step-num"><xsl:value-of select="stepNum"/></div>
                <h4><xsl:value-of select="stepTitle"/></h4>
                <p><xsl:value-of select="stepDesc"/></p>
              </div>
            </xsl:when>
            <xsl:otherwise>
              <div class="step reveal">
                <div class="step-num"><xsl:value-of select="stepNum"/></div>
                <h4><xsl:value-of select="stepTitle"/></h4>
                <p><xsl:value-of select="stepDesc"/></p>
              </div>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:for-each>
      </div>
    </section>
  </xsl:template>

  <!-- ═══════════════════
       TRACKING
       ═══════════════════ -->
  <xsl:template match="tracking">
    <section class="tracking" id="jarraipena">
      <div class="tracking-text reveal">
        <p class="section-label"><xsl:value-of select="trackingText/sectionLabel"/></p>
        <h2 class="section-title"><xsl:value-of select="trackingText/sectionTitle"/></h2>
        <p class="tdesc"><xsl:value-of select="trackingText/trackingDesc"/></p>
        <br/>
        <a href="{trackingText/trackingBtn/@href}" class="btn-primary" style="margin-top:1rem;display:inline-flex;">
          <xsl:value-of select="trackingText/trackingBtn"/>
        </a>
      </div>
      <div class="tracking-widget reveal">
        <h4><xsl:value-of select="trackingWidget/widgetTitle"/></h4>
        <div class="tracking-id"><xsl:value-of select="trackingWidget/trackingId"/></div>
        <div class="track-steps">
          <xsl:for-each select="trackingWidget/trackSteps/trackStep">
            <div class="track-step">
              <xsl:choose>
                <xsl:when test="@state='done'">
                  <div class="track-dot done"><xsl:value-of select="trackStatus"/></div>
                </xsl:when>
                <xsl:when test="@state='active'">
                  <div class="track-dot active">
                    <span><xsl:value-of select="trackStatus"/></span>
                  </div>
                </xsl:when>
                <xsl:otherwise>
                  <div class="track-dot pending"></div>
                </xsl:otherwise>
              </xsl:choose>
              <div class="track-info">
                <strong><xsl:value-of select="trackLabel"/></strong>
                <span class="ttime"><xsl:value-of select="trackTime"/></span>
              </div>
            </div>
          </xsl:for-each>
        </div>
      </div>
    </section>
  </xsl:template>

  <!-- ═══════════════════
       ROLES
       ═══════════════════ -->
  <xsl:template match="roles">
    <section class="roles" id="rolak">
      <div class="roles-header reveal">
        <p class="section-label"><xsl:value-of select="sectionLabel"/></p>
        <h2 class="section-title"><xsl:value-of select="sectionTitle"/></h2>
      </div>
      <div class="roles-grid">
        <xsl:for-each select="rolesGrid/roleCard">
          <div class="role-card reveal">
            <div class="role-emoji"><xsl:value-of select="roleEmoji"/></div>
            <h3><xsl:value-of select="roleTitle"/></h3>
            <ul class="role-features">
              <xsl:for-each select="roleFeatures/feature">
                <li><xsl:value-of select="."/></li>
              </xsl:for-each>
            </ul>
          </div>
        </xsl:for-each>
      </div>
    </section>
  </xsl:template>

  <!-- ═══════════════════
       FOOTER
       ═══════════════════ -->
  <xsl:template match="footer">
    <footer>
      <div class="footer-logo">
        <xsl:value-of select="footerBrand"/>
        <span><xsl:value-of select="footerHighlight"/></span>
      </div>
      <span class="footer-copy"><xsl:value-of select="copyright"/></span>
      <span class="footer-domain"><xsl:value-of select="domain"/></span>
    </footer>
  </xsl:template>

</xsl:stylesheet>
