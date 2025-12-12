<?php

/**
 * Template: Vista del Timeline del Proyecto (Cliente)
 * Archivo: templates/project-timeline.php
 */

$project_id = get_query_var('timeline_id');
$projects_class = Timeline_Projects::get_instance();
$milestones_class = Timeline_Milestones::get_instance();

$project = $projects_class->get_project($project_id);
if (!$project) {
    wp_redirect(home_url('/timeline-mis-proyectos'));
    exit;
}

// Verificar que el cliente tiene acceso
$current_user = $GLOBALS['timeline_plugin']->get_current_user();
if ($current_user->role === 'cliente') {
    $client_projects = $projects_class->get_client_projects($current_user->id);
    $has_access = false;
    foreach ($client_projects as $cp) {
        if ($cp->id == $project_id) {
            $has_access = true;
            break;
        }
    }
    if (!$has_access) {
        wp_redirect(home_url('/timeline-mis-proyectos'));
        exit;
    }
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
    <title><?php echo esc_html($project->name); ?> - Timeline</title>

    <?php wp_head(); ?> <!-- ← AÑADIR ESTA LÍNEA AQUÍ -->

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
            color: #000000;
        }

        .navbar {
            background: black;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: white;
        }

        .btn-back {
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
        }

        .btn-back:hover {
            background: white;
            color: black;
        }

        .header-section {
            background: #0a0a0a;
            color: #ffffff;
            padding: 60px 40px;
            text-align: center;
        }

        .header-section h1 {
            font-size: 48px;
            font-weight: 400;
            margin-bottom: 15px;
        }

        .header-section p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.6);
        }

        .container {
            max-width: 80%;
            margin: 0 auto;
            padding: 80px 40px;
            margin-top: 30px;
        }

        .timeline-wrapper {
            position: relative;
            margin: 80px 0;
        }

        .timeline-top-item:first-of-type {
            border-left: 3px solid black;
        }

        .timeline-top-item:last-of-type {
            border-right: 3px solid black;
        }

        /* ===== BARRA DE TIMELINE SUPERIOR ===== */
        .timeline-bar-container {
            width: 100%;
            padding: 0px;
            background: #f2f2f2;
            position: sticky;
            top: 0;
            z-index: 100;
            overflow-x: auto;
            overflow-y: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            scrollbar-width: thin;
            scrollbar-color: #bfbfbf #f2f2f2;
        }

        .timeline-bar-container::-webkit-scrollbar {
            height: 8px;
        }

        .timeline-bar-container::-webkit-scrollbar-track {
            background: #f2f2f2;
        }

        .timeline-bar-container::-webkit-scrollbar-thumb {
            background: #bfbfbf;
            border-radius: 4px;
        }

        .timeline-bar-container::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        /* Contenido scrollable en horizontal */
        .timeline-bar-inner {
            display: flex;
            gap: 0px;
            padding: 0 60px;
            position: relative;
            z-index: 2;
            min-width: max-content;
        }

        /* Línea horizontal - AHORA DENTRO DE timeline-bar-inner */
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
            pointer-events: none;
        }

        /* Cada hito superior */
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

        /* CAMBIO 2: Punto con background sólido para ocultar la línea */
        .timeline-top-point {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2px solid #000;
            margin: 0 auto;
            transition: all 0.3s ease;
            /* Añadir z-index superior y background */
            position: relative;
            z-index: 3;
            background: #f2f2f2;
        }

        .elementor-element-8bececf {
            display: none;
        }

        .elementor-element-d4dea6a {
            display: none;
        }

        .elementor-element-7ac052f {
            margin-top: 60px;
        }

        .elementor-location-footer a {
            color: black;
        }

        /* Colores según estado - ahora con background visible */
        .timeline-top-item.status-pendiente .timeline-top-point {
            background: #EDEDED;
        }

        .timeline-top-item.status-en_proceso .timeline-top-point {
            background: #FDC425;
        }

        .timeline-top-item.status-finalizado .timeline-top-point {
            background: #FFDE88;
        }

        /* Fecha con colores según estado */
        .timeline-top-date {
            font-size: 13px;
            font-weight: 600;
            margin-top: 0px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .timeline-top-item.status-pendiente {
            background: #EDEDED;
        }

        .timeline-top-item.status-en_proceso {
            background: #FDC425;
        }

        .timeline-top-item.status-finalizado {
            background: #FFDE88;
        }


        .timeline-top-item.active .timeline-top-point {
            background-color: #000;
        }

        .timeline-top-item.active .timeline-top-date {
            font-weight: 700;
            font-size: 14px;
        }

        /* LÍNEA VERTICAL DEL TIMELINE */
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
    z-index: 0;
}



        /* Línea continua hasta el último hito dentro del plazo */
        .vertical-timeline.has-extension::before {
            height: var(--line-end-position);
            background: black;
        }

        /* Línea discontinua después del plazo */
        .vertical-timeline.has-extension::after {
            content: '';
            position: absolute;
            left: 50%;
            top: var(--line-end-position);
            margin-top: 20px;
            bottom: 0;
            width: 2px;
            background-image: repeating-linear-gradient(to bottom,
                    black 0px,
                    black 10px,
                    transparent 10px,
                    transparent 20px);
            transform: translateX(-50%);
            z-index: 0;
        }

        /* Tarjetas de hitos con línea vertical */
        .milestone-card {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s forwards;
        }

        /* Alternar posición - Hitos impares a la DERECHA */
        .milestone-card:nth-child(odd) {
            padding-left: calc(50% + 25px);
        }

        /* Alternar posición - Hitos pares a la IZQUIERDA */
        .milestone-card:nth-child(even) {
            justify-content: flex-end;
            padding-right: calc(50% + 25px);
        }

        /* Contenedor interno del hito */
        .milestone-inner {
            display: flex;
            align-items: center;
            gap: 30px;
            background: #FFF9E6;
            border: 0px;
            border-radius: 15px;
            padding: 15px;
            max-width: 650px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            z-index: 999999;
        }

        .status-finalizado .milestone-inner {
            background-color: #FFDE88;
        }

        .milestone-card:nth-child(even) .milestone-inner {
            flex-direction: row-reverse;
        }

        .milestone-card:nth-child(odd) .milestone-card-content {
            text-align: left;
        }

        .milestone-card:nth-child(even) .milestone-card-content {
            text-align: right;
        }

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
        }

        /* Impares (derecha) → punto se coloca centrado respecto a la línea */
        .milestone-card:nth-child(odd)::after {
            left: 50%;
        }

        /* Pares (izquierda) */
        .milestone-card:nth-child(even)::after {
            left: 50%;
        }

        /* ===== CONECTOR: LÍNEA QUE SALE DEL PUNTO HACIA LA TARJETA ===== */
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

        /* Impares → tarjeta a la DERECHA → línea a la izquierda */
        .milestone-card:nth-child(odd)::before {
            left: 50%;
        }

        /* Pares → tarjeta a la IZQUIERDA → línea a la derecha */
        .milestone-card:nth-child(even)::before {
            right: 50%;
        }

        .status-pendiente .milestone-inner {
            background-color: #EDEDED;
        }

        .status-en_proceso .milestone-inner {
            background-color: #FDC425;
        }

        .milestone-card.status-pendiente::after {
            background: #EDEDED;
        }

        .milestone-card.status-en_proceso::after {
            background: #FDC425;
        }

        .milestone-card.status-finalizado::after {
            background: #FFDE88;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .milestone-card-image {
            position: relative;
            width: 180px;
            height: 120px;
            flex-shrink: 0;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
        }

        .milestone-card-image:not(:has(button:disabled)):hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .milestone-card-image:has(button:disabled) {
            opacity: 0.7;
            cursor: default;
        }

        .milestone-card-image:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }

        .milestone-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .milestone-card-btn {
            position: absolute;
            bottom: 0px;
            left: 50%;
            transform: translateX(-50%);
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
        }

        .milestone-card-btn:hover {
            background: black !important;
        }

        .carousel-nav-btn:hover,
        .modal-close:hover {
            background-color: transparent !important;
        }

        .modal-content button:focus {
            background-color: transparent !important;
            border: 0px !important;
        }

        .milestone-nav-btn:hover {
            background: black !important;
        }

        .milestone-card-content {
            flex: 1;
            padding: 0;
        }

        .milestone-card:nth-child(even) .milestone-card-content {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-content: flex-end;
            flex-wrap: wrap;
        }

        .milestone-card:nth-child(even) .milestone-card-content .milestone-card-date {
            order: 2;
        }

        .milestone-card:nth-child(even) .milestone-card-content .milestone-card-date {
            order: 1;
        }

        button:hover {
            background-color: none !important;
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

        .milestone-card-title .highlight {
            font-weight: 400;
        }

        .milestone-card-description {
            font-size: 14px;
            line-height: 1.6;
            color: black;
            margin-bottom: 0;
            text-align: justify;
        }

        .milestone-card-description strong {
            font-weight: 700;
            color: #000;
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
            background: rgb(0 0 0 / 65%);
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

        .modal-close:hover {
            opacity: 0.8;
        }

        .carousel-nav-btn {
            border: none;
            background: none;
            cursor: pointer;
        }

        .timeline-top-item.status-pendiente .timeline-top-date {
            pointer-events: none !important;
            /* Desactiva eventos del mouse */
        }

        .timeline-top-item.status-pendiente:hover {
            transform: none !important;
            pointer-events: none !important;
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
            /* Permite scroll vertical pero captura horizontal */
            user-select: none;
            /* Evita selección de texto durante el arrastre */
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
            /* Suaviza las transiciones */
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

        .carousel-prev,
        .carousel-next {
            display: none !important;
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

        .modal-description strong {
            font-weight: 700;
            color: #000;
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
            opacity: 0.8;
        }

        .navbar-brand img {
            height: 50px;
            width: auto;
        }

        .fijo-top {
            position: fixed;
            top: 0px;
            width: 100%;
            z-index: 999999999999999999999999999999999999;
        }

        .milestone-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        @media (max-width: 1100px) {
            .milestone-inner {
                flex-direction: column;
            }

            .milestone-card:nth-child(even) .milestone-inner {
                flex-direction: column;
            }
        }

        @media (max-width: 768px) {
            .vertical-timeline::before {
                left: 30px;
            }

            .container {
                max-width: 90%;
                margin: 0 auto;
                padding: 0px;
            }

            .milestone-card:nth-child(even)::after {
                left: 30px;
            }

            .vertical-timeline.has-extension::after {
                left: 30px;
                margin-top: 20px;
            }

            .milestone-card:nth-child(even)::before {
                left: 30px;
            }

            .milestone-card:nth-child(odd)::before {
                left: 30px;
            }

            .milestone-card:nth-child(odd)::after {
                left: 30px;
            }

            .milestone-card {
                padding-left: 60px !important;
                padding-right: 0 !important;
                justify-content: flex-start !important;
            }

            #milestoneModal .modal-content{
                    max-height: 76vh;
            }

            .titulodescarga {
                font-size: 24px !important;
            }

            .milestone-card::after {
                left: 30px;
            }

            .milestone-inner {
                flex-direction: column !important;
                max-width: 100%;
            }

            .milestone-card:nth-child(odd) .milestone-card-content,
            .milestone-card:nth-child(even) .milestone-card-content {
                text-align: center;
                width: 100%;
            }

            .milestone-card-image {
                width: 100%;
                height: 220px;
            }

            .modal-content {
                width: 95%;
            }

            .modal-info {
                padding: 30px;
            }

            .anchomodal {
                flex-direction: column;
            }

            .anchomodal div {
                width: 100%;
                text-align: justify;
            }

            .milestone-nav-arrows {
                position: relative;
                padding: 0px;
                margin-top: 20px;
            }

            .modal-top-bar {
                align-items: stretch;
                flex-direction: column;
                padding-bottom: 0px;
            }

            .modal-top-left {
                order: 2;
                justify-content: space-between;
            }

            .milestone-nav-btn {
                padding: 12px;
            }

            .modal-top-right {
                width: 100%;
                justify-content: space-between;
            }

            .milestone-nav-arrows {
                width: 100%;
                padding: 0px;
                margin-top: 25px;
                padding-top: 20px;
            }

            .modal-info {
                padding-bottom: 0px;
            }



            .timeline-top-item {
                min-width: 105px;
            }
        }
    </style>


<body>
    <div class="fijo-top">
        <nav class="navbar">
            <div class="navbar-brand"><img src="https://www.bebuilt.es/wp-content/uploads/2025/12/logo-bebuilt-blanco.png" alt=""></div>
            <a href="<?php echo home_url('/timeline-mis-proyectos'); ?>" class="btn-back">← Mis Proyectos</a>
        </nav>

        <div class="timeline-bar-container" id="timelineBarContainer">
            <div class="timeline-bar"></div>
            <div class="timeline-bar-inner" id="timelineBarInner">
                <?php foreach ($milestones as $index => $milestone): ?>
                    <?php
                    $date = new DateTime($milestone->date);
                    $status_class = 'status-' . esc_attr($milestone->status);
                    ?>
                    <div class="timeline-top-item <?php echo $status_class; ?>"
                        data-milestone-index="<?php echo $index; ?>"
                        <?php if ($milestone->status !== 'pendiente'): ?>
                        onclick="scrollToMilestone(<?php echo $index; ?>)"
                        <?php endif; ?>>
                        <div class="timeline-top-point"></div>
                        <div class="timeline-top-date">
                            <?php echo $date->format('d/m/Y'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="width:90%; text-align:right; margin:0 auto">
            <img style="width:40px; transform: rotate(-90deg);" src="https://www.bebuilt.es/wp-content/uploads/2025/12/5d74b93c5d708f2ece6870f78c1f0e9d6ea40017-1.gif" alt="">
        </div>
    </div>


    <div class="container">
        <h1 style="text-align:center"><?php echo esc_html($project->name); ?></h1><br>
        <p style="text-align:center"><?php echo esc_html($project->description); ?></p><br>

        <div class="vertical-timeline <?php echo ($last_in_time_index >= 0 && $last_in_time_index < count($milestones) - 1) ? 'has-extension' : ''; ?>"
            id="verticalTimeline"
            <?php if ($last_in_time_index >= 0 && $last_in_time_index < count($milestones) - 1): ?>
            style="--line-end-position: <?php echo (($last_in_time_index + 0.5) / count($milestones)) * 100; ?>%;"
            <?php endif; ?>>
            <?php foreach ($milestones as $index => $milestone): ?>
                <?php
                $date = new DateTime($milestone->date);

                // Asegurar que siempre haya una imagen
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

                            <div class="milestone-card-description">
                                <?php echo nl2br(esc_html(substr($milestone->description, 0, 150))); ?>
                                <?php if (strlen($milestone->description) > 150): ?>...<?php endif; ?>
                            </div>
                        </div>

                        <div class="milestone-card-image">
                            <img src="<?php echo esc_url($first_image); ?>" alt="<?php echo esc_attr($milestone->title); ?>">
                            <?php if ($milestone->status !== 'pendiente'): ?>
                                <button class="milestone-card-btn" onclick="openMilestoneModal(<?php echo $index; ?>)">
                                    + Información
                                </button>
                            <?php else: ?>
                                <button class="milestone-card-btn" style="background: #666; cursor: not-allowed;" disabled>
                                    Pendiente
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


    <?php
    $documents_class = Timeline_Documents::get_instance();
    $documents = $documents_class->get_project_documents($project_id);

    if (count($documents) > 0):
    ?>
        <div style="padding: 50px; margin-top: 100px; background-image: url('https://www.bebuilt.es/wp-content/uploads/2025/12/Background.webp'); background-size: cover; background-position: center;">
            <div style="padding:50px; border-radius: 30px;background: rgba(0, 0, 0, 0.55);">
                <h2 class="titulodescarga" style="text-align: center; font-size: 36px; font-weight: 700; margin-bottom: 15px; color:white">
                    Descarga <span style="font-weight: 400;">cualquiera de los documentos</span> del proyecto
                </h2>

                <div style="display: flex; flex-wrap: wrap; gap: 40px; justify-content: center; margin-top:50px; max-width: 1200px; margin-left: auto; margin-right: auto;">
                    <?php foreach ($documents as $doc): ?>
                        <a href="<?php echo esc_url($doc->file_url); ?>"
                            download
                            target="_blank"
                            style="text-decoration: none; text-align: center; transition: transform 0.3s; width: 180px;"
                            onmouseover="this.style.transform='translateY(-5px)'"
                            onmouseout="this.style.transform='translateY(0)'">
                            <div style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-21.svg" alt="Descargar" style="width: 90%;">
                            </div>
                            <div style="color: white; font-size: 17px; font-weight: 600; max-width: 180px; word-wrap: break-word; margin: 0 auto;">
                                <?php echo esc_html($doc->title); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>


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
                        <button class="carousel-nav-btn" id="carousel-prev-top" onclick="changeSlide(-1)" title="Imagen anterior">
                            <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-18.svg" alt="">
                        </button>
                        <button class="carousel-nav-btn" id="carousel-next-top" onclick="changeSlide(1)" title="Imagen siguiente">
                            <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-19.svg" alt="">
                        </button>
                    </div>

                    <button class="modal-close" onclick="closeMilestoneModal()"><img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-20.svg" alt=""></button>
                </div>
            </div>

            <div class="anchomodal" style="display: flex; gap: 15px; justify-content:space-between;">
                <div class="modal-info">
                    <h2 class="modal-title" id="modal-title"></h2>
                    <div class="modal-description" id="modal-description"></div>

                    <div class="milestone-nav-arrows">
                        <button class="milestone-nav-btn" id="prev-milestone-btn" onclick="navigateMilestone(-1)">
                            < Anterior
                                </button>
                                <button class="milestone-nav-btn" id="next-milestone-btn" onclick="navigateMilestone(1)">
                                    Siguiente >
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

        // ===== NUEVA FUNCIÓN: SCROLL AL HITO CORRESPONDIENTE =====
        function scrollToMilestone(index) {
            const milestoneCard = document.getElementById('milestone-' + index);
            if (milestoneCard) {
                // Desactivar el scroll sincronizado temporalmente
                isScrolling = true;

                // Activar el item en la barra superior
                const timelineItems = document.querySelectorAll('.timeline-top-item');
                timelineItems.forEach((item, idx) => {
                    if (idx === index) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });

                // Activar la card correspondiente
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

                // Reactivar después de 1 segundo
                setTimeout(() => {
                    isScrolling = false;
                }, 1000);
            }
        }

        // ===== NUEVA FUNCIÓN: SINCRONIZACIÓN DEL SCROLL =====
        function setupScrollSync() {
            const verticalTimeline = document.getElementById('verticalTimeline');
            const timelineBarInner = document.getElementById('timelineBarInner');
            const milestoneCards = document.querySelectorAll('.milestone-card');

            if (!verticalTimeline || !timelineBarInner || milestoneCards.length === 0) {
                return;
            }

            let ticking = false;

            window.addEventListener('scroll', function() {
                if (isScrolling) return; // No sincronizar si estamos haciendo scroll programático

                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        updateTimelinePosition();
                        ticking = false;
                    });
                    ticking = true;
                }
            });

            function updateTimelinePosition() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const windowHeight = window.innerHeight;
                const timelineStart = verticalTimeline.offsetTop;

                // Punto de referencia: 1/3 desde arriba de la pantalla
                const detectionPoint = scrollTop + (windowHeight / 3);

                let closestIndex = 0;
                let closestDistance = Infinity;

                // Si estamos antes del timeline, activar el primero
                if (scrollTop < timelineStart - 200) {
                    closestIndex = 0;
                } else {
                    // Buscar el hito cuyo centro esté más cerca del punto de detección
                    milestoneCards.forEach((card, index) => {
                        const cardTop = card.offsetTop;
                        const cardHeight = card.offsetHeight;
                        const cardCenter = cardTop + (cardHeight / 2);

                        // Calcular distancia al punto de detección
                        const distance = Math.abs(cardCenter - detectionPoint);

                        // Si este hito está más cerca que el anterior, actualizarlo
                        if (distance < closestDistance) {
                            closestDistance = distance;
                            closestIndex = index;
                        }
                    });
                }

                // Desplazar la barra horizontal para centrar el hito correspondiente
                const timelineItems = timelineBarInner.querySelectorAll('.timeline-top-item');
                if (timelineItems[closestIndex]) {
                    const itemLeft = timelineItems[closestIndex].offsetLeft;
                    const itemWidth = timelineItems[closestIndex].offsetWidth;
                    const containerWidth = timelineBarInner.parentElement.offsetWidth;

                    // Calcular el scroll para centrar el item
                    const scrollLeft = itemLeft - (containerWidth / 2) + (itemWidth / 2);

                    timelineBarInner.parentElement.scrollTo({
                        left: scrollLeft,
                        behavior: 'smooth'
                    });

                    // Destacar el item activo en la barra superior
                    timelineItems.forEach((item, idx) => {
                        if (idx === closestIndex) {
                            item.classList.add('active');
                        } else {
                            item.classList.remove('active');
                        }
                    });

                    // Destacar también el milestone-card activo
                    milestoneCards.forEach((card, idx) => {
                        if (idx === closestIndex) {
                            card.classList.add('active');
                        } else {
                            card.classList.remove('active');
                        }
                    });
                }
            }

        }

        function openMilestoneModal(index) {
            currentMilestoneIndex = index;
            currentSlide = 0;
            const milestone = milestones[index];

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

            // Crear contenedor de slides
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

            // Inicializar funcionalidad de swipe
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
            // Buscar el siguiente hito válido (no pendiente)
            let newIndex = currentMilestoneIndex + direction;

            while (newIndex >= 0 && newIndex < milestones.length) {
                if (milestones[newIndex].status !== 'pendiente') {
                    // Encontramos un hito válido, actualizar el contenido sin cerrar
                    currentMilestoneIndex = newIndex;
                    currentSlide = 0;
                    const milestone = milestones[newIndex];

                    // Aplicar una pequeña animación de transición
                    const modalContent = document.querySelector('.modal-content');
                    modalContent.style.opacity = '0.5';
                    modalContent.style.transform = 'scale(0.98)';

                    setTimeout(() => {
                        // Actualizar el fondo según el estado
                        modalContent.className = 'modal-content status-' + milestone.status;

                        // Actualizar fecha
                        const date = new Date(milestone.date);
                        document.getElementById('modal-date').textContent = date.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });

                        // Actualizar título y descripción
                        document.getElementById('modal-title').textContent = milestone.title;
                        document.getElementById('modal-description').innerHTML = milestone.description.replace(/\n/g, '<br>');

                        // Actualizar estado
                        const statusElement = document.getElementById('modal-status');
                        const statusLabels = {
                            'pendiente': 'Pendiente',
                            'en_proceso': 'En Proceso',
                            'finalizado': 'Finalizado'
                        };
                        statusElement.textContent = statusLabels[milestone.status] || milestone.status;
                        statusElement.className = 'modal-status status-' + milestone.status;

                        // Recargar carrusel
                        loadCarousel(milestone);

                        // Actualizar botones de navegación
                        updateMilestoneNavButtons();
                        updateCarouselNavButtons();

                        // Restaurar la animación
                        modalContent.style.opacity = '1';
                        modalContent.style.transform = 'scale(1)';
                    }, 200);

                    return;
                }
                newIndex += direction;
            }

            // Si llegamos aquí, no hay más hitos en esa dirección
            console.log('No hay más hitos ' + (direction > 0 ? 'siguientes' : 'anteriores'));
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

        window.onclick = function(event) {
            const modal = document.getElementById('milestoneModal');
            if (event.target == modal) {
                closeMilestoneModal();
            }
        }

        // Inicializar la sincronización del scroll cuando cargue la página
        document.addEventListener('DOMContentLoaded', function() {
            setupScrollSync();
        });


        function initSwipe(container) {
            let touchStartX = 0;
            let touchEndX = 0;
            let touchStartY = 0;
            let touchEndY = 0;
            let isDragging = false;

            // Touch events para móviles
            container.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
                isDragging = true;
                container.classList.add('dragging');
            }, {
                passive: true
            });

            container.addEventListener('touchmove', function(e) {
                if (!isDragging) return;

                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;

                // Calcular la diferencia
                const deltaX = Math.abs(touchEndX - touchStartX);
                const deltaY = Math.abs(touchEndY - touchStartY);

                // Si el movimiento horizontal es mayor que el vertical, prevenir scroll
                if (deltaX > deltaY) {
                    e.preventDefault();
                }
            }, {
                passive: false
            });

            container.addEventListener('touchend', function(e) {
                if (!isDragging) return;

                touchEndX = e.changedTouches[0].screenX;
                touchEndY = e.changedTouches[0].screenY;
                isDragging = false;
                container.classList.remove('dragging');

                handleSwipe();
            }, {
                passive: true
            });

            // Mouse events para desktop (opcional, pero útil)
            let mouseStartX = 0;
            let isMouseDragging = false;

            container.addEventListener('mousedown', function(e) {
                mouseStartX = e.screenX;
                isMouseDragging = true;
                container.classList.add('dragging');
                e.preventDefault();
            });

            container.addEventListener('mousemove', function(e) {
                if (!isMouseDragging) return;
                e.preventDefault();
            });

            container.addEventListener('mouseup', function(e) {
                if (!isMouseDragging) return;

                const mouseEndX = e.screenX;
                isMouseDragging = false;
                container.classList.remove('dragging');

                const deltaX = mouseStartX - mouseEndX;

                // Mínimo 50px de arrastre para cambiar slide
                if (Math.abs(deltaX) > 50) {
                    if (deltaX > 0) {
                        // Arrastre hacia la izquierda = siguiente
                        changeSlide(1);
                    } else {
                        // Arrastre hacia la derecha = anterior
                        changeSlide(-1);
                    }
                }
            });

            container.addEventListener('mouseleave', function() {
                if (isMouseDragging) {
                    isMouseDragging = false;
                    container.classList.remove('dragging');
                }
            });

            function handleSwipe() {
                const deltaX = touchStartX - touchEndX;
                const deltaY = Math.abs(touchStartY - touchEndY);

                // Mínimo 50px de swipe horizontal y que sea más horizontal que vertical
                if (Math.abs(deltaX) > 50 && Math.abs(deltaX) > deltaY) {
                    if (deltaX > 0) {
                        // Swipe hacia la izquierda = siguiente imagen
                        changeSlide(1);
                    } else {
                        // Swipe hacia la derecha = imagen anterior
                        changeSlide(-1);
                    }
                }
            }
        }
    </script>


    <?php
    // FOOTER DE WORDPRESS
    get_footer();
    ?>
</body>

</html>