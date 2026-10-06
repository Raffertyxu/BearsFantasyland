// 逐格算出影片頁並用 ffmpeg 編成 MP4。
// 用法：node render.mjs [--page brand.html --out bears-brand-story-45s.mp4] [--chrome "path\to\chrome.exe"] [--profile <暫存資料夾>]
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

fs.mkdirSync(OUT_DIR, { recursive: true });

const ffmpeg = spawn('ffmpeg', [
  '-y', '-loglevel', 'error',
  '-f', 'rawvideo', '-pix_fmt', 'rgba', '-s', `${W}x${H}`, '-r', String(FPS), '-i', '-',
  '-c:v', 'libx264', '-preset', 'slow', '-crf', '19', '-pix_fmt', 'yuv420p',
  '-profile:v', 'high', '-movflags', '+faststart', OUT
], { stdio: ['pipe', 'inherit', 'inherit'] });

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
      await writeFrame(body);
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
  ffmpeg.stdin.end();
  ffmpeg.on('close', (c) => {
    server.close();
    const secs = ((Date.now() - started) / 1000).toFixed(1);
    if (code === 0 && c === 0) console.log(`done: ${frames} frames -> ${OUT} (${secs}s)`);
    else console.error(`failed (page ${code}, ffmpeg ${c}) after ${frames} frames`);
    process.exit(code || c);
  });
}

server.listen(0, '127.0.0.1', () => {
  const { port } = server.address();
  const url = `http://127.0.0.1:${port}/showcase/video/${PAGE}?render`;
  console.log(`serving ${ROOT} on ${port}`);
  chrome = spawn(CHROME, [
    '--headless=new', `--user-data-dir=${PROFILE}`, '--no-first-run', '--no-default-browser-check',
    '--ignore-gpu-blocklist', '--enable-gpu', '--use-angle=d3d11', '--enable-unsafe-swiftshader',
    '--disable-background-timer-throttling', '--disable-renderer-backgrounding', '--disable-backgrounding-occluded-windows',
    '--mute-audio', '--window-size=1920,1080', url
  ], { stdio: 'ignore' });
  chrome.on('exit', (c) => { if (!finishing) { console.error(`chrome exited early (${c})`); finish(1); } });
});
