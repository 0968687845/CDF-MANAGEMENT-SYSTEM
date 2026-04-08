<?php
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

// Get constituencies for dropdown
$constituencies = ["Lusaka Central", "Ndola Central", "Livingstone", "Kitwe Central", "Chongwe",
    "Kabwe Central", "Mongu Central", "Solwezi Central", "Chipata Central", "Kasama Central",
    "Mufulira", "Choma", "Mazabuka", "Luangwa", "Petauke", "Mpika", "Chinsali", "Kapiri Mposhi",
    "Mansa", "Kalulushi", "Chililabombwe", "Chingola", "Kalomo", "Senanga", "Sesheke", "Kaoma",
    "Mumbwa", "Monze", "Gwembe", "Siavonga", "Katete", "Lundazi", "Chadiza", "Nyimba", "Isoka",
    "Nakonde", "Kasempa", "Mwinilunga", "Kabompo", "Zambezi", "Chavuma", "Mwense", "Nchelenge",
    "Kawambwa", "Samfya", "Mporokoso", "Kaputa", "Luwingu", "Mbala", "Milenge", "Chembe", "Mwansabombwe",
    "Lufwanyama", "Masaiti", "Chilanga", "Kafue", "Luangwa", "Rufunsa", "Shibuyunji", "Lukulu", "Mitete",
    "Mwandi", "Sikongo", "Kalabo", "Nkeyema", "Mulobezi", "Sioma", "Shangombo", "Vubwi", "Chama", "Lumezi",
    "Lundazi", "Chadiza", "Sinda", "Katete", "Mambwe", "Lavushimanda", "Chinsali", "Shiwang'andu", "Isoka",
    "Nakonde", "Kanchibiya", "Mafinga", "Chilubi", "Lunte", "Lupososhi", "Lubansenshi", "Kaputa", "Nsama",
    "Chiengi", "Pemba", "Namwala", "Itezhi-Tezhi", "Mkushi", "Serenje", "Bwacha", "Chisamba", "Shibuyunji",
    "Luano", "Kantanshi", "Kwacha", "Nkana", "Chimwemwe", "Wusakile", "Kamfinsa", "Matero", "Kabwata",
    "Mandevu", "Kanyama", "Chawama", "Rufunsa", "Chongwe", "Kafue", "Siavonga", "Gwembe", "Monze", "Pemba",
    "Bweengwa", "Namwala", "Keembe", "Mumbwa", "Itezhi-Tezhi", "Shiwang'andu", "Malole", "Kantanshi", "Mwense",
    "Nchelenge", "Chienge", "Chiengi", "Kaputa", "Nsama", "Luwingu", "Lubansenshi", "Chinsali", "Isoka",
    "Nakonde", "Kanchibiya", "Mafinga", "Chama", "Lumezi", "Lundazi", "Vubwi", "Chadiza", "Sinda", "Katete",
    "Petauke", "Nyimba", "Mambwe", "Lavushimanda", "Cipangali", "Kasenengwa", "Chipata Central", "Lumezi",
    "Lundazi", "Vubwi", "Chadiza", "Sinda", "Katete", "Petauke", "Nyimba", "Mambwe", "Lavushimanda"
];

$pageTitle = "M&E Officer Registration - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="M&E Officer registration for CDF Management System">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --success: #28a745;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        .navbar {
            background-color: var(--primary);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
            transition: var(--transition);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 1rem !important;
            border-radius: 4px;
        }

        .nav-link:hover, .nav-link:focus {
            color: var(--white) !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Registration Container */
        .register-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.6;
        }

        .register-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            z-index: 1;
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 25px;
            text-align: center;
            position: relative;
        }

        .register-form {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .officer-notice {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid var(--primary);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .admin-access-note {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 12px 30px;
            color: var(--white);
            transition: var(--transition);
            font-weight: 600;
            border-radius: 6px;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            color: var(--white);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        }

        .btn-outline-custom {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 6px;
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        .form-control {
            border: 2px solid var(--gray-light);
            border-radius: 6px;
            padding: 12px 15px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(26, 78, 138, 0.25);
        }

        .input-group-text {
            background-color: var(--primary);
            border: 2px solid var(--primary);
            color: var(--white);
        }

        .form-note {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .password-strength-container {
            margin-top: 10px;
        }

        .password-strength-container .progress {
            height: 6px;
        }

        .nrc-format-valid {
            color: var(--success);
        }

        .nrc-format-invalid {
            color: #dc3545;
        }

        /* Footer */
        .footer {
            background-color: var(--primary-dark);
            color: var(--white);
            padding: 2rem 0 1rem;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 2rem;
        }

        .disclaimer {
            background-color: rgba(0, 0, 0, 0.2);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .register-card {
                margin: 20px;
                width: auto;
            }
            
            .register-container {
                padding: 80px 10px 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="45" height="45">
                <span>Government of Zambia - CDF System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Registration Container -->
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <img src="coat-of-arms-of-zambia.jpg" alt="Republic of Zambia Coat of Arms" width="70" height="70" class="me-3">
                    <div>
                        <h3><i class="fas fa-user-tie me-2"></i>M&E Officer Registration</h3>
                        <p class="mb-0">Government of Zambia - CDF Management System</p>
                    </div>
                </div>
            </div>
            <div class="register-form">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <!-- Admin Access Note -->
                <div class="admin-access-note">
                    <h6><i class="fas fa-shield-alt me-2"></i>Administrative Access Required</h6>
                    <p class="mb-0">M&E Officer accounts require administrative approval. Your registration will be reviewed before system access is granted.</p>
                </div>
                
                <!-- Officer Notice -->
                <div class="officer-notice">
                    <h6><i class="fas fa-info-circle me-2 text-primary"></i>M&E Officer Registration Information</h6>
                    <p class="mb-0 small text-muted">As an M&E Officer, you'll have access to monitor and evaluate CDF projects in your assigned constituency. Please provide accurate government employment details for verification.</p>
                </div>
                
                <!-- Back to Home Button -->
                <div class="mb-4">
                    <a href="index.php" class="btn btn-outline-custom">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                    <a href="register.php" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-users me-2"></i>View All Roles
                    </a>
                </div>
                
                <form method="POST" action="auth.php">
                    <input type="hidden" name="role" value="officer">
                    
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h5 class="section-title"><i class="fas fa-user-circle"></i>Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label required-field">First Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label required-field">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label required-field">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label required-field">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nrc" class="form-label required-field">National Registration Card (NRC) Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" id="nrc" name="nrc" placeholder="e.g., 123456/78/9" required>
                                </div>
                                <div class="form-note nrc-format">
                                    <span id="nrc_format_status">Format: 123456/78/9 (Numbers only with slashes)</span>
                                    <span id="nrc_format_valid" class="nrc-format-valid ms-2" style="display: none;">
                                        <i class="fas fa-check-circle"></i> Valid format
                                    </span>
                                    <span id="nrc_format_invalid" class="nrc-format-invalid ms-2" style="display: none;">
                                        <i class="fas fa-times-circle"></i> Invalid format
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="dob" class="form-label required-field">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date" class="form-control" id="dob" name="dob" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label required-field">Gender</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="constituency" class="form-label required-field">Assigned Constituency</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <select class="form-select" id="constituency" name="constituency" required>
                                        <option value="">Select Constituency</option>
                                        <?php foreach ($constituencies as $constituency): ?>
                                            <option value="<?php echo htmlspecialchars($constituency); ?>"><?php echo htmlspecialchars($constituency); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="form-section">
                        <h5 class="section-title"><i class="fas fa-briefcase"></i>Professional Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label required-field">Employee ID</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label required-field">Department</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <input type="text" class="form-control" id="department" name="department" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label required-field">Position/Title</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                                    <input type="text" class="form-control" id="position" name="position" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="grade" class="form-label">Grade Level</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-chart-line"></i></span>
                                    <input type="text" class="form-control" id="grade" name="grade">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supervisor" class="form-label">Supervisor Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-check"></i></span>
                                    <input type="text" class="form-control" id="supervisor" name="supervisor">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_of_employment" class="form-label required-field">Date of Employment</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-check"></i></span>
                                    <input type="date" class="form-control" id="date_of_employment" name="date_of_employment" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="form-section">
                        <h5 class="section-title"><i class="fas fa-lock"></i>Account Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label required-field">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="form-note">Minimum 4 characters, letters and numbers only</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label required-field">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength-container mt-2">
                                    <div class="progress">
                                        <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small id="passwordStrengthText" class="form-note">Password strength</small>
                                </div>
                                <div class="form-note">Minimum 8 characters with uppercase, lowercase, number, and special character</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div id="passwordMatch" class="form-note"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> and confirm that I am an authorized government employee
                            </label>
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn btn-custom w-100 btn-lg mb-3">
                        <i class="fas fa-user-tie me-2"></i>Register as M&E Officer
                    </button>
                </form>

                <!-- Already have an account -->
                <div class="text-center mt-4">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Government of Zambia - M&E Officer Terms and Conditions</h6>
                    <p>By registering as an M&E Officer, you agree to the following terms:</p>
                    <ol>
                        <li>You must be an authorized government employee</li>
                        <li>All information provided must be accurate and verifiable</li>
                        <li>You will maintain confidentiality of all CDF project data</li>
                        <li>You agree to comply with all government regulations and policies</li>
                        <li>System access is subject to administrative approval</li>
                        <li>Any misuse of the system will result in disciplinary action</li>
                        <li>You will submit accurate and timely monitoring reports</li>
                    </ol>
                    <p>These terms are governed by the Constituency Development Fund Act Cap 324 and 2022 CDF Guidelines.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 small">&copy; <?php echo date('Y'); ?> Government of the Republic of Zambia. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex justify-content-md-end align-items-center">
                        <span class="me-3 small text-white-50">Official Government System</span>
                        <img src="Flag_of_Zambia.svg" alt="Zambia Flag" width="30" height="20">
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="disclaimer">
                    <p class="mb-2"><strong>Government Disclaimer:</strong> This is an official Government of Zambia system developed in compliance with the Constituency Development Fund Act Cap 324 and 2022 CDF Guidelines.</p>
                    <p class="mb-0"><strong>System Development:</strong> Web-based system developed by Samuel Sitemba (Student ID: 2200340), Zambia University College of Technology, under supervision of Ms. C. Mushikwa, 2025.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });

            // Password strength indicator
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check password criteria
                if (password.length >= 8) strength += 25;
                if (/[A-Z]/.test(password)) strength += 25;
                if (/[a-z]/.test(password)) strength += 25;
                if (/[0-9]/.test(password)) strength += 15;
                if (/[^A-Za-z0-9]/.test(password)) strength += 10;
                
                // Update progress bar
                strengthBar.style.width = strength + '%';
                
                // Update text and color
                if (strength < 40) {
                    strengthBar.className = 'progress-bar bg-danger';
                    strengthText.textContent = 'Weak password';
                } else if (strength < 70) {
                    strengthBar.className = 'progress-bar bg-warning';
                    strengthText.textContent = 'Medium password';
                } else {
                    strengthBar.className = 'progress-bar bg-success';
                    strengthText.textContent = 'Strong password';
                }
            });

            // Password confirmation check
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('passwordMatch');
            
            confirmPassword.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    passwordMatch.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
                } else {
                    passwordMatch.innerHTML = '<i class="fas fa-check-circle text-success"></i> Passwords match';
                }
            });

            // NRC format validation
            const nrcInput = document.getElementById('nrc');
            const nrcValid = document.getElementById('nrc_format_valid');
            const nrcInvalid = document.getElementById('nrc_format_invalid');
            
            nrcInput.addEventListener('input', function() {
                const nrcPattern = /^\d{6}\/\d{2}\/\d{1}$/;
                if (nrcPattern.test(this.value)) {
                    nrcValid.style.display = 'inline';
                    nrcInvalid.style.display = 'none';
                } else {
                    nrcValid.style.display = 'none';
                    nrcInvalid.style.display = 'inline';
                }
            });

            // Date validation - ensure employment date is not in future
            const employmentDate = document.getElementById('date_of_employment');
            employmentDate.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                if (selectedDate > today) {
                    alert('Employment date cannot be in the future');
                    this.value = '';
                }
            });

            // Form submission validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Check if passwords match
                if (passwordInput.value !== confirmPassword.value) {
                    alert('Passwords do not match. Please check your password confirmation.');
                    valid = false;
                }
                
                // Check NRC format
                const nrcPattern = /^\d{6}\/\d{2}\/\d{1}$/;
                if (!nrcPattern.test(nrcInput.value)) {
                    alert('Please enter a valid NRC number in the format: 123456/78/9');
                    valid = false;
                }
                
                // Check terms acceptance
                if (!document.getElementById('terms').checked) {
                    alert('You must accept the Terms and Conditions to register.');
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });

            // Navbar background change on scroll
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.style.backgroundColor = 'var(--primary-dark)';
                    navbar.style.padding = '0.5rem 0';
                } else {
                    navbar.style.backgroundColor = 'var(--primary)';
                    navbar.style.padding = '0.8rem 0';
                }
            });
        });
    </script>
</body>
</html>