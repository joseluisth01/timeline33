<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Timeline</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 9999;
        }
        
        .navbar-brand {
            font-size: 18px;
            font-weight: 300;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.9);
            z-index: 1001;
        }
        
        .navbar-menu {
            display: flex;
            gap: 40px;
            align-items: center;
        }
        
        .navbar-menu a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: color 0.3s;
            font-weight: 300;
            white-space: nowrap;
        }
        
        .navbar-menu a:hover,
        .navbar-menu a.active {
            color: rgba(200, 150, 100, 0.9);
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 30px;
            top: 0px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-size: 13px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 1px;
        }
        
        .user-role {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        
        .btn-logout {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.7);
            padding: 8px 20px;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 300;
            white-space: nowrap;
        }
        
        .btn-logout:hover {
            border-color: rgba(200, 150, 100, 0.5);
            color: rgba(200, 150, 100, 0.9);
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
            background: rgba(255, 255, 255, 0.8);
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px;
        }
        
        .welcome-section {
            margin-bottom: 80px;
        }
        
        .welcome-section h1 {
            font-size: 42px;
            font-weight: 200;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        
        .welcome-section p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 60px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
        }
        
        .action-card:hover {
            border-color: rgba(253, 196, 37, 0.3);
            background: rgba(255, 255, 255, 0.03);
            transform: translateY(-2px);
        }
        
        .action-card h3 {
            font-size: 16px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        
        .action-card p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 0.5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px 30px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: rgba(200, 150, 100, 0.3);
            background: rgba(255, 255, 255, 0.03);
        }
        
        .stat-title {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 300;
        }
        
        .stat-value {
            font-size: 56px;
            font-weight: 200;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.3);
            font-weight: 300;
            letter-spacing: 1px;
        }
        
        .info-section {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 50px 40px;
        }
        
        .info-section h2 {
            font-size: 24px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            letter-spacing: 1px;
        }
        
        .info-section p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.8;
            font-weight: 300;
            letter-spacing: 0.5px;
        }
        
        .info-section ul {
            list-style: none;
            margin-top: 30px;
            padding: 0;
        }
        
        .info-section li {
            padding: 12px 0;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-weight: 300;
            letter-spacing: 0.5px;
        }
        
        .info-section li:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .navbar-menu {
                position: fixed;
                top: 88px;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: rgba(10, 10, 10, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 80px 30px 30px;
                gap: 25px;
                align-items: flex-start;
                transition: right 0.4s ease;
                z-index: 2000;
                border-left: 1px solid rgba(255, 255, 255, 0.1);
                overflow-y: auto;
                display: none;
            }
            
            .navbar-menu.active {
                right: 0;
                display: flex;
            }
            
            .navbar-menu a {
                font-size: 14px;
                padding: 10px 0;
                width: 100%;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            }
            
            .navbar-user {
                position: fixed;
                bottom: 0;
                right: -100%;
                width: 280px;
                background: rgba(15, 15, 15, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 25px 30px;
                gap: 20px;
                align-items: stretch;
                transition: right 0.4s ease;
                z-index: 2000;
                border-left: 1px solid rgba(255, 255, 255, 0.1);
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                display: none;
            }
            
            .navbar-user.active {
                right: 0;
                display: block;
            }
            
            .user-info {
                text-align: left;
                padding-bottom: 15px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .user-name {
                font-size: 15px;
            }
            
            .user-role {
                font-size: 11px;
                margin-top: 5px;
            }
            
            .btn-logout {
                width: 100%;
                text-align: center;
                padding: 12px 20px;
            }
            
            .navbar-menu {
                gap: 20px;
            }
            
            .container {
                padding: 40px 20px;
            }
            
            .welcome-section h1 {
                font-size: 32px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand"><img style="height:35px" src="https://www.bebuilt.es/wp-content/uploads/2025/12/logo-bebuilt-blanco.png" alt=""></div>
        
        <!-- Hamburger Menu -->
        <div class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <div class="navbar-menu" id="navbarMenu">
            <a href="<?php echo home_url('/timeline-dashboard'); ?>" class="active">Dashboard</a>
            <?php if ($current_user && ($current_user->role === 'super_admin' || $current_user->role === 'administrador')): ?>
                <a href="<?php echo home_url('/timeline-proyectos'); ?>">Proyectos</a>
                <a href="<?php echo home_url('/timeline-usuarios'); ?>">Usuarios</a>
                <a href="<?php echo home_url('/timeline-audit-log'); ?>">Auditoría</a>
            <?php endif; ?>
            <a href="<?php echo home_url('/timeline-perfil'); ?>">Perfil</a>
        </div>
        <div class="navbar-user" id="navbarUser">
            <div class="user-info">
                <div class="user-name"><?php echo esc_html($current_user->username); ?></div>
                <div class="user-role">
                    <?php 
                        switch($current_user->role) {
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
            <a href="<?php echo home_url('/timeline-logout'); ?>" class="btn-logout">
                Salir
            </a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-section">
            <h1>Bienvenido, <?php echo esc_html($current_user->username); ?></h1>
            <p>Gestión de proyectos y seguimiento de actividades</p>
        </div>
        
        <?php if ($current_user->role === 'super_admin' || $current_user->role === 'administrador'): ?>
        <div class="quick-actions">
            <a href="<?php echo home_url('/timeline-proyecto-nuevo'); ?>" class="action-card">
                <h3>+ Nuevo Proyecto</h3>
                <p>Crear un nuevo proyecto de construcción</p>
            </a>
            
            <a href="<?php echo home_url('/timeline-proyectos'); ?>" class="action-card">
                <h3>Ver Proyectos</h3>
                <p>Gestionar todos los proyectos activos</p>
            </a>
            
            <a href="<?php echo home_url('/timeline-usuarios'); ?>" class="action-card">
                <h3>Gestionar Usuarios</h3>
                <p>Administrar clientes y permisos</p>
            </a>
            
            <a href="<?php echo home_url('/timeline-audit-log'); ?>" class="action-card">
                <h3>📊 Registro de Auditoría</h3>
                <p>Ver historial completo de actividades</p>
            </a>
        </div>
        <?php endif; ?>
        
        <?php
        // Obtener estadísticas reales
        global $wpdb;
        $table_projects = $wpdb->prefix . 'timeline_projects';
        $table_milestones = $wpdb->prefix . 'timeline_milestones';
        
        // Contar proyectos activos (en_proceso)
        $proyectos_activos = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_projects} WHERE project_status = 'en_proceso'"
        );
        
        // Contar hitos pendientes
        $hitos_pendientes = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_milestones} WHERE status = 'pendiente'"
        );
        
        // Contar proyectos completados
        $proyectos_completados = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_projects} WHERE project_status = 'finalizado'"
        );
        
        // Si el usuario es cliente, mostrar solo sus proyectos
        if ($current_user->role === 'cliente') {
            $table_project_clients = $wpdb->prefix . 'timeline_project_clients';
            
            $proyectos_activos = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_projects} p
                INNER JOIN {$table_project_clients} pc ON p.id = pc.project_id
                WHERE pc.client_id = %d AND p.project_status = 'en_proceso'",
                $current_user->id
            ));
            
            $proyectos_completados = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_projects} p
                INNER JOIN {$table_project_clients} pc ON p.id = pc.project_id
                WHERE pc.client_id = %d AND p.project_status = 'finalizado'",
                $current_user->id
            ));
            
            // Hitos pendientes de sus proyectos
            $hitos_pendientes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_milestones} m
                INNER JOIN {$table_projects} p ON m.project_id = p.id
                INNER JOIN {$table_project_clients} pc ON p.id = pc.project_id
                WHERE pc.client_id = %d AND m.status = 'pendiente'",
                $current_user->id
            ));
        }
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Proyectos Activos</div>
                <div class="stat-value"><?php echo intval($proyectos_activos); ?></div>
                <div class="stat-label">En progreso</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Hitos Pendientes</div>
                <div class="stat-value"><?php echo intval($hitos_pendientes); ?></div>
                <div class="stat-label">Por completar</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-title">Completados</div>
                <div class="stat-value"><?php echo intval($proyectos_completados); ?></div>
                <div class="stat-label">Proyectos finalizados</div>
            </div>
        </div>
        
        <div class="info-section">
            <h2>Sistema Timeline BEBUILT</h2>
            <p>Plataforma de gestión integral de proyectos de construcción con sistema completo de auditoría:</p>
            <ul>
                <li><strong>✓</strong> Gestión completa de proyectos con fechas y descripciones</li>
                <li><strong>✓</strong> Asignación de múltiples clientes por proyecto</li>
                <li><strong>✓</strong> Sistema de usuarios con 3 roles (Super Admin, Administrador, Cliente)</li>
                <li><strong>✓</strong> Timeline visual interactivo con hitos</li>
                <li><strong>✓</strong> Gestión de hitos con imágenes y estados</li>
                <li><strong>✓</strong> Área de documentos descargables</li>
                <li><strong>✓</strong> Notificaciones automáticas por email</li>
                <li><strong>✓</strong> Sistema completo de auditoría (quién, qué, cuándo, desde dónde)</li>
                <li><strong>✓</strong> Log de actividad con IP y User Agent</li>
            </ul>
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