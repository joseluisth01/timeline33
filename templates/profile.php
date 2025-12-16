<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Timeline</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            <?php if ($current_user->role === 'cliente'): ?>
            background: #ffffff;
            color: #000000;
            <?php else: ?>
            background: #0a0a0a;
            color: #ffffff;
            <?php endif; ?>
        }

        .navbar {
            <?php if ($current_user->role === 'cliente'): ?>
            background: #000000;
            border-bottom: 1px solid #e0e0e0;
            <?php else: ?>
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            <?php endif; ?>
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 9999;
        }

        .navbar-brand {
            font-size: 18px;
            font-weight: <?php echo $current_user->role === 'cliente' ? '600' : '300'; ?>;
            letter-spacing: <?php echo $current_user->role === 'cliente' ? '2px' : '3px'; ?>;
            text-transform: uppercase;
            color: <?php echo $current_user->role === 'cliente' ? '#fff' : 'rgba(255, 255, 255, 0.9)'; ?>;
            z-index: 1001;
        }

        <?php if ($current_user->role === 'cliente'): ?>
        .navbar-brand img {
            height: 50px;
            width: auto;
        }
        <?php endif; ?>

        .navbar-menu {
            display: flex;
            gap: <?php echo $current_user->role === 'cliente' ? '30px' : '40px'; ?>;
            align-items: center;
        }

        .navbar-menu a {
            <?php if ($current_user->role === 'cliente'): ?>
            color: #ffffff;
            <?php else: ?>
            color: rgba(255, 255, 255, 0.6);
            <?php endif; ?>
            text-decoration: none;
            font-size: 12px;
            letter-spacing: <?php echo $current_user->role === 'cliente' ? '1.5px' : '2px'; ?>;
            text-transform: uppercase;
            transition: color 0.3s;
            font-weight: <?php echo $current_user->role === 'cliente' ? '500' : '300'; ?>;
            white-space: nowrap;
        }

        .navbar-menu a:hover,
        .navbar-menu a.active {
            <?php if ($current_user->role === 'cliente'): ?>
            color: #ffffff;
            <?php else: ?>
            color: rgba(200, 150, 100, 0.9);
            <?php endif; ?>
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-size: <?php echo $current_user->role === 'cliente' ? '14px' : '13px'; ?>;
            font-weight: <?php echo $current_user->role === 'cliente' ? '500' : '300'; ?>;
            color: <?php echo $current_user->role === 'cliente' ? '#ffffff' : 'rgba(255, 255, 255, 0.9)'; ?>;
            letter-spacing: 1px;
        }

        .user-role {
            font-size: 10px;
            color: <?php echo $current_user->role === 'cliente' ? '#cccccc' : 'rgba(255, 255, 255, 0.4)'; ?>;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid <?php echo $current_user->role === 'cliente' ? '#ffffff' : 'rgba(255, 255, 255, 0.2)'; ?>;
            color: <?php echo $current_user->role === 'cliente' ? '#ffffff' : 'rgba(255, 255, 255, 0.7)'; ?>;
            padding: 8px 20px;
            font-size: 11px;
            letter-spacing: <?php echo $current_user->role === 'cliente' ? '1.5px' : '2px'; ?>;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 300;
            white-space: nowrap;
        }

        .btn-logout:hover {
            <?php if ($current_user->role === 'cliente'): ?>
            background: #ffffff;
            color: #000000;
            <?php else: ?>
            border-color: rgba(200, 150, 100, 0.5);
            color: rgba(200, 150, 100, 0.9);
            <?php endif; ?>
        }

        /* Hamburger Menu */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            z-index: 2001;
            padding: 5px;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 2px;
            background: <?php echo $current_user->role === 'cliente' ? '#ffffff' : 'rgba(255, 255, 255, 0.8)'; ?>;
            transition: all 0.3s;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(7px, 7px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(3px, -3px);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 40px;
        }

        .page-header {
            margin-bottom: 50px;
        }

        .page-header h1 {
            font-size: 42px;
            font-weight: 200;
            color: <?php echo $current_user->role === 'cliente' ? '#000' : 'rgba(255, 255, 255, 0.95)'; ?>;
            letter-spacing: 1px;
        }

        .alert {
            padding: 15px 25px;
            margin-bottom: 40px;
            font-size: 12px;
            border-left: 1px solid;
            <?php if ($current_user->role === 'cliente'): ?>
            background: #f8f9fa;
            <?php else: ?>
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            <?php endif; ?>
            letter-spacing: 0.5px;
        }

        .alert-success {
            color: <?php echo $current_user->role === 'cliente' ? '#28a745' : 'rgba(81, 207, 102, 0.9)'; ?>;
            border-color: <?php echo $current_user->role === 'cliente' ? '#28a745' : 'rgba(81, 207, 102, 0.5)'; ?>;
        }

        .alert-error {
            color: <?php echo $current_user->role === 'cliente' ? '#dc3545' : 'rgba(255, 107, 107, 0.9)'; ?>;
            border-color: <?php echo $current_user->role === 'cliente' ? '#dc3545' : 'rgba(255, 107, 107, 0.5)'; ?>;
        }

        .card {
            <?php if ($current_user->role === 'cliente'): ?>
            background: #ffffff;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            <?php else: ?>
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            <?php endif; ?>
            padding: 50px 40px;
            margin-bottom: 30px;
        }

        .card h2 {
            font-size: 20px;
            font-weight: 300;
            color: <?php echo $current_user->role === 'cliente' ? '#000' : 'rgba(255, 255, 255, 0.9)'; ?>;
            margin-bottom: 30px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .info-row {
            display: flex;
            padding: 20px 0;
            border-bottom: 1px solid <?php echo $current_user->role === 'cliente' ? '#f0f0f0' : 'rgba(255, 255, 255, 0.05)'; ?>;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            width: 180px;
            font-size: 11px;
            color: <?php echo $current_user->role === 'cliente' ? '#999' : 'rgba(255, 255, 255, 0.5)'; ?>;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 300;
        }

        .info-value {
            flex: 1;
            font-size: 14px;
            color: <?php echo $current_user->role === 'cliente' ? '#333' : 'rgba(255, 255, 255, 0.8)'; ?>;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 35px;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: <?php echo $current_user->role === 'cliente' ? '#666' : 'rgba(255, 255, 255, 0.6)'; ?>;
            font-weight: 300;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 0;
            <?php if ($current_user->role === 'cliente'): ?>
            background: #ffffff;
            border: none;
            border-bottom: 1px solid #e0e0e0;
            color: #000;
            <?php else: ?>
            background: transparent;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            <?php endif; ?>
            font-size: 14px;
            font-weight: 300;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-bottom-color: <?php echo $current_user->role === 'cliente' ? '#FDC425' : 'rgba(200, 150, 100, 0.6)'; ?>;
        }

        .help-text {
            font-size: 10px;
            color: <?php echo $current_user->role === 'cliente' ? '#999' : 'rgba(255, 255, 255, 0.3)'; ?>;
            margin-top: 8px;
            letter-spacing: 1px;
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            <?php if ($current_user->role === 'cliente'): ?>
            background: #FDC425;
            color: #000;
            border: 1px solid #FDC425;
            <?php else: ?>
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            <?php endif; ?>
            font-size: 11px;
            font-weight: 400;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-primary:hover {
            <?php if ($current_user->role === 'cliente'): ?>
            background: #e5b01f;
            <?php else: ?>
            background: rgba(200, 150, 100, 0.15);
            border-color: rgba(200, 150, 100, 0.5);
            <?php endif; ?>
        }

        @media (max-width: 768px) {

            .navbar {
                padding: 15px 20px;
            }

            .profile {
                display: block !important;
                margin-top: 40px;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .navbar-menu {
                position: fixed;
                top: 90px;
                right: -100%;
                width: 280px;
                height: 100vh;
                display: none;
                <?php if ($current_user->role === 'cliente'): ?>
                background: rgba(0, 0, 0, 0.98);
                border-left: 1px solid #333;
                <?php else: ?>
                background: rgba(10, 10, 10, 0.98);
                backdrop-filter: blur(20px);
                border-left: 1px solid rgba(255, 255, 255, 0.1);
                <?php endif; ?>
                flex-direction: column;
                padding: 80px 30px 30px;
                gap: 25px;
                align-items: flex-start;
                transition: right 0.4s ease;
                z-index: 2000;
                overflow-y: auto;
            }

            .navbar-menu.active {
                right: 0;
                display: flex;
            }

            .navbar-menu a {
                font-size: 14px;
                width: 100%;
                padding: 10px 0;
                border-bottom: 1px solid <?php echo $current_user->role === 'cliente' ? '#333' : 'rgba(255, 255, 255, 0.05)'; ?>;
                <?php if ($current_user->role === 'cliente'): ?>
                color: #ffffff;
                <?php endif; ?>
            }

            .navbar-user {
                position: fixed;
                bottom: 0;
                top: 0px;
                right: -100%;
                width: 280px;
                display: none;
                <?php if ($current_user->role === 'cliente'): ?>
                background: rgba(0, 0, 0, 0.98);
                border-left: 1px solid #333;
                border-top: 1px solid #333;
                <?php else: ?>
                background: rgba(15, 15, 15, 0.98);
                backdrop-filter: blur(20px);
                border-left: 1px solid rgba(255, 255, 255, 0.1);
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                <?php endif; ?>
                flex-direction: column;
                padding: 25px 30px;
                gap: 20px;
                align-items: stretch;
                transition: right 0.4s ease;
                z-index: 2000;
            }

            .navbar-user.active {
                right: 0;
                display: block;
            }

            .user-info {
                text-align: left;
                padding-bottom: 15px;
                border-bottom: 1px solid <?php echo $current_user->role === 'cliente' ? '#333' : 'rgba(255,255,255,0.1)'; ?>;
            }

            .user-name {
                font-size: 15px;
                <?php if ($current_user->role === 'cliente'): ?>
                color: #ffffff;
                <?php endif; ?>
            }

            .user-role {
                font-size: 11px;
                margin-top: 5px;
                <?php if ($current_user->role === 'cliente'): ?>
                color: #cccccc;
                <?php endif; ?>
            }

            .btn-logout {
                width: 100%;
                padding: 12px 20px;
                text-align: center;
                <?php if ($current_user->role === 'cliente'): ?>
                color: #ffffff;
                border-color: #ffffff;
                <?php endif; ?>
            }

            .container {
                padding: 40px 20px;
            }

            .page-header h1 {
                font-size: 32px;
            }

            .card {
                padding: 30px 20px;
            }

            .info-row {
                flex-direction: column;
                gap: 10px;
            }

            .info-label {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <?php if ($current_user->role === 'cliente'): ?>
            <div class="navbar-brand">
                <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/logo-bebuilt-blanco.png" alt="BEBUILT">
            </div>
        <?php else: ?>
            <div class="navbar-brand"><img style="height:35px" src="https://www.bebuilt.es/wp-content/uploads/2025/12/logo-bebuilt-blanco.png" alt=""></div>
        <?php endif; ?>

        <!-- Hamburger Menu -->
        <div class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="navbar-menu" id="navbarMenu">
            <?php if ($current_user->role === 'cliente'): ?>
                <a href="<?php echo home_url('/timeline-mis-proyectos'); ?>">Mis Proyectos</a>
                <a href="<?php echo home_url('/timeline-perfil'); ?>" class="active">Mi Perfil</a>
            <?php else: ?>
                <a href="<?php echo home_url('/timeline-dashboard'); ?>">Dashboard</a>
                <?php if ($current_user->role === 'super_admin' || $current_user->role === 'administrador'): ?>
                    <a href="<?php echo home_url('/timeline-proyectos'); ?>">Proyectos</a>
                    <a href="<?php echo home_url('/timeline-usuarios'); ?>">Usuarios</a>
                    <a href="<?php echo home_url('/timeline-audit-log'); ?>">Auditoría</a>
                <?php endif; ?>
                <a href="<?php echo home_url('/timeline-perfil'); ?>" class="active">Perfil</a>
            <?php endif; ?>
        </div>

        <div class="navbar-user" id="navbarUser">
            <div class="user-info">
                <div class="user-name"><?php echo esc_html($current_user->username); ?></div>
                <div class="user-role">
                    <?php
                    switch ($current_user->role) {
                        case 'super_admin':
                            echo 'Super Administrador';
                            break;
                        case 'administrador':
                            echo 'Administrador';
                            break;
                        case 'cliente':
                            echo 'Cliente';
                            break;
                    }
                    ?>
                </div>

            </div>
            <a href="<?php echo home_url('/timeline-logout'); ?>" class="btn-logout">Salir</a>
            <div class="profile">
                <?php if ($current_user->role === 'cliente'): ?>
                    <a href="<?php echo home_url('/timeline-mis-proyectos'); ?>">Mis Proyectos</a><br><br><br>
                    <a href="<?php echo home_url('/timeline-perfil'); ?>" class="active">Mi Perfil</a>
                <?php endif; ?>
            </div>
            <style>
                .profile {
                    display: none;
                }

                .profile a {
                    <?php if ($current_user->role === 'cliente'): ?>
                    color: #ffffff;
                    <?php else: ?>
                    color: #666;
                    <?php endif; ?>
                    text-decoration: none;
                    font-size: 12px;
                    letter-spacing: 1.5px;
                    text-transform: uppercase;
                    transition: color 0.3s;
                    font-weight: 500;
                    white-space: nowrap;
                    font-size: 14px;
                    width: 100%;
                    padding: 10px 0;
                    border-bottom: 1px solid <?php echo $current_user->role === 'cliente' ? '#333' : '#f0f0f0'; ?>;
                }
            </style>
        </div>
    </nav>


    <div class="container">
        <div class="page-header">
            <h1>Mi Perfil</h1>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'password_changed'): ?>
            <div class="alert alert-success">
                Contraseña cambiada correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                switch ($_GET['error']) {
                    case 'current_password':
                        echo 'La contraseña actual es incorrecta.';
                        break;
                    case 'match':
                        echo 'Las contraseñas no coinciden.';
                        break;
                    case 'length':
                        echo 'La contraseña debe tener al menos 8 caracteres.';
                        break;
                    case 'failed':
                        echo 'Error al cambiar la contraseña. Intenta de nuevo.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Información Personal</h2>

            <div class="info-row">
                <div class="info-label">Usuario</div>
                <div class="info-value"><?php echo esc_html($current_user->username); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo esc_html($current_user->email); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">Rol</div>
                <div class="info-value">
                    <?php
                    switch ($current_user->role) {
                        case 'super_admin':
                            echo 'Super Administrador';
                            break;
                        case 'administrador':
                            echo 'Administrador';
                            break;
                        case 'cliente':
                            echo 'Cliente';
                            break;
                    }
                    ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">Miembro desde</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($current_user->created_at)); ?></div>
            </div>
        </div>

        <div class="card">
            <h2>Cambiar Contraseña</h2>

            <form method="POST" action="<?php echo home_url('/timeline-action/change-password'); ?>">
                <input type="hidden" name="action" value="timeline_change_password">
                <?php wp_nonce_field('timeline_change_password', 'timeline_change_password_nonce'); ?>

                <div class="form-group">
                    <label for="current_password">Contraseña Actual</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <div class="help-text">Mínimo 8 caracteres</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Nueva Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn-primary">Cambiar Contraseña</button>
            </form>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const toggle = document.getElementById('mobileMenuToggle');
            const menu = document.getElementById('navbarMenu');
            const user = document.getElementById('navbarUser');

            toggle.classList.toggle('active');
            menu.classList.toggle('active');
            user.classList.toggle('active');
        }

        // Cerrar menú al hacer clic en un enlace (en móvil)
        document.querySelectorAll('.navbar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    toggleMobileMenu();
                }
            });
        });
    </script>
</body>

</html>