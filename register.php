<?php
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die("<p>Error: Token CSRF no válido</p>");
    }

    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password != $confirm_password) {
        echo "<p>Las contraseñas no coinciden. Por favor, intenta de nuevo.</p>";
    } else {
        $conex = getDBConnection();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Verificar si el username ya existe
        $sql = "SELECT * FROM usuarios WHERE username = ?";
        $stmt = $conex->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<p>El nombre de usuario ya está en uso. Por favor, elige otro.</p>";
        } else {
            $stmt->close();

            $sql = "INSERT INTO usuarios (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conex->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sss", $username, $email, $hashed_password);

                if ($stmt->execute()) {
                    // Iniciar sesión automáticamente
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['username'] = $username;

                    // Redirigir con ?register=success
                    header("Location: index.php?register=success");
                    exit();
                } else {
                    echo "<p>Error: " . $stmt->error . "</p>";
                }
                $stmt->close();
            } else {
                echo "<p>Error en la preparación: " . $conex->error . "</p>";
            }
        }
        $conex->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<form action="register.php" method="post" class="register_form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <label for="username">Nombre de usuario:</label>
    <input type="text" id="username" name="username" required>
    <label for="email">Correo Electrónico:</label>
    <input type="email" id="email" name="email" required>
    <label for="password">Contraseña:</label>
    <input type="password" id="password" name="password" required>
    <label for="confirm_password">Confirmar Contraseña:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>
    <button type="submit">Registrarse</button>
</form>
<br><br><br><br><br><br><br>

<?php
include('includes/footer.php');
?>
