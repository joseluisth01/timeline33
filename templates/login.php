<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Proyectos</title>
    <?php wp_head(); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: #0a0a0a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(200,150,100,0.03) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            width: 100%;
            max-width: 420px;
            padding: 60px 50px;
            position: relative;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        
        .login-header {
            margin-bottom: 50px;
        }
        
        .login-header h1 {
            color: #ffffff;
            font-size: 32px;
            font-weight: 300;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 300;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 0;
            background: transparent;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 15px;
            font-weight: 300;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-bottom-color: rgba(200, 150, 100, 0.8);
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0;
            font-size: 13px;
            font-weight: 400;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            background: rgba(200, 150, 100, 0.15);
            border-color: rgba(200, 150, 100, 0.5);
        }
        
        .alert {
            padding: 15px 20px;
            margin-bottom: 30px;
            font-size: 13px;
            border-left: 2px solid;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .alert-error {
            color: #ff6b6b;
            border-color: #ff6b6b;
        }
        
        .alert-success {
            color: #51cf66;
            border-color: #51cf66;
        }
        
        .logo-minimal {
            position: absolute;
            top: 30px;
            left: 50px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 11px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-minimal">BEBUILT</div>
        <div class="login-header">
            <h1>Timeline</h1>
            <p>Área de proyectos</p>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php if ($_GET['error'] == 'invalid'): ?>
                    Usuario o contraseña incorrectos
                <?php elseif ($_GET['error'] == 'nonce'): ?>
                    Error de seguridad. Por favor, intenta de nuevo.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
            <div class="alert alert-success">
                Sesión cerrada correctamente
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="timeline_login">
            <?php wp_nonce_field('timeline_login', 'timeline_login_nonce'); ?>
            
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
    </div>
    <?php wp_footer(); ?>
</body>
</html>