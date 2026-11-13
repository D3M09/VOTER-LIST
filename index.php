<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: SAMEORIGIN');
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; " .
    "style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; " .
    "connect-src 'self'; worker-src 'self' blob:; object-src 'none'; " .
    "base-uri 'none'; form-action 'self'; frame-ancestors 'self'"
);
?>
<!doctype html>
<html lang="bn" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#050b0c">
<meta name="robots" content="noindex, nofollow">
<title>Repdoc - ভোটার তথ্য অনুসন্ধান</title>
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/fonts/fonts.css">
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<div class="atmosphere" aria-hidden="true">
    <span class="orb orb-a"></span>
    <span class="orb orb-b"></span>
    <span class="orb orb-c"></span>
    <span class="grid-glow"></span>
</div>

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="./" aria-label="Repdoc home">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none">
                    <path d="M20 4c-6.6 0-12 5.4-12 12v3.2c0 2.7.9 5.2 2.4 7.2" stroke="url(#bm1)" stroke-width="2.4" stroke-linecap="round"/>
                    <path d="M20 10c-3.9 0-7 3.1-7 7v2.4c0 2 .6 3.9 1.7 5.4" stroke="url(#bm1)" stroke-width="2.4" stroke-linecap="round"/>
                    <path d="M20 16c-1.8 0-3.2 1.4-3.2 3.2v1.6c0 1.6.7 3 1.8 4" stroke="url(#bm1)" stroke-width="2.4" stroke-linecap="round"/>
                    <path d="M24.5 12.5c4.2 1 7.2 4.7 7.2 9.2v2.1c0 5.9-4.1 10.8-9.6 12" stroke="url(#bm1)" stroke-width="2.4" stroke-linecap="round"/>
                    <path d="M26 21.7c0 5.1-3.8 9.3-8.7 9.9" stroke="url(#bm1)" stroke-width="2.4" stroke-linecap="round"/>
                    <circle cx="20" cy="20" r="2.6" fill="#41f1b0"/>
                    <defs>
                        <linearGradient id="bm1" x1="6" y1="4" x2="33" y2="36" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#41f1b0"/>
                            <stop offset="1" stop-color="#f4d59a"/>
                        </linearGradient>
                    </defs>
                </svg>
            </span>
            <span class="brand-text">
                <strong>Repdoc</strong>
            </span>
        </a>
        <div class="topbar-right">
            <span class="topbar-meta" id="builtAt">Local · private</span>
        </div>
    </div>
</header>

<main class="shell">
    <section class="hero">
        <h1>Voter Search<br><span class="grad">One search away</span></h1>
        <div class="filters" aria-label="Search filters">
            <label class="field field-bare">
                <div class="select-wrap">
                    <select id="unionFilter"><option value="">সব ইউনিয়ন</option></select>
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 6 4 4 4-4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round"/></svg>
                </div>
            </label>
            <label class="field field-bare">
                <div class="select-wrap">
                    <select id="areaFilter"><option value="">সব এলাকা</option></select>
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 6 4 4 4-4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round"/></svg>
                </div>
            </label>
        </div>

        <form class="search" id="searchForm" role="search" autocomplete="off">
            <span class="search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </span>
            <input id="q" name="q" type="search" placeholder="নাম, ভোটার নম্বর, পিতা/মাতা, বা ঠিকানা লিখুন…" aria-label="Search voters" spellcheck="false" maxlength="120">
            <button type="button" class="search-clear" id="clearSearch" aria-label="Clear search" title="Clear search" hidden>
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
            <kbd class="search-kbd" aria-hidden="true">/</kbd>
            <button class="search-btn" type="submit">
                <span>Search</span>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <span class="search-progress" id="searchProgress" aria-hidden="true"></span>
        </form>

        <div class="adv-wrap">
            <button type="button" class="adv-toggle" id="advToggle" aria-expanded="false" aria-controls="advPanel">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 6h9M16 6h1M3 14h3M10 14h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="14" cy="6" r="2" stroke="currentColor" stroke-width="1.6"/><circle cx="7.5" cy="14" r="2" stroke="currentColor" stroke-width="1.6"/></svg>
                <span>Advanced search</span>
                <b class="adv-count" id="advCount" hidden>0</b>
                <svg class="adv-chev" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m4 6 4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="adv-panel" id="advPanel" hidden>
                <div class="adv-grid">
                    <label class="adv-field"><span>নাম</span><input id="advName" type="text" maxlength="80" placeholder="যেমন: মোনারুল" autocomplete="off"></label>
                    <label class="adv-field"><span>পিতা</span><input id="advFather" type="text" maxlength="80" placeholder="পিতার নাম" autocomplete="off"></label>
                    <label class="adv-field"><span>মাতা</span><input id="advMother" type="text" maxlength="80" placeholder="মাতার নাম" autocomplete="off"></label>
                    <label class="adv-field"><span>ভোটার নম্বর</span><input id="advVoterNo" type="text" maxlength="40" inputmode="numeric" placeholder="যেমন: 101883000224" autocomplete="off"></label>
                    <label class="adv-field"><span>ঠিকানা</span><input id="advAddress" type="text" maxlength="160" placeholder="গ্রাম বা পাড়া" autocomplete="off"></label>
                    <label class="adv-field"><span>পেশা</span><input id="advOccupation" type="text" maxlength="80" placeholder="যেমন: কৃষি" autocomplete="off"></label>
                    <label class="adv-field"><span>ক্রমিক (শুরু)</span><input id="advSerialMin" type="text" maxlength="12" inputmode="numeric" placeholder="যেমন: 100" autocomplete="off"></label>
                    <label class="adv-field"><span>ক্রমিক (শেষ)</span><input id="advSerialMax" type="text" maxlength="12" inputmode="numeric" placeholder="যেমন: 500" autocomplete="off"></label>
                    <label class="adv-field"><span>PDF পৃষ্ঠা</span><input id="advPage" type="text" maxlength="12" inputmode="numeric" placeholder="যেমন: 29" autocomplete="off"></label>
                </div>
                <div class="adv-actions">
                    <span>সব ঘর একসাথে মিললে ফলাফল আসবে।</span>
                    <button type="button" class="adv-clear" id="advClear">Clear advanced</button>
                </div>
            </div>
        </div>

        <div class="stats" id="stats">
            <div class="stat"><strong data-stat="records">—</strong><span>মোট রেকর্ড</span></div>
            <div class="stat"><strong data-stat="areas">—</strong><span>ভোটার এলাকা</span></div>
            <div class="stat"><strong data-stat="unions">—</strong><span>ইউনিয়ন</span></div>
            <div class="stat"><strong data-stat="pages">—</strong><span>PDF পৃষ্ঠা</span></div>
        </div>
    </section>

    <section class="workspace" id="workspace" hidden>
        <div class="results-panel panel">
            <div class="panel-head">
                <div>
                    <p class="panel-kicker">Search results</p>
                    <h2 id="resultsTitle">Ready when you are</h2>
                </div>
                <div class="result-meta">
                    <span id="resultCount" aria-live="polite">—</span>
                    <span class="dot-sep"></span>
                    <span id="resultTime"></span>
                </div>
            </div>

            <div class="results-scroll" id="resultsScroll">
                <div class="results-list" id="results" aria-live="polite"></div>
                <button class="load-more" id="loadMore" type="button" hidden>
                    <span>Load more results</span>
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 8l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>

            <div class="empty" id="emptyState">
                <div class="empty-art" aria-hidden="true">
                    <svg viewBox="0 0 120 120" fill="none">
                        <circle cx="60" cy="60" r="46" stroke="url(#eg1)" stroke-width="1.2" stroke-dasharray="2 7"/>
                        <circle cx="60" cy="60" r="33" stroke="url(#eg1)" stroke-width="1.4" opacity=".6"/>
                        <path d="M44 59.5 55 70l22-25" stroke="#41f1b0" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="60" cy="60" r="2.6" fill="#f4d59a"/>
                        <defs><linearGradient id="eg1" x1="20" y1="20" x2="100" y2="100"><stop stop-color="#41f1b0" stop-opacity=".8"/><stop offset="1" stop-color="#f4d59a" stop-opacity=".35"/></linearGradient></defs>
                    </svg>
                </div>
                <h3>Start with a name.</h3>
                <p>নাম, ১৩ ডিজিটের ভোটার নম্বর, পিতা/মাতার নাম বা ঠিকানা দিয়ে খুঁজুন। ফলাফল থেকে যেকোনো রেকর্ডে ক্লিক করলে মূল PDF পৃষ্ঠা লাইভ দেখা যাবে।</p>
                <div class="suggest" id="suggestAreas"></div>
            </div>
        </div>

        <aside class="viewer-panel panel" id="viewerPanel" aria-label="Voter details and live PDF">
            <button class="viewer-close" id="viewerClose" type="button" aria-label="Close viewer">
                <svg viewBox="0 0 20 20" fill="none"><path d="m5 5 10 10M15 5 5 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>

            <div class="viewer-empty" id="viewerEmpty">
                <div class="scan-art" aria-hidden="true">
                    <div class="scan-page">
                        <span></span><span></span><span></span><span></span>
                        <i class="scan-beam"></i>
                    </div>
                </div>
                <h3>See it on the source.</h3>
                <p>Select a voter from the results. We will render the original PDF and pin the exact card with a live highlight.</p>
                <span class="privacy-note">
                    <svg viewBox="0 0 18 18" fill="none" aria-hidden="true"><path d="M5 8V6a4 4 0 0 1 8 0v2M4 8h10v7H4z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                    Data served privately from this index — no third-party calls
                </span>
            </div>

            <div class="viewer-body" id="viewerBody" hidden>
                <div class="voter-head">
                    <span class="avatar" id="vAvatar" aria-hidden="true">?</span>
                    <div class="voter-id">
                        <div class="voter-title-row">
                            <h2 id="vName">—</h2>
                            <span class="badge badge-ok" id="vStatus">Active</span>
                        </div>
                        <p id="vMeta">—</p>
                    </div>
                    <button class="icon-btn" id="copyBtn" type="button" title="Copy voter details" aria-label="Copy voter details">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="7" y="7" width="9" height="10" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M13 7V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h1" stroke="currentColor" stroke-width="1.5"/></svg>
                    </button>
                </div>

                <div class="detail-grid">
                    <div class="detail"><span>ভোটার নম্বর</span><strong id="dVoterNo" class="mono">—</strong></div>
                    <div class="detail"><span>ক্রমিক</span><strong id="dSerial">—</strong></div>
                    <div class="detail"><span>পিতা</span><strong id="dFather">—</strong></div>
                    <div class="detail"><span>মাতা</span><strong id="dMother">—</strong></div>
                    <div class="detail"><span>পেশা</span><strong id="dOccupation">—</strong></div>
                    <div class="detail"><span>জন্ম তারিখ</span><strong id="dDob">—</strong></div>
                    <div class="detail detail-wide"><span>ঠিকানা</span><strong id="dAddress">—</strong></div>
                </div>

                <div class="chips" id="dChips"></div>

                <div class="pdf-card">
                    <div class="pdf-head">
                        <div class="pdf-title">
                            <span class="pdf-dot"></span>
                            <div>
                                <strong>Live source PDF</strong>
                                <small id="pdfFileName">Election Commission · form-1</small>
                            </div>
                        </div>
                        <label class="switch" title="Toggle live PDF view">
                            <input type="checkbox" id="liveToggle" checked>
                            <span class="switch-track"><span class="switch-thumb"></span></span>
                            <span class="switch-label">Live</span>
                        </label>
                    </div>

                    <div class="pdf-toolbar" id="pdfToolbar" hidden>
                        <div class="pdf-nav">
                            <button class="icon-btn sm" id="pagePrev" type="button" aria-label="Previous page">
                                <svg viewBox="0 0 18 18" fill="none"><path d="M11 5 6 9l5 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <span class="page-pill"><b id="pageNow">1</b><i>/</i><span id="pageTotal">1</span></span>
                            <button class="icon-btn sm" id="pageNext" type="button" aria-label="Next page">
                                <svg viewBox="0 0 18 18" fill="none"><path d="m7 5 5 4-5 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <button class="jump-btn" id="jumpVoter" type="button" hidden>Jump to voter</button>
                        <div class="pdf-zoom">
                            <button class="icon-btn sm" id="zoomOut" type="button" aria-label="Zoom out">
                                <svg viewBox="0 0 18 18" fill="none"><path d="M5 9h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </button>
                            <button class="zoom-pill" id="zoomFit" type="button" title="Fit to width">Fit</button>
                            <button class="icon-btn sm" id="zoomIn" type="button" aria-label="Zoom in">
                                <svg viewBox="0 0 18 18" fill="none"><path d="M9 5v8M5 9h8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="pdf-stage" id="pdfStage">
                        <div class="pdf-off" id="pdfOff" hidden>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H18a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6.5A2.5 2.5 0 0 1 4 18.5v-13Z" stroke="currentColor" stroke-width="1.4"/><path d="M8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                            <p>Live PDF view is off</p>
                            <button type="button" class="ghost-btn" id="enableLive">Enable live view</button>
                        </div>
                        <div class="pdf-loading" id="pdfLoading" hidden>
                            <span class="spinner"></span><p>Rendering source page…</p>
                        </div>
                        <div class="pdf-error" id="pdfError" hidden>
                            <p>The PDF could not be rendered.</p>
                            <button type="button" class="ghost-btn" id="retryPdf">Try again</button>
                        </div>
                        <div class="pdf-canvas-wrap" id="pdfCanvasWrap" hidden>
                            <canvas id="pdfCanvas"></canvas>
                            <div class="pdf-overlay" id="pdfOverlay">
                                <div class="pdf-highlight" id="pdfHighlight">
                                    <span class="hl-corner tl"></span><span class="hl-corner tr"></span>
                                    <span class="hl-corner bl"></span><span class="hl-corner br"></span>
                                    <span class="hl-tag" id="hlTag">Voter</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </section>
</main>

<footer class="footer">
    <span>Made with ❤️ by - Abdullah Al Emon</span>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<div class="backdrop" id="backdrop" hidden></div>

<script src="assets/app.js"></script>
</body>
</html>
