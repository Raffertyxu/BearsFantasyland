"""合成 alba-canvas.html 的 30 秒配樂與音效（120 BPM、D 小調），輸出 out/alba-canvas-score.wav。

全部由程式合成，沒有取樣、沒有版權素材；拍點與 alba-canvas.html 的 HITS／場景表一致。
用法：python NEWDESIGN/showcase/video/score.py   （需 numpy）
"""
import wave
from pathlib import Path

import numpy as np

SR = 48000
DUR = 30.0
N = int(SR * DUR)
OUT = Path(__file__).resolve().parent / "out" / "alba-canvas-score.wav"
rng = np.random.default_rng(7)

mix = np.zeros((2, N))      # 乾聲
send = np.zeros((2, N))     # 送殘響
duck = np.ones(N)           # 底鼓側鏈壓縮


def place(sig, t0, gain=1.0, pan=0.0, rev=0.0, ducked=False):
    """把單聲道或立體聲訊號放到 t0 秒。pan -1 左、1 右。"""
    sig = np.atleast_2d(sig)
    if sig.shape[0] == 1:
        l, r = np.cos((pan + 1) * np.pi / 4), np.sin((pan + 1) * np.pi / 4)
        sig = np.vstack([sig[0] * l * 1.414, sig[0] * r * 1.414])
    i0 = int(t0 * SR)
    if i0 >= N:
        return
    n = min(sig.shape[1], N - max(i0, 0))
    s = sig[:, :n] * gain
    if ducked:
        s = s * duck[i0:i0 + n]
    mix[:, i0:i0 + n] += s
    send[:, i0:i0 + n] += s * rev


def t_axis(dur):
    return np.arange(int(dur * SR)) / SR


def stft_filter(x, gain_fn, n=2048, hop=512):
    """時變濾波：gain_fn(frame 時間比例 0–1, 頻率陣列) -> 增益。"""
    win = np.hanning(n)
    pad = np.concatenate([np.zeros(n), x, np.zeros(n)])
    frames = (len(pad) - n) // hop
    out = np.zeros(len(pad))
    norm = np.zeros(len(pad))
    freqs = np.fft.rfftfreq(n, 1 / SR)
    for f in range(frames):
        seg = pad[f * hop:f * hop + n] * win
        spec = np.fft.rfft(seg) * gain_fn(f / max(frames - 1, 1), freqs)
        out[f * hop:f * hop + n] += np.fft.irfft(spec) * win
        norm[f * hop:f * hop + n] += win ** 2
    return (out / np.maximum(norm, 1e-6))[n:n + len(x)]


def band(fc, width):
    return lambda f: np.exp(-0.5 * (np.log2(np.maximum(f, 1) / fc) / width) ** 2)


# ---------------- 音色 ----------------
def boom(amp=1.0, dur=2.6):
    """重擊：下滑正弦 + 低頻噪音 + 瞬態。"""
    t = t_axis(dur)
    f = 32 + 48 * np.exp(-t * 6)
    sub = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 1.6)
    noise = stft_filter(rng.standard_normal(len(t)), lambda u, fr: band(180, 1.3)(fr)) * np.exp(-t * 7) * .9
    click = rng.standard_normal(len(t)) * np.exp(-t * 400) * .6
    return np.tanh((sub * 1.1 + noise + click) * 1.6) * amp


def whoosh(dur=.7, f0=300, f1=5000, rising=True):
    t = t_axis(dur)
    env = (t / dur) ** 2.2 if rising else np.exp(-t / dur * 4)
    x = stft_filter(rng.standard_normal(len(t)), lambda u, fr: band(f0 * (f1 / f0) ** u, .9)(fr))
    return x * env


def riser(dur, f0=110, f1=880):
    t = t_axis(dur)
    u = t / dur
    f = f0 * (f1 / f0) ** (u ** 1.6)
    tone = np.sin(2 * np.pi * np.cumsum(f) / SR) + .5 * np.sin(2 * np.pi * np.cumsum(f * 1.5) / SR)
    noise = stft_filter(rng.standard_normal(len(t)), lambda v, fr: band(300 * 30 ** v, 1.1)(fr))
    return (tone * .35 + noise * .9) * u ** 2.5


def kick():
    t = t_axis(.45)
    f = 45 + 110 * np.exp(-t * 30)
    return np.tanh(np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 7) * 2.2) + rng.standard_normal(len(t)) * np.exp(-t * 300) * .3


def clap():
    t = t_axis(.35)
    env = sum(np.exp(-np.maximum(t - d, 0) * 60) * (t >= d) for d in (0, .011, .023)) + np.exp(-t * 14) * .5
    return stft_filter(rng.standard_normal(len(t)), lambda u, fr: band(1500, .8)(fr)) * env * .9


def hat(open_=False):
    t = t_axis(.25 if open_ else .06)
    return stft_filter(rng.standard_normal(len(t)), lambda u, fr: (fr > 7000).astype(float)) * np.exp(-t * (14 if open_ else 70))


def shutter():
    t = t_axis(.12)
    a = stft_filter(rng.standard_normal(len(t)), lambda u, fr: band(3500, .6)(fr)) * np.exp(-t * 120)
    b = np.sin(2 * np.pi * 140 * t) * np.exp(-t * 40) * .8
    return a + b


def saw(freq, dur, harm=12, detune=.004):
    t = t_axis(dur)
    out = np.zeros(len(t))
    for d in (-detune, 0, detune):
        for k in range(1, harm + 1):
            out += np.sin(2 * np.pi * freq * (1 + d) * k * t + k) / k
    return out / 3


def pluck(freq, dur=.6):
    t = t_axis(dur)
    x = sum(np.sin(2 * np.pi * freq * k * t) * np.exp(-t * (6 + k * 5)) / k for k in range(1, 7))
    return x * (1 - np.exp(-t * 800))


def bell(freq, dur=3.0):
    t = t_axis(dur)
    x = sum(a * np.sin(2 * np.pi * freq * r * t) * np.exp(-t * dcy) for r, a, dcy in ((1, 1, 1.3), (2, .5, 2.2), (3.01, .25, 3.5), (4.2, .12, 5)))
    return x * (1 - np.exp(-t * 600))


def pad(freqs, dur, attack=1.5, release=1.5, harm=8):
    t = t_axis(dur)
    env = np.minimum(np.minimum(t / attack, 1), np.minimum((dur - t) / release, 1))
    l = sum(saw(f, dur, harm, .003) for f in freqs)
    r = sum(saw(f * 1.002, dur, harm, .005) for f in freqs)
    return np.vstack([l, r]) * env / len(freqs)


NOTE = lambda n: 440 * 2 ** ((n - 69) / 12)  # MIDI → Hz
D2, F2, A2, C3, D3, E3, F3, A3, BB1, BB2 = (NOTE(n) for n in (38, 41, 45, 48, 50, 52, 53, 57, 34, 46))

# ---------------- 編曲 ----------------
# 前段墊音（0–14）：Dm9，緩慢升起
place(pad([D2, A2, F3, C3 * 2, E3 * 2], 14.6, 3, 1.2), 0, .55, rev=.5)
# 光束掃過（0.2–1.95）：空氣聲
place(whoosh(1.75, 2500, 9000), .2, .25, pan=-.3, rev=.6)
place(riser(.9, 220, 880), 1.1, .35, rev=.4)
for at, amp in ((2, 1), (6, 1.25), (22, 1), (26, 1.2)):
    place(boom(amp), at, .5, rev=.35)
# 竹紋特寫、翻轉：低鳴與上升
place(bell(NOTE(74), 3), 2.5, .18, pan=.3, rev=.7)
place(bell(NOTE(69), 3), 3.0, .15, pan=-.3, rev=.7)
place(riser(1.6, 150, 600), 4.4, .4, rev=.4)
place(whoosh(.8, 200, 3000), 5.2, .45, rev=.3)
# 品名後的鈴聲旋律
for i, n in enumerate((74, 77, 81, 79)):
    place(bell(NOTE(n), 2.5), 6.5 + i * .5, .16, pan=(-.4, .4)[i % 2], rev=.7)
# 甩鏡（8）、急拉（8.9）、分件跳切（10/10.5/11）
place(whoosh(.55, 400, 6000), 7.45, .7, pan=.5, rev=.2)
place(whoosh(.5, 6000, 300, rising=False), 8.0, .5, pan=-.5, rev=.2)
place(whoosh(.45, 300, 4000), 8.6, .5, rev=.2)
place(boom(.5, 1.2), 9.05, .5, rev=.3)
for at in (10, 10.5, 11):
    place(shutter(), at, .8, rev=.25)
    place(kick(), at, .6)
# 側面輪廓＋蓄勢（12–14.875）
place(pad([D2, A2, D3, F3], 2.9, .3, .1, 10), 12, .4, rev=.4)
place(riser(2.875, 110, 1760), 12, .75, rev=.3)
for k in range(7):  # 頻閃 1/8 拍
    place(hat(), 14 + k * .125, .5 + k * .06, pan=(-.5, .5)[k % 2])
    place(pluck(NOTE(74 + (0, 3, 7, 12, 7, 3, 0)[k]), .2), 14 + k * .125, .2)
# drop（15–22）
chords = [(15, D2, (62, 65, 69, 74)), (17, BB1, (58, 62, 65, 70)), (19, F2, (65, 69, 72, 77)), (21, D2 * 2 ** (-2 / 12), (60, 64, 67, 72))]
for b in range(14):  # 拍
    at = 15 + b * .5
    if at >= 22:
        break
    light = at >= 19
    place(kick(), at, 1.3 if not light else .7)
    d0 = int(at * SR)
    dn = int(.35 * SR)
    duck[d0:d0 + dn] = np.minimum(duck[d0:d0 + dn], 1 - .7 * np.exp(-np.arange(dn) / SR * 9))
    if b % 2 == 1 and not light:
        place(clap(), at, .7, rev=.3)
    place(hat(b % 4 == 3), at + .25, .3, pan=.3)
for c0, root, notes in chords:
    for k in range(4):  # 低音：八分音符
        for half in (0, .25):
            t0 = c0 + k * .5 + half
            if t0 >= 22:
                continue
            place(np.tanh(saw(root, .24, 6) * 2) * np.exp(-t_axis(.24) * 5), t0, .5, ducked=True)
    for k in range(8):  # 琶音
        t0 = c0 + k * .25
        if t0 < 22:
            place(pluck(NOTE(notes[k % 4] + (12 if k >= 4 else 0)), .5), t0, .24, pan=(-.35, .35)[k % 2], rev=.35, ducked=True)
    place(pad([NOTE(n) for n in notes], 2.1, .05, .3, 6), c0, .22, rev=.4, ducked=True)
# 陳列段與標語（19–26）
place(whoosh(.6, 300, 5000), 21.4, .55, rev=.3)
place(pad([D2, A2, F3, A3, NOTE(76)], 8, 1.0, 2.5), 22, .5, rev=.6)
for i, n in enumerate((74, 72, 69, 77, 76, 74)):
    place(bell(NOTE(n), 3), 22.5 + i * .5 + (1 if i >= 3 else 0), .2, pan=(-.3, .3)[i % 2], rev=.7)
place(riser(1.2, 220, 880), 24.8, .35, rev=.4)
place(bell(NOTE(62), 4), 26, .3, rev=.8)
place(bell(NOTE(69), 4), 26.02, .2, rev=.8)

# ---------------- 殘響、母帶 ----------------
ir_t = t_axis(2.6)
ir = np.vstack([rng.standard_normal(len(ir_t)), rng.standard_normal(len(ir_t))]) * np.exp(-ir_t * 2.4)
ir[:, :int(.02 * SR)] = 0
size = 1 << int(np.ceil(np.log2(N + len(ir_t))))
wet = np.fft.irfft(np.fft.rfft(send, size) * np.fft.rfft(ir, size), size)[:, :N]
wet *= np.abs(mix).max() / max(np.abs(wet).max(), 1e-9) * .35
master = mix + wet
master = np.tanh(master / np.abs(master).max() * 1.6)
fade = np.minimum(1, (DUR - np.arange(N) / SR) / 1.2)
master *= np.clip(fade, 0, 1)
master *= .89 / np.abs(master).max()

OUT.parent.mkdir(exist_ok=True)
with wave.open(str(OUT), "wb") as w:
    w.setnchannels(2)
    w.setsampwidth(2)
    w.setframerate(SR)
    w.writeframes((master.T * 32767).astype(np.int16).tobytes())
print("wrote", OUT)
