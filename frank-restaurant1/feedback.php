<?php
$pageTitle    = 'Customer Feedback';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $overall = intval($_POST['rating']);
    $food    = intval($_POST['food']);
    $service = intval($_POST['service']);
    $ambiance= intval($_POST['ambiance']);
    $comment = sanitize($_POST['comment']);
    
    // Get user ID if logged in, otherwise null
    $uid = isLoggedIn() ? $user_id : null;
    
    db()->insert(
        "INSERT INTO feedback (user_id, name, email, overall_rating, food_rating, service_rating, ambiance_rating, comment) VALUES (?,?,?,?,?,?,?,?)",
        [$uid, $name, $email, $overall, $food, $service, $ambiance, $comment]
    );
    
    redirect('testimonials.php', 'Thank you for your feedback!', 'success');
}

$user_email = '';
if(isLoggedIn()) {
    $u = db()->fetchOne("SELECT email FROM users WHERE user_id=?", [$user_id]);
    $user_email = $u['email'];
}
?>

<div class="row justify-center">
    <div class="col-md-8">
        <div class="card animate-in">
            <h2 class="section-title text-center mb-4">We Value Your Feedback</h2>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required value="<?= isLoggedIn() ? $name : '' ?>">
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user_email) ?>">
                    </div>
                </div>
                
                <div class="mb-4 text-center">
                    <label class="block mb-2 font-bold">Overall Experience</label>
                    <div class="rating-stars text-4xl cursor-pointer justify-center flex gap-2" style="font-size: 2.5rem; justify-content: center; display: flex; gap: 10px;">
                        <?php for($i=1; $i<=5; $i++): ?>
                        <span onclick="setRating('rating', <?= $i ?>)" id="star_rating_<?= $i ?>" style="color:#ccc;">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating" required>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4 text-center">
                        <label class="block text-sm mb-1">Food</label>
                        <select name="food" class="form-control">
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="block text-sm mb-1">Service</label>
                        <select name="service" class="form-control">
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                    <div class="col-md-4 text-center">
                        <label class="block text-sm mb-1">Ambiance</label>
                        <select name="ambiance" class="form-control">
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group mb-4">
                    <label>Comments</label>
                    <textarea name="comment" class="form-control" rows="4"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">Submit Feedback</button>
            </form>
        </div>
    </div>
</div>

<script>
function setRating(inputName, val) {
    document.getElementById(inputName).value = val;
    for(let i=1; i<=5; i++) {
        document.getElementById('star_' + inputName + '_' + i).style.color = i <= val ? '#FFD700' : '#ccc';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
