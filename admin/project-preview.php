<?php
/**
 * Template: Vista Previa del Timeline (Vista Administrador simulando Cliente)
 * Archivo: admin/project-preview.php
 * ACTUALIZADO: Con botones y modales funcionales
 */

$project_id = get_query_var('timeline_id');
$projects_class = Timeline_Projects::get_instance();
$milestones_class = Timeline_Milestones::get_instance();

$project = $projects_class->get_project($project_id);
if (!$project) {
    wp_redirect(home_url('/timeline-proyectos'));
    exit;
}

$milestones = $milestones_class->get_project_milestones_with_images($project_id);

$start = new DateTime($project->start_date);
$end = new DateTime($project->end_date);
$actual_end = $project->actual_end_date ? new DateTime($project->actual_end_date) : $end;
$total_days = $start->diff($actual_end)->days;
$is_extended = $project->actual_end_date && $actual_end > $end;

// Calcular el índice del último hito dentro del plazo
$last_in_time_index = -1;
foreach ($milestones as $index => $milestone) {
    $milestone_date = new DateTime($milestone->date);
    if ($milestone_date <= $end) {
        $last_in_time_index = $index;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa - <?php echo esc_html($project->name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #ffffff;
        }

        /* NAVBAR ADMIN CON INDICADOR DE VISTA PREVIA */
        .navbar {
            background: black;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 9999;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .navbar-brand {
            font-size: 18px;
            font-weight: 300;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.9);
        }

        .preview-badge {
            padding: 8px 16px;
            background: rgba(253, 196, 37, 0.15);
            color: #FDC425;
            border: 1px solid #FDC425;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            padding: 12px 24px;
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-back:hover {
            border-color: rgba(255, 255, 255, 0.5);
            color: rgba(255, 255, 255, 0.9);
        }

        /* ESTILOS DEL CLIENTE (copiados de project-timeline.php) */
        .header-section {
            background: #ffffff;
            color: #000000;
            padding: 50px;
            text-align: center;
            margin-top: 88px;
        }

        .header-section h1 {
            font-size: 48px;
            font-weight: 400;
            margin-bottom: 15px;
        }

        .header-section p {
            font-size: 16px;
            color: #666;
        }

        .container {
            max-width: 80%;
            margin: 0 auto;
            padding: 80px 40px;
            background: #ffffff;
            color: #000;
            padding-top: 0px;
        }

        .timeline-wrapper {
            position: relative;
            margin: 80px 0;
        }

        /* Barra superior timeline */
        .timeline-bar-container {
            width: 100%;
            padding: 0px;
            background: #f2f2f2;
            position: sticky;
            top: 88px;
            z-index: 100;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .timeline-bar-inner {
            display: flex;
            gap: 0px;
            padding: 0 60px;
            position: relative;
            min-width: max-content;
        }

        .timeline-bar-inner::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            width: 100%;
            top: 36%;
            transform: translateY(-50%);
            height: 2px;
            background: black;
            z-index: 1;
        }

        .timeline-top-item {
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
            height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }

        .timeline-top-item:first-of-type { border-left: 3px solid black; }
        .timeline-top-item:last-of-type { border-right: 3px solid black; }

        .timeline-top-point {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2px solid #000;
            margin: 0 auto;
            transition: all 0.3s ease;
            position: relative;
            z-index: 3;
            background: #f2f2f2;
        }

        .timeline-top-item.status-pendiente { 
            background: #EDEDED; 
            cursor: default; /* ✅ No mostrar pointer en pendientes */
            opacity: 0.7; /* ✅ Apariencia deshabilitada */
        }
        .timeline-top-item.status-en_proceso { background: #FDC425; }
        .timeline-top-item.status-finalizado { background: #FFDE88; }

        .timeline-top-item.status-pendiente .timeline-top-point { background: #EDEDED; }
        .timeline-top-item.status-en_proceso .timeline-top-point { background: #FDC425; }
        .timeline-top-item.status-finalizado .timeline-top-point { background: #FFDE88; }

        /* ✅ Hover solo para no pendientes */
        .timeline-top-item.status-en_proceso:hover,
        .timeline-top-item.status-finalizado:hover {
            opacity: 0.8;
        }

        .timeline-top-date {
            font-size: 13px;
            font-weight: 600;
            margin-top: 0px;
            letter-spacing: 0.5px;
            color: black;
        }

        .timeline-top-item.active .timeline-top-point { background-color: #000; }
        .timeline-top-item.active .timeline-top-date { font-weight: 700; font-size: 14px; }

        /* Timeline vertical */
        .vertical-timeline {
            position: relative;
            margin-top: 60px;
        }

        .vertical-timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: black;
            transform: translateX(-50%);
        }

        .milestone-card {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s forwards;
        }

        @keyframes fadeInUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .milestone-card:nth-child(odd) { padding-left: calc(50% + 25px); }
        .milestone-card:nth-child(even) {
            justify-content: flex-end;
            padding-right: calc(50% + 25px);
        }

        .milestone-inner {
            display: flex;
            align-items: center;
            gap: 30px;
            background: #FFF9E6;
            border-radius: 15px;
            padding: 15px;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .milestone-card:nth-child(even) .milestone-inner { flex-direction: row-reverse; }

        .status-pendiente .milestone-inner { background-color: #EDEDED; }
        .status-en_proceso .milestone-inner { background-color: #FDC425; }
        .status-finalizado .milestone-inner { background-color: #FFDE88; }

        .milestone-card::after {
            content: '';
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #FDC425;
            border: 3px solid black;
            z-index: 6;
            left: 50%;
        }

        .milestone-card.status-pendiente::after { background: #EDEDED; }
        .milestone-card.status-en_proceso::after { background: #FDC425; }
        .milestone-card.status-finalizado::after { background: #FFDE88; }

        .milestone-card::before {
            content: '';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 2px;
            background: black;
            z-index: 4;
        }

        .milestone-card:nth-child(odd)::before { left: 30px !important; }
        .milestone-card:nth-child(even)::before { right: 50%; }

        .milestone-card-image {
            position: relative;
            width: 180px;
            height: 120px;
            flex-shrink: 0;
            overflow: visible; /* ✅ CAMBIO: De hidden a visible */
            border-radius: 10px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }

        .milestone-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        /* ✅ BOTÓN VISIBLE */
        .milestone-card-btn {
            position: absolute;
            bottom: 0px;
            left: 0;
            right: 0;
            background: #000;
            color: #fff;
            padding: 10px 20px;
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            white-space: nowrap;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            z-index: 10; /* ✅ Asegurar que esté encima */
            border-radius: 0 0 10px 10px;
        }

        .milestone-card-btn:hover {
            background: #333;
        }

        .milestone-card-btn:disabled {
            background: #666;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .milestone-card-content {
            flex: 1;
            padding: 0;
        }

        .milestone-card:nth-child(odd) .milestone-card-content { text-align: left; }
        .milestone-card:nth-child(even) .milestone-card-content {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-content: flex-end;
            flex-wrap: wrap;
        }

        .milestone-card-date {
            font-size: 16px;
            color: black;
            margin-bottom: 0px;
            font-weight: 500;
        }

        .milestone-card-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .milestone-card-title .highlight { font-weight: 400; }

        .milestone-card-description {
            font-size: 14px;
            line-height: 1.6;
            color: black;
            text-align: justify;
        }

        /* Modal de hito */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .modal-content {
            background: #ffffff;
            width: 90%;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            margin: 40px 0;
            border-radius: 40px;
        }

        .modal-content.status-en_proceso {
            background: #FDC425;
        }

        .modal-content.status-finalizado {
            background: #FFDE88;
        }

        .modal-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px;
            gap: 20px;
        }

        .modal-top-left {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .modal-top-right {
            display: flex;
            gap: 10px;
            align-items: center;
            width: 46%;
            justify-content: space-between;
        }

        .modal-close {
            border: none;
            background: none;
            cursor: pointer;
        }

        .carousel-nav-btn {
            border: none;
            background: none;
            cursor: pointer;
        }

        .carousel-nav-btn:hover:not(:disabled) {
            opacity: 0.8;
        }

        .carousel-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .modal-carousel {
            position: relative;
            width: 48%;
            height: 500px;
            overflow: hidden;
            padding: 30px;
            padding-top: 0px;
            border-radius: 20px;
            touch-action: pan-y;
            user-select: none;
        }

        .carousel-slides-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .carousel-slide {
            display: none;
            width: 100%;
            height: 100%;
            transition: transform 0.3s ease-out;
        }

        .carousel-slide.active {
            display: block;
        }

        .carousel-slides-container.dragging {
            cursor: grabbing;
        }

        .carousel-slides-container:not(.dragging) {
            cursor: grab;
        }

        .carousel-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 20px;
        }

        .carousel-indicators {
            position: absolute;
            bottom: 45px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
            justify-content: center;
        }

        .carousel-indicator {
            width: 12px !important;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .carousel-indicator.active {
            background: #FDC425;
            transform: scale(1.2);
        }

        .modal-info {
            padding: 30px;
            padding-top: 0px;
            width: 50%;
        }

        .modal-date {
            font-size: 14px;
            color: black;
            font-weight: 500;
        }

        .modal-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .modal-status {
            display: inline-block;
            padding: 8px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-radius: 5px;
            background-color: black;
            color: #FDC425;
            border-radius: 10px;
        }

        .modal-description {
            font-size: 16px;
            line-height: 1.8;
            color: black;
        }

        .milestone-nav-arrows {
            display: flex;
            justify-content: space-between;
            padding: 0px;
            bottom: 30px;
            position: absolute;
            width: 50%;
        }

        .milestone-card.active .milestone-inner {
            border: 2px solid black;
        }

        .milestone-card.active::after {
            background: black !important;
        }

        .milestone-nav-btn {
            background: #000;
            color: #fff;
            border: none;
            padding: 12px 30px;
            font-size: 12px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            border-radius: 10px;
        }

        .milestone-nav-btn:hover:not(:disabled) {
            background: #333;
        }

        .milestone-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }

            .header-section{
                margin-top: 130px;
                padding-bottom: 0px !important;
            }

            .timeline-bar-container{
                top: 155px;
            }

            .navbar-left {
                flex-direction: column;
                gap: 10px;
            }

            .container {
                max-width: 90%;
                padding: 40px 20px;
            }

            .milestone-card {
                padding-left: 60px !important;
                padding-right: 0 !important;
                justify-content: flex-start !important;
            }

            .milestone-card:nth-child(odd) .milestone-card-content,
            .milestone-card:nth-child(even) .milestone-card-content {
                text-align: center;
            }

            .milestone-inner {
                flex-direction: column !important;
            }

            .milestone-card-image {
                width: 100%;
                height: 220px;
            }

            .vertical-timeline::before {
                left: 30px;
            }

            .milestone-card::after,
            .milestone-card::before {
                left: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Admin con indicador -->
    <nav class="navbar">
        <div class="navbar-left">
            <div class="navbar-brand">BeBuilt</div>
            <div class="preview-badge">
                👁️ VISTA PREVIA - Modo Cliente
            </div>
        </div>
        <a href="<?php echo home_url('/timeline-proyecto-admin/' . $project_id); ?>" class="btn-back">← Volver a Gestión</a>
    </nav>

    <div class="timeline-bar-container">
        <div class="timeline-bar-inner">
            <?php foreach ($milestones as $index => $milestone): ?>
                <?php
                $date = new DateTime($milestone->date);
                $status_class = 'status-' . esc_attr($milestone->status);
                ?>
                <!-- ✅ onclick solo para no pendientes -->
                <div class="timeline-top-item <?php echo $status_class; ?>" 
                     <?php if ($milestone->status !== 'pendiente'): ?>
                     onclick="scrollToMilestone(<?php echo $index; ?>)"
                     <?php endif; ?>>
                    <div class="timeline-top-point"></div>
                    <div class="timeline-top-date"><?php echo $date->format('d/m/Y'); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Header -->
    <div class="header-section">
        <h1><?php echo esc_html($project->name); ?></h1>
        <p><?php echo esc_html($project->description); ?></p>
    </div>

    <!-- Contenido -->
    <div class="container">
        <!-- Timeline vertical -->
        <div class="vertical-timeline">
            <?php foreach ($milestones as $index => $milestone): ?>
                <?php
                $date = new DateTime($milestone->date);
                if (!empty($milestone->images) && isset($milestone->images[0]) && !empty($milestone->images[0]->image_url)) {
                    $first_image = $milestone->images[0]->image_url;
                } else {
                    $first_image = 'https://www.bebuilt.es/wp-content/uploads/2023/08/cropped-favicon.png';
                }
                ?>
                <div class="milestone-card status-<?php echo esc_attr($milestone->status); ?>" 
                     id="milestone-<?php echo $index; ?>"
                     data-milestone-index="<?php echo $index; ?>"
                     style="animation-delay: <?php echo $index * 0.15; ?>s;">
                    <div class="milestone-inner">
                        <div class="milestone-card-content">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="milestone-card-date"><?php echo $date->format('d/m/Y'); ?></div>
                                <h2 class="milestone-card-title">
                                    <span class="highlight"><?php echo esc_html(explode(' ', $milestone->title)[0]); ?></span>
                                    <?php echo esc_html(implode(' ', array_slice(explode(' ', $milestone->title), 1))); ?>
                                </h2>
                            </div>
                            <?php if (!empty($milestone->description)): ?>
                                <div class="milestone-card-description">
                                    <?php echo nl2br(esc_html(substr($milestone->description, 0, 150))); ?>
                                    <?php if (strlen($milestone->description) > 150): ?>...<?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="milestone-card-image">
                            <img src="<?php echo esc_url($first_image); ?>" alt="<?php echo esc_attr($milestone->title); ?>">
                            <!-- ✅ BOTÓN SIEMPRE VISIBLE -->
                            <?php if ($milestone->status === 'pendiente'): ?>
                                <button class="milestone-card-btn" disabled style="background: #666; cursor: not-allowed;">
                                    Pendiente
                                </button>
                            <?php else: ?>
                                <button class="milestone-card-btn" onclick="openMilestoneModal(<?php echo $index; ?>)">
                                    + Información
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($milestones) === 0): ?>
            <div style="text-align: center; padding: 80px 20px; color: #999;">
                <p>No hay hitos disponibles en este proyecto todavía</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de hito -->
    <div id="milestoneModal" class="modal">
        <div class="modal-content">
            <div class="modal-top-bar">
                <div class="modal-top-left">
                    <div class="modal-date" id="modal-date"></div>
                    <span class="modal-status" id="modal-status"></span>
                </div>

                <div class="modal-top-right">
                    <div style="display:flex; gap:10px; justify-content: space-between;">
                        <button class="carousel-nav-btn" id="carousel-prev-top" onclick="changeSlide(-1)">
                            <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-18.svg" alt="Anterior">
                        </button>
                        <button class="carousel-nav-btn" id="carousel-next-top" onclick="changeSlide(1)">
                            <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-19.svg" alt="Siguiente">
                        </button>
                    </div>
                    <button class="modal-close" onclick="closeMilestoneModal()">
                        <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-20.svg" alt="Cerrar">
                    </button>
                </div>
            </div>

            <div class="anchomodal" style="display: flex; gap: 15px; justify-content:space-between;">
                <div class="modal-info">
                    <h2 class="modal-title" id="modal-title"></h2>
                    <div class="modal-description" id="modal-description"></div>

                    <div class="milestone-nav-arrows">
                        <button class="milestone-nav-btn" id="prev-milestone-btn" onclick="navigateMilestone(-1)">
                            &lt; Anterior
                        </button>
                        <button class="milestone-nav-btn" id="next-milestone-btn" onclick="navigateMilestone(1)">
                            Siguiente &gt;
                        </button>
                    </div>
                </div>

                <div class="modal-carousel" id="modal-carousel">
                    <!-- Las imágenes se cargarán dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const milestones = <?php echo json_encode($milestones); ?>;
        let currentMilestoneIndex = 0;
        let currentSlide = 0;
        let totalSlides = 0;
        let isScrolling = false;

        // Scroll al hito correspondiente
        function scrollToMilestone(index) {
            // ✅ Verificar si el hito está pendiente
            const milestone = milestones[index];
            if (milestone.status === 'pendiente') {
                return; // No hacer nada para hitos pendientes
            }
            
            const milestoneCard = document.getElementById('milestone-' + index);
            if (milestoneCard) {
                isScrolling = true;

                const timelineItems = document.querySelectorAll('.timeline-top-item');
                timelineItems.forEach((item, idx) => {
                    if (idx === index) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });

                const milestoneCards = document.querySelectorAll('.milestone-card');
                milestoneCards.forEach((card, idx) => {
                    if (idx === index) {
                        card.classList.add('active');
                    } else {
                        card.classList.remove('active');
                    }
                });

                milestoneCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                setTimeout(() => {
                    isScrolling = false;
                }, 1000);
            }
        }

        function openMilestoneModal(index) {
            const milestone = milestones[index];
            
            // ✅ No abrir modal para hitos pendientes
            if (milestone.status === 'pendiente') {
                return;
            }
            
            currentMilestoneIndex = index;
            currentSlide = 0;

            const modal = document.getElementById('milestoneModal');
            const modalContent = modal.querySelector('.modal-content');

            modal.classList.add('active');
            modalContent.className = 'modal-content status-' + milestone.status;

            const date = new Date(milestone.date);
            document.getElementById('modal-date').textContent = date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            document.getElementById('modal-title').textContent = milestone.title;
            document.getElementById('modal-description').innerHTML = milestone.description.replace(/\n/g, '<br>');

            const statusElement = document.getElementById('modal-status');
            const statusLabels = {
                'pendiente': 'Pendiente',
                'en_proceso': 'En Proceso',
                'finalizado': 'Finalizado'
            };
            statusElement.textContent = statusLabels[milestone.status] || milestone.status;
            statusElement.className = 'modal-status status-' + milestone.status;

            loadCarousel(milestone);
            updateMilestoneNavButtons();
            updateCarouselNavButtons();

            document.body.style.overflow = 'hidden';
        }

        function closeMilestoneModal() {
            document.getElementById('milestoneModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function loadCarousel(milestone) {
            const carousel = document.getElementById('modal-carousel');
            carousel.innerHTML = '';

            const images = milestone.images && milestone.images.length > 0 ?
                milestone.images : [{
                    image_url: 'https://via.placeholder.com/900x500/cccccc/666666?text=Sin+Imagenes'
                }];

            totalSlides = images.length;

            const slidesContainer = document.createElement('div');
            slidesContainer.className = 'carousel-slides-container';
            slidesContainer.id = 'carouselSlidesContainer';

            images.forEach((img, index) => {
                const slide = document.createElement('div');
                slide.className = 'carousel-slide' + (index === 0 ? ' active' : '');
                slide.innerHTML = `<img src="${img.image_url}" alt="Imagen ${index + 1}" draggable="false">`;
                slidesContainer.appendChild(slide);
            });

            carousel.appendChild(slidesContainer);

            if (images.length > 1) {
                const indicators = document.createElement('div');
                indicators.className = 'carousel-indicators';
                images.forEach((_, index) => {
                    const indicator = document.createElement('div');
                    indicator.className = 'carousel-indicator' + (index === 0 ? ' active' : '');
                    indicator.onclick = () => goToSlide(index);
                    indicators.appendChild(indicator);
                });
                carousel.appendChild(indicators);
            }

            initSwipe(slidesContainer);
        }

        function changeSlide(direction) {
            const slides = document.querySelectorAll('.carousel-slide');
            const indicators = document.querySelectorAll('.carousel-indicator');

            slides[currentSlide].classList.remove('active');
            if (indicators.length) indicators[currentSlide].classList.remove('active');

            currentSlide = (currentSlide + direction + slides.length) % slides.length;

            slides[currentSlide].classList.add('active');
            if (indicators.length) indicators[currentSlide].classList.add('active');

            updateCarouselNavButtons();
        }

        function goToSlide(index) {
            const slides = document.querySelectorAll('.carousel-slide');
            const indicators = document.querySelectorAll('.carousel-indicator');

            slides[currentSlide].classList.remove('active');
            if (indicators.length) indicators[currentSlide].classList.remove('active');

            currentSlide = index;

            slides[currentSlide].classList.add('active');
            if (indicators.length) indicators[currentSlide].classList.add('active');

            updateCarouselNavButtons();
        }

        function updateCarouselNavButtons() {
            const prevBtn = document.getElementById('carousel-prev-top');
            const nextBtn = document.getElementById('carousel-next-top');

            if (totalSlides <= 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                prevBtn.disabled = false;
                nextBtn.disabled = false;
            }
        }

        function navigateMilestone(direction) {
            let newIndex = currentMilestoneIndex + direction;

            while (newIndex >= 0 && newIndex < milestones.length) {
                if (milestones[newIndex].status !== 'pendiente') {
                    currentMilestoneIndex = newIndex;
                    currentSlide = 0;
                    const milestone = milestones[newIndex];

                    const modalContent = document.querySelector('.modal-content');
                    modalContent.style.opacity = '0.5';
                    modalContent.style.transform = 'scale(0.98)';

                    setTimeout(() => {
                        modalContent.className = 'modal-content status-' + milestone.status;

                        const date = new Date(milestone.date);
                        document.getElementById('modal-date').textContent = date.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });

                        document.getElementById('modal-title').textContent = milestone.title;
                        document.getElementById('modal-description').innerHTML = milestone.description.replace(/\n/g, '<br>');

                        const statusElement = document.getElementById('modal-status');
                        const statusLabels = {
                            'pendiente': 'Pendiente',
                            'en_proceso': 'En Proceso',
                            'finalizado': 'Finalizado'
                        };
                        statusElement.textContent = statusLabels[milestone.status] || milestone.status;
                        statusElement.className = 'modal-status status-' + milestone.status;

                        loadCarousel(milestone);
                        updateMilestoneNavButtons();
                        updateCarouselNavButtons();

                        modalContent.style.opacity = '1';
                        modalContent.style.transform = 'scale(1)';
                    }, 200);

                    return;
                }
                newIndex += direction;
            }
        }

        function updateMilestoneNavButtons() {
            let hasPrevious = false;
            for (let i = currentMilestoneIndex - 1; i >= 0; i--) {
                if (milestones[i].status !== 'pendiente') {
                    hasPrevious = true;
                    break;
                }
            }

            let hasNext = false;
            for (let i = currentMilestoneIndex + 1; i < milestones.length; i++) {
                if (milestones[i].status !== 'pendiente') {
                    hasNext = true;
                    break;
                }
            }

            document.getElementById('prev-milestone-btn').disabled = !hasPrevious;
            document.getElementById('next-milestone-btn').disabled = !hasNext;
        }

        function initSwipe(container) {
            let touchStartX = 0;
            let touchEndX = 0;
            let isDragging = false;

            container.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
                isDragging = true;
                container.classList.add('dragging');
            }, {
                passive: true
            });

            container.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                touchEndX = e.changedTouches[0].screenX;
            }, {
                passive: true
            });

            container.addEventListener('touchend', function(e) {
                if (!isDragging) return;
                touchEndX = e.changedTouches[0].screenX;
                isDragging = false;
                container.classList.remove('dragging');

                const deltaX = touchStartX - touchEndX;
                if (Math.abs(deltaX) > 50) {
                    if (deltaX > 0) {
                        changeSlide(1);
                    } else {
                        changeSlide(-1);
                    }
                }
            }, {
                passive: true
            });
        }

        // Eventos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMilestoneModal();
            }
            if (document.getElementById('milestoneModal').classList.contains('active')) {
                if (e.key === 'ArrowLeft') {
                    changeSlide(-1);
                } else if (e.key === 'ArrowRight') {
                    changeSlide(1);
                }
            }
        });

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('milestoneModal');
            if (event.target == modal) {
                closeMilestoneModal();
            }
        }
    </script>

</body>
</html>