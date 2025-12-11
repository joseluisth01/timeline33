<?php
/**
 * Template: Visualización de Audit Log (Solo Admin/Super Admin)
 * Archivo: admin/audit-log.php
 */

$audit_log = Timeline_Audit_Log::get_instance();

// Procesar filtros
$filters = array();
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;
$filters['offset'] = ($page - 1) * $per_page;
$filters['limit'] = $per_page;

if (isset($_GET['filter_user']) && $_GET['filter_user'] !== '') {
    $filters['user_id'] = intval($_GET['filter_user']);
}

if (isset($_GET['filter_action']) && $_GET['filter_action'] !== '') {
    $filters['action'] = sanitize_text_field($_GET['filter_action']);
}

if (isset($_GET['filter_entity_type']) && $_GET['filter_entity_type'] !== '') {
    $filters['entity_type'] = sanitize_text_field($_GET['filter_entity_type']);
}

if (isset($_GET['filter_date_from']) && $_GET['filter_date_from'] !== '') {
    $filters['date_from'] = sanitize_text_field($_GET['filter_date_from']) . ' 00:00:00';
}

if (isset($_GET['filter_date_to']) && $_GET['filter_date_to'] !== '') {
    $filters['date_to'] = sanitize_text_field($_GET['filter_date_to']) . ' 23:59:59';
}

// Obtener logs y total
$logs = $audit_log->get_logs($filters);
$total_logs = $audit_log->count_logs($filters);
$total_pages = ceil($total_logs / $per_page);

// Obtener solo administradores para el filtro (excluir clientes)
global $wpdb;
$table_users = $wpdb->prefix . 'timeline_users';
$all_users = $wpdb->get_results("SELECT id, username, role FROM {$table_users} WHERE role IN ('super_admin', 'administrador') ORDER BY username ASC");

// Estadísticas del período filtrado
$stats = $audit_log->get_activity_stats(
    isset($filters['date_from']) ? $filters['date_from'] : null,
    isset($filters['date_to']) ? $filters['date_to'] : null
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Actividad - Timeline</title>
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
            max-width: 1800px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 25px;
        }
        
        .stat-value {
            font-size: 42px;
            font-weight: 200;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .filters-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group label {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        
        .filter-group select,
        .filter-group input[type="date"] {
            padding: 12px;
            background: black;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-size: 13px;
        }
        
        .filter-group select {
            cursor: pointer;
        }
        
        .filter-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-filter {
            padding: 12px 30px;
            background: rgba(253, 196, 37, 0.15);
            color: #FDC425;
            border: 1px solid #FDC425;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-filter:hover {
            background: rgba(253, 196, 37, 0.25);
        }
        
        .btn-clear {
            padding: 12px 30px;
            background: transparent;
            color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-clear:hover {
            border-color: rgba(255, 255, 255, 0.5);
            color: rgba(255, 255, 255, 0.8);
        }
        
        .logs-table-container {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        th {
            padding: 20px 15px;
            text-align: left;
            font-weight: 300;
            color: rgba(255, 255, 255, 0.6);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        td {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .badge {
            padding: 5px 12px;
            font-size: 9px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-radius: 3px;
            font-weight: 500;
        }
        
        .badge-create { background: rgba(40, 167, 69, 0.2); color: #51cf66; }
        .badge-update { background: rgba(0, 123, 255, 0.2); color: #4dabf7; }
        .badge-delete { background: rgba(255, 107, 107, 0.2); color: #ff6b6b; }
        .badge-view { background: rgba(108, 117, 125, 0.2); color: #adb5bd; }
        .badge-upload { background: rgba(253, 196, 37, 0.2); color: #FDC425; }
        .badge-download { background: rgba(102, 187, 106, 0.2); color: #66bb6a; }
        .badge-login { background: rgba(156, 39, 176, 0.2); color: #ba68c8; }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(253, 196, 37, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #FDC425;
            font-weight: 600;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .user-name {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .user-role {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.4);
        }
        
        .ip-address {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
            padding: 30px;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(253, 196, 37, 0.3);
            color: #FDC425;
        }
        
        .pagination .current {
            background: rgba(253, 196, 37, 0.15);
            border-color: #FDC425;
            color: #FDC425;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand"><img style="height:35px" src="https://www.bebuilt.es/wp-content/uploads/2025/12/logo-bebuilt-blanco.png" alt=""></div>
        <a href="<?php echo home_url('/timeline-dashboard'); ?>" class="btn-back">← Dashboard</a>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>Registro de Actividad</h1>
            <p>Auditoría completa de todas las acciones en el sistema</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Total Registros</div>
            </div>
            <?php foreach ($stats['by_action'] as $action): ?>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($action->count); ?></div>
                    <div class="stat-label"><?php echo esc_html($audit_log->format_action($action->action)); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Usuario</label>
                        <select name="filter_user">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user->id; ?>" <?php echo (isset($_GET['filter_user']) && $_GET['filter_user'] == $user->id) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($user->username); ?> (<?php echo esc_html($user->role); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Acción</label>
                        <select name="filter_action">
                            <option value="">Todas las acciones</option>
                            <option value="create" <?php echo (isset($_GET['filter_action']) && $_GET['filter_action'] == 'create') ? 'selected' : ''; ?>>Crear</option>
                            <option value="update" <?php echo (isset($_GET['filter_action']) && $_GET['filter_action'] == 'update') ? 'selected' : ''; ?>>Actualizar</option>
                            <option value="delete" <?php echo (isset($_GET['filter_action']) && $_GET['filter_action'] == 'delete') ? 'selected' : ''; ?>>Eliminar</option>
                            <option value="view" <?php echo (isset($_GET['filter_action']) && $_GET['filter_action'] == 'view') ? 'selected' : ''; ?>>Visualizar</option>
                            <option value="upload" <?php echo (isset($_GET['filter_action']) && $_GET['filter_action'] == 'upload') ? 'selected' : ''; ?>>Subir</option>
                            <option value="download" <?php echo (isset($_GET['filter_action']) && $_GET['filter_action'] == 'download') ? 'selected' : ''; ?>>Descargar</option>
                            <option value="login" <?php echo (isset($_GET['filter_action']) && $_GET['filter_action'] == 'login') ? 'selected' : ''; ?>>Login</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Tipo de Entidad</label>
                        <select name="filter_entity_type">
                            <option value="">Todos los tipos</option>
                            <option value="project" <?php echo (isset($_GET['filter_entity_type']) && $_GET['filter_entity_type'] == 'project') ? 'selected' : ''; ?>>Proyecto</option>
                            <option value="milestone" <?php echo (isset($_GET['filter_entity_type']) && $_GET['filter_entity_type'] == 'milestone') ? 'selected' : ''; ?>>Hito</option>
                            <option value="document" <?php echo (isset($_GET['filter_entity_type']) && $_GET['filter_entity_type'] == 'document') ? 'selected' : ''; ?>>Documento</option>
                            <option value="user" <?php echo (isset($_GET['filter_entity_type']) && $_GET['filter_entity_type'] == 'user') ? 'selected' : ''; ?>>Usuario</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Desde</label>
                        <input type="date" name="filter_date_from" value="<?php echo isset($_GET['filter_date_from']) ? esc_attr($_GET['filter_date_from']) : ''; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Hasta</label>
                        <input type="date" name="filter_date_to" value="<?php echo isset($_GET['filter_date_to']) ? esc_attr($_GET['filter_date_to']) : ''; ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">Aplicar Filtros</button>
                    <a href="<?php echo home_url('/timeline-audit-log'); ?>" class="btn-clear">Limpiar</a>
                </div>
            </form>
        </div>
        
        <div class="logs-table-container">
            <?php if (count($logs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Entidad</th>
                            <th>Detalles</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $date = new DateTime($log->created_at);
                                    echo $date->format('d/m/Y H:i:s');
                                    ?>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($log->username, 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo esc_html($log->username); ?></div>
                                            <div class="user-role"><?php echo esc_html($log->user_role); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo esc_attr($log->action); ?>">
                                        <?php echo esc_html($audit_log->format_action($log->action)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html($audit_log->format_entity_type($log->entity_type)); ?>
                                    <span style="color: rgba(255,255,255,0.4); font-size: 11px;">
                                        #<?php echo $log->entity_id; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log->details); ?></td>
                                <td>
                                    <span class="ip-address"><?php echo esc_html($log->ip_address); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $base_url = home_url('/timeline-audit-log?');
                        $query_params = $_GET;
                        unset($query_params['paged']);
                        $query_string = http_build_query($query_params);
                        
                        if ($page > 1): ?>
                            <a href="<?php echo $base_url . $query_string . '&paged=' . ($page - 1); ?>">← Anterior</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo $base_url . $query_string . '&paged=' . $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url . $query_string . '&paged=' . ($page + 1); ?>">Siguiente →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>No hay registros de actividad con los filtros seleccionados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>