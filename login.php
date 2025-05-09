<?php
session_start();
include('conexao.php');

// Verifica se o usuário já está logado
if(isset($_SESSION['id_usuario'])) {
    header("Location: painel.php");
    exit;
}

$erro = "";

// Verifica se o formulário foi enviado
if(isset($_POST['email']) && isset($_POST['senha'])) {
    $email = mysqli_real_escape_string($mysqli, $_POST['email']);
    $senha = $_POST['senha'];
    
    // Busca o usuário pelo email
    $sql = "SELECT id, nome, email, senha FROM usuarios WHERE email = '$email'";
    $result = mysqli_query($mysqli, $sql);
    
    if(mysqli_num_rows($result) == 1) {
        $usuario = mysqli_fetch_assoc($result);
        
        // Verifica a senha com password_verify
        if(password_verify($senha, $usuario['senha'])) {
            // Senha correta, cria sessão
            $_SESSION['id_usuario'] = $usuario['id'];
            $_SESSION['nome_usuario'] = $usuario['nome'];
            $_SESSION['email_usuario'] = $usuario['email'];
            
            // Redireciona para o painel
            header("Location: painel.php");
            exit;
        } else {
            $erro = "Email ou senha incorretos";
        }
    } else {
        $erro = "Email ou senha incorretos";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gerenciamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 15px;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #4e73df;
            border-radius: 1rem 1rem 0 0 !important;
            color: white;
            text-align: center;
            padding: 1.5rem;
            border: none;
        }
        .card-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-size: 0.9rem;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .logo {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .text-link {
            color: #4e73df;
            text-decoration: none;
        }
        .text-link:hover {
            text-decoration: underline;
        }
        .divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }
        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #e3e6f0;
        }
        .divider-text {
            position: relative;
            display: inline-block;
            padding: 0 1rem;
            background-color: #fff;
            color: #b7b9cc;
        }
        .social-button {
            width: 100%;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
        }
        .google {
            background-color: #ea4335;
        }
        .facebook {
            background-color: #3b5998;
        }
        .icon {
            margin-right: 0.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #5a5c69;
        }
        .input-group-text {
            background-color: #f8f9fc;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-circle logo"></i>
                <h4 class="mb-0">Bem-vindo ao Sistema</h4>
            </div>
            <div class="card-body">
                <?php if(!empty($erro)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Endereço de Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Digite seu email" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="lembrarme">
                            <label class="form-check-label" for="lembrarme">
                                Lembrar-me
                            </label>
                        </div>
                        <a href="#" class="text-link">Esqueceu a senha?</a>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
                </form>
                
                <div class="divider">
                    <span class="divider-text">OU</span>
                </div>
                
                <div class="social-button google">
                    <i class="fab fa-google icon"></i>
                    Entrar com Google
                </div>
                <div class="social-button facebook">
                    <i class="fab fa-facebook-f icon"></i>
                    Entrar com Facebook
                </div>
                
                <div class="text-center mt-4">
                    <span>Não tem uma conta? </span>
                    <a href="cadastro.php" class="text-link">Cadastre-se</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>




