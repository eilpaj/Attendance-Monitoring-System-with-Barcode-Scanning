<?php
include "../db_connection.php";

if(!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['LogOutButton'])){
    session_destroy();
    header("Location: ../login.php");
}

$user = $_SESSION['user'];
$lastName = ucwords(strtolower($user['last_name']));
$firstName = ucwords(strtolower($user['first_name']));
$middleInitial = ucfirst(strtolower(substr($user['middle_name'], 0, 1))) . ".";

$AccountName = $lastName . ", " . $firstName . " " . $middleInitial;

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMSBS Account</title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  </head>
  <body>
    <header class="WebHeader">
      <div class="d-flex justify-content-center align-items-center h-100">
        <div class="w-100"></div>
        <div class="w-100 h-100 text-center"><h1 class="m-0 p-0">AMSBS • 
        <?php
          $collegeid = $user['college_id'];
          $query = "SELECT college_acronym FROM college_info WHERE college_id = $collegeid";
          $GetCollegeId = $db->query("$query");
          $ResultGetCollegeId = $GetCollegeId->fetch_assoc();

          echo $ResultGetCollegeId['college_acronym'];
        ?> 
        </h1></div>
        <div class="w-100">
          <div class="d-flex justify-content-end">
          <div><a href="Account.php"><?php echo $AccountName ?></a></div>
          <div class="mx-3">
              <button style="background: none; border: none; padding: 0; margin: 0;cursor: pointer; color: inherit;" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" style="margin-top: -4px;"
                  class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                  <path fill-rule="evenodd"
                    d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                  <path fill-rule="evenodd"
                    d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                </svg>
              </button>           
          </div>
          </div>
        </div>
      </div>
    </header>
    <div class="container-fluid">
      <div class="d-flex">
      <div class="sidebar">
      <div>
        <nav class="">
          <ul>
            <!-- Dashboard -->
            <li class="mb-2">
              <a href="../index.php" style="color: white;text-decoration: none;">
              <div class="d-flex align-items-center px-2 py-2">
                <div class="me-2" style="margin-left: 2px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white"
                    class="bi bi-house-door-fill" viewBox="0 0 16 16">
                    <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5" />
                  </svg>
                </div>
                <div class="label">Dashboard</div>
              </div>
              </a>
            </li>
        
            <!-- Attendance Records -->
            <li class="mb-2">
              <a href="ManageAttendanceRecords.php" style="color: white;text-decoration: none;">
              <div class="d-flex align-items-center px-2 py-2">
                <div class="me-2" style="margin-left: 2px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white"
                    class="bi bi-clipboard2-data-fill" viewBox="0 0 16 16">
                    <path
                      d="M10 .5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5.5.5 0 0 1-.5.5.5.5 0 0 0-.5.5V2a.5.5 0 0 0 .5.5h5A.5.5 0 0 0 11 2v-.5a.5.5 0 0 0-.5-.5.5.5 0 0 1-.5-.5" />
                    <path
                      d="M4.085 1H3.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1h-.585q.084.236.085.5V2a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 4 2v-.5q.001-.264.085-.5M10 7a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0zm-6 4a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0zm4-3a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1" />
                  </svg>
                </div>
                <div class="label">Attendance Records</div>
              </div>
              </a>
            </li>

            <!-- Student Profiles -->
            <li class="mb-2">
                <a href="StudentProfiles.php" style="color: white; text-decoration: none;">
                  <div class="d-flex align-items-center px-2 py-2">
                    <div class="me-2" style="margin-left: 2px;">
                      <!-- Student Profiles Icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white" class="bi bi-people-fill" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                      </svg>
                    </div>
                    <div class="label">Student Profiles</div>
                  </div>
                </a>
              </li>
        
            <!-- Account -->
            <li class="NavCurrentPage mb-2">
              <a href="Account.php" style="color: white;text-decoration: none;">
              <div class="d-flex align-items-center px-2 py-2">
                <div class="me-2" style="margin-left: 2px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white"
                    class="bi bi-person-circle" viewBox="0 0 16 16">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                    <path fill-rule="evenodd"
                      d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
                  </svg>
                </div>
                <div class="label">Account</div>
              </div>
              </a>
            </li>
          </ul>
        </nav>
        
      </div>
    </div>
    <div style="width:50px;"></div>
      <div class="row w-100">
        <div class="col-12 p-2">

            <div class="mt-2">
                <p class="m-0" style="font-size: 24px;">
                Last Name, First Name, Middle Initial  
                </p>
                <p class="m-0" style="font-size: 24px;">
                Username  
                </p>
                <p class="m-0" style="font-size: 24px;">
                Organization / College
                </p>
            </div>
            <div>
                <button class="btn btn-sm btn-dark mt-4 mb-2" style="border-radius: 0;" data-bs-toggle="modal" data-bs-target="#editNameModal">Edit Name</button><br>
                <button class="btn btn-sm btn-dark" style="border-radius: 0;" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button><br>
                <button class="btn btn-sm btn-dark mt-4" style="border-radius: 0;">Create Account</button><br>
            </div>
            
        </div>
      </div>
    </div>
    </div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Logout Confirmation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to log out?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
        <!-- Logout Form -->
        <form method="POST">
          <button type="submit" class="btn btn-danger" name="LogOutButton">Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Edit Name Modal -->
<div class="modal fade" id="editNameModal" tabindex="-1" aria-labelledby="editNameModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="editNameModalLabel">Edit Name</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="update_name.php" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label for="lastName" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lastName" name="last_name" required>
          </div>
          <div class="mb-3">
            <label for="firstName" class="form-label">First Name</label>
            <input type="text" class="form-control" id="firstName" name="first_name" required>
          </div>
          <div class="mb-3">
            <label for="middleInitial" class="form-label">Middle Initial</label>
            <input type="text" class="form-control" id="middleInitial" name="middle_initial" maxlength="1">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Your Current Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="change_password.php" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label for="currentPassword" class="form-label">Current Password</label>
            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
          </div>
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <input type="password" class="form-control" id="newPassword" name="new_password" required>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>