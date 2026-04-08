<?php
require_once '../functions.php';
requireRole('beneficiary');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('../login.php');
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'budget' => floatval($_POST['budget'] ?? 0),
        'start_date' => $_POST['start_date'] ?? '',
        'end_date' => $_POST['end_date'] ?? '',
        'location' => trim($_POST['location'] ?? ''),
        'constituency' => trim($_POST['constituency'] ?? ''),
        'category' => $_POST['category'] ?? '',
        'subcategory' => $_POST['subcategory'] ?? '',
        'funding_source' => trim($_POST['funding_source'] ?? ''),
        'budget_breakdown' => trim($_POST['budget_breakdown'] ?? ''),
        'required_materials' => trim($_POST['required_materials'] ?? ''),
        'human_resources' => trim($_POST['human_resources'] ?? ''),
        'stakeholders' => trim($_POST['stakeholders'] ?? ''),
        'community_approval' => isset($_POST['community_approval']),
        'environmental_compliance' => isset($_POST['environmental_compliance']),
        'land_ownership' => isset($_POST['land_ownership']),
        'technical_feasibility' => isset($_POST['technical_feasibility']),
        'budget_approval' => isset($_POST['budget_approval']),
        'additional_notes' => trim($_POST['additional_notes'] ?? '')
    ];

    // Validation
    if (empty($project_data['title'])) {
        $errors['title'] = 'Project title is required';
    }
    
    if (empty($project_data['description'])) {
        $errors['description'] = 'Project description is required';
    }
    
    if ($project_data['budget'] <= 0) {
        $errors['budget'] = 'Valid budget amount is required';
    }
    
    if (empty($project_data['start_date'])) {
        $errors['start_date'] = 'Start date is required';
    }
    
    if (empty($project_data['end_date'])) {
        $errors['end_date'] = 'End date is required';
    } elseif ($project_data['start_date'] >= $project_data['end_date']) {
        $errors['end_date'] = 'End date must be after start date';
    }
    
    if (empty($project_data['location'])) {
        $errors['location'] = 'Project location is required';
    }
    
    if (empty($project_data['constituency'])) {
        $errors['constituency'] = 'Constituency is required';
    }
    
    if (empty($project_data['category'])) {
        $errors['category'] = 'Project category is required';
    }

    if (empty($errors)) {
        if (createNewProject($project_data, $_SESSION['user_id'])) {
            $success = true;
            $_SESSION['success_message'] = 'Project created successfully! It is now pending review.';
            redirect('index.php');
        } else {
            $errors['general'] = 'Failed to create project. Please try again.';
        }
    }
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);
$constituencies = getConstituencies();

// Sample constituencies data - in real implementation, this would come from database
$constituencies_list = [
    'Lusaka Central', 'Mandevu', 'Kabwata', 'Chawama', 'Matero', 'Kanyama',
    'Chongwe', 'Rufunsa', 'Luangwa', 'Chilanga', 'Choma Central', 'Livingstone',
    'Mongu Central', 'Solwezi Central', 'Ndola Central', 'Kitwe Central',
    'Chingola', 'Mufulira', 'Luanshya', 'Chililabombwe', 'Kafue', 'Mazabuka',
    'Monze', 'Nakonde', 'Kasama Central', 'Mpika', 'Mbala', 'Mansa', 'Kawambwa',
    'Chipata Central', 'Lundazi', 'Petauke', 'Katete', 'Mumbwa', 'Kaoma',
    'Senanga', 'Sesheke', 'Kalabo', 'Shangombo', 'Mwense', 'Nchelenge',
    'Kaputa', 'Mporokoso', 'Chinsali', 'Isoka', 'Nakonde', 'Chama'
];

$pageTitle = "Create New Project - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Create new project - CDF Management System - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a4e8a;
            --primary-dark: #0d3a6c;
            --primary-light: #2c6cb0;
            --primary-gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --secondary: #e9b949;
            --secondary-dark: #d4a337;
            --secondary-gradient: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            --light: #f8f9fa;
            --light-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --gray-lighter: #f8f9fa;
            --success: #28a745;
            --success-light: #d4edda;
            --warning: #ffc107;
            --warning-light: #fff3cd;
            --danger: #dc3545;
            --danger-light: #f8d7da;
            --info: #17a2b8;
            --info-light: #d1ecf1;
            --white: #ffffff;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
            --transition-slow: all 0.5s ease;
            --border-radius: 10px;
            --border-radius-sm: 6px;
            --border-radius-lg: 15px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            background-attachment: fixed;
            color: var(--dark);
            line-height: 1.7;
            font-weight: 400;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(26, 78, 138, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(233, 185, 73, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Form Section Navigation */
        .form-section {
            display: none;
            animation: fadeIn 0.5s ease-in;
        }

        .form-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }

        /* Rest of your existing CSS remains the same */
        .navbar {
            background: var(--primary-gradient);
            box-shadow: var(--shadow);
            padding: 0.8rem 0;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white) !important;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
        }

        .content-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .card-header {
            background: var(--light-gradient);
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
        }

        .form-control, 
        .form-select {
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius-sm);
            padding: 0.875rem 1rem;
            transition: var(--transition);
            font-size: 0.95rem;
            background: var(--white);
            box-shadow: var(--shadow-light);
        }

        .btn-primary-custom {
            background: var(--secondary-gradient);
            color: var(--dark);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .btn-outline-custom:hover {
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Step Indicator */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3rem;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gray-light);
            z-index: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--white);
            border: 3px solid var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 1rem;
            transition: var(--transition);
            font-size: 1.1rem;
            color: var(--gray);
            box-shadow: var(--shadow);
        }

        .step.active .step-number {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(26, 78, 138, 0.3);
        }

        .step.completed .step-number {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray);
            text-align: center;
            transition: var(--transition);
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .step-indicator {
                flex-wrap: wrap;
                gap: 1.5rem;
            }
            
            .step {
                flex: 0 0 calc(50% - 1rem);
            }
            
            .step-indicator::before {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40">
                CDF Beneficiary Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../beneficiary_dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php">
                                <i class="fas fa-project-diagram me-2"></i>My Projects
                            </a></li>
                            <li><a class="dropdown-item active" href="setup.php">
                                <i class="fas fa-plus-circle me-2"></i>New Project
                            </a></li>
                            <li><a class="dropdown-item" href="../communication/messages.php">
                                <i class="fas fa-comments me-2"></i>Chats
                            </a></li>
                            <li><a class="dropdown-item" href="../support/help.php">
                                <i class="fas fa-question-circle me-2"></i>Help
                            </a></li>
                            <li><a class="dropdown-item" href="../financial/expenses.php">
                                <i class="fas fa-receipt me-2"></i>My Expenses
                            </a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span class="notification-badge"><?php echo count($notifications); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                                <li>
                                    <a class="dropdown-item" href="../communication/notifications.php">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                        </div>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</p>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="../communication/notifications.php">View All Notifications</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-center text-muted" href="../communication/notifications.php">No new notifications</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <div class="profile-avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%); display: flex; align-items: center; justify-content: center; color: var(--dark); font-weight: 700;">
                                            <?php echo strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                                            <small class="text-muted">CDF Beneficiary</small>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../settings/profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="../settings/system.php">
                                <i class="fas fa-cog me-2"></i>Account Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?logout=true">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5 mb-5" style="margin-top: 100px !important;">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-2">Create New Project</h1>
                <p class="text-muted mb-0">Fill in the details below to submit your project for CDF funding</p>
            </div>
            <a href="index.php" class="btn btn-outline-custom">
                <i class="fas fa-arrow-left me-2"></i>Back to Projects
            </a>
        </div>

        <!-- Step Indicator -->
        <div class="content-card">
            <div class="card-body">
                <div class="step-indicator">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">Basic Info</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">Budget & Timeline</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">Requirements</div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label">Compliance</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-custom" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> Project created successfully! It is now pending review.
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger alert-custom" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($errors['general']); ?>
            </div>
        <?php endif; ?>

        <!-- Project Creation Form -->
        <form method="POST" action="" id="projectForm">
            <!-- Section 1: Basic Information -->
            <div class="form-section active" id="section-1">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Project Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>" 
                                       id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                       placeholder="Enter project title" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['title']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Project Category <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['category']) ? 'is-invalid' : ''; ?>" 
                                        id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="women-empowerment" <?php echo ($_POST['category'] ?? '') === 'women-empowerment' ? 'selected' : ''; ?>>Women Empowerment</option>
                                    <option value="youth-empowerment" <?php echo ($_POST['category'] ?? '') === 'youth-empowerment' ? 'selected' : ''; ?>>Youth Empowerment</option>
                                </select>
                                <?php if (isset($errors['category'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['category']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="subcategory" class="form-label">Project Subcategory</label>
                                <select class="form-select" id="subcategory" name="subcategory">
                                    <option value="">Select Subcategory</option>
                                    <!-- Subcategories will be populated by JavaScript -->
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Project Description <span class="text-danger">*</span></label>
                                <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                          id="description" name="description" rows="5" 
                                          placeholder="Provide a detailed description of your project" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['description']); ?></div>
                                <?php endif; ?>
                                <div class="form-text">Describe the project objectives, expected outcomes, and how it will benefit the community.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Project Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : ''; ?>" 
                                       id="location" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                                       placeholder="Enter project location (village, ward, area)" required>
                                <?php if (isset($errors['location'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['location']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="constituency" class="form-label">Constituency <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['constituency']) ? 'is-invalid' : ''; ?>" 
                                        id="constituency" name="constituency" required>
                                    <option value="">Select Constituency</option>
                                    <?php foreach ($constituencies_list as $constituency): ?>
                                        <option value="<?php echo htmlspecialchars($constituency); ?>" 
                                            <?php echo ($_POST['constituency'] ?? '') === $constituency ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($constituency); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['constituency'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['constituency']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="section-nav">
                            <div></div> <!-- Empty div for spacing -->
                            <button type="button" class="btn btn-primary-custom next-section" data-next="2">
                                Next: Budget & Timeline <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Budget & Timeline -->
            <div class="form-section" id="section-2">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Budget & Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="budget" class="form-label">Total Budget (ZMW) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?php echo isset($errors['budget']) ? 'is-invalid' : ''; ?>" 
                                       id="budget" name="budget" value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>" 
                                       placeholder="Enter total budget amount" min="0" step="0.01" required>
                                <?php if (isset($errors['budget'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['budget']); ?></div>
                                <?php endif; ?>
                                <div class="form-text">Enter the total amount required for this project in Zambian Kwacha.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="funding_source" class="form-label">Proposed Funding Source</label>
                                <input type="text" class="form-control" id="funding_source" name="funding_source" 
                                       value="<?php echo htmlspecialchars($_POST['funding_source'] ?? ''); ?>" 
                                       placeholder="e.g., CDF, Community Contribution, etc.">
                                <div class="form-text">Specify the source(s) of funding for this project.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['start_date']) ? 'is-invalid' : ''; ?>" 
                                       id="start_date" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                                <?php if (isset($errors['start_date'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['start_date']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control <?php echo isset($errors['end_date']) ? 'is-invalid' : ''; ?>" 
                                       id="end_date" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>" required>
                                <?php if (isset($errors['end_date'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['end_date']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="budget_breakdown" class="form-label">Budget Breakdown</label>
                                <textarea class="form-control" id="budget_breakdown" name="budget_breakdown" rows="4" 
                                          placeholder="Provide a detailed breakdown of how the budget will be utilized"><?php echo htmlspecialchars($_POST['budget_breakdown'] ?? ''); ?></textarea>
                                <div class="form-text">Break down the budget by major cost categories (materials, labor, equipment, etc.).</div>
                            </div>
                        </div>

                        <div class="section-nav">
                            <button type="button" class="btn btn-outline-custom prev-section" data-prev="1">
                                <i class="fas fa-arrow-left me-2"></i>Previous
                            </button>
                            <button type="button" class="btn btn-primary-custom next-section" data-next="3">
                                Next: Requirements <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Requirements -->
            <div class="form-section" id="section-3">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Project Requirements</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="required_materials" class="form-label">Required Materials</label>
                                <textarea class="form-control" id="required_materials" name="required_materials" rows="4" 
                                          placeholder="List all materials needed for this project"><?php echo htmlspecialchars($_POST['required_materials'] ?? ''); ?></textarea>
                                <div class="form-text">Specify materials, quantities, and any special requirements.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="human_resources" class="form-label">Human Resources</label>
                                <textarea class="form-control" id="human_resources" name="human_resources" rows="4" 
                                          placeholder="List required personnel, skills, and estimated labor"><?php echo htmlspecialchars($_POST['human_resources'] ?? ''); ?></textarea>
                                <div class="form-text">Specify the types of workers, skills needed, and estimated labor requirements.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="stakeholders" class="form-label">Key Stakeholders</label>
                                <textarea class="form-control" id="stakeholders" name="stakeholders" rows="3" 
                                          placeholder="List key stakeholders involved in this project"><?php echo htmlspecialchars($_POST['stakeholders'] ?? ''); ?></textarea>
                                <div class="form-text">Identify community leaders, government agencies, or organizations involved.</div>
                            </div>
                        </div>

                        <div class="section-nav">
                            <button type="button" class="btn btn-outline-custom prev-section" data-prev="2">
                                <i class="fas fa-arrow-left me-2"></i>Previous
                            </button>
                            <button type="button" class="btn btn-primary-custom next-section" data-next="4">
                                Next: Compliance <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Compliance -->
            <div class="form-section" id="section-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Compliance & Approvals</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <p class="text-muted">Please confirm the following compliance requirements:</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="community_approval" name="community_approval" 
                                           <?php echo isset($_POST['community_approval']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="community_approval">
                                        Community Approval Obtained
                                    </label>
                                    <div class="form-text">Confirmation that the local community supports this project.</div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="environmental_compliance" name="environmental_compliance" 
                                           <?php echo isset($_POST['environmental_compliance']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="environmental_compliance">
                                        Environmental Compliance
                                    </label>
                                    <div class="form-text">Project complies with environmental regulations and standards.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="land_ownership" name="land_ownership" 
                                           <?php echo isset($_POST['land_ownership']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="land_ownership">
                                        Land Ownership/Clearance
                                    </label>
                                    <div class="form-text">Proper land ownership or usage rights have been secured.</div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="technical_feasibility" name="technical_feasibility" 
                                           <?php echo isset($_POST['technical_feasibility']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="technical_feasibility">
                                        Technical Feasibility
                                    </label>
                                    <div class="form-text">Project has been assessed for technical viability.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="budget_approval" name="budget_approval" 
                                           <?php echo isset($_POST['budget_approval']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="budget_approval">
                                        Budget Approval
                                    </label>
                                    <div class="form-text">Budget has been reviewed and approved by relevant authorities.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="additional_notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="additional_notes" name="additional_notes" rows="4" 
                                          placeholder="Any additional information or special considerations"><?php echo htmlspecialchars($_POST['additional_notes'] ?? ''); ?></textarea>
                                <div class="form-text">Include any other relevant information about compliance or approvals.</div>
                            </div>
                        </div>

                        <div class="section-nav">
                            <button type="button" class="btn btn-outline-custom prev-section" data-prev="3">
                                <i class="fas fa-arrow-left me-2"></i>Previous
                            </button>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-paper-plane me-2"></i>Submit Project
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">CDF Management System</h5>
                    <p class="mb-0">Government of the Republic of Zambia</p>
                    <p class="text-muted">Empowering communities through sustainable development</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">&copy; <?php echo date('Y'); ?> CDF Management System. All rights reserved.</p>
                    <p class="text-muted mb-0">Version 2.1.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Section Navigation
            const sections = document.querySelectorAll('.form-section');
            const steps = document.querySelectorAll('.step');
            
            // Show section function
            function showSection(sectionNumber) {
                sections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === `section-${sectionNumber}`) {
                        section.classList.add('active');
                    }
                });
                
                // Update step indicators
                steps.forEach(step => {
                    step.classList.remove('active', 'completed');
                    const stepNumber = parseInt(step.dataset.step);
                    
                    if (stepNumber === sectionNumber) {
                        step.classList.add('active');
                    } else if (stepNumber < sectionNumber) {
                        step.classList.add('completed');
                    }
                });
                
                // Scroll to top of form
                document.querySelector('.form-section.active').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }
            
            // Next section buttons
            document.querySelectorAll('.next-section').forEach(button => {
                button.addEventListener('click', function() {
                    const nextSection = this.dataset.next;
                    if (validateCurrentSection(parseInt(nextSection) - 1)) {
                        showSection(parseInt(nextSection));
                    }
                });
            });
            
            // Previous section buttons
            document.querySelectorAll('.prev-section').forEach(button => {
                button.addEventListener('click', function() {
                    const prevSection = this.dataset.prev;
                    showSection(parseInt(prevSection));
                });
            });
            
            // Section validation
            function validateCurrentSection(sectionNumber) {
                const currentSection = document.getElementById(`section-${sectionNumber}`);
                const requiredFields = currentSection.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        
                        // Add error message if not exists
                        if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.textContent = 'This field is required';
                            field.parentNode.appendChild(errorDiv);
                        }
                    } else {
                        field.classList.remove('is-invalid');
                        // Remove error message if exists
                        if (field.nextElementSibling && field.nextElementSibling.classList.contains('invalid-feedback')) {
                            field.nextElementSibling.remove();
                        }
                    }
                });
                
                // Special validation for section 2 (dates)
                if (sectionNumber === 2) {
                    const startDate = document.getElementById('start_date').value;
                    const endDate = document.getElementById('end_date').value;
                    
                    if (startDate && endDate && startDate >= endDate) {
                        isValid = false;
                        document.getElementById('end_date').classList.add('is-invalid');
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.textContent = 'End date must be after start date';
                        document.getElementById('end_date').parentNode.appendChild(errorDiv);
                    }
                }
                
                if (!isValid) {
                    // Scroll to first error
                    const firstError = currentSection.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                
                return isValid;
            }
            
            // Category and Subcategory handling
            const categorySelect = document.getElementById('category');
            const subcategorySelect = document.getElementById('subcategory');
            
            // Subcategory options by category
            const subcategories = {
                'women-empowerment': [
                    { value: 'women-training', text: 'Women Training & Skills' },
                    { value: 'women-health', text: 'Women Health & Wellness' },
                    { value: 'women-income', text: 'Women Income Generation' },
                    { value: 'women-leadership', text: 'Women Leadership Development' },
                    { value: 'women-support', text: 'Women Support Services' },
                    { value: 'other-women', text: 'Other Women Empowerment' }
                ],
                'youth-empowerment': [
                    { value: 'youth-training', text: 'Youth Training' },
                    { value: 'startup-support', text: 'Startup Support' },
                    { value: 'sports-development', text: 'Sports Development' },
                    { value: 'other-youth', text: 'Other Youth Empowerment' }
                ]
            };

            // Update subcategories when category changes
            categorySelect.addEventListener('change', function() {
                const selectedCategory = this.value;
                
                // Clear existing options
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                
                // Add new options if category is selected
                if (selectedCategory && subcategories[selectedCategory]) {
                    subcategories[selectedCategory].forEach(function(subcategory) {
                        const option = document.createElement('option');
                        option.value = subcategory.value;
                        option.textContent = subcategory.text;
                        subcategorySelect.appendChild(option);
                    });
                }
            });

            // Trigger change event on page load if category is already selected
            if (categorySelect.value) {
                categorySelect.dispatchEvent(new Event('change'));
                
                // Set the previously selected subcategory if exists
                const previousSubcategory = '<?php echo $_POST['subcategory'] ?? ''; ?>';
                if (previousSubcategory) {
                    setTimeout(function() {
                        subcategorySelect.value = previousSubcategory;
                    }, 100);
                }
            }

            // Set minimum dates to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').min = today;
            document.getElementById('end_date').min = today;

            // Update end date minimum when start date changes
            document.getElementById('start_date').addEventListener('change', function() {
                document.getElementById('end_date').min = this.value;
            });
        });
    </script>
</body>
</html>