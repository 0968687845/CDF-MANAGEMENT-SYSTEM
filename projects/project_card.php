<?php
// This file is included in the projects.php to display individual project cards
?>
<div class="card project-card">
    <div class="project-status-badge">
        <span class="badge bg-<?php 
            switch($project['status']) {
                case 'completed': echo 'success'; break;
                case 'in-progress': echo 'warning'; break;
                case 'delayed': echo 'danger'; break;
                default: echo 'primary';
            }
        ?>"><?php echo ucfirst(str_replace('-', ' ', $project['status'])); ?></span>
    </div>
    <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
        <p class="card-text text-muted"><?php echo htmlspecialchars($project['description']); ?></p>
        
        <div class="progress-section mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small class="text-muted">Progress</small>
                <small class="fw-bold"><?php echo $project['progress']; ?>%</small>
            </div>
            <div class="progress">
                <div class="progress-bar bg-<?php 
                    switch($project['status']) {
                        case 'completed': echo 'success'; break;
                        case 'in-progress': echo 'warning'; break;
                        case 'delayed': echo 'danger'; break;
                        default: echo 'primary';
                    }
                ?>" style="width: <?php echo $project['progress']; ?>%"></div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted">Budget</small>
                <div class="fw-bold">ZMW <?php echo number_format($project['budget'], 2); ?></div>
            </div>
            <div class="col-6">
                <small class="text-muted">Timeline</small>
                <div class="fw-bold">
                    <?php echo date('M Y', strtotime($project['start_date'])); ?> - 
                    <?php echo date('M Y', strtotime($project['end_date'])); ?>
                </div>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted">Location</small>
                <div class="fw-bold"><?php echo htmlspecialchars($project['location'] ?? 'Not specified'); ?></div>
            </div>
            <div class="col-6">
                <small class="text-muted">M&E Officer</small>
                <div class="fw-bold"><?php echo htmlspecialchars($project['officer_name'] ?? 'Not assigned'); ?></div>
            </div>
        </div>
        
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary btn-sm action-btn" onclick="viewProjectDetails(<?php echo $project['id']; ?>)">
                <i class="fas fa-eye me-1"></i>View Details
            </button>
            <button class="btn btn-outline-primary btn-sm action-btn" onclick="updateProgress(<?php echo $project['id']; ?>)">
                <i class="fas fa-edit me-1"></i>Update Progress
            </button>
            <button class="btn btn-outline-info btn-sm action-btn" onclick="viewProjectDocuments(<?php echo $project['id']; ?>)">
                <i class="fas fa-folder me-1"></i>Documents
            </button>
        </div>
    </div>
</div>