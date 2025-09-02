 <?php
$admin_pass = "101049"; // ✅ Admin password
$msg_file   = "messages.txt";
$admin_file = "admin.txt";

// --- Handle Ajax Requests ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $msg    = trim($_POST["message"] ?? "");
    $pass   = $_POST["password"] ?? "";
    $index  = intval($_POST["index"] ?? -1);
    $file   = $_POST["file"] ?? "";

    // Save Public Message
    if ($action === "public" && $msg !== "") {
        file_put_contents($msg_file, $msg . PHP_EOL, FILE_APPEND);
        exit("ok");
    }

    // Save Admin Message
    if ($action === "admin" && $msg !== "") {
        if ($pass === $admin_pass) {
            file_put_contents($admin_file, $msg . PHP_EOL, FILE_APPEND);
            exit("ok");
        } else {
            exit("wrong");
        }
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
