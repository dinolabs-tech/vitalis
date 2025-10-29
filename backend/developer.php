<?php
session_start();

// Check if the user is logged in and is a super user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Superuser') {
    header("Location: login.php");
    exit();
}

// Handle log fetching
if (isset($_GET['action']) && $_GET['action'] === 'fetch_log' && isset($_GET['log_type'])) {
    $log_type = $_GET['log_type'];
    $log_file = '';

    if ($log_type === 'error_log') {
        $log_file = 'error_log.txt'; // Assuming error_log.txt is in the parent directory
    } elseif ($log_type === 'backup_log') {
        $log_file = 'backup.log'; // Assuming backup.log is in the backend directory
    } elseif ($log_type === 'error_log_no_ext') {
        $log_file = 'error_log'; // Assuming error_log (no extension) is in the current directory
    } elseif ($log_type === 'deploy_log') {
        $log_file = '../deploy.log'; // Assuming deploy.log is in the parent directory
    }

    if (!empty($log_file) && file_exists($log_file)) {
        echo file_get_contents($log_file);
    } else {
        echo "Log file not found or specified.";
    }
    exit(); // Exit after serving the log content
}

// Handle backup download
if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
    $file = 'backup_dinolabs_vitalis.sql'; // The name of the SQL backup file

    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        echo "Backup file not found.";
    }
    exit(); // Exit after serving the file
}

// Handle clearing logs
if (isset($_GET['action']) && $_GET['action'] === 'clear_log' && isset($_GET['log_type'])) {
    $log_type = $_GET['log_type'];
    $log_file = '';

    if ($log_type === 'error_log') {
        $log_file = 'error_log.txt';
    } elseif ($log_type === 'backup_log') {
        $log_file = 'backup.log';
    } elseif ($log_type === 'error_log_no_ext') {
        $log_file = 'error_log';
    } elseif ($log_type === 'deploy_log') {
        $log_file = '../deploy.log';
    }

    if (!empty($log_file) && file_exists($log_file)) {
        if (file_put_contents($log_file, '') !== false) {
            echo "Log file cleared successfully.";
        } else {
            echo "Error clearing log file.";
        }
    } else {
        echo "Log file not found or specified.";
    }
    exit(); // Exit after clearing the log
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include('components/head.php'); ?>

<body>
  <div class="wrapper">
    <?php include('components/sidebar.php'); ?>

    <div class="main-panel">
      <?php include('components/navbar.php'); ?>


            <div class="container">
                <div class="page-inner">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Developer</h3>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="superdashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Developer</li>
                            </ol>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h2>Developer Tools</h2>
                            <p>This page provides access to developer-specific tools and logs.</p>

                            <div class="card mt-4">
                                <div class="card-header">
                                    View Logs and Download Backup
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-12 d-flex flex-wrap gap-1">
                                            <button class="btn btn-primary" id="viewErrorLog">View error_log.txt</button>
                                            <button class="btn btn-warning" id="clearErrorLog">Clear error_log.txt</button>
                                            <button class="btn btn-info" id="viewBackupLog">View backup.log</button>
                                            <button class="btn btn-danger" id="clearBackupLog">Clear backup.log</button>
                                            <button class="btn btn-primary" id="viewErrorLogNoExt">View error_log</button>
                                            <button class="btn btn-warning" id="clearErrorLogNoExt">Clear error_log</button>
                                            <button class="btn btn-info" id="viewDeployLog">View deploy.log</button>
                                            <button class="btn btn-danger" id="clearDeployLog">Clear deploy.log</button>
                                            <a href="developer.php?action=download_backup" class="btn btn-success">
                                                Download SQL Backup
                                            </a>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div id="logContent" class="mt-3"
                                            style="white-space: pre-wrap; background-color: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 500px; overflow-y: scroll;">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <?php include('component/footer.php'); ?>
            </div>


        </div>
        <?php include('component/script.php'); ?>
        <!-- Includes general JavaScript scripts for the page -->

        <script>
            $(document).ready(function() {
                $('#viewErrorLog').click(function() {
                    $.ajax({
                        url: 'developer.php', // Call developer.php itself
                        type: 'GET',
                        data: {
                            action: 'fetch_log',
                            log_type: 'error_log'
                        },
                        success: function(response) {
                            $('#logContent').text(response);
                        },
                        error: function() {
                            $('#logContent').text('Error fetching error_log.txt');
                        }
                    });
                });

                $('#viewBackupLog').click(function() {
                    $.ajax({
                        url: 'developer.php', // Call developer.php itself
                        type: 'GET',
                        data: {
                            action: 'fetch_log',
                            log_type: 'backup_log'
                        },
                        success: function(response) {
                            $('#logContent').text(response);
                        },
                        error: function() {
                            $('#logContent').text('Error fetching backup.log');
                        }
                    });
                });

                $('#clearErrorLog').click(function() {
                    if (confirm(
                            'Are you sure you want to clear error_log.txt? This action cannot be undone.')) {
                        $.ajax({
                            url: 'developer.php',
                            type: 'GET',
                            data: {
                                action: 'clear_log',
                                log_type: 'error_log'
                            },
                            success: function(response) {
                                alert(response);
                                $('#logContent').text(''); // Clear displayed content
                            },
                            error: function() {
                                alert('Error clearing error_log.txt');
                            }
                        });
                    }
                });

                $('#clearBackupLog').click(function() {
                    if (confirm('Are you sure you want to clear backup.log? This action cannot be undone.')) {
                        $.ajax({
                            url: 'developer.php',
                            type: 'GET',
                            data: {
                                action: 'clear_log',
                                log_type: 'backup_log'
                            },
                            success: function(response) {
                                alert(response);
                                $('#logContent').text(''); // Clear displayed content
                            },
                            error: function() {
                                alert('Error clearing backup.log');
                            }
                        });
                    }
                });

                $('#viewErrorLogNoExt').click(function() {
                    $.ajax({
                        url: 'developer.php',
                        type: 'GET',
                        data: {
                            action: 'fetch_log',
                            log_type: 'error_log_no_ext'
                        },
                        success: function(response) {
                            $('#logContent').text(response);
                        },
                        error: function() {
                            $('#logContent').text('Error fetching error_log');
                        }
                    });
                });

                $('#clearErrorLogNoExt').click(function() {
                    if (confirm('Are you sure you want to clear error_log? This action cannot be undone.')) {
                        $.ajax({
                            url: 'developer.php',
                            type: 'GET',
                            data: {
                                action: 'clear_log',
                                log_type: 'error_log_no_ext'
                            },
                            success: function(response) {
                                alert(response);
                                $('#logContent').text(''); // Clear displayed content
                            },
                            error: function() {
                                alert('Error clearing error_log');
                            }
                        });
                    }
                });

                $('#viewDeployLog').click(function() {
                    $.ajax({
                        url: 'developer.php',
                        type: 'GET',
                        data: {
                            action: 'fetch_log',
                            log_type: 'deploy_log'
                        },
                        success: function(response) {
                            $('#logContent').text(response);
                        },
                        error: function() {
                            $('#logContent').text('Error fetching deploy.log');
                        }
                    });
                });

                $('#clearDeployLog').click(function() {
                    if (confirm('Are you sure you want to clear deploy.log? This action cannot be undone.')) {
                        $.ajax({
                            url: 'developer.php',
                            type: 'GET',
                            data: {
                                action: 'clear_log',
                                log_type: 'deploy_log'
                            },
                            success: function(response) {
                                alert(response);
                                $('#logContent').text(''); // Clear displayed content
                            },
                            error: function() {
                                alert('Error clearing deploy.log');
                            }
                        });
                    }
                });
            });
        </script>
</body>

</html>