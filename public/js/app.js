/* UnlockTool.us - Main JavaScript */
/* Ported from original index.php inline scripts */

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

// ===== Countdown Timers =====
function updateCountdowns() {
    document.querySelectorAll('[data-expire]').forEach(el => {
        const t = parseInt(el.dataset.expire) * 1000;
        const d = t - Date.now();
        if (d <= 0) { el.innerText = 'Đã hết hạn'; return; }
        const h = Math.floor(d / 3600000);
        const m = Math.floor((d % 3600000) / 60000);
        const s = Math.floor((d % 60000) / 1000);
        el.innerText = `${h}h ${m}m ${s}s`;
    });
}
function updateWaitingTime() {
    document.querySelectorAll('[data-waiting]').forEach(el => {
        const waited = Date.now() - parseInt(el.dataset.waiting) * 1000;
        if (waited > 0) {
            const h = Math.floor(waited / 3600000);
            const m = Math.floor((waited % 3600000) / 60000);
            const s = Math.floor((waited % 60000) / 1000);
            el.innerText = h > 0 ? `⏳ ${h}h ${m}m ${s}s` : m > 0 ? `⏳ ${m}m ${s}s` : `⏳ ${s}s`;
        }
    });
}
setInterval(updateCountdowns, 1000);
setInterval(updateWaitingTime, 1000);
window.addEventListener('load', function () { updateCountdowns(); updateWaitingTime(); });

// ===== Virtual Accounts (60 fake renting accounts) =====
function createVirtualRentingAccounts() {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;
    const STORAGE_KEY = 'virtual_accounts_timers';
    let savedTimers = {};
    try { const saved = localStorage.getItem(STORAGE_KEY); if (saved) savedTimers = JSON.parse(saved); } catch (e) { }
    const newTimers = {};
    const virtualAccounts = [];
    for (let i = 0; i < 60; i++) {
        const virtualId = 200 + i;
        const timerKey = 'virtual_' + virtualId;
        let expireTimestamp;
        if (savedTimers[timerKey] && savedTimers[timerKey] > Math.floor(Date.now() / 1000)) {
            expireTimestamp = savedTimers[timerKey];
        } else {
            const seed = virtualId * 12345;
            const randomHours = (seed % 649) + 1;
            const randomMinutes = ((seed * 7) % 60);
            const randomSeconds = ((seed * 13) % 60);
            expireTimestamp = Math.floor(Date.now() / 1000) + (randomHours * 3600) + (randomMinutes * 60) + randomSeconds;
        }
        newTimers[timerKey] = expireTimestamp;
        const now = Math.floor(Date.now() / 1000);
        const remaining = expireTimestamp - now;
        const dH = Math.floor(remaining / 3600);
        const dM = Math.floor((remaining % 3600) / 60);
        const dS = remaining % 60;
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${virtualId}</td><td>Unlocktool</td><td><button class="btn btn-secondary btn-sm" disabled>Đang thuê</button></td><td><span class="badge badge-danger">Đang thuê</span></td><td>M*********</td><td>U*******</td><td><span data-expire="${expireTimestamp}">${dH}h ${dM}m ${dS}s</span></td>`;
        virtualAccounts.push({ element: tr, expireTimestamp: expireTimestamp });
    }
    const allRows = Array.from(tbody.querySelectorAll('tr'));
    const waitingAccounts = [], expiredAccounts = [], rentingAccounts = [];
    allRows.forEach(tr => {
        const badge = tr.querySelector('.badge');
        const isWaiting = badge && badge.textContent.trim() === 'Chờ thuê';
        const waitingSpan = tr.querySelector('[data-waiting]');
        const expireSpan = tr.querySelector('[data-expire]');
        if (isWaiting) { waitingAccounts.push({ element: tr, expireTimestamp: 0 }); }
        else if (waitingSpan) { expiredAccounts.push({ element: tr, expireTimestamp: parseInt(waitingSpan.getAttribute('data-waiting')) }); }
        else if (expireSpan) { rentingAccounts.push({ element: tr, expireTimestamp: parseInt(expireSpan.getAttribute('data-expire')) }); }
        else { rentingAccounts.push({ element: tr, expireTimestamp: Number.MAX_SAFE_INTEGER }); }
    });
    rentingAccounts.push(...virtualAccounts);
    expiredAccounts.sort((a, b) => a.expireTimestamp - b.expireTimestamp);
    rentingAccounts.sort((a, b) => a.expireTimestamp - b.expireTimestamp);
    const allAccounts = [...waitingAccounts, ...expiredAccounts, ...rentingAccounts];
    tbody.innerHTML = '';
    allAccounts.forEach(a => tbody.appendChild(a.element));
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(newTimers)); } catch (e) { }
}

// ===== jQuery-dependent functionality =====
$(document).ready(function () {
    createVirtualRentingAccounts();
    setTimeout(updateCountdowns, 100);

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
        const raw = $(inputId).val().trim();
        if (!raw) { alert('Vui lòng nhập mã đơn hàng.'); return; }
        const match = raw.match(/(?:DH|RENT)?\d+/gi);
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
        const text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(() => alert('Đã sao chép: ' + text));
    });

    // Floating Contact — Premium FAB
    $('#fabContactBtn').on('click', function (e) {
        e.stopPropagation();
        $('#fabContactWrapper').toggleClass('open');
    });
    $(document).on('click', function (e) {
        const w = $('#fabContactWrapper');
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
