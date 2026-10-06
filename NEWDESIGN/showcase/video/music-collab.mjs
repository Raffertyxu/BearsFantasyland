// 合作提案影片原創配樂：程式合成，120 BPM、D 大調，重拍與音效對齊 collab.html 時間軸。
// 用法：node music-collab.mjs <輸出.wav>
// 產出 48 kHz、32-bit float 立體聲 WAV（未做響度標準化，由 mux 步驟的 loudnorm 處理）。
import fs from 'node:fs';

const SR = 48000, DUR = 60, N = SR * DUR;
const OUT = process.argv[2] || 'collab-music.wav';
const dry = [new Float32Array(N), new Float32Array(N)];
const send = [new Float32Array(N), new Float32Array(N)];
const duckBus = [new Float32Array(N), new Float32Array(N)]; // 會被大鼓側鏈壓縮的鋪底與貝斯

/* ---------------- 小工具 ---------------- */
let seed = 12345;
const rnd = () => { seed = (seed * 1664525 + 1013904223) >>> 0; return seed / 4294967296; };
const noise = () => rnd() * 2 - 1;
const mtof = (m) => 440 * Math.pow(2, (m - 69) / 12);
const TAU = Math.PI * 2;

// 把單聲道片段放到匯流排：pan -1..1（等功率），sendAmt 為殘響送出量
function place(buf, t0, gain, pan = 0, sendAmt = .2, bus = dry) {
  const s0 = Math.round(t0 * SR);
  const a = (pan + 1) * Math.PI / 4, gl = Math.cos(a) * gain, gr = Math.sin(a) * gain;
  for (let i = 0; i < buf.length; i++) {
    const j = s0 + i; if (j < 0) continue; if (j >= N) break;
    const v = buf[i];
    bus[0][j] += v * gl; bus[1][j] += v * gr;
    send[0][j] += v * gl * sendAmt; send[1][j] += v * gr * sendAmt;
  }
}

// RBJ biquad
function biquad(type, f, q) {
  const w = TAU * Math.min(f, SR * .45) / SR, c = Math.cos(w), s = Math.sin(w), al = s / (2 * q);
  let b0, b1, b2, a0, a1, a2;
  if (type === 'lp') { b0 = (1 - c) / 2; b1 = 1 - c; b2 = (1 - c) / 2; }
  else if (type === 'hp') { b0 = (1 + c) / 2; b1 = -(1 + c); b2 = (1 + c) / 2; }
  else { b0 = al; b1 = 0; b2 = -al; } // bp
  a0 = 1 + al; a1 = -2 * c; a2 = 1 - al;
  return [b0 / a0, b1 / a0, b2 / a0, a1 / a0, a2 / a0];
}
// 可隨時間改截止頻率的濾波器：fFn(t) 每 32 樣本更新一次
function filter(buf, type, fFn, q = .707) {
  let x1 = 0, x2 = 0, y1 = 0, y2 = 0, k = biquad(type, fFn(0), q);
  for (let i = 0; i < buf.length; i++) {
    if ((i & 31) === 0) k = biquad(type, fFn(i / SR), q);
    const x = buf[i], y = k[0] * x + k[1] * x1 + k[2] * x2 - k[3] * y1 - k[4] * y2;
    x2 = x1; x1 = x; y2 = y1; y1 = y; buf[i] = y;
  }
  return buf;
}

/* ---------------- 樂器 ---------------- */
// 柔和鋼琴（felt piano）：非諧和泛音＋高頻衰減較快＋琴槌噪音
function piano(m, dur, vel = .6, bright = 1) {
  const f = mtof(m), len = Math.min(dur + 1.6, 5), n = Math.round(len * SR);
  const b = new Float32Array(n);
  const P = f > 1200 ? 4 : 8;
  for (let k = 1; k <= P; k++) {
    const fk = f * k * Math.sqrt(1 + .00035 * k * k);
    if (fk > 9000) break;
    const amp = vel * Math.pow(k, -1.25 - (1 - bright) * .8) * (k === 1 ? 1 : .55);
    const dec = (.9 + .55 * k) * Math.pow(f / 440, .35);
    const w = TAU * fk / SR, ph = rnd() * TAU;
    // 遞迴振盪器加速
    let s1 = Math.sin(ph), s0 = Math.sin(ph - w); const c2 = 2 * Math.cos(w);
    for (let i = 0; i < n; i++) {
      const t = i / SR;
      const env = Math.exp(-t * dec) * Math.min(1, t / .004);
      b[i] += s1 * amp * env;
      const s2 = c2 * s1 - s0; s0 = s1; s1 = s2;
    }
  }
  // 放鍵
  const r0 = Math.round(dur * SR);
  for (let i = r0; i < n; i++) b[i] *= Math.exp(-(i - r0) / SR * 5);
  // 琴槌
  for (let i = 0; i < Math.min(n, 480); i++) b[i] += noise() * vel * .025 * Math.exp(-i / 90);
  return b;
}
// 鈴音（流程步驟、圖示描繪）
function bell(m, vel = .4, len = 2.5) {
  const f = mtof(m), n = Math.round(len * SR), b = new Float32Array(n);
  [[1, 1, 1.4], [2.0, .5, 2.2], [3.01, .3, 3.5], [4.17, .18, 5], [5.43, .1, 7]].forEach(([r, a, d]) => {
    const w = TAU * f * r / SR;
    for (let i = 0; i < n; i++) { const t = i / SR; b[i] += Math.sin(w * i) * a * vel * Math.exp(-t * d) * Math.min(1, t / .002); }
  });
  return b;
}
// 溫暖鋪底：每音三支微失諧鋸齒波＋低通＋慢起慢收
function pad(notes, dur, vel = .12, cut = 1100) {
  const att = .5, rel = .9, n = Math.round((dur + rel) * SR);
  const out = [new Float32Array(n), new Float32Array(n)];
  notes.forEach((m, ni) => {
    [-7, 0, 7].forEach((cents, di) => {
      const f = mtof(m) * Math.pow(2, cents / 1200), inc = f / SR;
      let ph = rnd();
      const b = new Float32Array(n);
      for (let i = 0; i < n; i++) { b[i] = (ph * 2 - 1); ph += inc; if (ph >= 1) ph -= 1; }
      filter(b, 'lp', (t) => cut * (1 + .15 * Math.sin(TAU * .2 * t + ni)), .6);
      filter(b, 'lp', () => cut * 1.6, .5);
      const ch = (di + ni) % 2;
      for (let i = 0; i < n; i++) out[ch][i] += b[i];
    });
  });
  for (let i = 0; i < n; i++) {
    const t = i / SR;
    const env = Math.min(1, t / att) * (t > dur ? Math.exp(-(t - dur) / rel * 3) : 1) * vel / notes.length;
    out[0][i] *= env; out[1][i] *= env;
  }
  return out;
}
function bass(m, dur, vel = .5) {
  const f = mtof(m), n = Math.round((dur + .15) * SR), b = new Float32Array(n);
  for (let i = 0; i < n; i++) {
    const t = i / SR;
    const env = Math.min(1, t / .006) * Math.exp(-t * 1.2) * (t > dur ? Math.exp(-(t - dur) * 40) : 1);
    b[i] = Math.tanh((Math.sin(TAU * f * t) + .35 * Math.sin(TAU * 2 * f * t) + .12 * Math.sin(TAU * 3 * f * t)) * 1.3) * vel * env;
  }
  return b;
}
function kick(vel = 1) {
  const n = Math.round(.5 * SR), b = new Float32Array(n); let ph = 0;
  for (let i = 0; i < n; i++) {
    const t = i / SR, f = 46 + 95 * Math.exp(-t * 28);
    ph += TAU * f / SR;
    b[i] = Math.sin(ph) * Math.exp(-t * 6.5) * vel + (i < 140 ? noise() * .15 * vel * (1 - i / 140) : 0);
  }
  return b;
}
function snap(vel = .5) {
  const n = Math.round(.35 * SR), b = new Float32Array(n);
  for (let i = 0; i < n; i++) {
    const t = i / SR;
    // 三次極短爆發＋尾巴，類似指響／拍手
    const e = Math.exp(-t * 30) + .6 * Math.exp(-Math.max(0, t - .008) * 60) * (t > .008 ? 1 : 0) + .5 * Math.exp(-Math.max(0, t - .016) * 60) * (t > .016 ? 1 : 0);
    b[i] = noise() * e * vel;
  }
  filter(b, 'bp', () => 1900, .9);
  return b;
}
function hat(vel = .2, len = .07) {
  const n = Math.round(len * SR), b = new Float32Array(n);
  for (let i = 0; i < n; i++) b[i] = noise() * Math.exp(-i / SR * 70) * vel;
  return filter(b, 'hp', () => 7500, .7);
}
function tom(m, vel = .4) {
  const n = Math.round(.6 * SR), b = new Float32Array(n); let ph = 0; const f0 = mtof(m);
  for (let i = 0; i < n; i++) { const t = i / SR; ph += TAU * f0 * (1 + .6 * Math.exp(-t * 20)) / SR; b[i] = Math.sin(ph) * Math.exp(-t * 7) * vel; }
  return b;
}
// 風切：帶通雜訊掃頻，峰值在 len*peak
function whoosh(len, vel = .3, f0 = 300, f1 = 3200, peak = .6) {
  const n = Math.round(len * SR), b = new Float32Array(n);
  for (let i = 0; i < n; i++) b[i] = noise();
  filter(b, 'bp', (t) => { const u = t / len; return u < peak ? f0 * Math.pow(f1 / f0, u / peak) : f1 * Math.pow(f0 / f1, (u - peak) / (1 - peak)); }, 1.4);
  for (let i = 0; i < n; i++) { const u = i / n; b[i] *= vel * (u < peak ? Math.pow(u / peak, 2) : Math.pow(1 - (u - peak) / (1 - peak), 1.5)); }
  return b;
}
// 上升音（結束於撞擊點）
function riser(len, vel = .3) {
  const n = Math.round(len * SR), b = new Float32Array(n);
  for (let i = 0; i < n; i++) b[i] = noise();
  filter(b, 'hp', (t) => 400 * Math.pow(18, t / len), .9);
  let ph = 0;
  for (let i = 0; i < n; i++) {
    const u = i / n; ph += TAU * (220 * Math.pow(4, u)) / SR;
    b[i] = (b[i] * .8 + Math.sin(ph) * .25 * u) * vel * Math.pow(u, 2.4);
  }
  return b;
}
// 撞擊：次低頻下潛＋低通爆裂
function impact(vel = 1) {
  const n = Math.round(2.4 * SR), b = new Float32Array(n), nz = new Float32Array(n); let ph = 0;
  for (let i = 0; i < n; i++) nz[i] = noise();
  filter(nz, 'lp', (t) => 3500 * Math.exp(-t * 3) + 120, .7);
  for (let i = 0; i < n; i++) {
    const t = i / SR; ph += TAU * (34 + 30 * Math.exp(-t * 5)) / SR;
    b[i] = (Math.sin(ph) * Math.exp(-t * 1.6) * .9 + nz[i] * Math.exp(-t * 4) * .5) * vel * Math.min(1, t / .002);
  }
  return b;
}

/* ---------------- 和聲 ---------------- */
// 每小節 2 秒，小節 0 從 4.5 秒（「木」穿越落地）開始
const BAR0 = 4.5, BAR = 2, BEAT = .5;
const CH = [
  { name:'D',     pad:[62, 66, 69, 76], bass:38 },
  { name:'A/C#',  pad:[61, 64, 69, 71], bass:37 },
  { name:'Bm7',   pad:[62, 66, 69, 73], bass:35 },
  { name:'Gmaj7', pad:[62, 66, 67, 71], bass:43 },
  { name:'Em7',   pad:[62, 64, 67, 71], bass:40 },
  { name:'Asus4', pad:[62, 64, 69, 71], bass:45 },
  { name:'F#m7',  pad:[61, 64, 66, 69], bass:42 },
  { name:'Gadd9', pad:[62, 66, 69, 71], bass:43 }
];
const A_DOM = { name:'A', pad:[61, 64, 69, 73], bass:45 };
const D_END = { name:'Dmaj9', pad:[61, 66, 69, 76], bass:38 };
function chordAt(t) {
  if (t >= 54.5) return D_END;
  if (t >= 52.5) return A_DOM;
  const k = Math.floor((t - BAR0) / BAR);
  return CH[((k % 8) + 8) % 8];
}
const MELODY = [
  [[0, 1.5, 78], [1.5, .5, 76], [2, 2, 74]],
  [[0, 1, 76], [1, 1, 73], [2, 2, 69]],
  [[0, 1.5, 74], [1.5, .5, 73], [2, 1, 71], [3, 1, 78]],
  [[0, 3, 76], [3, 1, 74]],
  [[0, 1, 71], [1, 1, 74], [2, 2, 79]],
  [[0, 1.5, 78], [1.5, .5, 76], [2, 1, 74], [3, 1, 76]],
  [[0, 1, 73], [1, 1, 76], [2, 2, 81]],
  [[0, 2, 78], [2, 1, 76], [3, 1, 74]]
];
const inAny = (t, ranges) => ranges.some(([a, b]) => t >= a - 1e-6 && t < b - 1e-6);

/* ---------------- 編曲 ---------------- */
// 鋪底：逐小節（含前導 0.5–4.5 的 F#m → G）
for (let bs = BAR0 - 2 * BAR; bs < DUR; bs += BAR) {
  const t0 = Math.max(.3, bs), ch = chordAt(bs + .01);
  if (bs >= 56.5) break;
  const isEnd = ch === D_END;
  const dur = isEnd ? DUR - t0 - .6 : bs + BAR - t0 + .15;
  const lvl = t0 < 4.5 ? .1 : inAny(t0, [[13.5, 15.5], [41.5, 48.5]]) ? .16 : .13;
  const p = pad(ch.pad, dur, lvl, t0 < 4.5 ? 700 : 1150);
  place(p[0], t0, 1, -.6, .45, duckBus); place(p[1], t0, 1, .6, .45, duckBus);
  if (isEnd) break;
}
// 開場：三個字各一聲鋼琴、木字填色時泛音閃光
[[1.0, 69], [1.14, 74], [1.28, 78]].forEach(([t, m], i) => place(piano(m, 1.4, .42, .8), t, .9, -.3 + i * .3, .55));
[86, 90, 93].forEach((m, i) => place(bell(m, .12, 3), 2.5 + i * .06, 1, -.4 + i * .4, .7));
place(riser(1.9, .22), 2.6, 1, 0, .5);
place(impact(.85), 4.5, 1, 0, .4);
[50, 57, 62, 66, 69].forEach((m) => place(piano(m, 2.5, .45, .9), 4.5, .8, 0, .5));

// 琶音（八分音符）：主歌、建構、主段、流程、場景、片尾前
const ARP = [[6.5, 13.5], [16.5, 20.3], [20.5, 27.5], [28.5, 34], [35.5, 41.5], [48.5, 54.5]];
for (let t = 6.5; t < 54.5; t += BEAT / 2) {
  if (!inAny(t, ARP)) continue;
  const ch = chordAt(t + .001), step = Math.round((t - BAR0) / (BEAT / 2)) % 8;
  const pool = [ch.bass + 24, ch.pad[0], ch.pad[1], ch.pad[2], ch.pad[3], ch.pad[2], ch.pad[1], ch.pad[0]];
  const accent = step % 2 === 0 ? 1 : .75;
  const vel = (inAny(t, [[6.5, 13.5], [48.5, 50.5]]) ? .2 : .26) * accent;
  place(piano(pool[step], .3, vel, .7), t, 1, step % 2 ? -.45 : -.2, .3);
}
// 主旋律
const MEL = [[4.5, 13.5, 0], [20.5, 27.5, 0], [35.5, 41.5, 12], [41.5, 48.5, 0]];
for (let bs = BAR0; bs < 54.5; bs += BAR) {
  const k = ((Math.round((bs - BAR0) / BAR) % 8) + 8) % 8;
  MELODY[k].forEach(([b, d, m]) => {
    const t = bs + b * BEAT;
    const sec = MEL.find(([a, z]) => t >= a && t < z - .01);
    if (!sec) return;
    const vel = sec[0] === 41.5 ? .5 : .44;
    place(piano(m + sec[2], d * BEAT, vel, sec[2] ? .75 : .9), t, 1, .25, .5);
  });
}
// 呼吸段（13.5–15.5）一個高音
place(piano(81, 1.5, .3, .8), 13.75, 1, .3, .7);
place(whoosh(1.6, .12, 200, 2000, .85), 14.1, 1, 0, .4);

// 貝斯
const BASS_PULSE = [[20.5, 27.5], [28.5, 34], [35.5, 41.5]];
const BASS_HOLD = [[6.5, 13.5], [16.5, 20.3], [48.5, 54.5]];
for (let t = 6.5; t < 54.5; t += BEAT / 2) {
  const ch = chordAt(t + .001), onBar = Math.abs(((t - BAR0) % BAR + BAR) % BAR) < 1e-6;
  if (inAny(t, BASS_PULSE)) place(bass(ch.bass, .2, onBar ? .5 : .36), t, 1, 0, .05, duckBus);
  else if (inAny(t, BASS_HOLD) && onBar) place(bass(ch.bass, 1.9, .42), t, 1, 0, .05, duckBus);
}
place(bass(38, 4.5, .5), 55, 1, 0, .05, duckBus);

// 鼓組
const kicks = [];
const KICK_HALF = [[8.5, 13.5], [50.5, 54.5]];
const KICK_FULL = [[16.5, 20.3], [20.5, 27.5], [28.5, 34], [35.5, 41.5]];
for (let t = 4.5; t < 54.5; t += BEAT) {
  const beat = Math.round((t - BAR0) / BEAT) % 4;
  let v = 0;
  if (inAny(t, KICK_FULL)) v = t < 20.3 ? .7 : .9;
  else if (inAny(t, KICK_HALF) && beat % 2 === 0) v = .6;
  if (v) { place(kick(v), t, 1, 0, .04); kicks.push(t); }
  if (inAny(t, [[20.5, 27.5], [28.5, 34], [35.5, 41.5]]) && beat % 2 === 1) place(snap(.42), t, 1, .1, .5);
}
for (let t = 10.5; t < 54.5; t += BEAT / 4) {
  const sub = Math.round((t - BAR0) / (BEAT / 4)) % 4;
  if (inAny(t, [[28.5, 34]])) place(hat(sub % 2 ? .1 : .16), t, 1, .35, .1);
  else if (inAny(t, [[10.5, 13.5], [16.5, 20.3], [20.5, 27.5], [35.5, 41.5], [52.5, 54.5]]) && sub === 2) place(hat(.15, .09), t, 1, .35, .1);
}
// 18.5–20.3 指響漸密
[18.5, 19.0, 19.5, 19.75, 19.875, 20.0, 20.0625, 20.125, 20.1875].forEach((t, i) => place(snap(.18 + i * .03), t, 1, 0, .5));

// 四格升起：每格一聲低沉木鼓＋輕風切
[0, 1, 2, 3].forEach((i) => {
  const t = 15.6 + i * .11;
  place(tom(45 - i * 2, .3), t, 1, -.6 + i * .4, .35);
  place(whoosh(.5, .07, 400, 2500, .3), t - .05, 1, -.6 + i * .4, .3);
});
place(riser(2.0, .24), 18.3, 1, 0, .5);
place(impact(.9), 20.3, 1, 0, .4);
[50, 57, 62, 69, 74].forEach((m) => place(piano(m, 2, .4, .9), 20.3, .75, 0, .5));

// 轉場風切（依 collab.html SHOTS，排除已有撞擊的鏡頭）
[[7, 1.0], [10.5, 1.0], [13.5, .8], [23.5, .6], [27.5, .6], [29, .4], [30, .4], [31, .4], [32, .4], [33, .4],
 [35.5, .6], [37, .5], [38.5, .5], [40, .5], [41.5, .9], [44.8, .8], [48.5, 1.0], [51.5, .9]].forEach(([at, d], i) => {
  const len = Math.max(d * 1.6, .6);
  place(whoosh(len, d <= .5 ? .1 : .13, 250, 2800 + (i % 3) * 600, .62), at - len * .62, 1, (i % 2 ? .5 : -.5), .35);
});
// 合作流程：五步各一聲鈴，音高上行
[[29, 74], [30, 76], [31, 78], [32, 81], [33, 83]].forEach(([t, m]) => place(bell(m, .2, 2), t, 1, .2, .55));
// 場景段：開場撞擊
place(riser(1.4, .2), 32.6, 1, 0, .5);
place(impact(.7), 34, 1, 0, .45);
// 永續四圖示：描線時的鈴音
[0, 1, 2, 3].forEach((i) => place(bell([81, 85, 88, 90][i], .1, 2.5), 45.2 + i * .22, 1, -.6 + i * .4, .7));
// 片尾
place(riser(2.3, .24), 52.7, 1, 0, .5);
place(impact(1), 55, 1, 0, .5);
[38, 50, 57, 61, 66, 69, 76].forEach((m, i) => place(piano(m, 4, .42, .9), 55 + i * .012, .8, -.3 + i * .1, .6));
[90, 93].forEach((m, i) => place(bell(m, .1, 4), 56 + i * .15, 1, i ? .5 : -.5, .8));

/* ---------------- 側鏈 ＋ 混音 ---------------- */
const duck = new Float32Array(N).fill(1);
for (const tk of kicks) {
  const s0 = Math.round(tk * SR);
  for (let i = 0; i < SR * .45 && s0 + i < N; i++) duck[s0 + i] = Math.min(duck[s0 + i], 1 - .5 * Math.exp(-i / SR * 9));
}
for (let c = 0; c < 2; c++) for (let i = 0; i < N; i++) dry[c][i] += duckBus[c][i] * duck[i];

/* ---------------- 殘響：FFT 卷積 ---------------- */
function fft(re, im, inv) {
  const n = re.length;
  for (let i = 1, j = 0; i < n; i++) {
    let bit = n >> 1; for (; j & bit; bit >>= 1) j ^= bit; j ^= bit;
    if (i < j) { let t = re[i]; re[i] = re[j]; re[j] = t; t = im[i]; im[i] = im[j]; im[j] = t; }
  }
  for (let len = 2; len <= n; len <<= 1) {
    const ang = TAU / len * (inv ? 1 : -1), wr = Math.cos(ang), wi = Math.sin(ang);
    for (let i = 0; i < n; i += len) {
      let cr = 1, ci = 0;
      for (let j = 0; j < len / 2; j++) {
        const a = i + j, b = a + len / 2;
        const xr = re[b] * cr - im[b] * ci, xi = re[b] * ci + im[b] * cr;
        re[b] = re[a] - xr; im[b] = im[a] - xi; re[a] += xr; im[a] += xi;
        const ncr = cr * wr - ci * wi; ci = cr * wi + ci * wr; cr = ncr;
      }
    }
  }
  if (inv) for (let i = 0; i < n; i++) { re[i] /= n; im[i] /= n; }
}
const RT = 2.6, IRN = Math.round(RT * 1.2 * SR), PRE = Math.round(.022 * SR);
let FN = 1; while (FN < N + IRN) FN <<= 1;
for (let c = 0; c < 2; c++) {
  // 指數衰減雜訊殘響，越後段越暗
  const ir = new Float64Array(IRN); let lp = 0, e = 0;
  for (let i = PRE; i < IRN; i++) {
    const t = (i - PRE) / SR, a = .25 + .7 * Math.min(1, t / 1.5);
    lp = lp * a + noise() * (1 - a);
    ir[i] = lp * Math.exp(-6.9 * t / RT) * Math.min(1, t / .015);
    e += ir[i] * ir[i];
  }
  const g = 1 / Math.sqrt(e);
  const sr = new Float64Array(FN), si = new Float64Array(FN), hr = new Float64Array(FN), hi = new Float64Array(FN);
  for (let i = 0; i < N; i++) sr[i] = send[c][i];
  for (let i = 0; i < IRN; i++) hr[i] = ir[i] * g;
  fft(sr, si, false); fft(hr, hi, false);
  for (let i = 0; i < FN; i++) { const r = sr[i] * hr[i] - si[i] * hi[i]; si[i] = sr[i] * hi[i] + si[i] * hr[i]; sr[i] = r; }
  fft(sr, si, true);
  for (let i = 0; i < N; i++) dry[c][i] += sr[i] * .55;
  console.log(`reverb ch${c} done`);
}

/* ---------------- 母帶：高通、柔性削峰、收尾淡出 ---------------- */
for (let c = 0; c < 2; c++) {
  const b = dry[c];
  filter(b, 'hp', () => 28, .7);
  let peak = 0; for (let i = 0; i < N; i++) peak = Math.max(peak, Math.abs(b[i]));
  const pre = .9 / peak * 1.8;
  for (let i = 0; i < N; i++) {
    const t = i / SR;
    const fade = Math.min(1, t / .05) * (t > 58.6 ? Math.max(0, 1 - (t - 58.6) / 1.4) ** 1.5 : 1);
    b[i] = Math.tanh(b[i] * pre) / Math.tanh(1.8) * .89 * fade;
  }
}

/* ---------------- 寫 WAV（float32） ---------------- */
const data = Buffer.alloc(N * 8);
for (let i = 0; i < N; i++) { data.writeFloatLE(dry[0][i], i * 8); data.writeFloatLE(dry[1][i], i * 8 + 4); }
const hdr = Buffer.alloc(44);
hdr.write('RIFF', 0); hdr.writeUInt32LE(36 + data.length, 4); hdr.write('WAVE', 8);
hdr.write('fmt ', 12); hdr.writeUInt32LE(16, 16); hdr.writeUInt16LE(3, 20); hdr.writeUInt16LE(2, 22);
hdr.writeUInt32LE(SR, 24); hdr.writeUInt32LE(SR * 8, 28); hdr.writeUInt16LE(8, 32); hdr.writeUInt16LE(32, 34);
hdr.write('data', 36); hdr.writeUInt32LE(data.length, 40);
fs.writeFileSync(OUT, Buffer.concat([hdr, data]));
console.log(`done -> ${OUT}`);
