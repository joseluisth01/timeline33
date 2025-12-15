<?php

/**
 * Template: Gestión de Documentos del Proyecto (Vista Administrador)
 * Archivo: admin/project-documents.php
 */

$project_id = get_query_var('timeline_id');
$projects_class = Timeline_Projects::get_instance();
$documents_class = Timeline_Documents::get_instance();

$project = $projects_class->get_project($project_id);
if (!$project) {
    wp_redirect(home_url('/timeline-proyectos'));
    exit;
}

$documents = $documents_class->get_project_documents($project_id);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - <?php echo esc_html($project->name); ?></title>
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
        }

        .navbar-brand {
            font-size: 18px;
            font-weight: 300;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.9);
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 36px;
            font-weight: 200;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 10px;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 13px;
        }

        .btn-upload {
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
            margin-bottom: 40px;
        }

        .btn-upload:hover {
            background: rgba(253, 196, 37, 0.25);
        }

        .alert {
            padding: 15px 25px;
            margin-bottom: 30px;
            font-size: 12px;
            border-left: 2px solid;
            background: rgba(255, 255, 255, 0.05);
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

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .document-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 25px;
            transition: all 0.3s;
            position: relative;
        }

        .document-card:hover {
            border-color: rgba(253, 196, 37, 0.3);
            background: rgba(255, 255, 255, 0.04);
        }

        .document-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.7;
        }

        .document-title {
            font-size: 16px;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 10px;
            word-break: break-word;
        }

        .document-info {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 15px;
        }

        .document-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        }

        .btn-small:hover {
            border-color: rgba(253, 196, 37, 0.5);
            color: #FDC425;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255, 255, 255, 0.3);
        }

        /* Modal de subir documento */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }

        .modal-content {
            background: #0a0a0a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 90%;
            max-width: 600px;
            padding: 50px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: rgba(255, 255, 255, 0.9);
        }

        .modal h2 {
            font-size: 24px;
            font-weight: 300;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 14px;
            background: black;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 14px;
        }

        .file-upload-area {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-area:hover {
            border-color: rgba(253, 196, 37, 0.5);
        }

        .file-upload-area p {
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 5px;
        }

        .file-upload-area small {
            color: rgba(255, 255, 255, 0.3);
            font-size: 11px;
        }

        .file-selected {
            margin-top: 15px;
            padding: 15px;
            background: rgba(253, 196, 37, 0.1);
            border: 1px solid rgba(253, 196, 37, 0.3);
            color: #FDC425;
            font-size: 13px;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: rgba(253, 196, 37, 0.15);
            color: #FDC425;
            border: 1px solid #FDC425;
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background: rgba(253, 196, 37, 0.25);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-brand"><img style="height:35px" src="https://www.bebuilt.es/wp-content/uploads/2025/12/logo-bebuilt-blanco.png" alt=""></div>
        <a href="<?php echo home_url('/timeline-proyectos'); ?>" class="btn-back">← Volver a Proyectos</a>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><?php echo esc_html($project->name); ?></h1>
            <p>Gestión de Documentos del Proyecto</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'uploaded':
                        echo 'Documento subido correctamente.';
                        break;
                    case 'deleted':
                        echo 'Documento eliminado correctamente.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                switch ($_GET['error']) {
                    case 'no_file':
                        echo 'No se ha seleccionado ningún archivo.';
                        break;
                    case 'upload_failed':
                        echo 'Error al subir el archivo.';
                        break;
                    case 'db_failed':
                        echo 'Error al guardar en la base de datos.';
                        break;
                    case 'delete_failed':
                        echo 'Error al eliminar el documento.';
                        break;
                    default:
                        echo 'Error al procesar la solicitud.';
                }
                ?>
            </div>
        <?php endif; ?>

        <button class="btn-upload" onclick="openUploadModal()">+ Subir Documento</button>

        <div class="documents-grid">
            <?php if (count($documents) > 0): ?>
                <?php foreach ($documents as $doc): ?>
                    <?php
                    // Iconos según tipo de archivo
                    $icon = '📄';
                    switch (strtolower($doc->file_type)) {
                        case 'pdf':
                            $icon = '📕';
                            break;
                        case 'doc':
                        case 'docx':
                            $icon = '📘';
                            break;
                        case 'xls':
                        case 'xlsx':
                            $icon = '📊';
                            break;
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'gif':
                            $icon = '🖼️';
                            break;
                        case 'zip':
                        case 'rar':
                            $icon = '📦';
                            break;
                    }

                    $upload_date = new DateTime($doc->uploaded_at);
                    ?>
                    <div class="document-card">
                        <div class="document-icon"><?php echo $icon; ?></div>
                        <h3 class="document-title"><?php echo esc_html($doc->title); ?></h3>
                        <div class="document-info">
                            <?php echo strtoupper($doc->file_type); ?> •
                            <?php echo $upload_date->format('d/m/Y'); ?>
                        </div>

                        <div class="document-actions">
                            <a href="<?php echo esc_url($doc->file_url); ?>"
                                class="btn-small"
                                download
                                target="_blank">Descargar</a>
                            <button class="btn-small" onclick="deleteDocument(<?php echo $doc->id; ?>)">Eliminar</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No hay documentos subidos en este proyecto</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para subir documento -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeUploadModal()">&times;</span>
            <h2>Subir Documento</h2>

            <form id="upload-form" method="POST" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="timeline_upload_document">
                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                <?php Timeline_Nonce::get_instance()->field('timeline_document'); ?>

                <div class="form-group">
                    <label for="document_title">Nombre del Documento *</label>
                    <input type="text" id="document_title" name="document_title" required
                        placeholder="Ej: Planos Proyecto, Licencia de Obra...">
                </div>

                <div class="form-group">
                    <label>Archivo *</label>
                    <input type="file" id="document_file" name="document_file" required style="display: none;"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.jpg,.jpeg,.png,.gif">
                    <div class="file-upload-area" onclick="document.getElementById('document_file').click()">
                        <p>Haz clic para seleccionar archivo</p>
                        <small>PDF, DOC, XLS, imágenes, ZIP (máx. 10MB)</small>
                    </div>
                    <div id="file-selected" class="file-selected" style="display: none;"></div>
                </div>

                <button type="submit" class="btn-submit" id="submit-btn" disabled>Subir Documento</button>
            </form>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
            document.getElementById('upload-form').reset();
            document.getElementById('file-selected').style.display = 'none';
            document.getElementById('submit-btn').disabled = true;
            document.body.style.overflow = 'auto';
        }

        function deleteDocument(id) {
            if (confirm('¿Estás seguro de eliminar este documento?')) {
                window.location.href = '<?php echo admin_url('admin-post.php'); ?>?action=timeline_delete_document&document_id=' + id + '&project_id=<?php echo $project_id; ?>&_timeline_nonce=<?php echo Timeline_Nonce::get_instance()->get('delete_document'); ?>';
            }
        }

        // Mostrar nombre del archivo seleccionado
        document.getElementById('document_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const fileSelected = document.getElementById('file-selected');
            const submitBtn = document.getElementById('submit-btn');

            if (fileName) {
                fileSelected.textContent = '✓ ' + fileName;
                fileSelected.style.display = 'block';
                submitBtn.disabled = false;
            } else {
                fileSelected.style.display = 'none';
                submitBtn.disabled = true;
            }
        });

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                closeUploadModal();
            }
        }
    </script>
</body>

</html>