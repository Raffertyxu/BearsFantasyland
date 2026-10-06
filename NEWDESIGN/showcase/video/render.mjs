// 逐格算出影片頁並用 ffmpeg 編成 MP4。
// 用法：node render.mjs [--page brand.html --out bears-brand-story-45s.mp4] [--chrome "path\to\chrome.exe"] [--profile <暫存資料夾>]
//   配樂：--audio <mp3> [--audio-start <秒>] [--audio-fade-out <秒，從片尾往前算>]
//         先算出無聲影片，再混入音訊（AAC 320k 雙聲道、-shortest，開頭 0.05s 淡入防爆音）。
//   單格：--stills 0.8,2.3,5 [--stills-dir <資料夾>]  只輸出指定時間點的 PNG，不編影片（頁面需支援 ?times=）。
//   不帶上述參數時行為與原本相同。
import http from 'node:http';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn } from 'node:child_process';

const HERE = import.meta.dirname;
const ROOT = path.resolve(HERE, '..', '..'); // NEWDESIGN/
const OUT_DIR = path.join(HERE, 'out');
const W = 1920, H = 1080, FPS = 30, FRAME_BYTES = W * H * 4;

const arg = (name, fallback) => {
  const i = process.argv.indexOf(name);
  return i > -1 ? process.argv[i + 1] : fallback;
};
const PAGE = arg('--page', 'reel.html');
const OUT = path.join(OUT_DIR, arg('--out', 'bears-furniture-30s.mp4'));
const CHROME = arg('--chrome', 'C:/Program Files/Google/Chrome/Application/chrome.exe');
const PROFILE = arg('--profile', fs.mkdtempSync(path.join(os.tmpdir(), 'reel-chrome-')));

const resolveInput = (p) => [path.resolve(p), path.resolve(HERE, p)].find((x) => fs.existsSync(x));
const AUDIO_ARG = arg('--audio', null);
const AUDIO = AUDIO_ARG ? resolveInput(AUDIO_ARG) : null;
if (AUDIO_ARG && !AUDIO) { console.error(`audio not found: ${AUDIO_ARG}`); process.exit(1); }
const AUDIO_START = Number(arg('--audio-start', 0));
const AUDIO_FADE_OUT = Number(arg('--audio-fade-out', 0));
const STILLS = arg('--stills', null);
const STILLS_DIR = path.resolve(arg('--stills-dir', path.join(OUT_DIR, 'stills')));
// 有配樂時先把無聲影片寫到暫存檔，算完再混音
const VIDEO_OUT = AUDIO ? OUT.replace(/\.mp4$/i, '') + '.video-only.tmp.mp4' : OUT;

fs.mkdirSync(OUT_DIR, { recursive: true });
if (STILLS) fs.mkdirSync(STILLS_DIR, { recursive: true });

const ffmpeg = STILLS ? null : spawn('ffmpeg', [
  '-y', '-loglevel', 'error',
  '-f', 'rawvideo', '-pix_fmt', 'rgba', '-s', `${W}x${H}`, '-r', String(FPS), '-i', '-',
  '-c:v', 'libx264', '-preset', 'slow', '-crf', '19', '-pix_fmt', 'yuv420p',
  '-profile:v', 'high', '-movflags', '+faststart', VIDEO_OUT
], { stdio: ['pipe', 'inherit', 'inherit'] });

const writeStill = (buf, t) => new Promise((resolve, reject) => {
  const file = path.join(STILLS_DIR, `t${Number(t).toFixed(2).padStart(5, '0')}.png`);
  const p = spawn('ffmpeg', ['-y', '-loglevel', 'error', '-f', 'rawvideo', '-pix_fmt', 'rgba', '-s', `${W}x${H}`, '-i', '-', '-frames:v', '1', file],
    { stdio: ['pipe', 'inherit', 'inherit'] });
  p.on('close', (c) => c === 0 ? (console.log(`still ${file}`), resolve()) : reject(new Error(`ffmpeg still exited ${c}`)));
  p.stdin.end(buf);
});

const MIME = { '.html': 'text/html; charset=utf-8', '.js': 'text/javascript', '.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg', '.css': 'text/css' };
let frames = 0, chrome = null;
const started = Date.now();

const readBody = (req) => new Promise((resolve, reject) => {
  const chunks = [];
  req.on('data', (c) => chunks.push(c));
  req.on('end', () => resolve(Buffer.concat(chunks)));
  req.on('error', reject);
});
const writeFrame = (buf) => new Promise((resolve) => {
  if (ffmpeg.stdin.write(buf)) resolve(); else ffmpeg.stdin.once('drain', resolve);
});

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, 'http://x');
  try {
    if (req.method === 'POST' && url.pathname === '/frame') {
      const body = await readBody(req);
      if (body.length !== FRAME_BYTES) { res.writeHead(400).end('bad frame size'); return; }
      if (STILLS) await writeStill(body, url.searchParams.get('t') ?? frames);
      else await writeFrame(body);
      frames++;
      res.writeHead(200).end('ok');
      return;
    }
    if (req.method === 'POST' && url.pathname === '/log') {
      const msg = (await readBody(req)).toString();
      console.log(`[page] ${msg}`);
      res.writeHead(200).end('ok');
      if (msg.startsWith('ERROR')) finish(1);
      return;
    }
    if (req.method === 'POST' && url.pathname === '/done') {
      res.writeHead(200).end('ok');
      finish(0);
      return;
    }
    const file = path.normalize(path.join(ROOT, decodeURIComponent(url.pathname)));
    if (!file.startsWith(ROOT) || !fs.existsSync(file) || fs.statSync(file).isDirectory()) { res.writeHead(404).end(); return; }
    res.writeHead(200, { 'Content-Type': MIME[path.extname(file).toLowerCase()] || 'application/octet-stream' });
    fs.createReadStream(file).pipe(res);
  } catch (err) {
    console.error(err);
    res.writeHead(500).end();
  }
});

let finishing = false;
function finish(code) {
  if (finishing) return;
  finishing = true;
  if (chrome) chrome.kill();
  if (STILLS) {
    server.close();
    console.log(`done: ${frames} stills -> ${STILLS_DIR}`);
    process.exit(code);
  }
  ffmpeg.stdin.end();
  ffmpeg.on('close', (c) => {
    server.close();
    const secs = ((Date.now() - started) / 1000).toFixed(1);
    if (code !== 0 || c !== 0) {
      console.error(`failed (page ${code}, ffmpeg ${c}) after ${frames} frames`);
      process.exit(code || c);
    }
    if (!AUDIO) {
      console.log(`done: ${frames} frames -> ${OUT} (${secs}s)`);
      process.exit(0);
    }
    muxAudio(frames / FPS).then((m) => {
      if (m === 0) {
        // 用 unlinkSync：Node 24 的 fs.rmSync 遇到含中文的 Windows 路徑會直接讓程序以 127 結束（2026-10-06 實測）
        try { fs.unlinkSync(VIDEO_OUT); }
        catch (err) { console.warn(`could not remove ${VIDEO_OUT}: ${err.code || err.message}`); }
      }
      console.log(m === 0 ? `done: ${frames} frames + audio -> ${OUT} (${secs}s)` : `audio mux failed; video-only file kept at ${VIDEO_OUT}`);
      process.exit(m);
    });
  });
}

// 混入配樂：從曲目 AUDIO_START 秒開始，開頭 0.05s 淡入，片尾 AUDIO_FADE_OUT 秒淡出
function muxAudio(duration) {
  const fades = ['afade=t=in:st=0:d=0.05'];
  if (AUDIO_FADE_OUT > 0) fades.push(`afade=t=out:st=${Math.max(duration - AUDIO_FADE_OUT, 0).toFixed(3)}:d=${AUDIO_FADE_OUT}`);
  const args = [
    '-y', '-loglevel', 'error',
    '-i', VIDEO_OUT,
    '-ss', String(AUDIO_START), '-i', AUDIO,
    '-map', '0:v:0', '-map', '1:a:0',
    '-c:v', 'copy', '-af', fades.join(','), '-c:a', 'aac', '-b:a', '320k', '-ac', '2', '-ar', '48000',
    '-shortest', '-movflags', '+faststart', OUT
  ];
  return new Promise((resolve) => {
    const p = spawn('ffmpeg', args, { stdio: 'inherit' });
    p.on('close', resolve);
  });
}

server.listen(0, '127.0.0.1', () => {
  const { port } = server.address();
  const url = `http://127.0.0.1:${port}/showcase/video/${PAGE}?render` + (STILLS ? `&times=${encodeURIComponent(STILLS)}` : '');
  console.log(`serving ${ROOT} on ${port}`);
  chrome = spawn(CHROME, [
    '--headless=new', `--user-data-dir=${PROFILE}`, '--no-first-run', '--no-default-browser-check',
    '--ignore-gpu-blocklist', '--enable-gpu', '--use-angle=d3d11', '--enable-unsafe-swiftshader',
    '--disable-background-timer-throttling', '--disable-renderer-backgrounding', '--disable-backgrounding-occluded-windows',
    '--mute-audio', '--window-size=1920,1080', url
  ], { stdio: 'ignore' });
  chrome.on('exit', (c) => { if (!finishing) { console.error(`chrome exited early (${c})`); finish(1); } });
});
