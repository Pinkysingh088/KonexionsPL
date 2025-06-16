<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
  <title>Login</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />
  <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      height: 100vh;
      background: linear-gradient(135deg, #003366, #4e5b61, #1abc9c);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-container {
      position: relative;
      width: 80%;
      max-width: 1000px;
      height: 500px;
      display: flex;
      overflow: hidden;
    }

    .login-box {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      transition: transform 0.5s ease-in-out;
      flex: 1;
    }

    .login-box:nth-child(2) {
      transform: translateX(100%);
    }

    .login-form {
      display: flex;
      flex-direction: column;
      width: 100%;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 26px;
      color: #003366;
    }

    .input-group {
      margin-bottom: 20px;
      width: 100%;
    }

    .input-group label {
      font-size: 14px;
      color: #34495e;
      margin-bottom: 8px;
    }

    .input-wrapper {
      position: relative;
      width: 100%;
    }

    .input-wrapper i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #1abc9c;
    }

    .input-wrapper input {
      width: 81%;
      padding: 12px 40px;
      font-size: 16px;
      border-radius: 5px;
      border: 1px solid #ddd;
      outline: none;
      transition: border-color 0.3s;
    }

    .input-wrapper input:focus {
      border-color: #1abc9c;
    }

    button {
      padding: 12px;
      font-size: 16px;
      background-color: #003366;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    button:hover {
      background-color: #1abc9c;
    }

    .error-message {
      color: red;
      font-size: 14px;
      text-align: center;
      margin-top: 10px;
    }

    .toggle-btn {
      margin-top: 20px;
      font-size: 14px;
      color: #003366;
      cursor: pointer;
      transition: color 0.3s;
    }

    .toggle-btn:hover {
      color: #1abc9c;
    }

    /* Logo Style */
    .login-box img {
      width: 120px; /* Adjust as needed */
      height: auto;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <!-- Admin Login -->
    <div class="login-box">
      <!-- Logo added here -->
      <img src="/KonexionsPL/photos/kon_logo.png" alt="Logo" />
      <h2>Admin Login</h2>
      <form class="login-form" method="POST" action="admin_login.php" onsubmit="return validateLogin('admin')">
        <div class="input-group">
          <label for="admin_username">Username</label>
          <div class="input-wrapper">
            <i class="fa fa-user"></i>
            <input type="text" id="admin_username" name="username" placeholder="Enter your username" required />
          </div>
        </div>
        <div class="input-group">
          <label for="admin_password">Password</label>
          <div class="input-wrapper">
            <i class="fa fa-lock"></i>
            <input type="password" id="admin_password" name="password" placeholder="Enter your password" required />
          </div>
        </div>
        <button type="submit">Login</button>
        <p class="error-message" id="error-msg"></p>
      </form>
      <div class="toggle-btn" onclick="toggleForm('user')">Switch to User Login</div>
    </div>

    <!-- User Login -->
    <div class="login-box">
      <!-- Logo added here as well -->
      <img src="/KonexionsPL/photos/kon_logo.png" alt="Logo" />
      <h2>User Login</h2>
      <form class="login-form" method="POST" action="user_login.php" onsubmit="return validateLogin('user')">
        <div class="input-group">
          <label for="user_username">Username</label>
          <div class="input-wrapper">
            <i class="fa fa-user"></i>
            <input type="text" id="user_username" name="username" placeholder="Enter your username" required />
          </div>
        </div>
        <div class="input-group">
          <label for="user_password">Password</label>
          <div class="input-wrapper">
            <i class="fa fa-lock"></i>
            <input type="password" id="user_password" name="password" placeholder="Enter your password" required />
          </div>
        </div>
        <button type="submit">Login</button>
        <p class="error-message" id="error-msg"></p>
      </form>
      <div class="toggle-btn" onclick="toggleForm('admin')">Switch to Admin Login</div>
    </div>
  </div>

  <script>
    function toggleForm(type) {
      const loginBoxes = document.querySelectorAll('.login-box');
      if (type === 'admin') {
        loginBoxes[0].style.transform = 'translateX(0)';
        loginBoxes[1].style.transform = 'translateX(100%)';
      } else {
        loginBoxes[0].style.transform = 'translateX(-100%)';
        loginBoxes[1].style.transform = 'translateX(0)';
      }
    }

    function validateLogin(type) {
      const username = type === 'admin' ? document.getElementById('admin_username').value : document.getElementById('user_username').value;
      const password = type === 'admin' ? document.getElementById('admin_password').value : document.getElementById('user_password').value;

      if (username === '' || password === '') {
        document.getElementById('error-msg').textContent = 'Please fill out both fields.';
        return false;
      }
      return true;
    }
  </script>
</body>
</html>
