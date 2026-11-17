#!/usr/bin/env node
/**
 * UnlockTool Password Rotation Agent
 * Tự động đổi mật khẩu tài khoản UnlockTool qua Chrome (puppeteer-core).
 *
 * Luồng: Poll API → Login unlocktool.net → Đổi pass → Report kết quả
 * Turnstile: Dừng lại, chờ user xác minh thủ công → tự tiếp tục
 */

import { setDefaultAutoSelectFamily } from 'node:net';
import puppeteer from 'puppeteer-core';
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
import { createInterface } from 'node:readline';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execSync, spawn } from 'node:child_process';

// This machine cannot reliably reach the API over IPv6. Prefer IPv4 so the
// unlocktool.us agent does not time out while polling or reporting a job.
setDefaultAutoSelectFamily(false);

const __dirname = dirname(fileURLToPath(import.meta.url));
const CONFIG_PATH = resolve(__dirname, 'agent-config.json');
const CHROME_PROFILE = resolve(__dirname, 'chrome-profile');
const DEBUG_PORT = 9224; // Separate from the thuetaikhoan.com.vn agent (9223)

const URLS = {
  login: 'https://unlocktool.net/post-in/',
  changePassword: 'https://unlocktool.net/password-change/',
};

const POLL_INTERVAL = 3_000;    // Check for a new job every 3 seconds
const JOB_TIMEOUT   = 120_000; // 2 minutes max per job

// ─── Helpers ──────────────────────────────────────────────────────────

function log(msg) {
  const ts = new Date().toLocaleTimeString('vi-VN');
  console.log(`[${ts}] ${msg}`);
}

function logError(msg) {
  const ts = new Date().toLocaleTimeString('vi-VN');
  console.error(`[${ts}] ❌ ${msg}`);
}

function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

/** Prompt user for input in the terminal. */
function askUser(question) {
  const rl = createInterface({ input: process.stdin, output: process.stdout });
  return new Promise(resolve => {
    rl.question(question, answer => { rl.close(); resolve(answer.trim()); });
  });
}

// ─── Config ──────────────────────────────────────────────────────────

function loadConfig() {
  if (existsSync(CONFIG_PATH)) {
    try {
      return JSON.parse(readFileSync(CONFIG_PATH, 'utf-8'));
    } catch { /* corrupt file, re-create */ }
  }
  return {};
}

function saveConfig(cfg) {
  writeFileSync(CONFIG_PATH, JSON.stringify(cfg, null, 2), 'utf-8');
}

/** Find Chrome executable on Windows. */
function findChrome() {
  const candidates = [
    process.env.CHROME_PATH,
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    `${process.env.LOCALAPPDATA}\\Google\\Chrome\\Application\\chrome.exe`,
  ].filter(Boolean);

  for (const p of candidates) {
    if (existsSync(p)) return p;
  }
  return null;
}

// ─── API Client ──────────────────────────────────────────────────────

class ApiClient {
  constructor(baseUrl, token) {
    this.baseUrl = baseUrl.replace(/\/+$/, '');
    this.token = token;
  }

  async poll() {
    const res = await fetch(`${this.baseUrl}/api/password-rotation-agent/poll`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (res.status === 401) throw new Error('TOKEN_INVALID');
    if (!res.ok) throw new Error(`Poll failed: HTTP ${res.status}`);

    const data = await res.json();
    return data.job || null;
  }

  async report(jobId, status, message = '') {
    const res = await fetch(`${this.baseUrl}/api/password-rotation-agent/jobs/${jobId}/report`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ status, message }),
    });

    if (!res.ok) {
      logError(`Report failed: HTTP ${res.status}`);
    }
  }
}

// ─── Browser Automation ──────────────────────────────────────────────

class UnlockToolAutomation {
  constructor(chromePath) {
    this.chromePath = chromePath;
    this.browser = null;
    this.page = null;
    this.chromeProcess = null;
    this.lastLoginFailure = null;
  }

  async launch() {
    if (this.browser) return;

    // Ensure chrome-profile dir exists
    if (!existsSync(CHROME_PROFILE)) {
      mkdirSync(CHROME_PROFILE, { recursive: true });
    }

    log('🌐 Khởi động Chrome (clean, không cờ automation)...');

    // Launch Chrome as a REGULAR process with remote debugging
    // NO automation flags = Cloudflare cannot detect it
    this.chromeProcess = spawn(this.chromePath, [
      `--remote-debugging-port=${DEBUG_PORT}`,
      `--user-data-dir=${CHROME_PROFILE}`,
      '--no-first-run',
      '--no-default-browser-check',
      '--start-maximized',
    ], {
      detached: false,
      stdio: 'ignore',
    });

    this.chromeProcess.on('exit', () => {
      this.chromeProcess = null;
      this.browser = null;
      this.page = null;
      log('⚠️ Chrome đã đóng.');
    });

    // Wait for Chrome to start and debugging port to be ready
    log('⏳ Chờ Chrome khởi động...');
    let connected = false;
    for (let attempt = 0; attempt < 60; attempt++) {
      try {
        this.browser = await puppeteer.connect({
          browserURL: `http://127.0.0.1:${DEBUG_PORT}`,
          defaultViewport: null,
        });
        connected = true;
        break;
      } catch {
        // Chrome not ready yet; retry quickly.
        await sleep(250);
      }
    }

    if (!connected) {
      logError('Không thể kết nối vào Chrome sau 15 giây.');
      this.killChrome();
      throw new Error('Chrome connection failed');
    }

    log('✅ Đã kết nối Chrome (clean browser — Cloudflare không phát hiện).');

    const pages = await this.browser.pages();
    this.page = pages[0] || await this.browser.newPage();

    this.browser.on('disconnected', () => {
      this.browser = null;
      this.page = null;
    });
  }

  killChrome() {
    if (this.chromeProcess) {
      try { this.chromeProcess.kill(); } catch { /* ignore */ }
      this.chromeProcess = null;
    }
  }

  async close() {
    if (this.browser) {
      try { this.browser.disconnect(); } catch { /* ignore */ }
      this.browser = null;
      this.page = null;
    }
    this.killChrome();
  }

  /** Check if Cloudflare challenge (full-page interstitial OR embedded Turnstile) is active. */
  async hasCloudflareChallenge() {
    try {
      const result = await this.page.evaluate(() => {
        const bodyText = document.body?.innerText || '';
        const title = document.title || '';

        // Full-page Cloudflare interstitial ("Just a moment...", "Performing security verification")
        const isInterstitial =
          title.toLowerCase().includes('just a moment') ||
          bodyText.includes('Performing security verification') ||
          bodyText.includes('Verify you are human') ||
          bodyText.includes('Checking if the site connection is secure') ||
          bodyText.includes('Enable JavaScript and cookies to continue') ||
          document.querySelector('#challenge-running, #challenge-stage') !== null;

        if (isInterstitial) return 'interstitial';

        // Embedded Turnstile widget
        const iframe = document.querySelector('iframe[src*="challenges.cloudflare.com"]');
        const widget = document.querySelector('#cf-turnstile, .cf-turnstile, [data-sitekey]');
        if (iframe || widget) {
          // Turnstile creates the response field before verification, but its
          // value is empty. The element existing does NOT mean it is solved.
          const widgetArea = document.querySelector('#cf-turnstile, .cf-turnstile') || document.body;
          const widgetText = widgetArea?.innerText || '';
          const hasSolvedText = widgetText.includes('Success');
          const responseField = document.querySelector(
            'input[name^="cf-turnstile-response"], textarea[name^="cf-turnstile-response"]'
          );
          const responseToken = String(
            responseField?.value || widget?.getAttribute('data-response') || ''
          ).trim();
          const hasSolvedToken = responseToken.length > 20;
          const hasSolvedCheckbox = document.querySelector('.cf-turnstile iframe[style*="display: none"], .cf-turnstile [aria-checked="true"]');

          if (hasSolvedText || hasSolvedToken || hasSolvedCheckbox) return false; // solved!
          return 'turnstile';
        }

        return false;
      });
      return result;
    } catch {
      return false;
    }
  }

  /** Read the embedded login Turnstile state without treating an empty field as solved. */
  async getTurnstileState() {
    try {
      return await this.page.evaluate(() => {
        const iframe = document.querySelector('iframe[src*="challenges.cloudflare.com"]');
        const widget = document.querySelector('#cf-turnstile, .cf-turnstile, [data-sitekey]');
        const responseField = document.querySelector(
          'input[name^="cf-turnstile-response"], textarea[name^="cf-turnstile-response"]'
        );
        const token = String(
          responseField?.value || widget?.getAttribute('data-response') || ''
        ).trim();

        return {
          present: Boolean(iframe || widget || responseField),
          solved: token.length > 20,
        };
      });
    } catch {
      return { present: false, solved: false };
    }
  }

  /** Require a real Turnstile response token before the agent may submit login. */
  async waitForLoginTurnstile(api, jobId) {
    const initial = await this.getTurnstileState();
    if (initial.solved) return true;

    log(initial.present
      ? '⚠️  TURNSTILE — hãy tích "Verify you are human" trên Chrome...'
      : '⏳ Đang chờ Turnstile xuất hiện và được xác minh...');
    await api.report(jobId, 'attention', 'Turnstile — cần tích Verify you are human trước khi đăng nhập.');

    let waited = 0;
    while (waited < 300_000) {
      await sleep(250);
      waited += 250;

      const state = await this.getTurnstileState();
      if (state.solved) {
        log('✅ Turnstile đã có mã xác minh — chuẩn bị đăng nhập...');
        await sleep(100);
        return true;
      }

      if (waited % 15_000 === 0) {
        log(`   ⏳ Chưa tích xác minh Turnstile... (${Math.round(waited / 1000)}s)`);
      }
    }

    logError('Timeout chờ tích xác minh Turnstile (5 phút).');
    return false;
  }

  /**
   * Wait for Cloudflare challenge to clear. Pauses agent and notifies panel.
   * Returns true when challenge is passed, false on timeout.
   */
  async waitForCloudflare(api, jobId) {
    const challengeType = await this.hasCloudflareChallenge();
    if (!challengeType) return true; // no challenge

    const label = challengeType === 'interstitial'
      ? '🛡️  CLOUDFLARE CHALLENGE'
      : '🔄 TURNSTILE WIDGET';

    log(`⚠️  ${label} — Chờ xác minh trên Chrome...`);
    log('   Nếu tự động qua thì agent tiếp tục. Nếu cần click, hãy thao tác trên Chrome.');
    await api.report(jobId, 'attention', `${label} — cần xác minh thủ công trên Chrome.`);

    let waited = 0;
    while (waited < 300_000) { // max 5 minutes
      await sleep(1_000);
      waited += 1_000;

      const still = await this.hasCloudflareChallenge();
      if (!still) {
        log('✅ Cloudflare đã xác minh xong — tiếp tục...');
        await sleep(200); // let the real page replace the challenge frame
        return true;
      }

      // Print dot every 15 seconds so user knows agent is alive
      if (waited % 15_000 === 0) {
        log(`   ⏳ Vẫn đang chờ Cloudflare... (${Math.round(waited / 1000)}s)`);
      }
    }

    logError('Timeout chờ Cloudflare (5 phút).');
    return false;
  }

  /** Login to unlocktool.net. Returns true if successful. */
  async login(username, password, api, jobId) {
    this.lastLoginFailure = null;
    log(`🔑 Đăng nhập: ${username}`);

    await this.page.goto(URLS.login, { waitUntil: 'domcontentloaded', timeout: 30_000 });
    await sleep(200);

    // A full-page Cloudflare interstitial must clear before the form is usable.
    // The embedded login Turnstile is handled after credentials are filled.
    const firstChallenge = await this.hasCloudflareChallenge();
    if (firstChallenge === 'interstitial') {
      const cfOk = await this.waitForCloudflare(api, jobId);
      if (!cfOk) return false;
    }

    // After Cloudflare clears, the page may have redirected — re-check URL
    await sleep(100);

    // If page is still on Cloudflare domain or challenge, navigate again
    if (!this.page.url().includes('unlocktool.net')) {
      await this.page.goto(URLS.login, { waitUntil: 'domcontentloaded', timeout: 20_000 });
      await sleep(200);
      const cfOk2 = await this.waitForCloudflare(api, jobId);
      if (!cfOk2) return false;
    }

    // Check if already logged in (redirected away from login page)
    if (!this.page.url().includes('post-in') && !this.page.url().includes('login')) {
      log('✅ Đã đăng nhập sẵn, bỏ qua login.');
      return true;
    }

    // Wait for login form to appear (max 15 seconds)
    try {
      await this.page.waitForSelector('input[name="log"], input[name="username"], #user_login', { timeout: 15_000 });
    } catch {
      // Check if it's another Cloudflare challenge
      const stillCf = await this.hasCloudflareChallenge();
      if (stillCf) {
        const ok = await this.waitForCloudflare(api, jobId);
        if (!ok) return false;
        // Try finding form again
        try {
          await this.page.waitForSelector('input[name="log"], input[name="username"], #user_login', { timeout: 10_000 });
        } catch {
          logError('Không tìm thấy form login sau khi Cloudflare đã qua.');
          return false;
        }
      } else {
        logError('Không tìm thấy form login — trang có thể đã thay đổi giao diện.');
        return false;
      }
    }

    // Clear and fill username
    const usernameInput = await this.page.$('input[name="log"], input[name="username"], #user_login, input[type="text"]');
    if (!usernameInput) {
      logError('Không tìm thấy ô Username trên trang login.');
      return false;
    }
    await usernameInput.click({ clickCount: 3 });
    await usernameInput.type(username, { delay: 1 });

    // Clear and fill password
    const passwordInput = await this.page.$('input[name="pwd"], input[name="password"], #user_pass, input[type="password"]');
    if (!passwordInput) {
      logError('Không tìm thấy ô Password trên trang login.');
      return false;
    }
    await passwordInput.click({ clickCount: 3 });
    await passwordInput.type(password, { delay: 1 });

    // Never submit the login form until Cloudflare has injected a non-empty
    // cf-turnstile-response token. Merely finding the hidden input is not enough.
    await sleep(100);
    const turnstileOk = await this.waitForLoginTurnstile(api, jobId);
    if (!turnstileOk) return false;

    // Click login button
    const loginBtn = await this.page.$('input[type="submit"][value*="Login"], button[type="submit"], input[name="wp-submit"]');
    if (!loginBtn) {
      logError('Không tìm thấy nút Login.');
      return false;
    }
    // Start waiting before the click so a fast redirect cannot be missed.
    await Promise.all([
      this.page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15_000 }).catch(() => null),
      loginBtn.click(),
    ]);

    // After login submit, might hit Cloudflare again
    await sleep(150);
    const postLoginCf = await this.hasCloudflareChallenge();
    if (postLoginCf) {
      const ok = await this.waitForCloudflare(api, jobId);
      if (!ok) return false;
    }

    await sleep(150);

    // Check if still on login page (= login failed)
    const currentUrl = this.page.url();
    if (currentUrl.includes('post-in') || currentUrl.includes('login')) {
      // Check for error messages
      const errorText = await this.page.evaluate(() => {
        const err = document.querySelector('.login-error, .woocommerce-error, .alert-danger, #login_error');
        return err ? err.textContent.trim() : '';
      });
      if (errorText) {
        this.lastLoginFailure = /correct username and password|incorrect|invalid (?:username|password)|wrong password|sai (?:tài khoản|mật khẩu)/i.test(errorText)
          ? 'credentials'
          : 'unknown';
        logError(`Login thất bại: ${errorText.substring(0, 100)}`);
      } else {
        this.lastLoginFailure = 'unknown';
        logError('Login thất bại — vẫn ở trang login.');
      }
      return false;
    }

    log('✅ Đăng nhập thành công!');
    return true;
  }

  /** Change password on unlocktool.net. Returns true if successful. */
  async changePassword(oldPassword, newPassword, api, jobId) {
    log('🔄 Đang đổi mật khẩu...');

    await this.page.goto(URLS.changePassword, { waitUntil: 'domcontentloaded', timeout: 30_000 });
    await sleep(200);

    // Wait for Cloudflare challenge if present
    const cfOk = await this.waitForCloudflare(api, jobId);
    if (!cfOk) return false;

    await sleep(100);

    // If redirected to login page, we're not authenticated
    if (this.page.url().includes('post-in') || this.page.url().includes('login')) {
      logError('Bị redirect về trang login — phiên đăng nhập hết hạn.');
      return false;
    }

    // Wait for password fields to appear
    try {
      await this.page.waitForSelector('input[type="password"]', { timeout: 15_000 });
    } catch {
      logError('Không tìm thấy form đổi mật khẩu.');
      return false;
    }

    // Find password fields — the change-password page has 3 password inputs
    const passwordInputs = await this.page.$$('input[type="password"]');

    if (passwordInputs.length < 3) {
      logError(`Trang đổi pass chỉ có ${passwordInputs.length} ô password (cần 3).`);
      return false;
    }

    // Field 1: Old password
    await passwordInputs[0].click({ clickCount: 3 });
    await passwordInputs[0].type(oldPassword, { delay: 1 });

    // Field 2: New password
    await passwordInputs[1].click({ clickCount: 3 });
    await passwordInputs[1].type(newPassword, { delay: 1 });

    // Field 3: Confirm new password
    await passwordInputs[2].click({ clickCount: 3 });
    await passwordInputs[2].type(newPassword, { delay: 1 });

    await sleep(100);

    // Click "Change password" button
    const changeBtn = await this.page.$('button[type="submit"], input[type="submit"]');
    if (!changeBtn) {
      logError('Không tìm thấy nút "Change password".');
      return false;
    }
    await Promise.all([
      this.page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15_000 }).catch(() => null),
      changeBtn.click(),
    ]);

    await sleep(250);

    // Check for success — the page title or body should contain "successful"
    const pageContent = await this.page.evaluate(() => {
      return {
        title: document.title,
        body: document.body?.innerText?.substring(0, 2000) || '',
        url: window.location.href,
      };
    });

    const successIndicators = ['successful', 'success', 'thành công', 'changed'];
    const isSuccess = successIndicators.some(s =>
      pageContent.title.toLowerCase().includes(s) ||
      pageContent.body.toLowerCase().includes(s)
    );

    if (isSuccess) {
      log('✅ Đổi mật khẩu thành công!');

      // Click "Update password" in Chrome's password save bubble
      // Chrome bubble is browser-level UI — need Windows native SendKeys
      await sleep(800);
      try {
        log('💾 Nhấn "Update password" trong Chrome...');
        const psScript = resolve(__dirname, 'click-save-password.ps1');
        execSync(`powershell -NoProfile -ExecutionPolicy Bypass -File "${psScript}"`, {
          timeout: 5_000,
          stdio: 'ignore',
        });
        await sleep(150);
        log('✅ Đã lưu mật khẩu vào Google.');
      } catch (e) {
        log('⚠️ Không thể tự động click "Update password" — bạn click thủ công nếu thấy popup.');
      }

      return true;
    }

    // Check for validation errors
    const hasErrors = await this.page.evaluate(() => {
      const errors = document.querySelectorAll('.woocommerce-error li, .alert-danger, .error-message, ul.errorlist li');
      return Array.from(errors).map(e => e.textContent.trim()).join('; ');
    });

    if (hasErrors) {
      logError(`Đổi pass thất bại — lỗi: ${hasErrors.substring(0, 200)}`);
    } else {
      logError('Đổi pass thất bại — không phát hiện thông báo thành công.');
    }

    return false;
  }

  /** Clear session and go to login page for next account. */
  async logout() {
    try {
      log('🚪 Xoá phiên đăng nhập...');

      // Clear cookies for unlocktool.net via CDP
      const cdp = await this.page.createCDPSession();
      const cookies = await cdp.send('Network.getCookies', {
        urls: ['https://unlocktool.net']
      });
      if (cookies.cookies.length > 0) {
        await cdp.send('Network.deleteCookies', {
          name: cookies.cookies.map(c => c.name).join(','),
          domain: 'unlocktool.net',
        });
      }
      // Clear all cookies for this domain more aggressively
      for (const cookie of cookies.cookies) {
        await cdp.send('Network.deleteCookies', {
          name: cookie.name,
          domain: cookie.domain,
        });
      }

      // Navigate directly to login page
      log('➡️ Chuyển về trang đăng nhập...');
      await this.page.goto('https://unlocktool.net/post-in/', {
        waitUntil: 'domcontentloaded', timeout: 20_000
      });
      await sleep(100);
    } catch (err) {
      log(`⚠️ Lỗi khi logout: ${err.message} — tiếp tục...`);
    }
  }
}

// ─── Main Agent Loop ─────────────────────────────────────────────────

async function main() {
  console.log('');
  console.log('╔══════════════════════════════════════════════════╗');
  console.log('║   🔐 UnlockTool Password Rotation Agent v1.0   ║');
  console.log('║   unlocktool.us                                 ║');
  console.log('╚══════════════════════════════════════════════════╝');
  console.log('');

  // ─── Load / setup config
  let cfg = loadConfig();

  if (!cfg.apiBaseUrl) {
    cfg.apiBaseUrl = 'https://unlocktool.us';
  }

  if (!cfg.token) {
    console.log('Chưa có mã kết nối. Tạo mã từ Admin Panel → Đổi Pass → UnlockTool → "Tạo mã kết nối mới"');
    console.log('');
    cfg.token = await askUser('Dán mã kết nối (token): ');
    if (!cfg.token || cfg.token.length < 32) {
      logError('Token không hợp lệ. Thoát.');
      process.exit(1);
    }
    saveConfig(cfg);
    log('✅ Đã lưu token vào agent-config.json');
  }

  // ─── Find Chrome
  const chromePath = (cfg.chromePath && existsSync(cfg.chromePath)) ? cfg.chromePath : findChrome();
  if (!chromePath) {
    logError('Không tìm thấy Chrome. Cài Chrome hoặc set CHROME_PATH trong agent-config.json.');
    process.exit(1);
  }
  cfg.chromePath = chromePath;
  saveConfig(cfg);
  log(`Chrome: ${chromePath}`);

  // ─── Init API client
  const api = new ApiClient(cfg.apiBaseUrl, cfg.token);

  // ─── Test connection
  log('Kiểm tra kết nối API...');
  try {
    await api.poll();
    log('✅ Kết nối API thành công!');
  } catch (err) {
    if (err.message === 'TOKEN_INVALID') {
      logError('Token không hợp lệ hoặc đã bị thu hồi. Tạo token mới từ Admin Panel.');
      // Delete invalid token
      cfg.token = '';
      saveConfig(cfg);
      process.exit(1);
    }
    logError(`Lỗi kết nối: ${err.message}`);
    process.exit(1);
  }

  // ─── Init browser automation
  const automation = new UnlockToolAutomation(chromePath);
  let consecutiveErrors = 0;

  // ─── First-run: Setup Google account in Chrome profile
  const googleSetupMarker = resolve(__dirname, 'chrome-profile', '.google-setup-done');
  if (!existsSync(googleSetupMarker)) {
    log('');
    log('📧 Lần đầu chạy — cần đăng nhập Google để lưu mật khẩu.');
    log('   Chrome sẽ mở trang đăng nhập Google...');
    log('');

    await automation.launch();

    // Navigate to Google account sign-in
    await automation.page.goto('https://accounts.google.com/signin', {
      waitUntil: 'networkidle2', timeout: 30_000
    });

    console.log('');
    console.log('╔══════════════════════════════════════════════════════╗');
    console.log('║  Đăng nhập Google trên Chrome rồi nhấn Enter ở đây  ║');
    console.log('╚══════════════════════════════════════════════════════╝');
    console.log('');
    await askUser('Nhấn Enter sau khi đã đăng nhập Google → ');

    // Also enable password saving: navigate to Chrome settings
    try {
      await automation.page.goto('chrome://settings/passwords', { waitUntil: 'networkidle2', timeout: 10_000 });
      await sleep(1_000);
      log('💡 Kiểm tra Chrome Settings → Passwords đã bật "Offer to save passwords".');
    } catch { /* chrome:// pages may not work via puppeteer connect */ }

    // Write marker so we don't ask again
    writeFileSync(googleSetupMarker, new Date().toISOString(), 'utf-8');
    log('✅ Đã thiết lập Google account — mật khẩu sẽ tự lưu vào Google.');
    log('');

    // Close Chrome so it relaunches fresh for the job loop
    await automation.close();
    await sleep(2_000);
  }

  // Handle graceful shutdown
  process.on('SIGINT', async () => {
    log('\\n🛑 Đang dừng agent...');
    await automation.close();
    process.exit(0);
  });

  // ─── Main loop
  log('🚀 Agent đang chạy — nhấn Ctrl+C để dừng.');
  console.log('─'.repeat(50));

  while (true) {
    let job = null;

    try {
      job = await api.poll();
    } catch (err) {
      if (err.message === 'TOKEN_INVALID') {
        logError('Token hết hạn. Tạo token mới từ Admin Panel.');
        await automation.close();
        process.exit(1);
      }
      consecutiveErrors++;
      logError(`Poll lỗi (${consecutiveErrors}): ${err.message}`);
      if (consecutiveErrors > 10) {
        logError('Quá nhiều lỗi liên tiếp. Dừng agent.');
        await automation.close();
        process.exit(1);
      }
      await sleep(POLL_INTERVAL);
      continue;
    }

    consecutiveErrors = 0;

    if (!job) {
      process.stdout.write(`\r⏳ Không có tác vụ — chờ ${POLL_INTERVAL / 1000}s...  `);
      await sleep(POLL_INTERVAL);
      continue;
    }

    console.log(''); // Clear the waiting line
    log(`📋 Job #${job.id}: ${job.username}`);

    try {
      // Launch Chrome if not already running
      await automation.launch();

      // Step 1: Login
      let loginOk = await automation.login(job.username, job.current_password, api, job.id);
      let passwordAlreadyChanged = false;

      // A previous run may have changed the password successfully but failed
      // before reporting back. Only after a definite credential error, verify
      // the queued new password once so the database can be reconciled safely.
      if (!loginOk
          && automation.lastLoginFailure === 'credentials'
          && job.new_password
          && job.new_password !== job.current_password) {
        log('🔁 Mật khẩu hiện tại không đúng — thử xác minh bằng mật khẩu mới đã tạo...');
        await automation.logout();
        loginOk = await automation.login(job.username, job.new_password, api, job.id);
        passwordAlreadyChanged = loginOk;

        if (passwordAlreadyChanged) {
          log('✅ Mật khẩu mới đăng nhập được — lần đổi trước đã thành công.');
        }
      }

      if (!loginOk) {
        await api.report(job.id, 'failed', 'Không thể đăng nhập vào unlocktool.net');
        logError(`Job #${job.id}: Login thất bại.`);
        await automation.logout();
        continue;
      }

      if (passwordAlreadyChanged) {
        await api.report(job.id, 'success', 'Mật khẩu mới đã đăng nhập thành công; đồng bộ lại dữ liệu sau lần đổi trước.');
        log(`✅ Job #${job.id}: Đã xác nhận mật khẩu mới — ${job.username}`);
        await automation.logout();
        await sleep(100);
        continue;
      }

      // Step 2: Change password
      const changeOk = await automation.changePassword(job.current_password, job.new_password, api, job.id);
      if (!changeOk) {
        await api.report(job.id, 'failed', 'Đổi mật khẩu thất bại trên unlocktool.net');
        logError(`Job #${job.id}: Đổi pass thất bại.`);
        await automation.logout();
        continue;
      }

      // Step 3: Report success — ONLY after confirmed success on page
      await api.report(job.id, 'success', 'Đổi mật khẩu thành công.');
      log(`✅ Job #${job.id}: Hoàn tất — ${job.username}`);

      // Logout to prepare for next account
      await automation.logout();

      // Brief pause between accounts
      await sleep(100);

    } catch (err) {
      logError(`Job #${job.id}: Lỗi ngoại lệ — ${err.message}`);
      try {
        await api.report(job.id, 'failed', `Lỗi agent: ${err.message.substring(0, 200)}`);
      } catch { /* ignore report error */ }

      // If browser crashed, null it out so it relaunches
      if (!automation.browser) {
        log('Chrome bị crash — sẽ khởi động lại ở job tiếp theo.');
      }
    }
  }
}

main().catch(err => {
  logError(`Fatal: ${err.message}`);
  process.exit(1);
});
