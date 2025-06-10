<?php
include "db_connection.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if(isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['LogInButton'])) {

    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    $username = trim($_POST['Username']);
    $password = trim($_POST['Password']);

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required";
        header("Location: login.php");
        exit();
    }

    $stmt = $db->prepare("SELECT * FROM user_accounts WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['status'] === NULL || $user['status'] === 'inactive') {
            echo "<script>
                alert('Your account is not active. Please contact administrator.');
                window.location.href = 'login.php';
            </script>";
            exit();
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'account_id' => $user['account_id'],
                'account_type' => $user['account_type'],
                'college_id' => $user['college_id'],
                'username' => $user['username'],
                'first_name' => $user['first_name'],
                'middle_name' => $user['middle_name'],
                'last_name' => $user['last_name'],
                'status' => $user['status']
            ];

            header("Location: index.php");
            exit();
        }
    }

    echo "<script>
        alert('Invalid username or password.');
        window.location.href = 'login.php';
    </script>";
    exit();
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMSBS Login</title>
    <link rel="stylesheet" href="styles/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  </head>
  <body>
    <header class="WebHeader">
      <div class="d-flex justify-content-center align-items-center h-100">
        <div class="w-100"></div>
        <div class="w-100 h-100 text-center"><h1 class="m-0 p-0">AMSBS</h1></div>
        <div class="w-100">
          <div class="d-flex justify-content-end">
        </div>
      </div>
    </header>
    <div class="container-fluid">
    <div class="BoxContainers text-center" style="max-width: 250px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            
        <div class="BoxContainersHeader p-2">
            Login
        </div>
            <div><br>
                <form method="POST" class="mx-4 mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input name="Username" class="mt-1 mb-2" type="text" placeholder="Username" required><br>
                    <input name="Password" class="mb-4" type="password" placeholder="Password" required><br>
                    <button name="LogInButton" class="btn btn-dark btn-sm" style="border-radius: 0;" type="submit">Login</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>