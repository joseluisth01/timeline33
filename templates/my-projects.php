
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
            color: #000;
        }

        /* NAVBAR */
        .navbar {
            background: black;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 9999;
            position: fixed;
            width: 100%;
        }

        .navbar-brand {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: white;
            z-index: 1001;
        }

        .navbar-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .navbar-menu a {
            color: white;
            text-decoration: none;
            font-size: 12px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            transition: color 0.3s;
            font-weight: 500;
            white-space: nowrap;
        }

        .navbar-menu a:hover,
        .navbar-menu a.active {
            color: white;
        }

        .elementor-element-8bececf{
            display: none;
        }

        .elementor-element-d4dea6a{
            display: none;
        }

        .elementor-location-footer a{
            color: black !important;
        }

        .elementor-location-header{
            display: none;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 500; color: white; }

        .btn-logout {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 8px 20px;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-logout:hover {
            background: #000;
            color: #fff;
        }

        /* ------------ HAMBURGER (idéntico al dashboard) ----------- */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            z-index: 2001;
            padding: 5px;
        }

        .mobile-menu-toggle span {
            width: 26px;
            height: 3px;
            background: white;
            transition: all 0.3s;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(7px, 7px);
        }

        .navbar-brand img{
                height: 50px;
                width: auto;
            }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(4px, -4px);
        }

        /* ------------- MENU RESPONSIVE LATERAL ---------------- */
        @media (max-width: 768px) {

            .mobile-menu-toggle { display: flex; }

            .navbar-menu {
                position: fixed;
                top: 0px;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: black;
                backdrop-filter: blur(20px);
                flex-direction: column;
                align-items: flex-start;
                padding: 80px 30px 30px;
                gap: 25px;
                transition: right 0.4s ease;
                z-index: 2000;
                border-left: 1px solid #e0e0e0;
                overflow-y: auto;
                display: none;
            }

            .navbar-menu.active { right: 0; 
            
            display: flex;
            }

            

            .navbar-menu a {
                width: 100%;
                padding: 12px 0;
                border-bottom: 1px solid #e0e0e0;
                font-size: 14px;
            }

            .navbar-user {
                position: fixed;
                bottom: 0;
                right: -100%;
                width: 280px;
                background: black;
                backdrop-filter: blur(20px);
                flex-direction: column;
                padding: 25px 30px;
                gap: 20px;
                align-items: stretch;
                transition: right 0.4s ease;
                z-index: 2000;
                border-left: 1px solid #e0e0e0;
                border-top: 1px solid #e0e0e0;
                display: none;
            }

            .navbar-user.active { right: 0; display: block;}

            .user-info {
                text-align: left;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }

            .btn-logout { width: 100%; text-align: center; padding: 12px; }
        }

        /* ------------------ CONTENIDO ------------------ */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
        }

        .intro-section {
            text-align: center;
            margin-bottom: 80px;
            margin-top: 80px;
        }

        .intro-section h1 {
            font-size: 48px;
            font-weight: 400;
            margin-bottom: 20px;
        }

        .intro-section h1 .highlight { font-weight: 700; }

        .intro-section p {
            font-size: 16px;
            color: black;
            line-height: 1.6;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(28%, 1fr));
            gap: 40px;
        }

        .project-card:first-child {
            grid-column: 1 / -1;
        }

        .project-card {
            position: relative;
            overflow: hidden;
        }

        .project-card:first-child .btn-view-project{
            margin: 0 auto;
            width: 40%;
        }

        .project-status {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #000;
            color: #fff;
            padding: 8px 16px;
            font-size: 15px;
            font-weight: 400;
            letter-spacing: 1px;
            border-radius: 10px;
            text-transform: uppercase;
        }

        .project-status.en-proceso { color: #FDC425; }
        .project-status.finalizado { color: #FFDE88; }

        .project-image {
            width: 100%;
            height: 350px;
            object-fit: cover;
            display: block;
            border-radius: 20px;
        }

        .project-content { background: #fff; text-align: center; }

        .project-title {
            font-size: 25px;
            color: #000;
            margin: 20px 0 30px;
            font-weight: bold;
        }

        .btn-view-project {
            display: block;
            background: #FDC425;
            padding: 14px 30px;
            color: #000;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-view-project:hover {
            background: black;
            color: #FDC425;
        }

        @media (max-width: 768px) {
            .container { padding: 40px 20px; }
            .intro-section h1 { font-size: 32px; }
            .projects-grid { grid-template-columns: 1fr; }

            .project-card:first-child .btn-view-project{
            margin: 0 auto;
            width: 100%;
        }
        }
    </style>


<body>

    <nav class="navbar">
        <div class="navbar-brand"><img src="https://www.bebuilt.es/wp-content/uploads/2025/12/logo-bebuilt-blanco.png" alt=""></div>

        <!-- HAMBURGER -->
        <div class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobile()">
            <span></span><span></span><span></span>
        </div>

        <div class="navbar-menu" id="navbarMenu">
            <a href="<?php echo home_url('/timeline-mis-proyectos'); ?>" class="active">Mis Proyectos</a>
            <a href="<?php echo home_url('/timeline-perfil'); ?>">Mi Perfil</a>
        </div>

        <div class="navbar-user" id="navbarUser">
            <div class="user-info">
                <div class="user-name"><?php echo esc_html($current_user->username); ?></div>
            </div>
            <a href="<?php echo home_url('/timeline-logout'); ?>" class="btn-logout">Salir</a>
        </div>
    </nav>

    <div class="container">

        <div class="intro-section">
            <h1>Explora <span class="highlight">tus proyectos</span></h1>
            <p>Esta es tu <strong>área de proyectos</strong>, aquí puedes acceder a <strong>toda la información</strong> sobre cada obra, ya esté finalizada o en proceso.</p>
        </div>

        <div class="projects-grid">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $project): ?>
                    <?php
                    $image = $project->featured_image ? $project->featured_image : 'https://via.placeholder.com/400x350';
                    $status_map = [
                        'en_proceso' => ['label' => 'EN PROCESO', 'class' => 'en-proceso'],
                        'pendiente' => ['label' => 'PENDIENTE', 'class' => 'pendiente'],
                        'finalizado' => ['label' => 'FINALIZADO', 'class' => 'finalizado']
                    ];
                    $current_status = $status_map[$project->project_status] ?? $status_map['en_proceso'];
                    ?>
                    <div class="project-card">
                        <div class="project-status <?php echo $current_status['class']; ?>">
                            <?php echo $current_status['label']; ?>
                        </div>

                        <img src="<?php echo esc_url($image); ?>" class="project-image">

                        <div class="project-content">
                            <h2 class="project-title"><?php echo esc_html($project->name); ?></h2>
                            <a href="<?php echo home_url('/timeline-proyecto/' . $project->id); ?>" class="btn-view-project">
                                Ver información del proyecto
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><h2>No tienes proyectos asignados</h2></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleMobile() {
            const toggle = document.getElementById('mobileMenuToggle');
            const menu = document.getElementById('navbarMenu');
            const user = document.getElementById('navbarUser');

            toggle.classList.toggle('active');
            menu.classList.toggle('active');
            user.classList.toggle('active');
        }

        // Cerrar menú al hacer clic en enlaces
        document.querySelectorAll('.navbar-menu a').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) toggleMobile();
            });
        });
    </script>

    <?php 
    // FOOTER DE WORDPRESS
    get_footer(); 
    ?>

</body>
</html>