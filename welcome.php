<?php
require_once 'functions.php';

// Check if user is logged in and welcome should be shown
if (!isLoggedIn() || !isset($_SESSION['show_welcome'])) {
    redirect('login.php');
}

$username = $_SESSION['welcome_username'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'user';

// Clear the welcome flag so it doesn't show again
unset($_SESSION['show_welcome']);

// Determine redirect URL based on user role
$redirectUrl = 'index.php';
switch ($userRole) {
    case 'admin':
        $redirectUrl = 'admin_dashboard.php';
        break;
    case 'officer':
        $redirectUrl = 'officer_dashboard.php';
        break;
    case 'beneficiary':
        $redirectUrl = 'beneficiary_dashboard.php';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - CDF Management System | Government of Zambia</title>
    <meta name="description" content="Welcome to CDF Management System - Government of the Republic of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Color System - Enhanced Contrast */
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            --white: #ffffff;
            --shadow-lg: 0 8px 30px rgba(0, 0, 0, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--primary-gradient);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            padding: 1rem;
        }

        /* Background Pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(233, 185, 73, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }

        .welcome-container {
            text-align: center;
            padding: 2rem;
            max-width: 850px;
            width: 100%;
            position: relative;
            z-index: 2;
            border-radius: 24px;
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(233, 185, 73, 0.3);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .coat-of-arms {
            width: 140px;
            height: 140px;
            margin-bottom: 2rem;
            filter: 
                drop-shadow(0 8px 16px rgba(0,0,0,0.5))
                contrast(1.1)
                brightness(1.05);
            border: 3px solid rgba(233, 185, 73, 0.5);
            border-radius: 16px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            animation: float 3s ease-in-out infinite;
            box-shadow: 
                0 0 30px rgba(233, 185, 73, 0.4),
                inset 0 0 20px rgba(255, 255, 255, 0.12);
            object-fit: contain;
        }

        .welcome-title {
            font-size: clamp(2.2rem, 7vw, 3.5rem);
            font-weight: 900;
            margin-bottom: 0.5rem;
            text-shadow: 0 6px 20px rgba(0,0,0,0.6);
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.3s forwards;
            line-height: 1.15;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .welcome-subtitle {
            font-size: clamp(1.8rem, 5vw, 2.8rem);
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 14px rgba(0,0,0,0.5);
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.6s forwards;
            line-height: 1.25;
            letter-spacing: -0.3px;
            color: var(--secondary);
        }

        .user-name {
            color: var(--secondary);
            text-shadow: 0 3px 10px rgba(0,0,0,0.6);
            display: block;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .system-name {
            color: rgba(255,255,255,0.95);
            text-shadow: 0 2px 6px rgba(0,0,0,0.5);
            display: block;
            font-size: 0.9em;
            margin-top: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .welcome-message {
            font-size: clamp(1rem, 3vw, 1.4rem);
            margin-bottom: 2rem;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.9s forwards;
            text-shadow: 0 3px 10px rgba(0,0,0,0.5);
            line-height: 1.7;
            font-weight: 500;
            letter-spacing: 0.2px;
            color: rgba(255,255,255,0.98);
        }

        .redirect-message {
            font-size: 1.1rem;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 1.8s forwards;
            text-shadow: 0 2px 6px rgba(0,0,0,0.5);
            margin-bottom: 1.5rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: rgba(255,255,255,0.95);
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid var(--secondary);
            border-radius: 50%;
            margin: 0 auto;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 1.8s forwards, spin 1s linear infinite 1.8s;
        }

        .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--secondary) 0%, #ffd700 100%);
            color: var(--primary-dark);
            padding: 0.7rem 2rem;
            border-radius: 50px;
            font-weight: 800;
            font-size: clamp(0.95rem, 2vw, 1.15rem);
            margin-bottom: 2rem;
            text-shadow: 0 1px 2px rgba(255,255,255,0.3);
            box-shadow: 0 6px 20px rgba(233, 185, 73, 0.35);
            opacity: 0;
            animation: fadeInUp 1s ease-out 1.2s forwards;
            letter-spacing: 0.5px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Progress Bar */
        .progress-container {
            width: 280px;
            height: 8px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
            margin: 1.5rem auto;
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 0.8s ease-out 1.5s forwards;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }

        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--secondary) 0%, #ffd700 50%, var(--secondary) 100%);
            border-radius: 4px;
            animation: progress 3s cubic-bezier(0.4, 0.0, 0.2, 1) 1.5s forwards;
            box-shadow: 0 0 10px rgba(233, 185, 73, 0.5);
        }

        @keyframes progress {
            from { width: 0%; }
            to { width: 100%; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1.5rem;
            }

            .welcome-container {
                padding: 1.75rem;
                border-radius: 20px;
            }

            .coat-of-arms {
                width: 120px;
                height: 120px;
                margin-bottom: 1.5rem;
            }

            .welcome-title {
                margin-bottom: 0.3rem;
            }

            .welcome-subtitle {
                margin-bottom: 1.2rem;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }

            .welcome-container {
                padding: 1.5rem;
                border-radius: 16px;
            }

            .coat-of-arms {
                width: 100px;
                height: 100px;
                margin-bottom: 1rem;
                border-width: 2px;
            }

            .welcome-title {
                margin-bottom: 0.25rem;
            }

            .welcome-subtitle {
                margin-bottom: 1rem;
            }

            .welcome-message {
                margin-bottom: 1.5rem;
            }

            .progress-container {
                width: 240px;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia Coat of Arms" class="coat-of-arms">
        
        <h1 class="welcome-title">Welcome Back!</h1>
        
        <h2 class="welcome-subtitle">
            <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
        </h2>
        
        <div class="role-badge">
            <i class="fas fa-user-shield"></i>&nbsp;
            <?php echo ucfirst($userRole); ?> Access
        </div>
        
        <p class="welcome-message">
            <strong>Government of Zambia</strong><br>
            Constituency Development Fund<br>
            Management System
        </p>
        
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
        
        <p class="redirect-message">
            <i class="fas fa-spinner fa-spin"></i>&nbsp;
            Preparing your dashboard
        </p>
    </div>

    <script>
        // Redirect to dashboard after 3.5 seconds
        setTimeout(function() {
            window.location.href = '<?php echo $redirectUrl; ?>';
        }, 3500);

        // Add interactive effects and keyboard shortcuts
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.welcome-container');
            
            // Subtle click effect
            container.addEventListener('click', function() {
                this.style.transform = 'scale(0.99)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 100);
            });
            
            // Keyboard shortcuts to skip (Enter, Space, or Escape)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'Escape') {
                    e.preventDefault();
                    window.location.href = '<?php echo $redirectUrl; ?>';
                }
            });
        });
    </script>
</body>
</html>