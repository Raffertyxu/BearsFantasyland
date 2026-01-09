# 飛熊入夢 BEAR'S FANTASYLAND

> **Design System & Brand Guidelines**  
> <https://a1.haotaimaker.com/>

---

## 設計理念

參考 Hermès 愛馬仕的極簡奢華美學，結合木作工藝的溫潤質感。  
**核心原則**：清晰、快速、木作質感購物流程。

---

## 色彩系統 (Color Palette)

| Token | Hex | 用途 |
|-------|-----|------|
| `--bf-cream` | `#F5F1EB` | 主背景、留白 |
| `--bf-sand` | `#E6D9CC` | 次要背景、卡片 |
| `--bf-taupe` | `#C4A995` | 邊框、分隔線 |
| `--bf-brown` | `#8A6754` | 主要強調色、CTA、標題 |
| `--bf-text` | `#3D3D3D` | 正文文字 |
| `--bf-text-light` | `#7A7A7A` | 次要文字 |
| `--bf-white` | `#FFFFFF` | 純白 |
| `--bf-black` | `#1A1A1A` | 深色模式/對比 |

---

## 字體系統 (Typography)

```css
--bf-font-display: 'Noto Serif TC', 'Times New Roman', serif;  /* 標題、品牌名 */
--bf-font-body: 'Noto Sans TC', -apple-system, sans-serif;     /* 正文 */
--bf-font-english: 'Playfair Display', serif;                  /* 英文標語 */
```

| 層級 | 字型 | 字重 | 大小 | 用途 |
|------|------|------|------|------|
| H1 | Display | 700 | 48px | 頁面主標題 |
| H2 | Display | 600 | 32px | 區塊標題 |
| H3 | Body | 600 | 24px | 卡片標題 |
| Body | Body | 400 | 16px | 正文 |
| Small | Body | 400 | 14px | 註解、標籤 |

---

## 導覽選單結構 (Navigation)

### 第一層 (Primary Navigation)

| 項目 | 英文 | 類型 |
|------|------|------|
| 最新消息 | NEWS | 連結 |
| 家具 | FURNITURE | Mega Menu |
| 家居用品 | HOUSEHOLD ITEMS | Mega Menu |
| 飛熊造夢所 | Bear's Woodmaking Lab | Mega Menu |
| 關於我們 | ABOUT US | 連結 |
| 鑑賞 | GALLERY | 連結 |
| 空間租借 | SPACE RENTAL | 連結 |
| 訂制與運送 | CUSTOM & DELIVERY | 連結 |

### 第二層 (Dropdown Structure)

**家具 / FURNITURE**

- Ar 系列
- Be 系列  
- Ch 系列
- 經典系列

**家居用品 / HOUSEHOLD ITEMS**

- 椅子
- _(可擴充)_

**飛熊造夢所 / Bear's Woodmaking Lab**

- 飛熊入夢 YouTube 頻道
- 木工旅程 | 課程總覽
- 優質工具（購物車）
- 飛熊學堂
- 木師介紹
- 熊熊速報
- 常見問題
- 寫信給我們

---

## 設計風格指南 (Style Guide)

### 愛馬仕式極簡原則

1. **大量留白** - 讓木作產品自己說話
2. **單一聚焦** - 每個畫面只有一個視覺重點
3. **高質感攝影** - 產品圖是核心資產
4. **微動效** - 細膩的 hover/scroll 過渡
5. **無邊框設計** - 避免過多邊框分隔

### 按鈕樣式

```css
/* Primary CTA */
.bf-btn-primary {
    background: var(--bf-brown);
    color: var(--bf-white);
    padding: 14px 32px;
    font-family: var(--bf-font-body);
    font-weight: 600;
    letter-spacing: 1px;
    border: none;
    transition: all 0.3s ease;
}

/* Secondary / Outline */
.bf-btn-outline {
    background: transparent;
    color: var(--bf-brown);
    border: 1px solid var(--bf-brown);
    padding: 14px 32px;
}
```

### 卡片樣式

```css
.bf-card {
    background: var(--bf-white);
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border-radius: 0; /* 方正，木作感 */
    overflow: hidden;
    transition: transform 0.4s ease;
}

.bf-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.12);
}
```

---

## 購物流程設計 (E-Commerce UX)

> 目標：**3 步內完成結帳**

```
[1] 瀏覽產品 → [2] 加入購物車 → [3] 結帳
```

### 關鍵設計

- **Sticky Add-to-Cart** - 滾動時購買按鈕固定可見
- **Quick View** - 不離開列表即可預覽產品
- **Mini Cart Drawer** - 側邊滑出購物車，不跳頁
- **Guest Checkout** - 訪客可直接結帳

---

## RWD 斷點 (Breakpoints)

| 名稱 | 寬度 | 用途 |
|------|------|------|
| Mobile | < 768px | 單欄、漢堡選單 |
| Tablet | 768px - 1024px | 兩欄 |
| Desktop | > 1024px | 多欄、Mega Menu |

---

## 檔案結構 (建議)

```
BEARSFANTASYLAND/
├── components/           # UI 組件
│   ├── header.html
│   ├── mega-menu.html
│   ├── product-card.html
│   ├── cart-drawer.html
│   └── footer.html
├── css/
│   └── bf-tokens.css     # CSS Variables
├── js/
├── images/
└── BEARSFANTASYLAND.MD   # 設計規範 (本文件)
```

---

## 待開發組件 (To-Do)

- [ ] Header / Mega Menu
- [ ] Hero Banner (大圖輪播)
- [ ] Product Grid (產品列表)
- [ ] Product Detail (產品詳情)
- [ ] Cart Drawer (側邊購物車)
- [ ] Footer
- [ ] Quick View Modal
