<?php

/** @var Array $dataVars */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container loginBox">
    <div class="row justify-content-center">
        <div id="registerMessage" class="text-center text-danger mb-3"></div>
        <form class="form-signin needs-validation" method="post" id="gForm" novalidate>
            <div class="form-label-group mb-3">
                <label for="name" class="form-label">Name (this will be visible to other users)</label>
                <input name="name" type="text" id="name" class="form-control" placeholder="Name"
                       required autofocus maxlength="255" minlength="1">
                <div class="invalid-feedback">
                    Please provide a name (1-255 characters).
                </div>
            </div>
            <div class="form-label-group mb-3">
                <label for="login" class="form-label">Username (you will use this to log in)</label>
                <input name="login" type="text" id="login" class="form-control" placeholder="Login"
                       required maxlength="50" minlength="3">
                <div class="invalid-feedback">
                    Please provide a username (3-50 characters).
                </div>
            </div>
            <div class="form-label-group mb-3">
                <label for="password" class="form-label">Password</label>
                <input name="password" type="password" id="password" class="form-control"
                       placeholder="Password" required minlength="8">
                <div class="invalid-feedback">
                    Password needs to be at least 8 characters long.
                </div>
            </div>
            <div class="form-label-group mb-3">
                <label for="repeat_password" class="form-label">Repeat password</label>
                <input name="repeat_password" type="password" id="repeat_password" class="form-control" placeholder="Same password"
                       required minlength="8">
                <div class="invalid-feedback">
                    Passwords need to match and be at least 8 characters.
                </div>
            </div>
            <div class="row formRow">
                <div class="col d-flex justify-content-center align-items-center" >
                    <button class="btn btn-danger customButton mb-3" type="submit" name="submit">
                        Register
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const $form = document.getElementById('gForm');
    const $name = document.getElementById('name');
    const $username = document.getElementById('login');
    const $passwordField = document.getElementById('password');
    const $repeatPassword = document.getElementById('repeat_password');
    const messageDiv = document.getElementById('registerMessage');

    $form.addEventListener('submit', async (gEvent) => {
        gEvent.preventDefault();

        let invalid = 0;

        // Validate name
        const nameValid = $name.value.trim().length >= 1 && $name.value.length <= 255;
        if (!nameValid) {
            $name.classList.add('is-invalid');
            invalid++;
        } else {
            $name.classList.remove('is-invalid');
        }

        // Validate username
        const usernameValid = $username.value.trim().length >= 3 && $username.value.length <= 50;
        if (!usernameValid) {
            $username.classList.add('is-invalid');
            invalid++;
        } else {
            $username.classList.remove('is-invalid');
        }

        // Validate password
        const passwordValid = $passwordField.value.length >= 8;
        if (!passwordValid) {
            $passwordField.classList.add('is-invalid');
            invalid++;
        } else {
            $passwordField.classList.remove('is-invalid');
        }

        // Validate password match
        const passwordsMatch = $passwordField.value === $repeatPassword.value && $passwordField.value.length >= 8;
        if (!passwordsMatch) {
            $repeatPassword.classList.add('is-invalid');
            invalid++;
        } else {
            $repeatPassword.classList.remove('is-invalid');
        }

        if (invalid > 0) {
            return;
        }

        // Clear previous messages
        messageDiv.textContent = '';
        messageDiv.className = 'text-center text-danger mb-3';

        try {
            // Show loading state
            const submitBtn = $form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registering...';

            const formData = new FormData($form);
            formData.append('submit', '1');

            const response = await fetch('<?= $link->url("auth.register") ?>', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin',
                body: formData
            });

            const json = await response.json();

            if (json.ok) {
                messageDiv.className = 'text-center text-success mb-3';
                messageDiv.textContent = 'Registration successful! Redirecting...';
                setTimeout(() => {
                    window.location.href = json.redirect;
                }, 500);
            } else {
                messageDiv.className = 'text-center text-danger mb-3';
                messageDiv.textContent = json.error || 'Registration failed';
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        } catch (e) {
            console.error('Registration error:', e);
            messageDiv.className = 'text-center text-danger mb-3';
            messageDiv.textContent = 'An error occurred. Please try again.';
            const submitBtn = $form.querySelector('button[type="submit"]');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
});
</script>

