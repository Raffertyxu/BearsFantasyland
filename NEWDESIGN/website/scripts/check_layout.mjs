#!/usr/bin/env node
/**
 * Layout guardrail for the NEWDESIGN front end (local preview only).
 *
 * Requires the local preview server, started from the repository root:
 *   php -S localhost:8766 -t NEWDESIGN/website
 * Uses the installed Google Chrome (playwright-core, channel "chrome");
 * it never downloads Playwright browsers.
 *
 * Modes
 *   node scripts/check_layout.mjs                 check all pages x all widths (exit 1 on failure)
 *   node scripts/check_layout.mjs --snapshot f    record element rects + font sizes (regression baseline)
 *   node scripts/check_layout.mjs --compare a b   diff two snapshots (exit 1 when anything moved > 1px)
 *
 * Options
 *   --base URL          preview origin (default http://localhost:8766, env BFND_PREVIEW_BASE)
 *   --pages a,b         subset of page keys (see PAGES)
 *   --widths 375x812,.. viewport list
 *   --variant theme|plugin|both   theme = preview_theme=1 (default), plugin = plugin fallback CSS
 *   --no-screenshots    skip PNG output (check mode)
 *   --out DIR           screenshot/report folder (default qa-screenshots)
 *   --concurrency N     parallel pages (default 3)
 */
import { chromium } from 'playwright-core';
import { mkdir, writeFile, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

export const PAGES = {
  home: 'page=home',
  furniture: 'page=furniture',
  lifestyle: 'page=lifestyle',
  school: 'page=school',
  story: 'page=story',
  collaboration: 'page=collaboration',
  journal: 'page=journal',
  service: 'page=service',
  work: 'page=work&slug=ridge-table',
  course: 'page=course&slug=beginner',
  'lifestyle-work': 'page=lifestyle-work&slug=alba-canvas',
};
const CHECK_WIDTHS = ['375x812', '768x1024', '1366x768', '1920x1080', '2560x1440', '3440x1440', '3840x2160', '5120x2880', '7680x4320'];
const SNAPSHOT_WIDTHS = ['375x812', '768x1024', '1366x768', '1600x900', '1920x1080'];
const DESKTOP_MIN = 1101; // matches the site's desktop breakpoint
const MIN_DESKTOP_FONT = 12;
const WRAP_MIN_RATIO = 0.70;
// .bf-wrap elements that are intentionally narrower than the page column.
const NARROW_WRAP_ALLOW = [
  '.bf-service-section', // purchase/service policy page: deliberate reading column
  '.bf-collab-sustain-inner', '.bf-collab-after-inner', // text half of a photo + text split panel
];

function parseArgs(argv) {
  const args = { variant: 'theme', screenshots: true, out: 'qa-screenshots', concurrency: 3 };
  for (let i = 0; i < argv.length; i++) {
    const a = argv[i];
    const next = () => argv[++i];
    if (a === '--snapshot') args.snapshot = next();
    else if (a === '--compare') { args.compare = [next(), next()]; }
    else if (a === '--base') args.base = next();
    else if (a === '--pages') args.pages = next().split(',').filter(Boolean);
    else if (a === '--widths') args.widths = next().split(',').filter(Boolean);
    else if (a === '--variant') args.variant = next();
    else if (a === '--no-screenshots') args.screenshots = false;
    else if (a === '--out') args.out = next();
    else if (a === '--concurrency') args.concurrency = Math.max(1, parseInt(next(), 10) || 1);
    else if (a === '--help' || a === '-h') { console.log(readHelp()); process.exit(0); }
    else { console.error(`Unknown argument: ${a}`); process.exit(2); }
  }
  args.base = (args.base || process.env.BFND_PREVIEW_BASE || 'http://localhost:8766').replace(/\/$/, '');
  return args;
}
function readHelp() { return 'See the header comment of scripts/check_layout.mjs'; }

function variantsOf(v) { return v === 'both' ? ['theme', 'plugin'] : [v]; }
function urlFor(base, variant, key) {
  const q = PAGES[key];
  if (!q) throw new Error(`Unknown page key: ${key}`);
  return `${base}/preview.php?${variant === 'theme' ? 'preview_theme=1&' : ''}${q}`;
}
function parseSize(s) { const [w, h] = s.split('x').map(Number); return { width: w, height: h || Math.round(w * 9 / 16) }; }

const STABILISE_CSS = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}';

async function preparePage(page, url) {
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e && e.message || e)));
  const res = await page.goto(url, { waitUntil: 'networkidle', timeout: 90000 });
  if (!res || !res.ok()) errors.push(`HTTP ${res ? res.status() : 'no response'}`);
  await page.addStyleTag({ content: STABILISE_CSS });
  await page.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach((img) => { img.loading = 'eager'; });
    await Promise.all(Array.from(document.images).map((img) => (img.complete ? null : new Promise((r) => { img.addEventListener('load', r, { once: true }); img.addEventListener('error', r, { once: true }); setTimeout(r, 15000); }))));
    // Decode everything so beyond-viewport screenshots paint below-the-fold photos.
    await Promise.all(Array.from(document.images).map((img) => { img.decoding = 'sync'; return img.decode ? img.decode().catch(() => null) : null; }));
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
  });
  await page.waitForTimeout(250);
  return errors;
}

/* ---------------------------------------------------------------- snapshot */
function collectSnapshot() {
  const out = [];
  const all = document.body.querySelectorAll('*');
  for (const el of all) {
    const tag = el.tagName.toLowerCase();
    if (tag === 'script' || tag === 'style' || tag === 'template') continue;
    const r = el.getBoundingClientRect();
    const cs = getComputedStyle(el);
    const cls = typeof el.className === 'string' ? el.className.trim().split(/\s+/).slice(0, 2).join('.') : '';
    const round = (n) => Math.round(n * 100) / 100;
    out.push([`${tag}${cls ? '.' + cls : ''}`, round(r.left + scrollX), round(r.top + scrollY), round(r.width), round(r.height), parseFloat(cs.fontSize)]);
  }
  return { scrollHeight: document.documentElement.scrollHeight, scrollWidth: document.documentElement.scrollWidth, elements: out };
}

/* ---------------------------------------------------------------- checks */
function collectIssues(opts) {
  const { desktopMin, minFont, wrapRatio, narrowAllow } = opts;
  const vw = window.innerWidth;
  const issues = [];
  const warnings = [];
  const docEl = document.documentElement;
  const describe = (el) => {
    let s = el.tagName.toLowerCase();
    if (el.id) s += '#' + el.id;
    if (typeof el.className === 'string' && el.className.trim()) s += '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.');
    const parent = el.parentElement && el.parentElement.closest('[class]');
    if (parent && parent !== el) s = `${parent.className.split(/\s+/)[0]} > ${s}`;
    return s;
  };
  const snippet = (el) => (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 40);

  if (docEl.scrollWidth > docEl.clientWidth + 1) {
    issues.push({ type: 'horizontal-overflow', detail: `scrollWidth ${docEl.scrollWidth} > clientWidth ${docEl.clientWidth}` });
  }
  const bodyText = document.body.innerText || '';
  const php = bodyText.match(/(Fatal error|Warning|Deprecated|Notice|Parse error):\s/);
  if (php) issues.push({ type: 'php-error', detail: bodyText.slice(Math.max(0, php.index - 20), php.index + 160) });

  const hiddenByAncestor = (el) => {
    for (let n = el; n && n !== document.body; n = n.parentElement) {
      if (n.hidden || n.getAttribute('aria-hidden') === 'true' || n.inert) return true;
      if (n.tagName === 'DIALOG' && !n.open) return true;
      if (n.tagName === 'DETAILS' && !n.open && n !== el && !(el.closest('summary'))) return true;
      const cs = getComputedStyle(n);
      if (cs.display === 'none' || cs.visibility === 'hidden' || cs.visibility === 'collapse' || parseFloat(cs.opacity) === 0) return true;
      if (cs.position === 'absolute' && (cs.clip === 'rect(0px, 0px, 0px, 0px)' || cs.clipPath === 'inset(50%)')) return true;
    }
    return false;
  };
  const textRectOf = (el) => {
    let box = null;
    for (const node of el.childNodes) {
      if (node.nodeType !== 3 || !node.nodeValue.trim()) continue;
      const range = document.createRange();
      range.selectNodeContents(node);
      for (const r of range.getClientRects()) {
        if (r.width < 0.5 || r.height < 0.5) continue;
        box = box ? { left: Math.min(box.left, r.left), top: Math.min(box.top, r.top), right: Math.max(box.right, r.right), bottom: Math.max(box.bottom, r.bottom) } : { left: r.left, top: r.top, right: r.right, bottom: r.bottom };
      }
    }
    return box;
  };
  const paddingBox = (el) => {
    const r = el.getBoundingClientRect();
    const left = r.left + el.clientLeft; const top = r.top + el.clientTop;
    return { left, top, right: left + el.clientWidth, bottom: top + el.clientHeight };
  };

  const seen = new Set();
  for (const el of document.body.querySelectorAll('*')) {
    const tag = el.tagName;
    if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT' || tag === 'TEMPLATE' || tag === 'OPTION' || tag === 'svg') continue;
    let hasText = false;
    for (const n of el.childNodes) if (n.nodeType === 3 && n.nodeValue.trim()) { hasText = true; break; }
    if (!hasText) continue;
    const text = textRectOf(el);
    if (!text) continue;
    if (hiddenByAncestor(el)) continue;
    const cs = getComputedStyle(el);
    const fs = parseFloat(cs.fontSize);
    // Visually-hidden helpers (screen-reader text) are tiny on purpose.
    const er = el.getBoundingClientRect();
    if (er.width <= 2 && er.height <= 2) continue;
    const tolV = Math.max(2, fs * 0.35);
    const intentional = cs.webkitLineClamp !== 'none' && cs.webkitLineClamp !== '' || cs.textOverflow === 'ellipsis';

    if (vw >= desktopMin && fs < minFont - 0.05) {
      issues.push({ type: 'small-font', el: describe(el), detail: `${fs}px "${snippet(el)}"` });
    }
    if (cs.display !== 'inline' && cs.display !== 'contents') {
      const over = text.right - er.right > 1 || er.left - text.left > 1 || text.bottom - er.bottom > tolV;
      if (over) {
        const key = 'self:' + describe(el);
        if (!seen.has(key)) {
          seen.add(key);
          (intentional ? warnings : issues).push({ type: intentional ? 'truncated-by-design' : 'text-overflows-box', el: describe(el), detail: `text ${Math.round(text.right - text.left)}x${Math.round(text.bottom - text.top)} vs box ${Math.round(er.width)}x${Math.round(er.height)} "${snippet(el)}"` });
        }
      }
    }
    for (let a = el.parentElement; a && a !== document.documentElement; a = a.parentElement) {
      const acs = getComputedStyle(a);
      const clipX = acs.overflowX === 'hidden' || acs.overflowX === 'clip';
      const clipY = acs.overflowY === 'hidden' || acs.overflowY === 'clip';
      if (!clipX && !clipY) continue;
      if ((acs.overflowX === 'auto' || acs.overflowX === 'scroll') || (acs.overflowY === 'auto' || acs.overflowY === 'scroll')) continue;
      const pb = paddingBox(a);
      const outX = clipX && (text.right - pb.right > 1 || pb.left - text.left > 1);
      const outY = clipY && (text.bottom - pb.bottom > tolV || pb.top - text.top > tolV);
      if (outX || outY) {
        // Content entirely outside a clipping box (e.g. an inactive slide) is not visible text.
        const fullyOutside = text.right <= pb.left || text.left >= pb.right || text.bottom <= pb.top || text.top >= pb.bottom;
        // Off-canvas by design: inactive carousel slides and collapsed menus.
        if (fullyOutside && el.closest('[data-bfnd-banner-slide], [data-bfnd-carousel] [aria-hidden], .bf-nav-course-menu')) break;
        const key = 'clip:' + describe(el);
        if (!seen.has(key)) {
          seen.add(key);
          const type = intentional ? 'truncated-by-design' : (fullyOutside ? 'text-hidden-by-clip' : 'text-clipped');
          (intentional ? warnings : issues).push({ type, el: describe(el), detail: `clipped by ${describe(a)} (${outX ? 'x' : ''}${outY ? 'y' : ''}) "${snippet(el)}"` });
        }
        break;
      }
    }
  }

  if (vw >= 1920) {
    for (const wrap of document.querySelectorAll('.bf-wrap')) {
      if (hiddenByAncestor(wrap)) continue;
      if (narrowAllow.some((sel) => wrap.matches(sel))) continue;
      const w = wrap.getBoundingClientRect().width;
      if (w > 0 && w < wrapRatio * docEl.clientWidth) {
        issues.push({ type: 'narrow-wrap', el: describe(wrap), detail: `${Math.round(w)}px = ${(100 * w / docEl.clientWidth).toFixed(1)}% of ${docEl.clientWidth}px` });
      }
    }
  }
  return { issues, warnings, scrollHeight: docEl.scrollHeight };
}

async function screenshot(page, file, size) {
  const client = await page.context().newCDPSession(page);
  const metrics = await page.evaluate(() => ({ w: document.documentElement.clientWidth, h: document.documentElement.scrollHeight }));
  const scale = Math.min(1, 1920 / size.width);
  const shoot = async (h, name) => {
    const { data } = await client.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width: metrics.w, height: h, scale } });
    await writeFile(name, Buffer.from(data, 'base64'));
  };
  await shoot(Math.min(metrics.h, size.height), file.replace(/\.png$/, '-top.png'));
  // Chrome cannot rasterise arbitrarily tall surfaces; cap the full-page height.
  await shoot(Math.min(metrics.h, Math.floor(30000 / scale)), file);
  await client.detach();
}

async function runPool(jobs, concurrency, worker) {
  let index = 0;
  const runners = Array.from({ length: Math.min(concurrency, jobs.length) }, async () => {
    while (index < jobs.length) { const job = jobs[index++]; await worker(job); }
  });
  await Promise.all(runners);
}

async function launch() {
  return chromium.launch({ channel: 'chrome', headless: true });
}

async function snapshotMode(args) {
  const browser = await launch();
  const result = { createdAt: new Date().toISOString(), base: args.base, pages: {} };
  const jobs = [];
  for (const variant of variantsOf(args.variant)) for (const key of args.pages || Object.keys(PAGES)) for (const w of args.widths || SNAPSHOT_WIDTHS) jobs.push({ variant, key, w });
  await runPool(jobs, args.concurrency, async ({ variant, key, w }) => {
    const size = parseSize(w);
    const context = await browser.newContext({ viewport: size, deviceScaleFactor: 1, reducedMotion: 'reduce' });
    const page = await context.newPage();
    try {
      const errors = await preparePage(page, urlFor(args.base, variant, key));
      const snap = await page.evaluate(collectSnapshot);
      result.pages[`${variant}|${key}|${w}`] = { ...snap, errors };
      process.stdout.write('.');
    } finally { await context.close(); }
  });
  await browser.close();
  await mkdir(path.dirname(path.resolve(args.snapshot)), { recursive: true });
  await writeFile(args.snapshot, JSON.stringify(result));
  console.log(`\nSnapshot of ${jobs.length} page/width combinations written to ${args.snapshot}`);
}

async function compareMode(args) {
  const [a, b] = await Promise.all(args.compare.map(async (f) => JSON.parse(await readFile(f, 'utf8'))));
  let failures = 0;
  const keys = Array.from(new Set([...Object.keys(a.pages), ...Object.keys(b.pages)])).sort();
  for (const key of keys) {
    const pa = a.pages[key]; const pb = b.pages[key];
    if (!pa || !pb) { console.log(`MISSING ${key} (${pa ? 'only in A' : 'only in B'})`); failures++; continue; }
    const diffs = [];
    if (pa.elements.length !== pb.elements.length) diffs.push(`element count ${pa.elements.length} -> ${pb.elements.length}`);
    if (Math.abs(pa.scrollHeight - pb.scrollHeight) > 1) diffs.push(`scrollHeight ${pa.scrollHeight} -> ${pb.scrollHeight}`);
    if (Math.abs(pa.scrollWidth - pb.scrollWidth) > 1) diffs.push(`scrollWidth ${pa.scrollWidth} -> ${pb.scrollWidth}`);
    const n = Math.min(pa.elements.length, pb.elements.length);
    let moved = 0;
    for (let i = 0; i < n; i++) {
      const ea = pa.elements[i]; const eb = pb.elements[i];
      if (ea[0] !== eb[0]) { diffs.push(`element ${i} differs: ${ea[0]} vs ${eb[0]}`); break; }
      const delta = [1, 2, 3, 4, 5].map((k) => Math.abs(ea[k] - eb[k]));
      if (delta.some((d) => d > 1)) {
        moved++;
        if (moved <= 5) diffs.push(`#${i} ${ea[0]} [x,y,w,h,fs] ${ea.slice(1).join(',')} -> ${eb.slice(1).join(',')}`);
      }
    }
    if (moved > 5) diffs.push(`... ${moved} elements moved > 1px in total`);
    if (diffs.length) { failures++; console.log(`DIFF ${key}\n  ${diffs.join('\n  ')}`); }
  }
  console.log(`${keys.length - failures}/${keys.length} page/width combinations identical within 1px.`);
  process.exit(failures ? 1 : 0);
}

async function checkMode(args) {
  const outDir = path.resolve(ROOT, args.out);
  await mkdir(outDir, { recursive: true });
  const browser = await launch();
  const report = { createdAt: new Date().toISOString(), base: args.base, results: [] };
  const jobs = [];
  for (const variant of variantsOf(args.variant)) for (const key of args.pages || Object.keys(PAGES)) for (const w of args.widths || CHECK_WIDTHS) jobs.push({ variant, key, w });
  await runPool(jobs, args.concurrency, async ({ variant, key, w }) => {
    const size = parseSize(w);
    const context = await browser.newContext({ viewport: size, deviceScaleFactor: 1, reducedMotion: 'reduce' });
    const page = await context.newPage();
    try {
      const errors = await preparePage(page, urlFor(args.base, variant, key));
      const found = await page.evaluate(collectIssues, { desktopMin: DESKTOP_MIN, minFont: MIN_DESKTOP_FONT, wrapRatio: WRAP_MIN_RATIO, narrowAllow: NARROW_WRAP_ALLOW });
      for (const e of errors) found.issues.push({ type: 'page-error', detail: e });
      let shot = null;
      if (args.screenshots) {
        shot = path.join(outDir, `${variant}-${key}-${w}.png`);
        await screenshot(page, shot, size);
      }
      report.results.push({ variant, page: key, viewport: w, issues: found.issues, warnings: found.warnings, screenshot: shot });
      process.stdout.write(found.issues.length ? 'F' : '.');
    } catch (err) {
      report.results.push({ variant, page: key, viewport: w, issues: [{ type: 'run-error', detail: String(err && err.message || err) }], warnings: [] });
      process.stdout.write('E');
    } finally { await context.close(); }
  });
  await browser.close();
  process.stdout.write('\n');
  report.results.sort((x, y) => (x.variant + x.page + parseSize(x.viewport).width).localeCompare(y.variant + y.page + parseSize(y.viewport).width, 'en', { numeric: true }));
  await writeFile(path.join(outDir, 'report.json'), JSON.stringify(report, null, 2));

  const byWidth = new Map();
  for (const r of report.results) {
    const s = byWidth.get(r.viewport) || { pass: 0, fail: 0 };
    r.issues.length ? s.fail++ : s.pass++;
    byWidth.set(r.viewport, s);
  }
  for (const [w, s] of [...byWidth].sort((x, y) => parseSize(x[0]).width - parseSize(y[0]).width)) console.log(`${w.padEnd(10)} pass ${s.pass}  fail ${s.fail}`);
  let failed = 0;
  for (const r of report.results) {
    if (!r.issues.length) continue;
    failed++;
    console.log(`\nFAIL ${r.variant} ${r.page} @ ${r.viewport}`);
    for (const i of r.issues.slice(0, 12)) console.log(`  [${i.type}] ${i.el ? i.el + ' ' : ''}${i.detail || ''}`);
    if (r.issues.length > 12) console.log(`  ... ${r.issues.length - 12} more (see report.json)`);
  }
  const warnCount = report.results.reduce((n, r) => n + r.warnings.length, 0);
  console.log(`\n${report.results.length - failed}/${report.results.length} passed; ${warnCount} intentional-truncation warnings. Report: ${path.join(outDir, 'report.json')}`);
  process.exit(failed ? 1 : 0);
}

const args = parseArgs(process.argv.slice(2));
if (args.compare) await compareMode(args);
else if (args.snapshot) await snapshotMode(args);
else await checkMode(args);
