<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#1a1a2e;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a2e;padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#16213e;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
                    {{-- Header --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea,#764ba2);padding:30px 40px;text-align:center;">
                            <h1 style="color:#fff;margin:0;font-size:24px;font-weight:700;">UNLOCKTOOL.US</h1>
                            <p style="color:rgba(255,255,255,0.8);margin:8px 0 0;font-size:14px;">Thuê tài khoản tự động 24/7</p>
                        </td>
                    </tr>

                    {{-- Success Badge --}}
                    <tr>
                        <td style="padding:30px 40px 0;text-align:center;">
                            <div style="display:inline-block;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);border-radius:12px;padding:12px 24px;">
                                <span style="color:#10b981;font-size:16px;font-weight:700;">✅ Thanh toán thành công!</span>
                            </div>
                        </td>
                    </tr>

                    {{-- Order Info --}}
                    <tr>
                        <td style="padding:24px 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(255,255,255,0.05);border-radius:12px;border:1px solid rgba(255,255,255,0.1);">
                                <tr>
                                    <td style="padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.05);">
                                        <span style="color:rgba(255,255,255,0.5);font-size:13px;">Mã đơn hàng</span><br>
                                        <strong style="color:#fff;font-size:18px;letter-spacing:1px;">{{ $order->tracking_code }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:6px 0;">
                                                    <span style="color:rgba(255,255,255,0.5);font-size:13px;">Gói thuê:</span>
                                                    <span style="color:#fff;font-size:14px;font-weight:600;float:right;">{{ $packageName }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;">
                                                    <span style="color:rgba(255,255,255,0.5);font-size:13px;">Số tiền:</span>
                                                    <span style="color:#fff;font-size:14px;font-weight:600;float:right;">{{ $formattedAmount }}</span>
                                                </td>
                                            </tr>
                                            @if($expiresAt)
                                            <tr>
                                                <td style="padding:6px 0;">
                                                    <span style="color:rgba(255,255,255,0.5);font-size:13px;">Hết hạn:</span>
                                                    <span style="color:#fbbf24;font-size:14px;font-weight:600;float:right;">{{ $expiresAt }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Account Credentials --}}
                    @if($order->account)
                    <tr>
                        <td style="padding:0 40px 24px;">
                            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.15),rgba(16,185,129,0.05));border:1px solid rgba(16,185,129,0.25);border-radius:12px;overflow:hidden;">
                                <div style="background:rgba(16,185,129,0.2);padding:14px 20px;border-bottom:1px solid rgba(16,185,129,0.15);">
                                    <span style="color:#34d399;font-size:15px;font-weight:700;">🔑 Thông tin tài khoản</span>
                                </div>
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="padding:16px 20px;border-bottom:1px solid rgba(16,185,129,0.1);">
                                            <span style="color:rgba(255,255,255,0.5);font-size:13px;">Username</span><br>
                                            <strong style="color:#fff;font-size:18px;font-family:monospace;letter-spacing:0.5px;">{{ $order->account->username }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:16px 20px;">
                                            <span style="color:rgba(255,255,255,0.5);font-size:13px;">Password</span><br>
                                            <strong style="color:#fff;font-size:18px;font-family:monospace;letter-spacing:0.5px;">{{ $order->account->password }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- Order Link --}}
                    <tr>
                        <td style="padding:0 40px 24px;text-align:center;">
                            <a href="{{ url('/order-status?orderCode=' . $order->tracking_code) }}" style="display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700;">
                                📋 Xem đơn hàng trên website
                            </a>
                        </td>
                    </tr>

                    {{-- Important Notes --}}
                    <tr>
                        <td style="padding:0 40px 24px;">
                            <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:16px 20px;">
                                <p style="color:#fbbf24;font-size:13px;font-weight:700;margin:0 0 8px;">⚠️ Lưu ý quan trọng:</p>
                                <ul style="color:rgba(255,255,255,0.6);font-size:12px;margin:0;padding-left:16px;line-height:1.8;">
                                    <li>Thời gian thuê tính real-time từ lúc nhận tài khoản (tính cả khi tắt máy)</li>
                                    <li>Không đổi PC trong cùng 1 phiên thuê (quy định của UnlockTool)</li>
                                    <li>Hỗ trợ kỹ thuật: <a href="https://zalo.me/0777333763" style="color:#60a5fa;">Zalo 0777333763</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:rgba(0,0,0,0.2);padding:20px 40px;text-align:center;">
                            <p style="color:rgba(255,255,255,0.4);font-size:12px;margin:0;">
                                UNLOCKTOOL.US — Thuê tài khoản tự động 24/7<br>
                                Zalo: 0799161640 | 0777333763
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
