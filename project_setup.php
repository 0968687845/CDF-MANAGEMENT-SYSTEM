<?php
require_once 'functions.php';
requireRole('beneficiary');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_documentation'])) {
        saveInitialDocumentation($_POST, $_SESSION['user_id']);
    } elseif (isset($_POST['save_budget'])) {
        saveBudgetPlanning($_POST, $_SESSION['user_id']);
    } elseif (isset($_POST['save_timeline'])) {
        saveTimelineSetup($_POST, $_SESSION['user_id']);
    } elseif (isset($_POST['save_resources'])) {
        saveResourceAllocation($_POST, $_SESSION['user_id']);
    } elseif (isset($_POST['submit_compliance'])) {
        saveComplianceChecklist($_POST, $_SESSION['user_id']);
    }
}

$userData = getUserData();
$notifications = getNotifications($_SESSION['user_id']);
$setupProgress = getSetupProgress($_SESSION['user_id']);

$pageTitle = "Project Setup - CDF Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="Project setup and planning - Government of Zambia">
    <meta name="author" content="Government of the Republic of Zambia">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
            --gov-blue: #003366;
            --gov-gold: #FFD700;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .setup-header {
            background: linear-gradient(135deg, var(--gov-blue) 0%, #004080 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .setup-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .setup-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .setup-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .setup-card .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 3px solid var(--gov-blue);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }

        .progress-tracker {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .step-indicator:hover {
            background: #f8f9fa;
        }

        .step-indicator.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--gov-blue) 100%);
            color: white;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-weight: 700;
            background: #e9ecef;
            color: #6c757d;
        }

        .step-indicator.active .step-number {
            background: white;
            color: var(--primary);
        }

        .step-indicator.completed .step-number {
            background: var(--success);
            color: white;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .budget-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .budget-item:hover {
            border-color: var(--primary);
        }

        .timeline-milestone {
            background: white;
            border-left: 4px solid var(--primary);
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }

        .resource-card {
            text-align: center;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .resource-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .compliance-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .compliance-item.checked {
            background: rgba(25, 135, 84, 0.05);
            border-color: var(--success);
        }

        .compliance-item:hover {
            background: #f8f9fa;
        }

        .nav-pills .nav-link {
            border-radius: 8px;
            margin: 0.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
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

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .guide-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .template-download {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .template-download:hover {
            border-color: var(--primary);
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="coat-of-arms-of-zambia.jpg" alt="Zambia Coat of Arms" width="40" height="40" class="me-2">
                Government of Zambia - CDF System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">
                            <i class="fas fa-project-diagram me-1"></i>Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="project_setup.php">
                            <i class="fas fa-cogs me-1"></i>Project Setup
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($userData['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?logout=true"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Setup Header -->
    <section class="setup-header" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" style="--bs-breadcrumb-divider: '›';">
                            <li class="breadcrumb-item"><a href="projects.php" class="text-white-50">Projects</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Project Setup</li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold">Project Setup Wizard</h1>
                    <p class="lead mb-0">Complete these steps to set up your CDF project for success</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $setupProgress['overall']; ?>%"></div>
                    </div>
                    <small class="text-white-50">Overall Progress: <?php echo $setupProgress['overall']; ?>% Complete</small>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row">
            <!-- Progress Tracker Sidebar -->
            <div class="col-lg-4">
                <div class="progress-tracker sticky-top" style="top: 100px;">
                    <h5 class="mb-4">Setup Progress</h5>
                    
                    <div class="step-indicator <?php echo $setupProgress['documentation'] == 100 ? 'completed' : 'active'; ?>" onclick="showStep('documentation')">
                        <div class="step-number">1</div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Initial Documentation</h6>
                            <small class="opacity-75">Project details and requirements</small>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $setupProgress['documentation']; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="step-indicator <?php echo $setupProgress['budget'] == 100 ? 'completed' : ''; ?>" onclick="showStep('budget')">
                        <div class="step-number">2</div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Budget Planning</h6>
                            <small class="opacity-75">Financial planning and allocation</small>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $setupProgress['budget']; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="step-indicator <?php echo $setupProgress['timeline'] == 100 ? 'completed' : ''; ?>" onclick="showStep('timeline')">
                        <div class="step-number">3</div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Timeline Setup</h6>
                            <small class="opacity-75">Project schedule and milestones</small>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $setupProgress['timeline']; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="step-indicator <?php echo $setupProgress['resources'] == 100 ? 'completed' : ''; ?>" onclick="showStep('resources')">
                        <div class="step-number">4</div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Resource Allocation</h6>
                            <small class="opacity-75">Team and material resources</small>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $setupProgress['resources']; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="step-indicator <?php echo $setupProgress['compliance'] == 100 ? 'completed' : ''; ?>" onclick="showStep('compliance')">
                        <div class="step-number">5</div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Compliance Checklist</h6>
                            <small class="opacity-75">Regulatory requirements</small>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $setupProgress['compliance']; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <h6>Need Help?</h6>
                        <p class="small mb-2">Contact your M&E Officer for assistance with project setup.</p>
                        <button class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-question-circle me-1"></i>Get Help
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Initial Documentation Step -->
                <div class="setup-card fade-in-up" id="documentation-step">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Step 1: Initial Documentation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="guide-box">
                            <h6><i class="fas fa-lightbulb me-2"></i>Setup Guide</h6>
                            <p class="mb-0">Complete all required project documentation before proceeding to budget planning. This information will be used for project approval and monitoring.</p>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-section">
                                <h6>Basic Project Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Project Title *</label>
                                        <input type="text" class="form-control" name="project_title" required 
                                               placeholder="Enter project title">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Project Category *</label>
                                        <select class="form-select" name="project_category" required>
                                            <option value="">Select category</option>
                                            <option value="infrastructure">Infrastructure</option>
                                            <option value="education">Education</option>
                                            <option value="health">Health</option>
                                            <option value="agriculture">Agriculture</option>
                                            <option value="youth-empowerment">Youth Empowerment</option>
                                            <option value="women-empowerment">Women Empowerment</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Constituency *</label>
                                        <input type="text" class="form-control" name="constituency" required 
                                               value="<?php echo htmlspecialchars($userData['constituency'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ward *</label>
                                        <input type="text" class="form-control" name="ward" required 
                                               placeholder="Enter ward name">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Project Location *</label>
                                    <textarea class="form-control" name="location" rows="2" required 
                                              placeholder="Detailed project location address"></textarea>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6>Project Description & Objectives</h6>
                                <div class="mb-3">
                                    <label class="form-label">Detailed Project Description *</label>
                                    <textarea class="form-control" name="description" rows="4" required 
                                              placeholder="Describe the project in detail, including purpose and expected outcomes"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Project Objectives *</label>
                                    <textarea class="form-control" name="objectives" rows="3" required 
                                              placeholder="List specific, measurable objectives for this project"></textarea>
                                    <div class="form-text">Enter each objective on a new line</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Expected Impact</label>
                                    <textarea class="form-control" name="expected_impact" rows="3" 
                                              placeholder="Describe the expected impact on the community"></textarea>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6>Required Documents</h6>
                                <div class="mb-3">
                                    <label class="form-label">Project Proposal Document *</label>
                                    <input type="file" class="form-control" name="proposal_document" accept=".pdf,.doc,.docx">
                                    <div class="form-text">Upload your detailed project proposal (PDF or Word document)</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Site Photos</label>
                                    <input type="file" class="form-control" name="site_photos[]" multiple accept="image/*">
                                    <div class="form-text">Upload photos of the project site (optional but recommended)</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Community Resolution Letter</label>
                                    <input type="file" class="form-control" name="community_letter" accept=".pdf,.doc,.docx,.jpg,.png">
                                    <div class="form-text">Letter from community leadership supporting the project</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <div>
                                    <small class="text-muted">* Required fields</small>
                                </div>
                                <button type="submit" name="save_documentation" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save & Continue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Budget Planning Step -->
                <div class="setup-card fade-in-up" id="budget-step" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>Step 2: Budget Planning
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="guide-box">
                            <h6><i class="fas fa-calculator me-2"></i>Budget Guidelines</h6>
                            <p class="mb-0">Plan your budget according to CDF guidelines. Ensure all costs are justified and include a 10% contingency for unexpected expenses.</p>
                        </div>

                        <form method="POST">
                            <div class="form-section">
                                <h6>Budget Summary</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Total Project Budget (ZMW) *</label>
                                        <input type="number" class="form-control" name="total_budget" required 
                                               min="0" step="0.01" placeholder="0.00">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Contingency Fund (%)</label>
                                        <input type="number" class="form-control" name="contingency" value="10" 
                                               min="0" max="20" step="0.1">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Project Duration (Months)</label>
                                        <input type="number" class="form-control" name="duration" 
                                               min="1" max="36" placeholder="e.g., 12">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6>Budget Breakdown</h6>
                                <div id="budget-items">
                                    <div class="budget-item">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label class="form-label">Item Description</label>
                                                <input type="text" class="form-control" name="budget_items[0][description]" 
                                                       placeholder="e.g., Construction materials">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Quantity</label>
                                                <input type="number" class="form-control" name="budget_items[0][quantity]" 
                                                       min="1" value="1">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Unit Cost (ZMW)</label>
                                                <input type="number" class="form-control" name="budget_items[0][unit_cost]" 
                                                       min="0" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetItem(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addBudgetItem()">
                                    <i class="fas fa-plus me-1"></i>Add Budget Item
                                </button>
                            </div>

                            <div class="form-section">
                                <h6>Funding Sources</h6>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="funding_cdf" checked>
                                        <label class="form-check-label">CDF Funding</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="funding_community">
                                        <label class="form-check-label">Community Contribution</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="funding_other">
                                        <label class="form-check-label">Other Sources</label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Budget Justification</label>
                                    <textarea class="form-control" name="budget_justification" rows="3" 
                                              placeholder="Explain how the budget was determined and why each item is necessary"></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="showStep('documentation')">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                                <button type="submit" name="save_budget" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save & Continue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Timeline Setup Step -->
                <div class="setup-card fade-in-up" id="timeline-step" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-road me-2"></i>Step 3: Timeline Setup
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="guide-box">
                            <h6><i class="fas fa-calendar-alt me-2"></i>Timeline Guidelines</h6>
                            <p class="mb-0">Create a realistic project timeline with clear milestones. Consider seasonal factors and resource availability when planning.</p>
                        </div>

                        <form method="POST">
                            <div class="form-section">
                                <h6>Project Timeline</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Date *</label>
                                        <input type="date" class="form-control" name="start_date" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Expected Completion Date *</label>
                                        <input type="date" class="form-control" name="end_date" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6>Project Milestones</h6>
                                <div id="milestone-items">
                                    <div class="timeline-milestone">
                                        <div class="row">
                                            <div class="col-md-5 mb-3">
                                                <label class="form-label">Milestone Name</label>
                                                <input type="text" class="form-control" name="milestones[0][name]" 
                                                       placeholder="e.g., Site Preparation Complete">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Due Date</label>
                                                <input type="date" class="form-control" name="milestones[0][due_date]">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Weight (%)</label>
                                                <input type="number" class="form-control" name="milestones[0][weight]" 
                                                       min="0" max="100" placeholder="20">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="milestones[0][description]" rows="2" 
                                                      placeholder="Describe what completion of this milestone entails"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeMilestone(this)">
                                            <i class="fas fa-times me-1"></i>Remove Milestone
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addMilestone()">
                                    <i class="fas fa-plus me-1"></i>Add Milestone
                                </button>
                            </div>

                            <div class="form-section">
                                <h6>Critical Dependencies</h6>
                                <div class="mb-3">
                                    <label class="form-label">Key Dependencies</label>
                                    <textarea class="form-control" name="dependencies" rows="3" 
                                              placeholder="List any critical dependencies that could impact the timeline"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Risk Factors</label>
                                    <textarea class="form-control" name="risk_factors" rows="3" 
                                              placeholder="Identify potential risks to the timeline and mitigation strategies"></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="showStep('budget')">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                                <button type="submit" name="save_timeline" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save & Continue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resource Allocation Step -->
                <div class="setup-card fade-in-up" id="resources-step" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Step 4: Resource Allocation
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="guide-box">
                            <h6><i class="fas fa-tools me-2"></i>Resource Planning</h6>
                            <p class="mb-0">Identify all required resources including human resources, materials, and equipment. Ensure availability before project commencement.</p>
                        </div>

                        <form method="POST">
                            <div class="form-section">
                                <h6>Human Resources</h6>
                                <div class="row" id="team-members">
                                    <div class="col-md-6 mb-3">
                                        <div class="resource-card">
                                            <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                                            <h6>Project Manager</h6>
                                            <p class="small text-muted">Overall project coordination</p>
                                            <input type="text" class="form-control form-control-sm" name="team[manager]" 
                                                   placeholder="Name of project manager">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTeamMember()">
                                    <i class="fas fa-plus me-1"></i>Add Team Member
                                </button>
                            </div>

                            <div class="form-section">
                                <h6>Materials & Equipment</h6>
                                <div id="material-items">
                                    <div class="budget-item">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Material/Equipment</label>
                                                <input type="text" class="form-control" name="materials[0][name]" 
                                                       placeholder="e.g., Cement, bricks, tools">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Quantity Needed</label>
                                                <input type="number" class="form-control" name="materials[0][quantity]" 
                                                       min="1" value="1">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Source</label>
                                                <input type="text" class="form-control" name="materials[0][source]" 
                                                       placeholder="Supplier or source">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addMaterialItem()">
                                    <i class="fas fa-plus me-1"></i>Add Material
                                </button>
                            </div>

                            <div class="form-section">
                                <h6>Stakeholder Engagement</h6>
                                <div class="mb-3">
                                    <label class="form-label">Key Stakeholders</label>
                                    <textarea class="form-control" name="stakeholders" rows="3" 
                                              placeholder="List key stakeholders and their roles in the project"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Community Engagement Plan</label>
                                    <textarea class="form-control" name="engagement_plan" rows="3" 
                                              placeholder="Describe how the community will be involved in the project"></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="showStep('timeline')">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                                <button type="submit" name="save_resources" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save & Continue
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Compliance Checklist Step -->
                <div class="setup-card fade-in-up" id="compliance-step" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>Step 5: Compliance Checklist
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="guide-box">
                            <h6><i class="fas fa-shield-alt me-2"></i>Compliance Requirements</h6>
                            <p class="mb-0">Ensure your project meets all CDF and government compliance requirements before submission for approval.</p>
                        </div>

                        <form method="POST">
                            <div class="form-section">
                                <h6>Mandatory Compliance Items</h6>
                                <div id="compliance-checklist">
                                    <div class="compliance-item">
                                        <div class="form-check flex-grow-1">
                                            <input class="form-check-input" type="checkbox" name="compliance[project_proposal]">
                                            <label class="form-check-label">
                                                <strong>Project Proposal Document</strong>
                                                <small class="d-block text-muted">Detailed project proposal with objectives and implementation plan</small>
                                            </label>
                                        </div>
                                        <div class="ms-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-download me-1"></i>Template
                                            </button>
                                        </div>
                                    </div>

                                    <div class="compliance-item">
                                        <div class="form-check flex-grow-1">
                                            <input class="form-check-input" type="checkbox" name="compliance[budget_breakdown]">
                                            <label class="form-check-label">
                                                <strong>Detailed Budget Breakdown</strong>
                                                <small class="d-block text-muted">Itemized budget with cost justifications</small>
                                            </label>
                                        </div>
                                        <div class="ms-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-download me-1"></i>Template
                                            </button>
                                        </div>
                                    </div>

                                    <div class="compliance-item">
                                        <div class="form-check flex-grow-1">
                                            <input class="form-check-input" type="checkbox" name="compliance[community_approval]">
                                            <label class="form-check-label">
                                                <strong>Community Approval</strong>
                                                <small class="d-block text-muted">Evidence of community consultation and approval</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="compliance-item">
                                        <div class="form-check flex-grow-1">
                                            <input class="form-check-input" type="checkbox" name="compliance[environmental_assessment]">
                                            <label class="form-check-label">
                                                <strong>Environmental Impact Assessment</strong>
                                                <small class="d-block text-muted">Required for projects with potential environmental impact</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="compliance-item">
                                        <div class="form-check flex-grow-1">
                                            <input class="form-check-input" type="checkbox" name="compliance[business_registration]">
                                            <label class="form-check-label">
                                                <strong>Business Registration</strong>
                                                <small class="d-block text-muted">PACRA registration for implementing entity</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="compliance-item">
                                        <div class="form-check flex-grow-1">
                                            <input class="form-check-input" type="checkbox" name="compliance[tax_clearance]">
                                            <label class="form-check-label">
                                                <strong>Tax Clearance Certificate</strong>
                                                <small class="d-block text-muted">Valid ZRA tax clearance certificate</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h6>Additional Requirements</h6>
                                <div class="mb-3">
                                    <label class="form-label">Special Permits or Licenses</label>
                                    <textarea class="form-control" name="special_permits" rows="2" 
                                              placeholder="List any special permits or licenses required"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Compliance Notes</label>
                                    <textarea class="form-control" name="compliance_notes" rows="3" 
                                              placeholder="Any additional compliance considerations"></textarea>
                                </div>
                            </div>

                            <div class="template-download mb-4">
                                <i class="fas fa-file-download fa-3x text-primary mb-3"></i>
                                <h5>Download Setup Templates</h5>
                                <p class="text-muted">Get all the templates you need for complete project setup</p>
                                <button type="button" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Download All Templates
                                </button>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="showStep('resources')">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                                <button type="submit" name="submit_compliance" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-2"></i>Submit for Approval
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let budgetItemCount = 1;
        let milestoneCount = 1;
        let teamMemberCount = 1;
        let materialCount = 1;

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('[id$="-step"]').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected step
            document.getElementById(step + '-step').style.display = 'block';
            
            // Update active step in tracker
            document.querySelectorAll('.step-indicator').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`[onclick="showStep('${step}')"]`).classList.add('active');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function addBudgetItem() {
            const budgetItems = document.getElementById('budget-items');
            const newItem = document.createElement('div');
            newItem.className = 'budget-item';
            newItem.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">Item Description</label>
                        <input type="text" class="form-control" name="budget_items[${budgetItemCount}][description]" 
                               placeholder="e.g., Construction materials">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="budget_items[${budgetItemCount}][quantity]" 
                               min="1" value="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit Cost (ZMW)</label>
                        <input type="number" class="form-control" name="budget_items[${budgetItemCount}][unit_cost]" 
                               min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetItem(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            budgetItems.appendChild(newItem);
            budgetItemCount++;
        }

        function removeBudgetItem(button) {
            if (document.querySelectorAll('.budget-item').length > 1) {
                button.closest('.budget-item').remove();
            }
        }

        function addMilestone() {
            const milestoneItems = document.getElementById('milestone-items');
            const newMilestone = document.createElement('div');
            newMilestone.className = 'timeline-milestone';
            newMilestone.innerHTML = `
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Milestone Name</label>
                        <input type="text" class="form-control" name="milestones[${milestoneCount}][name]" 
                               placeholder="e.g., Site Preparation Complete">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="milestones[${milestoneCount}][due_date]">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Weight (%)</label>
                        <input type="number" class="form-control" name="milestones[${milestoneCount}][weight]" 
                               min="0" max="100" placeholder="20">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="milestones[${milestoneCount}][description]" rows="2" 
                              placeholder="Describe what completion of this milestone entails"></textarea>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeMilestone(this)">
                    <i class="fas fa-times me-1"></i>Remove Milestone
                </button>
            `;
            milestoneItems.appendChild(newMilestone);
            milestoneCount++;
        }

        function removeMilestone(button) {
            if (document.querySelectorAll('.timeline-milestone').length > 1) {
                button.closest('.timeline-milestone').remove();
            }
        }

        function addTeamMember() {
            const teamMembers = document.getElementById('team-members');
            const roles = ['Site Supervisor', 'Technical Officer', 'Community Liaison', 'Finance Officer', 'Quality Assurance'];
            const icons = ['fa-hard-hat', 'fa-cogs', 'fa-handshake', 'fa-calculator', 'fa-clipboard-check'];
            
            const newMember = document.createElement('div');
            newMember.className = 'col-md-6 mb-3';
            newMember.innerHTML = `
                <div class="resource-card">
                    <i class="fas ${icons[teamMemberCount % icons.length]} fa-2x text-primary mb-2"></i>
                    <h6>${roles[teamMemberCount % roles.length]}</h6>
                    <p class="small text-muted">Team member role</p>
                    <input type="text" class="form-control form-control-sm" name="team[member_${teamMemberCount}]" 
                           placeholder="Name of team member">
                </div>
            `;
            teamMembers.appendChild(newMember);
            teamMemberCount++;
        }

        function addMaterialItem() {
            const materialItems = document.getElementById('material-items');
            const newItem = document.createElement('div');
            newItem.className = 'budget-item';
            newItem.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Material/Equipment</label>
                        <input type="text" class="form-control" name="materials[${materialCount}][name]" 
                               placeholder="e.g., Cement, bricks, tools">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity Needed</label>
                        <input type="number" class="form-control" name="materials[${materialCount}][quantity]" 
                               min="1" value="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Source</label>
                        <input type="text" class="form-control" name="materials[${materialCount}][source]" 
                               placeholder="Supplier or source">
                    </div>
                </div>
            `;
            materialItems.appendChild(newItem);
            materialCount++;
        }

        // Initialize the first step
        document.addEventListener('DOMContentLoaded', function() {
            showStep('documentation');
            
            // Add event listeners to compliance checkboxes
            document.querySelectorAll('.compliance-item .form-check-input').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        this.closest('.compliance-item').classList.add('checked');
                    } else {
                        this.closest('.compliance-item').classList.remove('checked');
                    }
                });
            });
        });

        // Form validation
        function validateForm(step) {
            // Add form validation logic here
            return true;
        }
    </script>
</body>
</html>