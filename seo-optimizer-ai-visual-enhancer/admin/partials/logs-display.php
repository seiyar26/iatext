<div class="wrap">
    <h1>Logs SEO AI Optimizer</h1>
    
    <div class="logs-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Post ID</th>
                    <th>Action</th>
                    <th>Statut</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'seoai_logs';
                $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100", ARRAY_A);
                
                if (!empty($logs)) {
                    foreach ($logs as $log) {
                        $status_class = $log['status'] === 'success' ? 'success' : ($log['status'] === 'error' ? 'error' : 'warning');
                        ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($log['post_id']); ?>" target="_blank">
                                    <?php echo $log['post_id']; ?>
                                </a>
                            </td>
                            <td><?php echo $log['action']; ?></td>
                            <td><span class="log-status <?php echo $status_class; ?>"><?php echo $log['status']; ?></span></td>
                            <td><?php echo $log['message']; ?></td>
                            <td><?php echo $log['created_at']; ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="6">Aucun log disponible.</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <style>
        .log-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .log-status.success {
            background-color: #d4edda;
            color: #155724;
        }
        .log-status.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .log-status.warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .logs-container {
            margin-top: 20px;
        }
    </style>
</div>
