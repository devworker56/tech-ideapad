<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDVA - Micro-Dons Vérifiables et Attribués</title>
    <!-- Favicon Setup -->
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 100px 0;
    }
    .charity-card {
        transition: transform 0.3s;
        margin-bottom: 20px;
    }
    .charity-card:hover {
        transform: translateY(-5px);
    }
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .icon-container {
        display: inline-block;
        position: relative;
        width: 1.5em;
        height: 1.5em;
        margin-right: 8px;
    }
    .shield-icon {
        position: absolute;
        font-size: 1.5em;
        color: #c0c0c0; /* Silver color */
        text-shadow: 
            0 0 2px rgba(0,0,0,0.3), /* Outer shadow */
            0 0 4px rgba(255,255,255,0.5); /* Inner glow for metallic effect */
        filter: drop-shadow(0 0 2px rgba(255,255,255,0.7)); /* Additional shine */
    }
    .heart-icon {
        position: absolute;
        font-size: 0.8em;
        color: white; /* Changed to white */
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-shadow: 0 0 1px rgba(0,0,0,0.5); /* Changed to dark shadow for better visibility */
    }
    /* Add this new style for your navbar logo */
    .navbar-logo {
        height: 30px;
        width: auto;
        margin-right: 8px;
    }
</style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <!--<a class="navbar-brand" href="index.php">
                <span class="icon-container">
                    <i class="fas fa-shield shield-icon"></i>
                    <i class="fas fa-hand-holding-heart heart-icon"></i>
                </span> MDVA
            </a>-->
            <a class="navbar-brand" href="index.php">
            <img src="images/favicon.png" alt="MDVA Logo" class="navbar-logo">MDVA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_type'])): ?>
                        <?php if($_SESSION['user_type'] == 'charity'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../charity/dashboard.php">Dashboard</a>
                            </li>
                        <?php elseif($_SESSION['user_type'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/dashboard.php">Admin Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/register.php">Register</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>