// Asset label sheet (resources/views/assets/labels.blade.php): lays the label slots out into A4 pages using the grid
// size from the page's CSS, draws every QR code with the bundled 'qrcode' package (no CDN: the app runs offline on
// the local network), and enables Print only once all of them have rendered.
import QRCode from 'qrcode';

document.addEventListener('DOMContentLoaded', async () => {
    const sheet = document.getElementById('sheet');
    const slots = document.getElementById('slots');
    const button = document.querySelector('[data-print]');
    if (!sheet || !slots || !button) {
        return;
    }

    // 1. Pages of --label-cols x --label-rows (the CSS :root block is the one place the grid is defined).
    const css = getComputedStyle(document.documentElement);
    const perPage = parseInt(css.getPropertyValue('--label-cols'), 10) * parseInt(css.getPropertyValue('--label-rows'), 10);
    const labels = Array.from(slots.children);

    for (let i = 0; i < labels.length; i += perPage) {
        const page = document.createElement('div');
        page.className = 'page';
        const grid = document.createElement('div');
        grid.className = 'grid';
        labels.slice(i, i + perPage).forEach((label) => grid.appendChild(label));
        page.appendChild(grid);
        sheet.appendChild(page);
    }
    slots.remove();

    const pages = sheet.children.length;
    document.querySelector('[data-page-count]').textContent = ` on ${pages} ${pages === 1 ? 'page' : 'pages'}`;

    if (perPage !== parseInt(sheet.dataset.perPage, 10)) {
        const warning = document.querySelector('[data-grid-warning]');
        warning.textContent = `The CSS grid holds ${perPage} labels per page but AssetController::LABELS_PER_PAGE is ${sheet.dataset.perPage}; update one to match.`;
        warning.hidden = false;
    }

    // 2. Every QR code, as inline SVG (sharp at any print resolution). Print waits for all of them.
    const targets = Array.from(sheet.querySelectorAll('[data-qr]'));
    try {
        await Promise.all(targets.map(async (target) => {
            target.innerHTML = await QRCode.toString(target.dataset.qr, { type: 'svg', margin: 0, errorCorrectionLevel: 'M' });
        }));
    } catch (error) {
        button.textContent = 'QR codes failed to render';
        console.error(error);
        return;
    }

    document.body.classList.add('qr-ready');
    button.disabled = false;
    button.textContent = 'Print';
    button.addEventListener('click', () => window.print());
});
