'use strict';

const canvas = document.querySelector('[data-finance-activity]');
if (canvas && typeof Chart === 'function') {
    const rows = JSON.parse(canvas.dataset.financeActivity);
    const metrics = [
        ['invoiced_ht', 'Facturé HT', '#896dbb'],
        ['net_received', 'Encaissement net', '#308099'],
        ['expenses', 'Dépenses TTC', '#bf487a'],
    ];
    const euros = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'});
    canvas.parentElement.hidden = false;
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: rows.map(row => row.label),
            datasets: metrics.map(([key, label, color]) => ({
                label, data: rows.map(row => Number(row[key])), borderColor: color, backgroundColor: color,
            })),
        },
        options: {
            maintainAspectRatio: false,
            animation: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            scales: {
                x: {ticks: {maxTicksLimit: 8}, grid: {display: false}},
                y: {ticks: {callback: value => euros.format(value)}},
            },
            plugins: {legend: {position: 'bottom'}},
        },
    });
    document.querySelector('.dashboard-data').open = false;
}
