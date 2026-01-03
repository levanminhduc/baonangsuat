# Tailwind CSS Offline Strategy for Bao Nang Suat

## Overview
Muc tieu la loai bo CDN Tailwind, chuyen sang build CSS offline cho du an PHP thuan khong co build tools. Yeu cau khong dung Node.js, build thu cong tren may dev, tach 2 build CSS rieng cho giao dien chinh va trang no-line.

## Requirements and Assumptions
- Khong dung Node.js
- Build thu cong tren may dev
- 2 file CSS output: main va no-line
- Mau primary mac dinh: #143583, primary-dark: #0f2a66, success: #4CAF50, warning: #ff9800, danger: #f44336
- Trang no-line dung primary #2196F3

## Options
### Option A: Tailwind Standalone CLI (Khong can Node.js)
- Uu diem: khong can Node, de mang theo, phu hop moi truong PHP thuan
- Nhuoc diem: can tai binary ve may dev, cong viec build thu cong

### Option B: npm + Tailwind CLI (Can Node.js)
- Uu diem: tieu chuan cong dong, de tich hop script
- Nhuoc diem: can Node.js, khong phu hop rang buoc

### Option C: Pre-built CSS download
- Uu diem: nhanh, khong can build
- Nhuoc diem: kho custom theme, file lon, khong purge dung class

## Recommended Approach
Chon Option A. Su dung Tailwind Standalone CLI, tao 2 file CSS output va 2 input file rieng. Ly do: phu hop rang buoc khong dung Node, van giu duoc theme custom va kich thuoc CSS nho nho nho.

## Architecture
- Tai binary tailwindcss ve thu muc tools hoac su dung ban portable tai root
- Tao thu muc src va dist trong assets
- Tao 1 config chinh cho theme mac dinh
- Tao 1 config rieng cho no-line de doi primary

## Folder Structure
- assets/
  - tailwind/
    - src/
      - main.css
      - no-line.css
    - dist/
      - main.css
      - no-line.css
- tailwind.config.js
- tailwind.no-line.config.js
- tools/
  - tailwindcss.exe

## Data Flow
1. Dev cap nhat class Tailwind trong file PHP
2. Chay lenh build Tailwind CLI
3. Tailwind scan cac file PHP de lay class
4. Tao CSS output vao assets/tailwind/dist
5. PHP load CSS offline tu dist

## API Contracts
Khong phat sinh API moi. Khong thay doi route hoac endpoint.

## Data Model
Khong thay doi data model.

## Security
- Giam rui ro phu thuoc CDN ben ngoai
- Khong can mo them quyen he thong

## Observability
Khong co log moi. Kiem tra build bang kich thuoc file va load CSS tren trinh duyet.

## Rollout and Migration
1. Tao file config va thu muc
2. Build CSS
3. Cap nhat PHP thay the CDN bang link CSS offline
4. Xac nhan UI khong bi break

## Testing
- Mo nhap-nang-suat.php, admin.php, no-line.php va so sanh UI
- Kiem tra class Tailwind duoc ap dung
- Kiem tra dung mau primary cho no-line

## Risks and Open Questions
- Dynamic class khong duoc scan se bi mat CSS
- Thieu file PHP trong danh sach content
- Cong viec build thu cong co the bi quen

## Tailwind Config (main)
```javascript
module.exports = {
  content: [
    "./*.php",
    "./includes/**/*.php",
    "./assets/js/**/*.js",
    "./api/**/*.php",
    "./classes/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        primary: "#143583",
        "primary-dark": "#0f2a66",
        success: "#4CAF50",
        warning: "#ff9800",
        danger: "#f44336"
      }
    }
  }
}
```

## Tailwind Config (no-line)
```javascript
const base = require("./tailwind.config.js")

module.exports = {
  ...base,
  theme: {
    ...base.theme,
    extend: {
      ...base.theme.extend,
      colors: {
        ...base.theme.extend.colors,
        primary: "#2196F3"
      }
    }
  }
}
```

## Input CSS
- assets/tailwind/src/main.css
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

- assets/tailwind/src/no-line.css
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

## Build Commands
- Build main
```
tools/tailwindcss.exe -c tailwind.config.js -i assets/tailwind/src/main.css -o assets/tailwind/dist/main.css --minify
```
- Build no-line
```
tools/tailwindcss.exe -c tailwind.no-line.config.js -i assets/tailwind/src/no-line.css -o assets/tailwind/dist/no-line.css --minify
```

## Update PHP Files
- nhap-nang-suat.php va admin.php dung main.css
- no-line.php dung no-line.css
- Xoa CDN script va them link

## Implementation Plan
1. Tao thu muc assets/tailwind/src va assets/tailwind/dist, them file input CSS (15 phut) - Code mode
2. Tai tailwindcss.exe vao tools (10 phut) - Code mode
3. Tao tailwind.config.js va tailwind.no-line.config.js (15 phut) - Code mode
4. Chay build CLI tao main.css va no-line.css (10 phut) - Code mode
5. Cap nhat PHP files de load CSS offline (10 phut) - Code mode
6. Kiem tra UI 3 trang (20 phut) - Code mode
