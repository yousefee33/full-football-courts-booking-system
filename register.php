<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);

    // Validate inputs
    $errors = [];

    // name validation
    if (empty($name)) {
        $errors[] = "name is required";
    } elseif (strlen($name) < 3) {
        $errors[] = "name must be at least 3 characters long";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Phone validation
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $errors[] = "Invalid phone number format";
    }

    // Full name validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (empty($errors)) {
        // Check if name or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ? OR email = ?");
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "name or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, phone, full_name, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $full_name);

            if ($stmt->execute()) {
                $success_message = "Registration successful! Please login.";
                // Redirect to login page after 3 seconds
                header("refresh:3;url=login.php");
            } else {
                $error_message = "Error creating account. Please try again.";
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .submit-btn:hover {
            background: #218838;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }
        .requirement i {
            font-size: 0.75rem;
        }
        .requirement.valid {
            color: #28a745;
        }
        .requirement.invalid {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">⚽ El 7arefaa Court</div>
    </nav>

    <div class="register-container">
        <h1>Create Account</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm" novalidate>
            <div class="form-group">
                <label for="name">name</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('password')"></i>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="length">
                        <i class="fas fa-circle"></i>
                        At least 6 characters
                    </div>
                    <div class="requirement" id="letter">
                        <i class="fas fa-circle"></i>
                        Contains letters
                    </div>
                    <div class="requirement" id="number">
                        <i class="fas fa-circle"></i>
                        Contains numbers
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="toggle-password fas fa-eye" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password validation
        const password = document.getElementById('password');
        const requirements = {
            length: document.getElementById('length'),
            letter: document.getElementById('letter'),
            number: document.getElementById('number')
        };

        password.addEventListener('input', function() {
            const value = this.value;
            
            // Check length
            if (value.length >= 6) {
                requirements.length.classList.add('valid');
                requirements.length.classList.remove('invalid');
            } else {
                requirements.length.classList.add('invalid');
                requirements.length.classList.remove('valid');
            }

            // Check for letters
            if (/[a-zA-Z]/.test(value)) {
                requirements.letter.classList.add('valid');
                requirements.letter.classList.remove('invalid');
            } else {
                requirements.letter.classList.add('invalid');
                requirements.letter.classList.remove('valid');
            }

            // Check for numbers
            if (/\d/.test(value)) {
                requirements.number.classList.add('valid');
                requirements.number.classList.remove('invalid');
            } else {
                requirements.number.classList.add('invalid');
                requirements.number.classList.remove('valid');
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
