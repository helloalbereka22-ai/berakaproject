<!DOCTYPE html>
<html>
<head>
    <title>Login Link Test</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .link {
            display: block;
            padding: 15px 20px;
            margin: 10px 0;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            transition: all 0.2s;
        }
        .link:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
        .info {
            background: #e0e7ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            font-family: monospace;
        }
        h2 { color: #4f46e5; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="test-box">
        <h1>🔐 Login Link Diagnostic</h1>
        
        <h2>Laravel Route Helper</h2>
        <div class="info">
            <strong>route('login') generates:</strong><br>
            {{ route('login') }}
        </div>
        <a href="{{ route('login') }}" class="link">
            ✅ Click to Test Login (Route Helper)
        </a>

        <h2>Direct URL</h2>
        <div class="info">
            <strong>Hard-coded URL:</strong><br>
            {{ url('/login') }}
        </div>
        <a href="{{ url('/login') }}" class="link">
            ✅ Click to Test Login (URL Helper)
        </a>

        <h2>Relative Path</h2>
        <div class="info">
            <strong>Relative path:</strong><br>
            /albareka/public/login
        </div>
        <a href="/albareka/public/login" class="link">
            ✅ Click to Test Login (Hard-coded Path)
        </a>

        <h2>Environment Info</h2>
        <div class="info">
            <strong>APP_URL:</strong> {{ config('app.url') }}<br>
            <strong>Current URL:</strong> {{ url()->current() }}<br>
            <strong>Base Path:</strong> {{ url('/') }}
        </div>

        <h2>Instructions</h2>
        <p>1. Access this page at: <code>http://localhost/albareka/public/test-login-link</code></p>
        <p>2. Click each button above</p>
        <p>3. See which one successfully shows the login page</p>
        <p>4. Check your browser console (F12) for any errors</p>
    </div>
</body>
</html>
