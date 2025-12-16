<?php

/**
 * Template: Gestión de Proyectos (Solo Admin/Super Admin)
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proyectos - Timeline</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
            max-width: 1600px;
            margin: 0 auto;
            padding: 60px 40px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
        }

        .page-header h1 {
            font-size: 42px;
            font-weight: 200;
            color: rgba(255, 255, 255, 0.95);
            letter-spacing: 1px;
        }

        .btn-primary {
            padding: 14px 30px;
            background: rgba(253, 196, 37, 0.15);
            color: #FDC425;
            border: 1px solid #FDC425;
            font-size: 11px;
            font-weight: 400;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background: rgba(253, 196, 37, 0.25);
        }

        .alert {
            padding: 15px 25px;
            margin-bottom: 40px;
            font-size: 12px;
            border-left: 1px solid;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            letter-spacing: 0.5px;
        }

        .alert-success {
            color: rgba(81, 207, 102, 0.9);
            border-color: rgba(81, 207, 102, 0.5);
        }

        .alert-error {
            color: rgba(255, 107, 107, 0.9);
            border-color: rgba(255, 107, 107, 0.5);
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .project-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s;
            overflow: hidden;
        }

        .project-card:hover {
            border-color: rgba(253, 196, 37, 0.3);
            background: rgba(255, 255, 255, 0.03);
        }

        .project-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        .project-content {
            padding: 30px;
        }

        .project-title {
            font-size: 20px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .project-address {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }

        .project-dates {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .project-date {
            flex: 1;
        }

        .project-date-label {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 5px;
        }

        .project-date-value {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 300;
        }

        .project-clients {
            margin-bottom: 20px;
        }

        .project-clients-label {
            font-size: 9px;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }

        .client-tag {
            display: inline-block;
            padding: 6px 12px;
            background: rgba(253, 196, 37, 0.1);
            color: #FDC425;
            font-size: 10px;
            letter-spacing: 1px;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .project-actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            flex: 1;
            padding: 10px;
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: flex;
    align-items: center;
        }

        .btn-small:hover {
            border-color: rgba(253, 196, 37, 0.5);
            color: #FDC425;
        }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            color: rgba(255, 255, 255, 0.3);
        }

        .empty-state p {
            font-size: 14px;
            letter-spacing: 1px;
            margin-bottom: 30px;
        }

        .project-status-badge {
            display: inline-block;
            padding: 8px 16px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .project-status-badge.en_proceso {
            background: rgba(253, 196, 37, 0.15);
            color: #FDC425;
            border: 1px solid rgba(253, 196, 37, 0.3);
        }

        .project-status-badge.pendiente {
            background: rgba(237, 237, 237, 0.1);
            color: #EDEDED;
            border: 1px solid rgba(237, 237, 237, 0.2);
        }

        .project-status-badge.finalizado {
            background: rgba(255, 222, 136, 0.15);
            color: #FFDE88;
            border: 1px solid rgba(255, 222, 136, 0.3);
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
                top: 0px;
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

            .container {
                padding: 40px 20px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .page-header h1 {
                font-size: 32px;
            }

            .projects-grid {
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
            <a href="<?php echo home_url('/timeline-dashboard'); ?>">Dashboard</a>
            <a href="<?php echo home_url('/timeline-proyectos'); ?>" class="active">Proyectos</a>
            <a href="<?php echo home_url('/timeline-usuarios'); ?>">Usuarios</a>
            <a href="<?php echo home_url('/timeline-audit-log'); ?>">Auditoría</a>
            <a href="<?php echo home_url('/timeline-perfil'); ?>">Perfil</a>
        </div>

        <div class="navbar-user" id="navbarUser">
            <div class="user-info">
                <div class="user-name"><?php echo esc_html($current_user->username); ?></div>
                <div class="user-role">
                    <?php echo $current_user->role === 'super_admin' ? 'Super Administrador' : 'Administrador'; ?>
                </div>
            </div>
            <a href="<?php echo home_url('/timeline-logout'); ?>" class="btn-logout">
                Salir
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Proyectos</h1>
            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                <select id="client-filter" style="padding: 12px 40px 12px 20px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); color: #fff; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; cursor: pointer; appearance: none; background-image: url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 12 12\'%3E%3Cpath fill=\'rgba(255,255,255,0.4)\' d=\'M6 9L1 4h10z\'/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 15px center; background-color: rgba(0,0,0,0.3);">
                    <option value="all">Todos los proyectos</option>
                    <option value="unassigned">Sin clientes asignados</option>
                    <?php
                    if ($projects_class) {
                        $all_clients = $projects_class->get_available_clients();
                        foreach ($all_clients as $client) {
                            echo '<option value="' . esc_attr($client->id) . '">' . esc_html($client->username) . '</option>';
                        }
                    }
                    ?>
                </select>
                <a href="<?php echo home_url('/timeline-proyecto-nuevo'); ?>" class="btn-primary">+ Nuevo Proyecto</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'created':
                        echo 'Proyecto creado correctamente.';
                        break;
                    case 'updated':
                        echo 'Proyecto actualizado correctamente.';
                        break;
                    case 'deleted':
                        echo 'Proyecto eliminado correctamente.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                Error al procesar la solicitud.
            </div>
        <?php endif; ?>

        <div class="projects-grid">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $project): ?>
                    <?php
                    $clients = $projects_class->get_project_clients($project->id);
                    $image = $project->featured_image ? $project->featured_image : 'https://via.placeholder.com/400x250/1a1a1a/666666?text=Sin+Imagen';

                    $status_labels = [
                        'en_proceso' => 'EN PROCESO',
                        'pendiente' => 'PENDIENTE',
                        'finalizado' => 'FINALIZADO'
                    ];

                    $project_status = isset($project->project_status) ? $project->project_status : 'en_proceso';
                    $status_label = isset($status_labels[$project_status]) ? $status_labels[$project_status] : 'EN PROCESO';
                    $status_class = $project_status;
                    ?>
                    <div class="project-card">
                        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($project->name); ?>" class="project-image">
                        <div class="project-content">
                            <span class="project-status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_label); ?>
                            </span>
                            <h3 class="project-title"><?php echo esc_html($project->name); ?></h3>
                            <p class="project-address"><?php echo esc_html($project->address); ?></p>

                            <div class="project-dates">
                                <div class="project-date">
                                    <div class="project-date-label">Inicio</div>
                                    <div class="project-date-value"><?php echo date('d/m/Y', strtotime($project->start_date)); ?></div>
                                </div>
                                <div class="project-date">
                                    <div class="project-date-label">Fin Previsto</div>
                                    <div class="project-date-value"><?php echo date('d/m/Y', strtotime($project->end_date)); ?></div>
                                </div>
                            </div>

                            <?php if (!empty($clients)): ?>
                                <div class="project-clients">
                                    <div class="project-clients-label">Clientes Asignados</div>
                                    <?php
                                    $client_ids = array();
                                    foreach ($clients as $client):
                                        $client_ids[] = $client->id;
                                    ?>
                                        <span class="client-tag"><?php echo esc_html($client->username); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php $client_ids_str = !empty($client_ids) ? implode(',', $client_ids) : 'none'; ?>
                            <?php else: ?>
                                <?php $client_ids_str = 'none'; ?>
                            <?php endif; ?>

                            <div class="project-actions">
                                <a href="<?php echo home_url('/timeline-proyecto-admin/' . $project->id); ?>" class="btn-small">Timeline</a>
                                <a href="<?php echo home_url('/timeline-documentos/' . $project->id); ?>" class="btn-small">Documentos</a>
                                <a href="<?php echo home_url('/timeline-proyecto-vista-previa/' . $project->id); ?>" class="btn-small" style="background: rgba(253, 196, 37, 0.1); border-color: rgba(253, 196, 37, 0.3); color: #FDC425;">👁️ Vista Previa</a>
                                <a href="<?php echo home_url('/timeline-proyecto-editar/' . $project->id); ?>" class="btn-small">Editar</a>
                                <button class="btn-small btn-delete" onclick="deleteProject(<?php echo $project->id; ?>, '<?php echo esc_js($project->name); ?>')">Eliminar</button>
                            </div>
                        </div>
                        <input type="hidden" class="project-clients-data" value="<?php echo esc_attr($client_ids_str); ?>">
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No hay proyectos creados</p>
                    <a href="<?php echo home_url('/timeline-proyecto-nuevo'); ?>" class="btn-primary">Crear Primer Proyecto</a>
                </div>
            <?php endif; ?>
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

        // Filtro de proyectos por cliente
        document.getElementById('client-filter').addEventListener('change', function() {
            const filterValue = this.value;
            const projectCards = document.querySelectorAll('.project-card');

            projectCards.forEach(card => {
                const clientsData = card.querySelector('.project-clients-data');
                if (!clientsData) return;

                const clientIds = clientsData.value;

                if (filterValue === 'all') {
                    card.style.display = '';
                } else if (filterValue === 'unassigned') {
                    card.style.display = (clientIds === 'none') ? '' : 'none';
                } else {
                    if (clientIds === 'none') {
                        card.style.display = 'none';
                    } else {
                        const hasClient = clientIds.split(',').includes(filterValue);
                        card.style.display = hasClient ? '' : 'none';
                    }
                }
            });
        });

        // NUEVA FUNCIÓN: Eliminar proyecto
        function deleteProject(projectId, projectName) {
            const confirmMessage = `¿Estás seguro de eliminar el proyecto "${projectName}"?\n\n` +
                `⚠️ ESTA ACCIÓN ELIMINARÁ:\n` +
                `• Todos los hitos del proyecto\n` +
                `• Todas las imágenes de los hitos\n` +
                `• Todos los documentos del proyecto\n` +
                `• Asignaciones de clientes\n\n` +
                `Esta acción NO se puede deshacer.`;

            if (confirm(confirmMessage)) {
                window.location.href = '<?php echo home_url('/timeline-action/delete-project'); ?>?project_id=' + projectId + '&_wpnonce=<?php echo wp_create_nonce('timeline_delete_project'); ?>';
            }
        }
    </script>
</body>

</html>