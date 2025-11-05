<?php
// This file redirects to add_destination.php with ID parameter
// The add_destination.php handles both add and edit functionality

if (isset($_GET['id'])) {
    header('Location: add_destination.php?id=' . (int)$_GET['id']);
} else {
    header('Location: destinations.php');
}
exit();
?>