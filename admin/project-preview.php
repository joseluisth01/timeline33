<?php
/**
 * Template: Vista Previa del Timeline (Vista Administrador simulando Cliente)
 * Archivo: admin/project-preview.php
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
            background: #0a0a0a;
            color: #ffffff;
        }

        /* NAVBAR ADMIN CON INDICADOR DE VISTA PREVIA */
        .navbar {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
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
            padding: 60px 40px;
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
        }

        .timeline-wrapper {
            position: relative;
            margin: 80px 0;
        }

        /* Barra superior timeline */
        .timeline-bar-container {
            width: 100%;
            padding: 20px 0;
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

        .timeline-top-item.status-pendiente { background: #EDEDED; }
        .timeline-top-item.status-en_proceso { background: #FDC425; }
        .timeline-top-item.status-finalizado { background: #FFDE88; }

        .timeline-top-item.status-pendiente .timeline-top-point { background: #EDEDED; }
        .timeline-top-item.status-en_proceso .timeline-top-point { background: #FDC425; }
        .timeline-top-item.status-finalizado .timeline-top-point { background: #FFDE88; }

        .timeline-top-date {
            font-size: 13px;
            font-weight: 600;
            margin-top: 0px;
            letter-spacing: 0.5px;
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

        .milestone-card:nth-child(odd)::before { left: 50%; }
        .milestone-card:nth-child(even)::before { right: 50%; }

        .milestone-card-image {
            position: relative;
            width: 180px;
            height: 120px;
            flex-shrink: 0;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }

        .milestone-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        /* Documentos */
        .documents-section {
            padding: 50px;
            margin-top: 100px;
            background-image: url('https://www.bebuilt.es/wp-content/uploads/2025/12/Background.webp');
            background-size: cover;
            background-position: center;
        }

        .documents-inner {
            padding: 50px;
            border-radius: 30px;
            background: rgba(0, 0, 0, 0.55);
        }

        .documents-title {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
        }

        .documents-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            justify-content: center;
            margin-top: 50px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .document-item {
            text-decoration: none;
            text-align: center;
            width: 180px;
        }

        .document-icon {
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .document-icon img { width: 90%; }

        .document-title {
            color: white;
            font-size: 17px;
            font-weight: 600;
            word-wrap: break-word;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
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

    <!-- Header -->
    <div class="header-section">
        <h1><?php echo esc_html($project->name); ?></h1>
        <p><?php echo esc_html($project->description); ?></p>
    </div>

    <!-- Contenido (copiado de project-timeline.php) -->
    <div class="container">
        <!-- Barra superior -->
        <div class="timeline-bar-container">
            <div class="timeline-bar-inner">
                <?php foreach ($milestones as $index => $milestone): ?>
                    <?php
                    $date = new DateTime($milestone->date);
                    $status_class = 'status-' . esc_attr($milestone->status);
                    ?>
                    <div class="timeline-top-item <?php echo $status_class; ?>">
                        <div class="timeline-top-point"></div>
                        <div class="timeline-top-date"><?php echo $date->format('d/m/Y'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

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
                <div class="milestone-card status-<?php echo esc_attr($milestone->status); ?>">
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

    <!-- Documentos -->
    <?php
    $documents_class = Timeline_Documents::get_instance();
    $documents = $documents_class->get_project_documents($project_id);
    if (count($documents) > 0):
    ?>
        <div class="documents-section">
            <div class="documents-inner">
                <h2 class="documents-title">
                    Descarga <span style="font-weight: 400;">cualquiera de los documentos</span> del proyecto
                </h2>
                <div class="documents-grid">
                    <?php foreach ($documents as $doc): ?>
                        <a href="<?php echo esc_url($doc->file_url); ?>" class="document-item" download target="_blank">
                            <div class="document-icon">
                                <img src="https://www.bebuilt.es/wp-content/uploads/2025/12/Vector-21.svg" alt="Descargar">
                            </div>
                            <div class="document-title"><?php echo esc_html($doc->title); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>