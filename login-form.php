<?php if (!isset($is_auth)) exit; ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - MiniFileServer</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:#1e293b;padding:40px;border-radius:12px;border:1px solid #334155;width:90%;max-width:380px}
.login-box h1{text-align:center;color:#38bdf8;font-size:22px;margin-bottom:5px}
.login-box .sub{text-align:center;color:#64748b;font-size:13px;margin-bottom:24px}
.login-box .icon{text-align:center;font-size:50px;margin-bottom:10px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;color:#94a3b8;margin-bottom:5px;font-weight:500}
.form-group input{width:100%;padding:10px 14px;background:#0f172a;border:1px solid #334155;border-radius:8px;color:#e2e8f0;font-size:15px;outline:none}
.form-group input:focus{border-color:#0ea5e9}
.btn{display:block;width:100%;padding:11px;border-radius:8px;border:none;cursor:pointer;font-size:15px;font-weight:500;text-align:center;background:#0ea5e9;color:#fff;transition:background .2s}
.btn:hover{background:#0284c7}
.msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;text-align:center}
.msg.error{background:#450a0a;color:#fca5a5;border:1px solid #7f1d1d}
</style>
</head>
<body>
<div class="login-box">
<div class="icon">🔒</div>
<h1>MiniFileServer</h1>
<p class="sub">Masukkan password admin</p>
<?php if ($msg): ?><div class="msg <?=$msg_type?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<form method="post">
<div class="form-group"><label>Password</label><input type="password" name="pass" required autofocus></div>
<button type="submit" name="login" class="btn">Login</button>
</form>
</div>
</body>
</html>
