<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Add Student';
$errors = [];

// Values kept on validation failure so the user doesn't retype everything
$form = [
    'STUDENT_NO'    => '',
    'LASTNAME'      => '',
    'FIRSTNAME'     => '',
    'MIDDLENAME'    => '',
    'GENDER'        => '',
    'BIRTHDATE'     => '',
    'ADDRESS'       => '',
    'CONTACT'       => '',
    'EMAIL'         => '',
    'COURSE_ID'     => '',
    'YEARLEVEL_ID'  => '',
    'SECTION_ID'    => '',
    'SCHOOLYEAR_ID' => '',
    'STATUS'        => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $default) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    if ($form['STUDENT_NO'] === '') $errors[] = "Student No. is required.";
    if ($form['LASTNAME'] === '')   $errors[] = "Last name is required.";
    if ($form['FIRSTNAME'] === '')  $errors[] = "First name is required.";
    if ($form['EMAIL'] !== '' && !filter_var($form['EMAIL'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email address is not valid.";
    }

    // Uniqueness check on student number
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT ID FROM tbl_student WHERE STUDENT_NO = ?");
        $check->execute([$form['STUDENT_NO']]);
        if ($check->fetch()) {
            $errors[] = "That Student No. is already in use.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO tbl_student
              (STUDENT_NO, LASTNAME, FIRSTNAME, MIDDLENAME, GENDER, BIRTHDATE, ADDRESS, CONTACT, EMAIL,
               COURSE_ID, YEARLEVEL_ID, SECTION_ID, SCHOOLYEAR_ID, STATUS)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $form['STUDENT_NO'], $form['LASTNAME'], $form['FIRSTNAME'], $form['MIDDLENAME'],
            $form['GENDER'] ?: null, $form['BIRTHDATE'] ?: null, $form['ADDRESS'], $form['CONTACT'], $form['EMAIL'],
            $form['COURSE_ID'] ?: null, $form['YEARLEVEL_ID'] ?: null, $form['SECTION_ID'] ?: null,
            $form['SCHOOLYEAR_ID'] ?: null, $form['STATUS'],
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Student added successfully.'];
        header("Location: students.php");
        exit;
    }
}

$courses     = $pdo->query("SELECT ID, COURSE FROM tbl_course ORDER BY COURSE")->fetchAll();
$yearlevels  = $pdo->query("SELECT ID, YEARLEVEL FROM tbl_yearlevel ORDER BY ID")->fetchAll();
$sections    = $pdo->query("SELECT ID, SECTION FROM tbl_section ORDER BY SECTION")->fetchAll();
$schoolyears = $pdo->query("SELECT ID, SCHOOLYEAR FROM tbl_schoolyear ORDER BY SCHOOLYEAR")->fetchAll();

require_once 'includes/header.php';
?>

<div class="panel form-panel">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <ul><?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="student_add.php" class="stacked-form">
    <?php include 'includes/student_form_fields.php'; ?>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Student</button>
      <a href="students.php" class="btn btn-link">Cancel</a>
    </div>
  </form>
</div>

<?php require_once 'includes/footer.php'; ?>
