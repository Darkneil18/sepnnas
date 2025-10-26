<?php
require_once '../config/config.php';
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../classes/Feedback.php';
require_once '../classes/Event.php';

$database = new Database();
$db = $database->getConnection();
$feedback = new Feedback($db);
$event = new Event($db);

$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;
$success_message = '';
$error_message = '';

if($event_id) {
    $event_details = $event->getEventById($event_id);
    
    if(!$event_details) {
        header('Location: events.php');
        exit();
    }
    
    // Check if user has already submitted feedback
    if($feedback->hasUserSubmittedFeedback($event_id, $_SESSION['user_id'])) {
        $error_message = "You have already submitted feedback for this event.";
    }
} else {
    header('Location: events.php');
    exit();
}

if($_POST && !$error_message) {
    $feedback->event_id = $event_id;
    $feedback->user_id = $_SESSION['user_id'];
    $feedback->rating = $_POST['rating'];
    $feedback->comments = $_POST['comments'];
    $feedback->suggestions = $_POST['suggestions'];
    
    if($feedback->submitFeedback()) {
        $success_message = "Thank you for your feedback! Your response has been recorded.";
    } else {
        $error_message = "Failed to submit feedback. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - SEPNAS Event Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .feedback-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .feedback-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .feedback-body {
            padding: 2rem;
        }
        .rating-stars {
            font-size: 2rem;
            color: #ffc107;
            cursor: pointer;
        }
        .rating-stars .star {
            transition: all 0.2s;
        }
        .rating-stars .star:hover,
        .rating-stars .star.active {
            transform: scale(1.1);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .event-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="feedback-card">
                    <div class="feedback-header">
                        <h3><i class="fas fa-comments me-2"></i>Event Feedback</h3>
                        <p class="mb-0">Help us improve future events</p>
                    </div>
                    <div class="feedback-body">
                        <?php if($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            </div>
                            <div class="text-center">
                                <a href="events.php" class="btn btn-primary">Back to Events</a>
                            </div>
                        <?php elseif($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                            </div>
                            <div class="text-center">
                                <a href="events.php" class="btn btn-primary">Back to Events</a>
                            </div>
                        <?php else: ?>
                            <!-- Event Information -->
                            <div class="event-info">
                                <h5><?php echo htmlspecialchars($event_details['title']); ?></h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-calendar me-2"></i>
                                            <?php echo date('M d, Y', strtotime($event_details['event_date'])); ?>
                                        </p>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-clock me-2"></i>
                                            <?php echo date('g:i A', strtotime($event_details['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($event_details['end_time'])); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            <?php echo htmlspecialchars($event_details['venue_name']); ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($event_details['organizer_first_name'] . ' ' . $event_details['organizer_last_name']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label">How would you rate this event? *</label>
                                    <div class="rating-stars" id="ratingStars">
                                        <i class="fas fa-star star" data-rating="1"></i>
                                        <i class="fas fa-star star" data-rating="2"></i>
                                        <i class="fas fa-star star" data-rating="3"></i>
                                        <i class="fas fa-star star" data-rating="4"></i>
                                        <i class="fas fa-star star" data-rating="5"></i>
                                    </div>
                                    <input type="hidden" name="rating" id="ratingInput" required>
                                    <small class="text-muted">Click on a star to rate (1 = Poor, 5 = Excellent)</small>
                                </div>

                                <div class="mb-4">
                                    <label for="comments" class="form-label">Comments</label>
                                    <textarea class="form-control" id="comments" name="comments" rows="4" 
                                              placeholder="Tell us about your experience at this event..."></textarea>
                                </div>

                                <div class="mb-4">
                                    <label for="suggestions" class="form-label">Suggestions for Improvement</label>
                                    <textarea class="form-control" id="suggestions" name="suggestions" rows="3" 
                                              placeholder="How can we make future events better?"></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-submit">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        let currentRating = 0;

        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                currentRating = index + 1;
                ratingInput.value = currentRating;
                updateStars();
            });

            star.addEventListener('mouseenter', () => {
                highlightStars(index + 1);
            });
        });

        document.getElementById('ratingStars').addEventListener('mouseleave', () => {
            updateStars();
        });

        function highlightStars(rating) {
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        function updateStars() {
            stars.forEach((star, index) => {
                if (index < currentRating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('Please select a rating before submitting.');
            }
        });
    </script>
</body>
</html>
