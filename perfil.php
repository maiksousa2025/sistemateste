<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$erro = "";
$sucesso = "";
$usuario_id = $_SESSION["usuario_id"];

try {
    $stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        $erro = "Usuário não encontrado.";
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar dados: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $novo_email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $nova_senha = $_POST["senha"];

    if (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } else {
        try {
            // Atualiza apenas o e-mail
            $stmt = $pdo->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
            $stmt->execute([$novo_email, $usuario_id]);

            // Atualiza a senha, se for fornecida
            if (!empty($nova_senha)) {
                if (strlen($nova_senha) < 6) {
                    $erro = "A nova senha deve ter pelo menos 6 caracteres.";
                } else {
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                    $stmt->execute([$senha_hash, $usuario_id]);
                }
            }

            if (empty($erro)) {
                $sucesso = "Dados atualizados com sucesso!";
                $usuario["email"] = $novo_email;
            }
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - Sistema MPHP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Meu Perfil</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger"><?php echo $erro; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($sucesso)): ?>
                        <div class="alert alert-success"><?php echo $sucesso; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario["nome"]); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($usuario["email"]); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nova Senha (opcional)</label>
                            <input type="password" name="senha" class="form-control" placeholder="Mínimo 6 caracteres">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="painel.php" class="btn btn-secondary btn-sm">Voltar ao Painel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

