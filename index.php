<?php
require_once 'config.php';

// If logged in → go to dashboard
// If not logged in → show landing page
if (isset($_SESSION['email'])) {
    header("Location: dashbord.php");
} else {
    header("Location: landing.php");
}
exit();
