<?php
session_start();

// **PERUBAHAN 1: Memperbaiki Logika Session Alert**
// Ambil data user jika ada
$name = $_SESSION['name'] ?? null;

// Ambil alert JIKA ADA (untuk kasus redirect dari halaman lain)
$alerts = $_SESSION['alerts'] ?? [];

// Ambil status form aktif jika ada
$active_form = $_SESSION['active_form'] ?? '';

// KOSONGKAN HANYA session alert dan form setelah dibaca
unset($_SESSION['alerts']);
unset($_SESSION['active_form']);
// **AKHIR PERUBAHAN 1**
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Feeling</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    
    <header>
        <a href="#" class="logo"><img src="assets/feeling.png" alt=""></a>
        <nav>
            </nav>

        <div class="user-auth">
            <?php if (!empty($name)): ?>
            <div class="profile-box">
                <div class="avatar-circle"><?= strtoupper(substr($name, 0, 1)); ?></div> <div class="dropdown">
                    <a href="#">My Account</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
            <?php else: ?>
            <button type="button" class="login-btn-modal">Login</button>
            <?php endif; ?>
        </div>
    </header>

    <section>
        <div class="bg-line"></div>     
        <div class="finisher-header"></div>
        <h1>Premium coffee for everyone</h1>
    </section>

    <?php if (!empty($alerts)) :?>
    <div class="alert-box">
        <?php foreach ($alerts as $alert) : ?>
        <div class="alert <?= htmlspecialchars($alert['type']); ?>">
             <i class='bx <?= $alert['type'] === 'success' ? 'bxs-check-circle' : 'bxs-x-circle'; ?>'></i> 
            <span><?= htmlspecialchars($alert['message']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="auth-modal <?= $active_form === 'register' ? 'show slide' : ($active_form === 'login' ? 'show' : ''); ?>">
        <button type="button" class="close-btn-modal"><i class='bx bx-x'></i> </button>
        
        <div class="form-box login">
            <h2>Login</h2>
            <form action="auth_process.php" method="POST">
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class='bx bxs-envelope'></i> 
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class='bx bxs-lock'></i> 
                </div>
                <button type="submit" name="login_btn" class="btn">Login</button>
                <p>Don't have an account? <a href="#" class="register-link">Register</a></p>
            </form>
        </div>

        <div class="form-box register">
            <h2>Register</h2>
            <form action="auth_process.php" method="POST">
                <div class="input-box">
                    <input type="text" name="name" placeholder="Nama Lengkap" required> <i class='bx bxs-user'></i> 
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class='bx bxs-envelope'></i> 
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class='bx bxs-lock'></i> 
                </div>
                <button type="submit" name="register_btn" class="btn">Register</button>
                <p>Already have an account? <a href="#" class="login-link">Login</a></p>
            </form>
        </div>
    </div>





    <script src="assets\script.js"></script>



    <script src="assets\finisher-header.es5.min.js" type="text/javascript"></script>

   

    <script type="text/javascript">

        new FinisherHeader({

            "count": 21,

            "size": {

                "min": 1300,

                "max": 1500,

                "pulse": 1.7

            },

            "speed": {

                "x": {

                    "min": 0.6,

                    "max": 3

                },

                "y": {

                    "min": 0.6,

                    "max": 3

                }

            },

            "colors": {

                "background": "#c55353",

                "particles": [

                    "#ff4261",

                    "#a72626",

                    "#ff8282",

                    "#f83232"

                ]

            },

            "blending": "lighten",

            "opacity": {

                "center": 0.6,

                "edge": 0

            },

            "skew": 0,

            "shapes": [

                "c"

            ]

        });

    </script>



</body>



</html>