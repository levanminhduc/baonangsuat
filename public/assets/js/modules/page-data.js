export function readPageData(id = 'bnsPageData') {
    const el = document.getElementById(id);
    if (!el) return {};
    try {
        return JSON.parse(el.textContent) ?? {};
    } catch (error) {
        console.error('Invalid page data:', id, error);
        return {};
    }
}
