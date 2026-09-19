#!/usr/bin/env node
/**
 * UnlockTool Password Rotation Agent
 * Tá»± Ä‘á»™ng Ä‘á»•i máº­t kháº©u tÃ i khoáº£n UnlockTool qua Chrome (puppeteer-core).
 *
 * Luá»“ng: Poll API â†’ Login unlocktool.net â†’ Äá»•i pass â†’ Report káº¿t quáº£
 * Turnstile: Dá»«ng láº¡i, chá» user xÃ¡c minh thá»§ cÃ´ng â†’ tá»± tiáº¿p tá»¥c
 */

import puppeteer from 'puppeteer-core';
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
import { createInterface } from 'node:readline';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execSync, spawn } from 'node:child_process';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CONFIG_PATH = resolve(__dirname, 'agent-config.json');
const CHROME_PROFILE = resolve(__dirname, 'chrome-profile');
const DEBUG_PORT = 9224; // Use different port from thuetaikhoan agent (9223) // Use non-standard port to avoid conflicts

const URLS = {
  login: 'https://unlocktool.net/post-in/',
  changePassword: 'https://unlocktool.net/password-change/',
};

const POLL_INTERVAL = 15_000;   // 15 seconds between polls when idle
const JOB_TIMEOUT   = 120_000; // 2 minutes max per job

// â”€â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

function log(msg) {
  const ts = new Date().toLocaleTimeString('vi-VN');
  console.log(`[${ts}] ${msg}`);
}

function logError(msg) {
  const ts = new Date().toLocaleTimeString('vi-VN');
  console.error(`[${ts}] âŒ ${msg}`);
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

// â”€â”€â”€ Config â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

// â”€â”€â”€ API Client â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

// â”€â”€â”€ Browser Automation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

class UnlockToolAutomation {
  constructor(chromePath) {
    this.chromePath = chromePath;
    this.browser = null;
    this.page = null;
    this.chromeProcess = null;
  }

  async launch() {
    if (this.browser) return;

    // Ensure chrome-profile dir exists
    if (!existsSync(CHROME_PROFILE)) {
      mkdirSync(CHROME_PROFILE, { recursive: true });
    }

    log('ðŸŒ Khá»Ÿi Ä‘á»™ng Chrome (clean, khÃ´ng cá» automation)...');

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
      log('âš ï¸ Chrome Ä‘Ã£ Ä‘Ã³ng.');
    });

    // Wait for Chrome to start and debugging port to be ready
    log('â³ Chá» Chrome khá»Ÿi Ä‘á»™ng...');
    let connected = false;
    for (let attempt = 0; attempt < 30; attempt++) {
      await sleep(1_000);
      try {
        this.browser = await puppeteer.connect({
          browserURL: `http://127.0.0.1:${DEBUG_PORT}`,
          defaultViewport: null,
        });
        connected = true;
        break;
      } catch {
        // Chrome not ready yet, retry
      }
    }

    if (!connected) {
      logError('KhÃ´ng thá»ƒ káº¿t ná»‘i vÃ o Chrome sau 30 giÃ¢y.');
      this.killChrome();
      throw new Error('Chrome connection failed');
    }

    log('âœ… ÄÃ£ káº¿t ná»‘i Chrome (clean browser â€” Cloudflare khÃ´ng phÃ¡t hiá»‡n).');

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
          // Check multiple solved indicators
          const widgetArea = document.querySelector('#cf-turnstile, .cf-turnstile') || document.body;
          const widgetText = widgetArea?.innerText || '';
          const hasSolvedText = widgetText.includes('Success');
          const hasSolvedAttr = document.querySelector('[data-response], .cf-turnstile[data-response], input[name="cf-turnstile-response"]');
          const hasSolvedCheckbox = document.querySelector('.cf-turnstile iframe[style*="display: none"], .cf-turnstile [aria-checked="true"]');

          if (hasSolvedText || hasSolvedAttr || hasSolvedCheckbox) return false; // solved!
          return 'turnstile';
        }

        return false;
      });
      return result;
    } catch {
      return false;
    }
  }

  /**
   * Wait for Cloudflare challenge to clear. Pauses agent and notifies panel.
   * Returns true when challenge is passed, false on timeout.
   */
  async waitForCloudflare(api, jobId) {
    const challengeType = await this.hasCloudflareChallenge();
    if (!challengeType) return true; // no challenge

    const label = challengeType === 'interstitial'
      ? 'ðŸ›¡ï¸  CLOUDFLARE CHALLENGE'
      : 'ðŸ”„ TURNSTILE WIDGET';

    log(`âš ï¸  ${label} â€” Chá» xÃ¡c minh trÃªn Chrome...`);
    log('   Náº¿u tá»± Ä‘á»™ng qua thÃ¬ agent tiáº¿p tá»¥c. Náº¿u cáº§n click, hÃ£y thao tÃ¡c trÃªn Chrome.');
    await api.report(jobId, 'attention', `${label} â€” cáº§n xÃ¡c minh thá»§ cÃ´ng trÃªn Chrome.`);

    let waited = 0;
    while (waited < 300_000) { // max 5 minutes
      await sleep(3_000);
      waited += 3_000;

      const still = await this.hasCloudflareChallenge();
      if (!still) {
        log('âœ… Cloudflare Ä‘Ã£ xÃ¡c minh xong â€” tiáº¿p tá»¥c...');
        await sleep(1_000); // wait for real page to fully load
        return true;
      }

      // Print dot every 15 seconds so user knows agent is alive
      if (waited % 15_000 === 0) {
        log(`   â³ Váº«n Ä‘ang chá» Cloudflare... (${Math.round(waited / 1000)}s)`);
      }
    }

    logError('Timeout chá» Cloudflare (5 phÃºt).');
    return false;
  }

  /** Login to unlocktool.net. Returns true if successful. */
  async login(username, password, api, jobId) {
    log(`ðŸ”‘ ÄÄƒng nháº­p: ${username}`);

    await this.page.goto(URLS.login, { waitUntil: 'domcontentloaded', timeout: 30_000 });
    await sleep(800);

    // Wait for Cloudflare challenge to clear FIRST
    const cfOk = await this.waitForCloudflare(api, jobId);
    if (!cfOk) return false;

    // After Cloudflare clears, the page may have redirected â€” re-check URL
    await sleep(500);

    // If page is still on Cloudflare domain or challenge, navigate again
    if (!this.page.url().includes('unlocktool.net')) {
      await this.page.goto(URLS.login, { waitUntil: 'domcontentloaded', timeout: 20_000 });
      await sleep(800);
      const cfOk2 = await this.waitForCloudflare(api, jobId);
      if (!cfOk2) return false;
    }

    // Check if already logged in (redirected away from login page)
    if (!this.page.url().includes('post-in') && !this.page.url().includes('login')) {
      log('âœ… ÄÃ£ Ä‘Äƒng nháº­p sáºµn, bá» qua login.');
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
          logError('KhÃ´ng tÃ¬m tháº¥y form login sau khi Cloudflare Ä‘Ã£ qua.');
          return false;
        }
      } else {
        logError('KhÃ´ng tÃ¬m tháº¥y form login â€” trang cÃ³ thá»ƒ Ä‘Ã£ thay Ä‘á»•i giao diá»‡n.');
        return false;
      }
    }

    // Clear and fill username
    const usernameInput = await this.page.$('input[name="log"], input[name="username"], #user_login, input[type="text"]');
    if (!usernameInput) {
      logError('KhÃ´ng tÃ¬m tháº¥y Ã´ Username trÃªn trang login.');
      return false;
    }
    await usernameInput.click({ clickCount: 3 });
    await usernameInput.type(username, { delay: 5 });

    // Clear and fill password
    const passwordInput = await this.page.$('input[name="pwd"], input[name="password"], #user_pass, input[type="password"]');
    if (!passwordInput) {
      logError('KhÃ´ng tÃ¬m tháº¥y Ã´ Password trÃªn trang login.');
      return false;
    }
    await passwordInput.click({ clickCount: 3 });
    await passwordInput.type(password, { delay: 5 });

    // Wait for Turnstile to finish and inject token into form
    await sleep(1_500);
    const formCf = await this.hasCloudflareChallenge();
    if (formCf) {
      const ok = await this.waitForCloudflare(api, jobId);
      if (!ok) return false;
    }

    // Click login button
    const loginBtn = await this.page.$('input[type="submit"][value*="Login"], button[type="submit"], input[name="wp-submit"]');
    if (!loginBtn) {
      logError('KhÃ´ng tÃ¬m tháº¥y nÃºt Login.');
      return false;
    }
    await loginBtn.click();

    // Wait for navigation
    try {
      await this.page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30_000 });
    } catch {
      // Navigation might not trigger if there's an error on the same page
    }

    // After login submit, might hit Cloudflare again
    await sleep(500);
    const postLoginCf = await this.hasCloudflareChallenge();
    if (postLoginCf) {
      const ok = await this.waitForCloudflare(api, jobId);
      if (!ok) return false;
    }

    await sleep(500);

    // Check if still on login page (= login failed)
    const currentUrl = this.page.url();
    if (currentUrl.includes('post-in') || currentUrl.includes('login')) {
      // Check for error messages
      const errorText = await this.page.evaluate(() => {
        const err = document.querySelector('.login-error, .woocommerce-error, .alert-danger, #login_error');
        return err ? err.textContent.trim() : '';
      });
      if (errorText) {
        logError(`Login tháº¥t báº¡i: ${errorText.substring(0, 100)}`);
      } else {
        logError('Login tháº¥t báº¡i â€” váº«n á»Ÿ trang login.');
      }
      return false;
    }

    log('âœ… ÄÄƒng nháº­p thÃ nh cÃ´ng!');
    return true;
  }

  /** Change password on unlocktool.net. Returns true if successful. */
  async changePassword(oldPassword, newPassword, api, jobId) {
    log('ðŸ”„ Äang Ä‘á»•i máº­t kháº©u...');

    await this.page.goto(URLS.changePassword, { waitUntil: 'domcontentloaded', timeout: 30_000 });
    await sleep(800);

    // Wait for Cloudflare challenge if present
    const cfOk = await this.waitForCloudflare(api, jobId);
    if (!cfOk) return false;

    await sleep(500);

    // If redirected to login page, we're not authenticated
    if (this.page.url().includes('post-in') || this.page.url().includes('login')) {
      logError('Bá»‹ redirect vá» trang login â€” phiÃªn Ä‘Äƒng nháº­p háº¿t háº¡n.');
      return false;
    }

    // Wait for password fields to appear
    try {
      await this.page.waitForSelector('input[type="password"]', { timeout: 15_000 });
    } catch {
      logError('KhÃ´ng tÃ¬m tháº¥y form Ä‘á»•i máº­t kháº©u.');
      return false;
    }

    // Find password fields â€” the change-password page has 3 password inputs
    const passwordInputs = await this.page.$$('input[type="password"]');

    if (passwordInputs.length < 3) {
      logError(`Trang Ä‘á»•i pass chá»‰ cÃ³ ${passwordInputs.length} Ã´ password (cáº§n 3).`);
      return false;
    }

    // Field 1: Old password
    await passwordInputs[0].click({ clickCount: 3 });
    await passwordInputs[0].type(oldPassword, { delay: 5 });

    // Field 2: New password
    await passwordInputs[1].click({ clickCount: 3 });
    await passwordInputs[1].type(newPassword, { delay: 5 });

    // Field 3: Confirm new password
    await passwordInputs[2].click({ clickCount: 3 });
    await passwordInputs[2].type(newPassword, { delay: 5 });

    await sleep(500);

    // Click "Change password" button
    const changeBtn = await this.page.$('button[type="submit"], input[type="submit"]');
    if (!changeBtn) {
      logError('KhÃ´ng tÃ¬m tháº¥y nÃºt "Change password".');
      return false;
    }
    await changeBtn.click();

    // Wait for result
    try {
      await this.page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 20_000 });
    } catch {
      // page may not navigate
    }

    await sleep(800);

    // Check for success â€” the page title or body should contain "successful"
    const pageContent = await this.page.evaluate(() => {
      return {
        title: document.title,
        body: document.body?.innerText?.substring(0, 2000) || '',
        url: window.location.href,
      };
    });

    const successIndicators = ['successful', 'success', 'thÃ nh cÃ´ng', 'changed'];
    const isSuccess = successIndicators.some(s =>
      pageContent.title.toLowerCase().includes(s) ||
      pageContent.body.toLowerCase().includes(s)
    );

    if (isSuccess) {
      log('âœ… Äá»•i máº­t kháº©u thÃ nh cÃ´ng!');

      // Click "Update password" in Chrome's password save bubble
      // Chrome bubble is browser-level UI â€” need Windows native SendKeys
      await sleep(1_500);
      try {
        log('ðŸ’¾ Nháº¥n "Update password" trong Chrome...');
        const psScript = resolve(__dirname, 'click-save-password.ps1');
        execSync(`powershell -NoProfile -ExecutionPolicy Bypass -File "${psScript}"`, {
          timeout: 5_000,
          stdio: 'ignore',
        });
        await sleep(500);
        log('âœ… ÄÃ£ lÆ°u máº­t kháº©u vÃ o Google.');
      } catch (e) {
        log('âš ï¸ KhÃ´ng thá»ƒ tá»± Ä‘á»™ng click "Update password" â€” báº¡n click thá»§ cÃ´ng náº¿u tháº¥y popup.');
      }

      return true;
    }

    // Check for validation errors
    const hasErrors = await this.page.evaluate(() => {
      const errors = document.querySelectorAll('.woocommerce-error li, .alert-danger, .error-message, ul.errorlist li');
      return Array.from(errors).map(e => e.textContent.trim()).join('; ');
    });

    if (hasErrors) {
      logError(`Äá»•i pass tháº¥t báº¡i â€” lá»—i: ${hasErrors.substring(0, 200)}`);
    } else {
      logError('Äá»•i pass tháº¥t báº¡i â€” khÃ´ng phÃ¡t hiá»‡n thÃ´ng bÃ¡o thÃ nh cÃ´ng.');
    }

    return false;
  }

  /** Clear session and go to login page for next account. */
  async logout() {
    try {
      log('ðŸšª XoÃ¡ phiÃªn Ä‘Äƒng nháº­p...');

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
      log('âž¡ï¸ Chuyá»ƒn vá» trang Ä‘Äƒng nháº­p...');
      await this.page.goto('https://unlocktool.net/post-in/', {
        waitUntil: 'domcontentloaded', timeout: 20_000
      });
      await sleep(500);
    } catch (err) {
      log(`âš ï¸ Lá»—i khi logout: ${err.message} â€” tiáº¿p tá»¥c...`);
    }
  }
}

// â”€â”€â”€ Main Agent Loop â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

async function main() {
  console.log('');
  console.log('â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—');
  console.log('â•‘   ðŸ” UnlockTool Password Rotation Agent v1.0   â•‘');
  console.log('â•‘   unlocktool.us                           â•‘');
  console.log('â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•');
  console.log('');

  // â”€â”€â”€ Load / setup config
  let cfg = loadConfig();

  if (!cfg.apiBaseUrl) {
    cfg.apiBaseUrl = 'https://unlocktool.us';
  }

  if (!cfg.token) {
    console.log('ChÆ°a cÃ³ mÃ£ káº¿t ná»‘i. Táº¡o mÃ£ tá»« Admin Panel â†’ Äá»•i Pass â†’ UnlockTool â†’ "Táº¡o mÃ£ káº¿t ná»‘i má»›i"');
    console.log('');
    cfg.token = await askUser('DÃ¡n mÃ£ káº¿t ná»‘i (token): ');
    if (!cfg.token || cfg.token.length < 32) {
      logError('Token khÃ´ng há»£p lá»‡. ThoÃ¡t.');
      process.exit(1);
    }
    saveConfig(cfg);
    log('âœ… ÄÃ£ lÆ°u token vÃ o agent-config.json');
  }

  // â”€â”€â”€ Find Chrome
  const chromePath = cfg.chromePath || findChrome();
  if (!chromePath) {
    logError('KhÃ´ng tÃ¬m tháº¥y Chrome. CÃ i Chrome hoáº·c set CHROME_PATH trong agent-config.json.');
    process.exit(1);
  }
  cfg.chromePath = chromePath;
  saveConfig(cfg);
  log(`Chrome: ${chromePath}`);

  // â”€â”€â”€ Init API client
  const api = new ApiClient(cfg.apiBaseUrl, cfg.token);

  // â”€â”€â”€ Test connection
  log('Kiá»ƒm tra káº¿t ná»‘i API...');
  try {
    await api.poll();
    log('âœ… Káº¿t ná»‘i API thÃ nh cÃ´ng!');
  } catch (err) {
    if (err.message === 'TOKEN_INVALID') {
      logError('Token khÃ´ng há»£p lá»‡ hoáº·c Ä‘Ã£ bá»‹ thu há»“i. Táº¡o token má»›i tá»« Admin Panel.');
      // Delete invalid token
      cfg.token = '';
      saveConfig(cfg);
      process.exit(1);
    }
    logError(`Lá»—i káº¿t ná»‘i: ${err.message}`);
    process.exit(1);
  }

  // â”€â”€â”€ Init browser automation
  const automation = new UnlockToolAutomation(chromePath);
  let consecutiveErrors = 0;

  // â”€â”€â”€ First-run: Setup Google account in Chrome profile
  const googleSetupMarker = resolve(__dirname, 'chrome-profile', '.google-setup-done');
  if (!existsSync(googleSetupMarker)) {
    log('');
    log('ðŸ“§ Láº§n Ä‘áº§u cháº¡y â€” cáº§n Ä‘Äƒng nháº­p Google Ä‘á»ƒ lÆ°u máº­t kháº©u.');
    log('   Chrome sáº½ má»Ÿ trang Ä‘Äƒng nháº­p Google...');
    log('');

    await automation.launch();

    // Navigate to Google account sign-in
    await automation.page.goto('https://accounts.google.com/signin', {
      waitUntil: 'networkidle2', timeout: 30_000
    });

    console.log('');
    console.log('â•”â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•—');
    console.log('â•‘  ÄÄƒng nháº­p Google trÃªn Chrome rá»“i nháº¥n Enter á»Ÿ Ä‘Ã¢y  â•‘');
    console.log('â•šâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•');
    console.log('');
    await askUser('Nháº¥n Enter sau khi Ä‘Ã£ Ä‘Äƒng nháº­p Google â†’ ');

    // Also enable password saving: navigate to Chrome settings
    try {
      await automation.page.goto('chrome://settings/passwords', { waitUntil: 'networkidle2', timeout: 10_000 });
      await sleep(1_000);
      log('ðŸ’¡ Kiá»ƒm tra Chrome Settings â†’ Passwords Ä‘Ã£ báº­t "Offer to save passwords".');
    } catch { /* chrome:// pages may not work via puppeteer connect */ }

    // Write marker so we don't ask again
    writeFileSync(googleSetupMarker, new Date().toISOString(), 'utf-8');
    log('âœ… ÄÃ£ thiáº¿t láº­p Google account â€” máº­t kháº©u sáº½ tá»± lÆ°u vÃ o Google.');
    log('');

    // Close Chrome so it relaunches fresh for the job loop
    await automation.close();
    await sleep(2_000);
  }

  // Handle graceful shutdown
  process.on('SIGINT', async () => {
    log('\\nðŸ›‘ Äang dá»«ng agent...');
    await automation.close();
    process.exit(0);
  });

  // â”€â”€â”€ Main loop
  log('ðŸš€ Agent Ä‘ang cháº¡y â€” nháº¥n Ctrl+C Ä‘á»ƒ dá»«ng.');
  console.log('â”€'.repeat(50));

  while (true) {
    let job = null;

    try {
      job = await api.poll();
    } catch (err) {
      if (err.message === 'TOKEN_INVALID') {
        logError('Token háº¿t háº¡n. Táº¡o token má»›i tá»« Admin Panel.');
        await automation.close();
        process.exit(1);
      }
      consecutiveErrors++;
      logError(`Poll lá»—i (${consecutiveErrors}): ${err.message}`);
      if (consecutiveErrors > 10) {
        logError('QuÃ¡ nhiá»u lá»—i liÃªn tiáº¿p. Dá»«ng agent.');
        await automation.close();
        process.exit(1);
      }
      await sleep(POLL_INTERVAL);
      continue;
    }

    consecutiveErrors = 0;

    if (!job) {
      process.stdout.write(`\râ³ KhÃ´ng cÃ³ tÃ¡c vá»¥ â€” chá» ${POLL_INTERVAL / 1000}s...  `);
      await sleep(POLL_INTERVAL);
      continue;
    }

    console.log(''); // Clear the waiting line
    log(`ðŸ“‹ Job #${job.id}: ${job.username}`);

    try {
      // Launch Chrome if not already running
      await automation.launch();

      // Step 1: Login
      const loginOk = await automation.login(job.username, job.current_password, api, job.id);
      if (!loginOk) {
        await api.report(job.id, 'failed', 'KhÃ´ng thá»ƒ Ä‘Äƒng nháº­p vÃ o unlocktool.net');
        logError(`Job #${job.id}: Login tháº¥t báº¡i.`);
        await automation.logout();
        continue;
      }

      // Step 2: Change password
      const changeOk = await automation.changePassword(job.current_password, job.new_password, api, job.id);
      if (!changeOk) {
        await api.report(job.id, 'failed', 'Äá»•i máº­t kháº©u tháº¥t báº¡i trÃªn unlocktool.net');
        logError(`Job #${job.id}: Äá»•i pass tháº¥t báº¡i.`);
        await automation.logout();
        continue;
      }

      // Step 3: Report success â€” ONLY after confirmed success on page
      await api.report(job.id, 'success', 'Äá»•i máº­t kháº©u thÃ nh cÃ´ng.');
      log(`âœ… Job #${job.id}: HoÃ n táº¥t â€” ${job.username}`);

      // Logout to prepare for next account
      await automation.logout();

      // Brief pause between accounts
      await sleep(500);

    } catch (err) {
      logError(`Job #${job.id}: Lá»—i ngoáº¡i lá»‡ â€” ${err.message}`);
      try {
        await api.report(job.id, 'failed', `Lá»—i agent: ${err.message.substring(0, 200)}`);
      } catch { /* ignore report error */ }

      // If browser crashed, null it out so it relaunches
      if (!automation.browser) {
        log('Chrome bá»‹ crash â€” sáº½ khá»Ÿi Ä‘á»™ng láº¡i á»Ÿ job tiáº¿p theo.');
      }
    }
  }
}

main().catch(err => {
  logError(`Fatal: ${err.message}`);
  process.exit(1);
});
