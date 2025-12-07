$form = document.getElementById("gForm");
$name = document.getElementById("name");
$username = document.getElementById("login");
$passwordField = document.getElementById("password");
$repeatPassword = document.getElementById("repeat_password");
$form.addEventListener('submit', (gEvent) => {
    let invalid = 0;
    if ($name.value.length < 1) {
        $name.classList.add("is-invalid");
        invalid++;
    } else {
        $name.classList.remove("is-invalid");
    }
    if ($username.value.length < 1) {
        $username.classList.add("is-invalid");
        invalid++;
    } else {
        $username.classList.remove("is-invalid");
    }
    if ($passwordField.value.length < 8) {
        $passwordField.classList.add("is-invalid");
        invalid++;
    } else {
        $passwordField.classList.remove("is-invalid");
    }
    if ($passwordField.value !== $repeatPassword.value) {
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




