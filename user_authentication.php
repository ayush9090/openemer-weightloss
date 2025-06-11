require_once("$srcdir/clinic_access.php");

function authenticate_user($user, $password) {
    // ... existing authentication code ...

    // After successful authentication, store clinic_id in session
    $_SESSION['clinic_id'] = $userInfo['clinic_id'];
    if (acl_check('admin', 'super')) {
        $_SESSION['is_admin'] = true;
    }
}
