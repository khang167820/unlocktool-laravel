<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - UnlockTool.us</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        background: #0f172a;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .login-container {
        width: 100%;
        max-width: 420px;
    }
    .login-logo {
        text-align: center;
        margin-bottom: 32px;
    }
    .login-logo-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        font-size: 28px;
    }
    .login-logo h1 {
        font-size: 24px;
        font-weight: 800;
        color: #f1f5f9;
    }
    .login-logo p {
        font-size: 14px;
        color: #64748b;
        margin-top: 4px;
    }
    .login-card {
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 20px;
        padding: 36px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 8px;
    }
    .form-input {
        width: 100%;
        padding: 12px 16px;
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 10px;
        color: #f1f5f9;
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .form-input::placeholder { color: #475569; }
    .login-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        font-family: inherit;
    }
    .login-btn:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    .alert {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .alert-error {
        background: rgba(220, 38, 38, 0.15);
        color: #ef4444;
        border: 1px solid rgba(220, 38, 38, 0.3);
    }
    .alert-success {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .login-footer {
        text-align: center;
        margin-top: 24px;
    }
    .login-footer a {
        color: #64748b;
        font-size: 13px;
        text-decoration: none;
    }
    .login-footer a:hover { color: #94a3b8; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <div class="login-logo-icon">🔐</div>
            <h1>Admin Panel</h1>
            <p>UnlockTool.us</p>
        </div>
        
        <div class="login-card">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            
            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Enter username" value="{{ old('username') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Enter password" required>
                </div>
                <button type="submit" class="login-btn">Sign In</button>
            </form>
        </div>
        
        <div class="login-footer">
            <a href="/">← Back to website</a>
        </div>
    </div>
</body>
</html>
