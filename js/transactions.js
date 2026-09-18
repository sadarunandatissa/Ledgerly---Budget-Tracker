/* =============================================================================
   Ledgerly - transaction history
   Instant search filters the rows already in the table (no request at all).
   The filter bar asks api/transactions.php for a fresh set with fetch(),
   so the table redraws without reloading the page.
   ========================================================================== */

(function () {
  'use strict';

  var table = document.getElementById('txnTable');
  if (!table) { return; }

  var body = document.getElementById('txnBody');
  var search = document.getElementById('searchBox');
  var countLabel = document.getElementById('resultCount');
  var sumLabel = document.getElementById('resultSum');
  var emptyRow = document.getElementById('emptyRow');
  var form = document.getElementById('filterForm');
  var resetBtn = document.getElementById('resetFilters');
  var sortKey = null;
  var sortAsc = true;

  function money(n) {
    return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function rows() {
    return Array.prototype.slice.call(body.querySelectorAll('tr[data-row]'));
  }

  /* Instant search: hide rows whose text does not contain the term. */
  function applySearch() {
    var term = (search.value || '').trim().toLowerCase();
    var shown = 0;
    var total = 0;

    rows().forEach(function (row) {
      var haystack = row.dataset.search || row.textContent.toLowerCase();
      var match = term === '' || haystack.indexOf(term) !== -1;
      row.hidden = !match;
      if (match) {
        shown++;
        var amount = parseFloat(row.dataset.amount);
        total += row.dataset.type === 'income' ? amount : -amount;
      }
    });

    countLabel.textContent = shown + (shown === 1 ? ' entry' : ' entries');
    sumLabel.textContent = (total < 0 ? '-' : '') + money(Math.abs(total));
    sumLabel.className = 'legend-value ' + (total < 0 ? 'amount-out' : 'amount-in');
    emptyRow.hidden = shown !== 0;
  }

  var typingTimer;
  search.addEventListener('input', function () {
    window.clearTimeout(typingTimer);
    typingTimer = window.setTimeout(applySearch, 120);
  });
  search.addEventListener('search', applySearch);

  /* Column sorting on click. */
  table.querySelectorAll('th[data-sort]').forEach(function (th) {
    th.style.cursor = 'pointer';
    th.title = 'Sort by ' + th.textContent.trim().toLowerCase();
    th.addEventListener('click', function () {
      var key = th.dataset.sort;
      sortAsc = sortKey === key ? !sortAsc : true;
      sortKey = key;

      rows().sort(function (a, b) {
        var x = a.dataset[key], y = b.dataset[key];
        if (key === 'amount') { x = parseFloat(x); y = parseFloat(y); }
        if (x < y) { return sortAsc ? -1 : 1; }
        if (x > y) { return sortAsc ? 1 : -1; }
        return 0;
      }).forEach(function (row) { body.appendChild(row); });

      body.appendChild(emptyRow);
    });
  });

  /* Build one table row from an API record. */
  function buildRow(item) {
    var tr = document.createElement('tr');
    var isIncome = item.type === 'income';
    tr.setAttribute('data-row', '');
    tr.dataset.amount = item.amount;
    tr.dataset.type = item.type;
    tr.dataset.category = item.category;
    tr.dataset.date = item.txn_date;
    tr.dataset.search = (item.category + ' ' + item.description + ' ' + item.txn_date + ' ' + item.type).toLowerCase();

    tr.innerHTML =
      '<td class="num">' + item.txn_date + '</td>' +
      '<td><span class="tag ' + (isIncome ? 'tag-in' : 'tag-out') + '">' +
        (isIncome ? 'Income' : 'Expense') + '</span></td>' +
      '<td>' + item.category + '</td>' +
      '<td>' + (item.description || '<span class="text-muted">No note</span>') + '</td>' +
      '<td class="amount text-end ' + (isIncome ? 'amount-in' : 'amount-out') + '">' +
        (isIncome ? '+' : '-') + money(item.amount) + '</td>' +
      '<td class="text-end text-nowrap">' +
        '<a class="btn btn-sm btn-outline-ink" href="add-transaction.php?id=' + item.id + '">Edit</a> ' +
        '<a class="btn btn-sm btn-outline-ink" href="delete-transaction.php?id=' + item.id + '" ' +
        'onclick="return confirm(\'Delete this entry? This cannot be undone.\');">Delete</a>' +
      '</td>';
    return tr;
  }

  /* Ask the server for a filtered set and redraw the table. */
  function fetchFiltered(event) {
    if (event) { event.preventDefault(); }
    var params = new URLSearchParams(new FormData(form)).toString();
    body.setAttribute('aria-busy', 'true');
    countLabel.textContent = 'Loading';

    fetch('api/transactions.php?' + params, { headers: { 'X-Requested-With': 'fetch' } })
      .then(function (response) {
        if (!response.ok) { throw new Error('Request failed'); }
        return response.json();
      })
      .then(function (data) {
        rows().forEach(function (row) { row.remove(); });
        data.transactions.forEach(function (item) {
          body.insertBefore(buildRow(item), emptyRow);
        });
        applySearch();
      })
      .catch(function () {
        countLabel.textContent = 'Could not load entries. Check your connection and filter again.';
      })
      .finally(function () {
        body.removeAttribute('aria-busy');
      });
  }

  form.addEventListener('submit', fetchFiltered);
  form.querySelectorAll('select').forEach(function (select) {
    select.addEventListener('change', fetchFiltered);
  });

  resetBtn.addEventListener('click', function () {
    form.reset();
    search.value = '';
    fetchFiltered();
  });

  applySearch();
})();
