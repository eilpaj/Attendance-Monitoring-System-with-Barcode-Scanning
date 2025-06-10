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
    <title>AMSBS Attendance Records</title>
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
            <li class="NavCurrentPage mb-2">
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
            <li class="mb-2">
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
            <div class="d-flex justify-content-between">
                <div class="w-100 d-flex">
                    <div class="w-100">
                    <div class="input-group">
                      <form method="GET" class="input-group">
                        <input style="border-radius: 0;" type="text" class="form-control" name="search" placeholder="Attendance Name"
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" aria-describedby="button-addon2">
                      
                        <button style="border-radius: 0;" class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
                      
                        <?php if (isset($_GET['search']) && $_GET['search'] !== ''): ?>
                          <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-danger" style="border-radius: 0;">Clear</a>
                        <?php endif; ?>
                      </form>  
                      </div>
                    </div>
                    <div class="w-100">

                    </div>
                    <div class="w-100 text-end"><a class="btn btn-warning" style="border-radius: 0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill" viewBox="0 0 16 16">
                            <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                            <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                          </svg>
                        Print Multiple</a></div>
                    </div>
            </div>
            <div>
                <table class="mt-2 mb-2 table table-bordered align-middle table-striped">
                    <thead class="table-dark">
                      <tr>
                        <th class="p-1 fw-normal text-center">#</th>
                        <th class="p-1 fw-normal">DATE & TIME</th>
                        <th class="p-1 fw-normal">ATTENDANCE NAME</th>
                        <th class="p-1 fw-normal">PARTICIPANTS</th>
                        <th class="p-1 fw-normal">P</th>
                        <th class="p-1 fw-normal">L</th>
                        <th class="p-1 fw-normal">A</th>
                        <th class="p-1 fw-normal">TURNOUT</th>
                      </tr>
                      <?php
$CollegeID = $user['college_id'];
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

$query = "SELECT 
            ai.id_attendance_info,
            ai.name,
            ai.section,
            DATE_FORMAT(ai.date, '%m/%d/%Y') as formatted_date,
            DATE_FORMAT(ai.time_in, '%h:%i %p') as formatted_time_in,
            DATE_FORMAT(ai.time_out, '%h:%i %p') as formatted_time_out,
            DATE_FORMAT(ai.time_in_cut_off, '%h:%i %p') as formatted_time_in_cutoff,
            DATE_FORMAT(ai.time_out_cut_off, '%h:%i %p') as formatted_time_out_cutoff,

            (SELECT COUNT(*) 
             FROM attendance_record ar 
             WHERE ar.id_attendance_info = ai.id_attendance_info) as total_participants,

            (SELECT COUNT(*) 
             FROM attendance_record ar 
             WHERE ar.id_attendance_info = ai.id_attendance_info
             AND ar.time_in IS NOT NULL 
             AND ar.time_out IS NOT NULL) as present_count,

            (SELECT COUNT(*) 
             FROM attendance_record ar 
             WHERE ar.id_attendance_info = ai.id_attendance_info
             AND ar.time_in IS NOT NULL
             AND ar.time_in > ai.time_in_cut_off
             AND ar.time_in <= ai.time_out) as late_count,

            (SELECT COUNT(*) 
             FROM attendance_record ar 
             WHERE ar.id_attendance_info = ai.id_attendance_info
             AND (ar.time_in IS NULL OR ar.time_out IS NULL)) as absent_count
            
          FROM attendance_info ai
          WHERE ai.college_id = ?
          AND ai.deleted_at IS NULL 
          AND ai.name LIKE ?
          ORDER BY ai.created_at DESC, ai.time_in DESC";

$stmt = $db->prepare($query);
$stmt->bind_param('is', $CollegeID, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
$attendanceRecords = $result->fetch_all(MYSQLI_ASSOC);

?>

<tbody>
<?php if (empty($attendanceRecords)): ?>
    <tr>
        <td colspan="8" class="text-center text-muted py-3">
            No records found.
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($attendanceRecords as $index => $record): 
        $formattedSections = implode(', ', array_map('trim', explode(',', $record['section'])));
        $turnoutPercentage = $record['total_participants'] > 0 
            ? round(($record['present_count'] / $record['total_participants']) * 100)
            : 0;
    ?>
    <tr class="Selection" onclick="window.location.href='AttendanceRecord.php?id=<?= $record['id_attendance_info'] ?>';" style="cursor: pointer;">
        <td class="p-1 text-center"><?= $index + 1 ?></td>
        <td class="p-1"><?= $record['formatted_date'] ?> <br> <?= $record['formatted_time_in'] ?> - <?= $record['formatted_time_out_cutoff'] ?></td>
        <td class="p-1"><?= htmlspecialchars($record['name']) ?></td>
        <td class="p-1" style="max-width: 300px;"><?= htmlspecialchars($formattedSections) ?></td>
        <td class="p-1"><?= $record['present_count'] ?></td>
        <td class="p-1"><?= $record['late_count'] ?></td>
        <td class="p-1"><?= $record['absent_count'] ?></td>
        <td class="p-1"><?= $record['present_count'] ?>/<?= $record['total_participants'] ?> (<?= $turnoutPercentage ?>%)</td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>

                </table>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>