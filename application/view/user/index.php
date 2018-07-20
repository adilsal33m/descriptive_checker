<div class="container">
    <div class="box">
        <h2>Your profile</h2>

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <div><b>Your username:</b> <?= $this->user_name; ?></div>
        <div><b>Your email:</b> <?= $this->user_email; ?></div><br>
        <div><b>Your account type is:</b> <?= $this->user_account_type == 7 ? "Teacher" : "Student"; ?></div>
    </div>
</div>
