/* =============================================================================
   Ledgerly - dashboard charts
   Reads the data PHP printed into #dashboardData and draws two Chart.js charts.
   The period buttons refilter the same data set in the browser: no page reload.
   ========================================================================== */

(function () {
  'use strict';

  var holder = document.getElementById('dashboardData');
  if (!holder || typeof Chart === 'undefined') { return; }

  var entries = JSON.parse(holder.textContent || '[]');

  var palette = ['#2f3f8f', '#10725a', '#a8422c', '#b4761a', '#5a6bbd',
                 '#3f8f7a', '#7b4b8f', '#2c6ea8', '#8f6b2f', '#6c7689'];

  Chart.defaults.font.family = '"IBM Plex Sans", system-ui, sans-serif';
  Chart.defaults.color = '#56637a';

  var donutCtx = document.getElementById('categoryChart');
  var barCtx = document.getElementById('monthlyChart');
  var donut = null;
  var bars = null;

  // When the book is empty PHP renders an invitation instead of the charts.
  if (!donutCtx || !barCtx) { return; }

  function money(n) {
    return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  /* Keep only the entries inside the chosen number of days. */
  function withinPeriod(days) {
    if (days === 0) { return entries; }
    var limit = new Date();
    limit.setHours(0, 0, 0, 0);
    limit.setDate(limit.getDate() - days);
    return entries.filter(function (row) { return new Date(row.txn_date) >= limit; });
  }

  function byCategory(rows) {
    var totals = {};
    rows.forEach(function (row) {
      if (row.type !== 'expense') { return; }
      totals[row.category] = (totals[row.category] || 0) + parseFloat(row.amount);
    });
    return Object.keys(totals)
      .map(function (key) { return { label: key, value: totals[key] }; })
      .sort(function (a, b) { return b.value - a.value; });
  }

  function byMonth(rows) {
    var months = {};
    rows.forEach(function (row) {
      var key = row.txn_date.slice(0, 7);
      if (!months[key]) { months[key] = { income: 0, expense: 0 }; }
      months[key][row.type] += parseFloat(row.amount);
    });
    return Object.keys(months).sort().slice(-6).map(function (key) {
      var d = new Date(key + '-01');
      return {
        label: d.toLocaleDateString(undefined, { month: 'short', year: '2-digit' }),
        income: months[key].income,
        expense: months[key].expense
      };
    });
  }

  function drawDonut(data) {
    var labels = data.map(function (d) { return d.label; });
    var values = data.map(function (d) { return d.value; });
    var colors = data.map(function (_, i) { return palette[i % palette.length]; });

    if (donut) { donut.destroy(); }
    donut = new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{ data: values, backgroundColor: colors, borderColor: '#fff', borderWidth: 2 }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                var total = values.reduce(function (a, b) { return a + b; }, 0);
                var share = total ? Math.round((ctx.parsed / total) * 100) : 0;
                return ctx.label + ': ' + money(ctx.parsed) + ' (' + share + '%)';
              }
            }
          }
        }
      }
    });

    var legend = document.getElementById('categoryLegend');
    var total = values.reduce(function (a, b) { return a + b; }, 0);
    legend.innerHTML = '';
    if (!data.length) {
      legend.innerHTML = '<li>No spending recorded in this period.</li>';
      return;
    }
    data.forEach(function (row, i) {
      var share = total ? Math.round((row.value / total) * 100) : 0;
      var li = document.createElement('li');
      li.innerHTML =
        '<span class="legend-key"><span class="legend-swatch" style="background:' +
        palette[i % palette.length] + '"></span>' + row.label + '</span>' +
        '<span class="legend-value">' + money(row.value) + ' &middot; ' + share + '%</span>';
      legend.appendChild(li);
    });
  }

  function drawBars(data) {
    if (bars) { bars.destroy(); }
    bars = new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: data.map(function (d) { return d.label; }),
        datasets: [
          { label: 'Income', data: data.map(function (d) { return d.income; }), backgroundColor: '#10725a' },
          { label: 'Spent', data: data.map(function (d) { return d.expense; }), backgroundColor: '#a8422c' }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
          tooltip: { callbacks: { label: function (ctx) { return ctx.dataset.label + ': ' + money(ctx.parsed.y); } } }
        },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, grid: { color: '#e4e9f1' }, ticks: { callback: function (v) { return money(v); } } }
        }
      }
    });
  }

  /* Recalculate the four summary figures for the chosen period. */
  function updateTotals(rows) {
    var income = 0, spent = 0;
    rows.forEach(function (row) {
      if (row.type === 'income') { income += parseFloat(row.amount); }
      else { spent += parseFloat(row.amount); }
    });

    document.querySelector('[data-total-income]').textContent = money(income);
    document.querySelector('[data-total-spent]').textContent = money(spent);
    document.querySelector('[data-total-remaining]').textContent = money(income - spent);
    document.querySelector('[data-total-entries]').textContent = rows.length;

    var bar = document.getElementById('spendRule');
    var note = document.getElementById('spendNote');
    var used = income > 0 ? Math.min((spent / income) * 100, 100) : (spent > 0 ? 100 : 0);
    bar.querySelector('span').style.width = used + '%';
    bar.classList.toggle('over', income > 0 && spent > income);

    if (income === 0 && spent === 0) {
      note.textContent = 'Nothing recorded in this period.';
    } else if (income === 0) {
      note.textContent = 'No income recorded, so every entry here is money going out.';
    } else if (spent > income) {
      note.textContent = 'You spent ' + money(spent - income) + ' more than you took in.';
    } else {
      note.textContent = Math.round((spent / income) * 100) + '% of your income is spent.';
    }
  }

  function render(days) {
    var rows = withinPeriod(days);
    updateTotals(rows);
    drawDonut(byCategory(rows));
    drawBars(byMonth(rows));
  }

  document.querySelectorAll('[data-period]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.querySelectorAll('[data-period]').forEach(function (b) {
        b.classList.remove('btn-accent');
        b.classList.add('btn-outline-ink');
      });
      button.classList.remove('btn-outline-ink');
      button.classList.add('btn-accent');
      render(parseInt(button.dataset.period, 10));
    });
  });

  render(0);
})();
