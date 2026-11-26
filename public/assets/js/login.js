import { api, fetchCsrfToken } from './modules/api.js';
import { toast } from './modules/toast.js';
import { readPageData } from './modules/page-data.js';

// Dọn khoá "ghi nhớ tên đăng nhập" của form login cũ
localStorage.removeItem('rememberedUsername');

function notifyError(message) {
    toast.error(message);
}

async function showLineSelect(lines) {
    const modalEl = document.getElementById('lineSelectModal');
    const list = document.getElementById('lineList');

    if (!modalEl || !list) return;

    await fetchCsrfToken();

    list.textContent = '';
    lines.forEach(line => {
        const li = document.createElement('li');
        li.dataset.id = line.id;
        li.className = 'cursor-pointer border-b border-gray-200 px-4 py-3 transition-colors last:border-b-0 hover:bg-gray-50 hover:font-medium hover:text-[#5b73e8]';
        li.textContent = `${line.ma_line} - ${line.ten_line}`;
        list.appendChild(li);
    });

    list.querySelectorAll('li').forEach(li => {
        li.addEventListener('click', async () => {
            const lineId = li.dataset.id;
            try {
                const result = await api('POST', '/auth/select-line', { line_id: parseInt(lineId) });
                if (result.success) {
                    window.location.href = result.redirect_url || 'nhap-nang-suat.php';
                } else {
                    notifyError(result.message);
                }
            } catch (error) {
                notifyError('Lỗi chọn LINE');
            }
        });
    });

    modalEl.classList.remove('hidden');
    modalEl.classList.add('flex');
    modalEl.removeAttribute('aria-hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const lines = readPageData('allowedLines');
    if (Array.isArray(lines) && lines.length > 0) {
        showLineSelect(lines);
    }
});
