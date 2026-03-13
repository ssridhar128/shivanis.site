<?php
// Include this after defining $comments. For report pages: analyst comments with user name, date, time (persistent).
?>
<section class="card bg-secondary border-dark mb-4">
    <div class="card-body">
        <h2 class="h5 card-title text-light">Section Observations</h2>
        <p class="small text-secondary mb-3">Analyst comments decoding the meaning of the data below. Each comment is stored with the user who wrote it and when.</p>
        <?php foreach ($comments as $c): ?>
        <div class="border-bottom border-dark py-2 mb-2">
            <small class="text-secondary"><?= htmlspecialchars($c['username']) ?> · <?= htmlspecialchars($c['created_at']) ?></small>
            <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($c['comment_text'])) ?></p>
        </div>
        <?php endforeach; ?>
        <?php if (empty($comments)): ?><p class="text-secondary mb-0">No observations yet.</p><?php endif; ?>

        <?php if (getCurrentRole() === ROLE_ANALYST || getCurrentRole() === ROLE_SUPER_ADMIN): ?>
        <form method="post" class="mt-3">
            <label for="comment_text" class="form-label">Enter observations (decode the meaning of the data)...</label>
            <textarea name="comment_text" id="comment_text" class="form-control bg-dark text-light border-dark" rows="3" required placeholder="e.g. Load time spike on 3/8 suggests a deployment or traffic event."></textarea>
            <button type="submit" class="btn btn-primary mt-2">Post Comment</button>
        </form>
        <?php endif; ?>
    </div>
</section>
