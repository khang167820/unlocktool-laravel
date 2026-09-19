# 🔐 UnlockTool Password Rotation Agent — unlocktool.us

Tool Windows tự động đổi mật khẩu tài khoản UnlockTool sau khi khách hết hạn thuê.

## Yêu cầu

- **Node.js** ≥ 18 — [Tải tại đây](https://nodejs.org/)
- **Google Chrome** đã cài trên máy

## Cách dùng

### 1. Tạo mã kết nối (1 lần)

Vào Admin Panel → **Đổi Pass** → Nhấn **"🔐 Tạo mã kết nối mới"** → Copy mã.

### 2. Đưa account vào hàng đợi

Nhấn **"▶ Đưa X account vào Auto"** trên panel.

### 3. Mở tool

Nhấn đúp `start-agent.cmd` → Dán mã kết nối khi được hỏi (chỉ lần đầu).

### 4. Để tool chạy

- Tool tự poll hàng đợi mỗi 15 giây
- Tự mở Chrome → Đăng nhập → Đổi pass → Báo kết quả
- Nếu gặp Turnstile → Dừng lại, báo trên console → Bạn xác minh trên Chrome → Tool tự tiếp tục
- **Ctrl+C** để dừng tool

## Lưu ý: Chạy song song với agent thuetaikhoan.com.vn

Agent này dùng **Chrome debug port 9224** (thuetaikhoan dùng 9223), nên 2 agent có thể chạy đồng thời trên cùng máy mà không xung đột.

Tuy nhiên mỗi agent dùng **profile Chrome riêng** (thư mục `chrome-profile/`), nên cần đăng nhập Google riêng cho agent này ở lần đầu tiên.

## Bảo mật

- Mã kết nối lưu trong `agent-config.json` (đã gitignore)
- Chrome profile riêng trong `chrome-profile/` (đã gitignore)
- Mật khẩu không hiển thị trên console
- Tool **KHÔNG vượt CAPTCHA/Turnstile** — chờ bạn tự xác minh

## Cấu hình nâng cao (agent-config.json)

```json
{
  "apiBaseUrl": "https://unlocktool.us",
  "token": "your-token-here",
  "chromePath": "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"
}
```
