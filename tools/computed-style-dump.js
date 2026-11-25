// So computed style toàn DOM giữa hai bản HTML tĩnh: BASE/before/<trang>.html và BASE/after/<trang>.html.
// Chạy: phục vụ thư mục chứa before/ và after/ (vd. php -S 127.0.0.1:8765), sửa BASE/PAGES/WIDTHS bên dưới,
// dán cả file vào Playwright MCP browser_run_code; kết quả là số khác biệt theo trang × độ rộng.
async (page) => {
  const BASE = 'http://127.0.0.1:8765/';
  const PAGES = ['nhap-nang-suat', 'admin', 'bieu-do', 'huong-dan', 'no-line'];
  const WIDTHS = [1366, 390];
  const dump = () => {
    const skip = new Set(['SCRIPT', 'STYLE', 'LINK', 'META', 'TITLE', 'HEAD', 'BASE']);
    document.querySelectorAll('.hidden').forEach(e => e.classList.remove('hidden'));
    document.querySelectorAll('[style*="display: none"],[style*="display:none"]').forEach(e => e.style.display = '');
    const out = [];
    for (const el of document.querySelectorAll('*')) {
      if (skip.has(el.tagName) || el.closest('svg') && el.tagName !== 'svg') continue;
      const row = {};
      for (const pseudo of [null, '::before', '::after', '::placeholder']) {
        const cs = getComputedStyle(el, pseudo);
        if (pseudo && pseudo !== '::placeholder' && cs.content === 'none') continue;
        if (pseudo === '::placeholder' && !el.matches('input,textarea')) continue;
        const o = {};
        for (const p of cs) { if (p.startsWith('--') || /^(border-(top-right|bottom-left|bottom-right)-radius|border-(start|end)-(start|end)-radius|border-(right|bottom|left)-color|transition-.*|background-position.*|clip|clip-path)$/.test(p) || /^(caret-color|column-rule-color|row-rule-color|text-decoration-color|text-emphasis-color|-webkit-text-(fill|stroke)-color|perspective-origin|transform-origin|.*-(inline|block)-.*|(inline|block)-size|(min|max)-(inline|block)-size|border-(inline|block)-.*|(margin|padding|inset)-(inline|block).*)$/.test(p)) continue; o[p] = cs.getPropertyValue(p).replace(/^(rgba\(0, 0, 0, 0\) 0px 0px 0px 0px, )+/, '').replace('2.706e+07px', '9999px'); } if (o['outline-style'] === 'none') { delete o['outline-color']; delete o['outline-width']; delete o['outline-offset']; } for (const sd of ['top','right','bottom','left']) { if (o['border-' + sd + '-width'] === '0px') delete o['border-' + sd + '-color']; }
        row[pseudo || 'el'] = o;
      }
      out.push({ t: el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + '.' + String(el.className.baseVal ?? el.className).trim().split(/\s+/).join('.'), s: row });
    }
    return out;
  };
  const result = {};
  for (const w of WIDTHS) {
    await page.setViewportSize({ width: w, height: 800 });
    for (const n of PAGES) {
      const d = {};
      for (const v of ['before', 'after']) {
        await page.goto(BASE + v + '/' + n + '.html', { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);
        d[v] = await page.evaluate(dump);
      }
      const diffs = {};
      let cnt = 0;
      if (d.before.length !== d.after.length) { result[n + '-' + w] = 'DOM size differs ' + d.before.length + ' vs ' + d.after.length; continue; }
      d.before.forEach((b, i) => {
        const a = d.after[i];
        for (const ps of Object.keys({ ...b.s, ...a.s })) {
          const bo = b.s[ps] || {}, ao = a.s[ps] || {};
          for (const p of Object.keys({ ...bo, ...ao })) {
            if (bo[p] !== ao[p]) {
              cnt++;
              const key = p + (ps === 'el' ? '' : ps) + ' | ' + bo[p] + ' => ' + ao[p];
              (diffs[key] = diffs[key] || []).push(b.t.slice(0, 90));
            }
          }
        }
      });
      const summary = Object.entries(diffs).map(([k, v]) => ({ k, n: v.length, ex: [...new Set(v)].slice(0, 3) })).sort((x, y) => y.n - x.n);
      result[n + '-' + w] = { elements: d.before.length, diffs: cnt, kinds: summary.length, top: summary.filter(x => !/^(outline-|scale|transform|background-color \| rgba\(\d+, \d+, \d+, 0\.\d+\) => oklab|height|width|.* @ div#bnsBarSlot)/.test(x.k + ' @ ' + x.ex[0])).slice(0, 14).map(x => x.n + ' × ' + x.k + '  @ ' + x.ex.join(' ; ').slice(0, 110)) };
    }
  }
  return result;
}
