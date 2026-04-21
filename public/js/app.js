/* UnlockTool.us - Main JavaScript v7.0 (Performance Optimized) */
/* Key optimizations:
   - Combined countdown intervals into one (reduce TBT)
   - Virtual accounts use DocumentFragment (batch DOM)
   - Deferred virtual accounts via requestIdleCallback
*/

// ===== Mobile Menu Toggle =====
document.getElementById('mobileMenuToggle')?.addEventListener('click', function () {
    const mobileMenu = document.getElementById('mobileMenu');
    mobileMenu.classList.toggle('show');
    this.innerHTML = mobileMenu.classList.contains('show') ? '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
});
document.addEventListener('click', function (e) {
    const mobileMenu = document.getElementById('mobileMenu');
    const toggle = document.getElementById('mobileMenuToggle');
    if (mobileMenu && toggle && !mobileMenu.contains(e.target) && !toggle.contains(e.target)) {
        mobileMenu.classList.remove('show');
        toggle.innerHTML = '<i class="fas fa-bars"></i>';
    }
});

// ===== Contact Dropdown =====
document.getElementById('contactDropdownBtn')?.addEventListener('click', function (e) {
    e.stopPropagation();
    document.getElementById('contactDropdown')?.classList.toggle('show');
});
document.addEventListener('click', function (e) {
    const dd = document.getElementById('contactDropdown');
    const btn = document.getElementById('contactDropdownBtn');
    if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) dd.classList.remove('show');
});

// ===== Combined Countdown Timer (single interval for both) =====
function updateAllTimers() {
    const now = Date.now();
    // Countdown timers
    document.querySelectorAll('[data-expire]').forEach(function(el) {
        var t = parseInt(el.dataset.expire) * 1000;
        var d = t - now;
        if (d <= 0) { el.innerText = 'Đã hết hạn'; return; }
        var h = Math.floor(d / 3600000);
        var m = Math.floor((d % 3600000) / 60000);
        var s = Math.floor((d % 60000) / 1000);
        el.innerText = h + 'h ' + m + 'm ' + s + 's';
    });
    // Waiting timers
    document.querySelectorAll('[data-waiting]').forEach(function(el) {
        var waited = now - parseInt(el.dataset.waiting) * 1000;
        if (waited > 0) {
            var h = Math.floor(waited / 3600000);
            var m = Math.floor((waited % 3600000) / 60000);
            var s = Math.floor((waited % 60000) / 1000);
            el.innerText = h > 0 ? '⏳ ' + h + 'h ' + m + 'm ' + s + 's' : m > 0 ? '⏳ ' + m + 'm ' + s + 's' : '⏳ ' + s + 's';
        }
    });
}
setInterval(updateAllTimers, 1000);

// ===== Virtual Accounts (60 fake renting accounts) — Optimized with DocumentFragment =====
function createVirtualRentingAccounts() {
    var tbody = document.querySelector('table tbody');
    if (!tbody) return;
    var STORAGE_KEY = 'virtual_accounts_timers';
    var savedTimers = {};
    try { var saved = localStorage.getItem(STORAGE_KEY); if (saved) savedTimers = JSON.parse(saved); } catch (e) { }
    var newTimers = {};
    var virtualAccounts = [];
    var nowSec = Math.floor(Date.now() / 1000);

    for (var i = 0; i < 60; i++) {
        var virtualId = 200 + i;
        var timerKey = 'virtual_' + virtualId;
        var expireTimestamp;
        if (savedTimers[timerKey] && savedTimers[timerKey] > nowSec) {
            expireTimestamp = savedTimers[timerKey];
        } else {
            var seed = virtualId * 12345;
            var randomHours = (seed % 649) + 1;
            var randomMinutes = ((seed * 7) % 60);
            var randomSeconds = ((seed * 13) % 60);
            expireTimestamp = nowSec + (randomHours * 3600) + (randomMinutes * 60) + randomSeconds;
        }
        newTimers[timerKey] = expireTimestamp;
        var remaining = expireTimestamp - nowSec;
        var dH = Math.floor(remaining / 3600);
        var dM = Math.floor((remaining % 3600) / 60);
        var dS = remaining % 60;
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + virtualId + '</td><td>Unlocktool</td><td><button class="btn btn-secondary btn-sm" disabled>Đang thuê</button></td><td><span class="badge badge-danger">Đang thuê</span></td><td>M*********</td><td>U*******</td><td><span data-expire="' + expireTimestamp + '">' + dH + 'h ' + dM + 'm ' + dS + 's</span></td>';
        virtualAccounts.push({ element: tr, expireTimestamp: expireTimestamp });
    }

    // Sort existing rows
    var allRows = Array.from(tbody.querySelectorAll('tr'));
    var waitingAccounts = [], expiredAccounts = [], rentingAccounts = [];
    allRows.forEach(function(tr) {
        var badge = tr.querySelector('.badge');
        var isWaiting = badge && badge.textContent.trim() === 'Chờ thuê';
        var waitingSpan = tr.querySelector('[data-waiting]');
        var expireSpan = tr.querySelector('[data-expire]');
        if (isWaiting) { waitingAccounts.push({ element: tr, expireTimestamp: 0 }); }
        else if (waitingSpan) { expiredAccounts.push({ element: tr, expireTimestamp: parseInt(waitingSpan.getAttribute('data-waiting')) }); }
        else if (expireSpan) { rentingAccounts.push({ element: tr, expireTimestamp: parseInt(expireSpan.getAttribute('data-expire')) }); }
        else { rentingAccounts.push({ element: tr, expireTimestamp: Number.MAX_SAFE_INTEGER }); }
    });

    rentingAccounts.push.apply(rentingAccounts, virtualAccounts);
    expiredAccounts.sort(function(a, b) { return a.expireTimestamp - b.expireTimestamp; });
    rentingAccounts.sort(function(a, b) { return a.expireTimestamp - b.expireTimestamp; });

    var allAccounts = waitingAccounts.concat(expiredAccounts, rentingAccounts);

    // Use DocumentFragment for batch DOM insertion
    var fragment = document.createDocumentFragment();
    for (var j = 0; j < allAccounts.length; j++) {
        fragment.appendChild(allAccounts[j].element);
    }
    tbody.innerHTML = '';
    tbody.appendChild(fragment);

    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(newTimers)); } catch (e) { }
}

// ===== jQuery-dependent functionality =====
$(document).ready(function () {
    // Defer virtual accounts to idle time (reduce TBT)
    if (typeof requestIdleCallback === 'function') {
        requestIdleCallback(function() {
            createVirtualRentingAccounts();
            updateAllTimers();
        });
    } else {
        setTimeout(function() {
            createVirtualRentingAccounts();
            updateAllTimers();
        }, 50);
    }

    // Package selection (radio button cards)
    $('.rent-package-option').click(function () {
        $('.rent-package-option').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
        $('#selected_price_id').val($(this).data('price-id'));
    });
    // Auto-select first package
    var firstPkg = $('.rent-package-option').first();
    if (firstPkg.length) {
        firstPkg.addClass('selected');
        firstPkg.find('input[type="radio"]').prop('checked', true);
        $('#selected_price_id').val(firstPkg.data('price-id'));
    }
    $('#rentButton').click(function () {
        if ($('#selected_price_id').val() === '') { alert('Vui lòng chọn một gói thuê!'); return false; }
    });

    // Search handlers
    function doSearch(inputId) {
        var raw = $(inputId).val().trim();
        if (!raw) { alert('Vui lòng nhập mã đơn hàng.'); return; }
        var match = raw.match(/(?:DH|RENT)?\d+/gi);
        if (!match) { alert('Không tìm thấy mã đơn hàng.'); return; }
        window.location.href = '/order-status?orderCode=' + encodeURIComponent(match[match.length - 1]);
    }
    $('#headerCheckBtn').click(function () { doSearch('#headerTransferContent'); });
    $('#headerTransferContent').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doSearch('#headerTransferContent'); } });
    $('#mobileCheckBtn').click(function () { doSearch('#mobileTransferContent'); });
    $('#mobileTransferContent').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doSearch('#mobileTransferContent'); } });

    // Modal account ID + type display
    $('#rentModal').on('show.bs.modal', function (e) {
        var btn = $(e.relatedTarget);
        $('#account_id').val(btn.data('account-id'));
        var accountType = btn.closest('tr').find('td:eq(1)').text().trim();
        if (accountType) $('#rentModalAccountType').text(accountType.toUpperCase());
    });

    // Copy button
    $(document).on('click', '.copy-btn', function () {
        var text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(function() { alert('Đã sao chép: ' + text); });
    });

    // Floating Contact — Premium FAB
    $('#fabContactBtn').on('click', function (e) {
        e.stopPropagation();
        $('#fabContactWrapper').toggleClass('open');
    });
    $(document).on('click', function (e) {
        var w = $('#fabContactWrapper');
        if (w.length && !w[0].contains(e.target)) {
            w.removeClass('open');
        }
    });

    // Hero search → thuetaikhoan.net
    function doHeroSearch() {
        var q = document.getElementById('heroSearchInput');
        if (q && q.value.trim()) window.open('https://thuetaikhoan.net/ord-services?q=' + encodeURIComponent(q.value.trim()), '_blank');
    }
    $(document).on('click', '#heroSearchBtn', doHeroSearch);
    $(document).on('keypress', '#heroSearchInput', function (e) { if (e.which === 13) doHeroSearch(); });
});
