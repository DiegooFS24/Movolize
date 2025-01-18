<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
?>

<form action="login.php" method="post" class="login_form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"> <!-- Token CSRF -->
    <label for="email">Correo Electrónico:</label>
    <input type="email" id="email" name="email" required>
    <label for="password">Contraseña:</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Iniciar Sesión</button>
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die("<p>Error: Token CSRF no válido</p>");
    }

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Contraseña se verifica encriptada.

    $conex = getDBConnection();

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conex->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Guardar sesión y redirigir con ?login=success
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php?login=success");
        exit();
    } else {
        echo "<p>Correo electrónico o contraseña incorrectos</p>";
    }

    $stmt->close();
    $conex->close();
}
?>

<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<?php
include('includes/footer.php');
?>
