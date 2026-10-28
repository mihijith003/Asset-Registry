// Dashboard charts (resources/views/dashboard.blade.php). Chart.js is bundled by Vite - no CDN, the app runs
// offline on the local network. The data comes from DashboardController as JSON in <script id="dashboard-data">.
// Every bar carries its value as text (valueLabels plugin below), so the charts read in greyscale print too.
import Chart from 'chart.js/auto';

const digits = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const compact = new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 });
let currency = '';   // from the controller (config app.currency_symbol), set before the charts are built

// Same format as the money() helper: symbol, non-breaking space, negatives in brackets. Used in tooltips only; the
// bars carry bare compact figures and the axis title carries the symbol, so the charts stay readable.
const money = (n) => `${currency}\u00A0${n < 0 ? `(${digits.format(-n)})` : digits.format(n)}`;

// Draws options.plugins.valueLabels.format(value, index, dataset) at the end of each bar.
const valueLabels = {
    id: 'valueLabels',
    afterDatasetsDraw(chart, args, options) {
        if (!options || !options.format) {
            return;
        }
        const { ctx } = chart;
        const horizontal = chart.options.indexAxis === 'y';
        ctx.save();
        ctx.font = '600 11px system-ui, sans-serif';
        ctx.fillStyle = '#1e293b';
        chart.data.datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex);
            if (meta.hidden) {
                return;
            }
            meta.data.forEach((bar, index) => {
                const text = options.format(dataset.data[index], index, dataset);
                if (text === null || text === '') {
                    return;
                }
                ctx.textAlign = horizontal ? 'left' : 'center';
                ctx.textBaseline = horizontal ? 'middle' : 'bottom';
                ctx.fillText(text, horizontal ? bar.x + 6 : bar.x, horizontal ? bar.y : bar.y - 4);
            });
        });
        ctx.restore();
    },
};

const base = (extra = {}) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    layout: { padding: { top: 22, right: 48 } },
    ...extra,
    plugins: { legend: { display: false }, ...(extra.plugins || {}) },
});

const builders = {
    subTypes: (data) => ({
        type: 'bar',
        data: { labels: data.labels, datasets: [{ label: 'Assets', data: data.counts, backgroundColor: '#4f46e5' }] },
        options: base({
            indexAxis: 'y',
            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { valueLabels: { format: (v) => String(v) } },
        }),
    }),

    ages: (data) => ({
        type: 'bar',
        data: { labels: data.labels, datasets: [{ label: 'Assets', data: data.counts, backgroundColor: '#4f46e5' }] },
        options: base({
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: {
                valueLabels: { format: (v, i) => (v ? `${v} (${data.shares[i].toFixed(1)}%)` : '0') },
                tooltip: { callbacks: { label: (c) => ` ${c.raw} assets, ${data.shares[c.dataIndex].toFixed(1)}% of all` } },
            },
        }),
    }),

    values: (data) => ({
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                { label: 'Total cost', data: data.cost, backgroundColor: '#312e81' },
                { label: 'Net book value', data: data.nbv, backgroundColor: '#a5b4fc' },
            ],
        },
        options: base({
            scales: { y: { beginAtZero: true, title: { display: true, text: `Amount (${currency})` }, ticks: { callback: (v) => compact.format(v) } } },
            plugins: {
                legend: { display: true, position: 'bottom' },
                valueLabels: { format: (v) => compact.format(v) },
                tooltip: {
                    callbacks: {
                        label: (c) => ` ${c.dataset.label}: ${money(c.raw)}`,
                        footer: (items) => {
                            const i = items[0].dataIndex;
                            return `Depreciation to date: ${money(data.cost[i] - data.nbv[i])}`;
                        },
                    },
                },
            },
        }),
    }),

    outcomes: (data) => ({
        type: 'bar',
        data: { labels: data.labels, datasets: [{ label: 'Assets', data: data.counts, backgroundColor: '#4f46e5' }] },
        options: base({
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: {
                valueLabels: { format: (v) => String(v) },
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const i = c.dataIndex;
                            return data.labels[i] === 'Sold'
                                ? ` ${c.raw} sold, proceeds ${money(data.values[i])} (net book value ${money(data.nbv[i])})`
                                : ` ${c.raw} ${data.labels[i].toLowerCase()}, net book value written off ${money(data.values[i])}`;
                        },
                    },
                },
            },
        }),
    }),
};

document.addEventListener('DOMContentLoaded', () => {
    const source = document.getElementById('dashboard-data');
    if (!source) {
        return;
    }
    const charts = JSON.parse(source.textContent);
    currency = charts.currency;
    Chart.register(valueLabels);

    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const key = canvas.dataset.chart;
        if (builders[key] && charts[key]) {
            new Chart(canvas, builders[key](charts[key]));
        }
    });
});
