function verifieLogin() {
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value.trim();

    if(email === "" || password === ""){
        alert("Please fill all the fields");
        return false;
    }

    if(email.length < 5 || password.length < 6){
        alert("Please enter valid data (email at least 5 chars, password at least 6 chars)");
        return false;
    }

    alert("Form submitted successfully");
    return true;
}

function verifieSignup() {
    var cin = document.getElementById("cin").value.trim();
    var firstname = document.getElementById("firstname").value.trim();
    var lastname = document.getElementById("lastname").value.trim();
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value.trim();

    if(cin === "" || firstname === "" || lastname === "" || email === "" || password === ""){
        alert("Please fill all the fields");
        return false;
    }

    if(cin.length < 8 || firstname.length < 2 || lastname.length < 2 || email.length < 5 || password.length < 6){
        alert("Please enter valid data (CIN at least 8 chars, names at least 2 chars, email at least 5 chars, password at least 6 chars)");
        return false;
    }

    if(isNaN(cin)){
        alert("Please enter a valid CIN number");
        return false;
    }

    alert("Form submitted successfully");
    return true;
}

function verifieEdit() {
    var firstname = document.getElementById("firstname").value.trim();
    var lastname = document.getElementById("lastname").value.trim();
    var email = document.getElementById("email").value.trim();

    if(firstname === "" || lastname === "" || email === ""){
        alert("Please fill all the fields");
        return false;
    }

    if(firstname.length < 2 || lastname.length < 2 || email.length < 5){
        alert("Please enter valid data (names at least 2 chars, email at least 5 chars)");
        return false;
    }

    alert("Form submitted successfully");
    return true;
}
