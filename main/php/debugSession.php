<?php
session_start();
echo "<pre>";
echo "SESSION CONTENTS:\n";
print_r($_SESSION);
echo "</pre>";

// Check what your login actually sets
if (isset($_SESSION['userID'])) {
    echo "userID is set: " . $_SESSION['userID'] . "<br>";
}
if (isset($_SESSION['user_id'])) {
    echo "user_id is set: " . $_SESSION['user_id'] . "<br>";
}
if (isset($_SESSION['admin_id'])) {
    echo "admin_id is set: " . $_SESSION['admin_id'] . "<br>";
}
if (isset($_SESSION['userType'])) {
    echo "userType is set: " . $_SESSION['userType'] . "<br>";
}
if (isset($_SESSION['user_type'])) {
    echo "user_type is set: " . $_SESSION['user_type'] . "<br>";
}
if (isset($_SESSION['username'])) {
    echo "username is set: " . $_SESSION['username'] . "<br>";
}
?>