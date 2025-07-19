<?php
session_start();
require_once 'php/config.php';

// Kullanıcı zaten giriş yapmışsa admin paneline yönlendir
if(isset($_SESSION['user_id'])) {
    header("Location: admin-panel.php");
    exit();
}

// Giriş formu gönderildiyse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if($user && password_verify($password, $user['sifre'])) {
            // Giriş başarılı
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ad_soyad'];
            $_SESSION['user_role'] = $user['yetki_seviyesi'];
            
            // Son giriş tarihini güncelle
            $update = $db->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?");
            $update->execute([$user['id']]);
            
            header("Location: admin-panel.php");
            exit();
        } else {
            $error = "Geçersiz kullanıcı adı veya şifre!";
        }
    } catch(PDOException $e) {
        $error = "Giriş işlemi sırasında bir hata oluştu: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ÇAKRA Admin Giriş</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-container {
      max-width: 450px;
      width: 100%;
      margin: 20px;
      background: white;
      border-radius: 25px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.2);
      overflow: hidden;
      animation: slideIn 0.6s ease-out;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-header {
      background: linear-gradient(135deg, #2c3e50, #34495e);
      color: white;
      padding: 40px 30px;
      text-align: center;
    }

    .login-header h3 {
      margin: 0;
      font-size: 2.2rem;
      font-weight: 300;
      margin-bottom: 10px;
    }

    .login-header p {
      margin: 0;
      opacity: 0.9;
      font-size: 16px;
    }

    .login-body {
      padding: 40px;
    }

    .form-control {
      border-radius: 15px;
      border: 2px solid #e0e6ed;
      padding: 18px 20px;
      font-size: 16px;
      transition: all 0.3s ease;
      background: #f8f9ff;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
      background: white;
    }

    .form-label {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 12px;
      font-size: 15px;
    }

    .btn-login {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      border-radius: 15px;
      padding: 18px 30px;
      font-size: 18px;
      font-weight: 600;
      color: white;
      width: 100%;
      transition: all 0.3s ease;
      margin-top: 20px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
      color: white;
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .error-message {
      background: linear-gradient(135deg, #ffe6e6, #ffcccc);
      color: #721c24;
      padding: 15px 20px;
      border-radius: 15px;
      margin-top: 20px;
      text-align: center;
      font-weight: 500;
      border: 2px solid #f8d7da;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 20%, 40%, 60%, 80%, 100% {
        transform: translateX(0);
      }
      10%, 30%, 50%, 70%, 90% {
        transform: translateX(-5px);
      }
    }

    .login-icon {
      font-size: 60px;
      margin-bottom: 20px;
      display: block;
      opacity: 0.9;
    }

    .input-group {
      position: relative;
      margin-bottom: 25px;
    }

    .input-icon {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #6c757d;
      z-index: 2;
    }

    .input-with-icon {
      padding-left: 55px;
    }

    .brand-subtitle {
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .form-floating {
      position: relative;
    }

    .loading-spinner {
      display: none;
      margin-left: 10px;
    }

    @media (max-width: 576px) {
      .login-container {
        margin: 10px;
        border-radius: 20px;
      }
      
      .login-header {
        padding: 30px 20px;
      }
      
      .login-body {
        padding: 30px 20px;
      }
      
      .login-header h3 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-header">
    <span class="login-icon">🔐</span>
    <h3>ÇAKRA</h3>
    <p class="brand-subtitle">Yönetim Sistemi</p>
    <p>İş takibi ve üretim yönetimi</p>
  </div>
  
  <div class="login-body">
    <form id="loginForm" method="POST">
      <div class="input-group">
        <span class="input-icon">👤</span>
        <input type="text" class="form-control input-with-icon" id="username" name="username" placeholder="Kullanıcı adınızı girin" required>
      </div>

      <div class="input-group">
        <span class="input-icon">🔒</span>
        <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="Şifrenizi girin" required>
      </div>

      <button type="submit" class="btn btn-login">
        <span id="loginText">Giriş Yap</span>
        <div class="spinner-border spinner-border-sm loading-spinner" role="status">
          <span class="visually-hidden">Yükleniyor...</span>
        </div>
      </button>
    </form>

   <div id="errorMsg" class="error-message" style="display:none;"></div>
  </div>
</div>

<script>
document.getElementById("loginForm").addEventListener("submit", function(e) {
  e.preventDefault();
  
  const form = this;
  const user = document.getElementById("username").value.trim();
  const pass = document.getElementById("password").value.trim();
  const loginBtn = document.querySelector('.btn-login');
  const loginText = document.getElementById('loginText');
  const loadingSpinner = document.querySelector('.loading-spinner');
  const errorMsg = document.getElementById("errorMsg");
  
  // Hata mesajını gizle
  errorMsg.style.display = "none";
  
  // Loading durumu
  loginBtn.disabled = true;
  loginText.textContent = "Giriş yapılıyor...";
  loadingSpinner.style.display = "inline-block";
  
  // AJAX ile PHP'ye giriş isteği gönder
  fetch('login-process.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `username=${encodeURIComponent(user)}&password=${encodeURIComponent(pass)}`
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      loginText.textContent = "✅ Başarılı! Yönlendiriliyor...";
      loadingSpinner.style.display = "none";
      
      // Başarılı girişte yönlendirme
      setTimeout(() => {
        window.location.href = data.redirect || "admin-panel.php";
      }, 1000);
    } else {
      showError(data.message || "Geçersiz kullanıcı adı veya şifre!");
    }
  })
  .catch(error => {
    showError("İşlem sırasında bir hata oluştu: " + error.message);
  });
  
  function showError(message) {
    // Reset button
    loginBtn.disabled = false;
    loginText.textContent = "🚀 Giriş Yap";
    loadingSpinner.style.display = "none";
    
    // Show error
    errorMsg.textContent = message;
    errorMsg.style.display = "block";
    
    // Clear password field
    document.getElementById("password").value = "";
    
    // Focus on username if empty, otherwise focus on password
    if (!user) {
      document.getElementById("username").focus();
    } else {
      document.getElementById("password").focus();
    }
    
    // Hata mesajını 5 saniye sonra gizle
    setTimeout(() => {
      errorMsg.style.display = "none";
    }, 5000);
  }
});

// Input focus effects (aynı kalabilir)
document.querySelectorAll('.form-control').forEach(input => {
  input.addEventListener('focus', function() {
    this.parentElement.querySelector('.input-icon').style.color = '#667eea';
  });
  
  input.addEventListener('blur', function() {
    this.parentElement.querySelector('.input-icon').style.color = '#6c757d';
  });
});

// Enter key handling (aynı kalabilir)
document.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    const form = document.getElementById('loginForm');
    if (document.activeElement.tagName === 'INPUT') {
      form.dispatchEvent(new Event('submit'));
    }
  }
});
</script>

</body>
</html>