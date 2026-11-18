'use strict';

const activity = document.querySelector('[data-activity]');

if (activity && typeof Chart === 'function') {
    const rows = JSON.parse(activity.dataset.activity);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const panel = activity.parentElement;
    panel.hidden = false;

    new Chart(activity, {
        type: 'bar',
        data: {
            labels: rows.map(row => row.label),
            datasets: [
                {label: 'Demandes de rendez-vous', data: rows.map(row => row.appointments), backgroundColor: '#896dbb', borderRadius: 4},
                {label: 'Demandes de devis', data: rows.map(row => row.quotes), backgroundColor: '#bf487a', borderRadius: 4},
            ],
        },
        options: {
            maintainAspectRatio: false,
            animation: reducedMotion ? false : {duration: 250},
            scales: {
                x: {grid: {display: false}, ticks: {maxTicksLimit: 8}},
                y: {beginAtZero: true, ticks: {precision: 0}},
            },
            plugins: {
                legend: {position: 'bottom', labels: {usePointStyle: true, boxWidth: 9}},
            },
        },
    });

    document.querySelector('.dashboard-data').open = false;
}
