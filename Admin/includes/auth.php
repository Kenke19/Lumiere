<?php
session_start();

function isAdmin() {
  return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
  if (!isAdmin()) {
    header('Location: ../auth.php');
    exit();
  }
}
