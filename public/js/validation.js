$form = document.getElementById("gForm");
$name = document.getElementById("name");
$username = document.getElementById("login");
$passwordField = document.getElementById("password");
$repeatPassword = document.getElementById("repeat_password");

// Form submission validation - only validate on submit, not during typing
$form.addEventListener('submit', (gEvent) => {
    let invalid = 0;

    // Validate name
    const nameValid = $name.value.trim().length >= 1 && $name.value.length <= 255;
    if (!nameValid) {
        $name.classList.add("is-invalid");
        invalid++;
    } else {
        $name.classList.remove("is-invalid");
    }

    // Validate username
    const usernameValid = $username.value.trim().length >= 3 && $username.value.length <= 50;
    if (!usernameValid) {
        $username.classList.add("is-invalid");
        invalid++;
    } else {
        $username.classList.remove("is-invalid");
    }

    // Validate password
    const passwordValid = $passwordField.value.length >= 8;
    if (!passwordValid) {
        $passwordField.classList.add("is-invalid");
        invalid++;
    } else {
        $passwordField.classList.remove("is-invalid");
    }

    // Validate password match
    const passwordsMatch = $passwordField.value === $repeatPassword.value && $passwordField.value.length >= 8;
    if (!passwordsMatch) {
        $repeatPassword.classList.add("is-invalid");
        invalid++;
    } else {
        $repeatPassword.classList.remove("is-invalid");
    }

    if (invalid > 0) {
        gEvent.preventDefault();
        gEvent.stopPropagation();
    } else {
        $form.classList.add("was-validated");
    }
})




