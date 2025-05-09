<?php
// Este arquivo é usado para validar o login do usuário de forma assíncrona se necessário
session_start();
require 'conexao.php';

// Verifica se foi enviado um POST com as credenciais
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $senha = $_POST["senha"];
    $resposta = array();
    
    // Validação básica
    if (empty($email) || empty($senha)) {
        $resposta["status"] = "erro";
        $resposta["mensagem"] = "Por favor, preencha todos os campos.";
    } else {
        // Verifica no banco de dados
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 1) {
                $usuario = $stmt->fetch();
                
                // Verifica a senha
                if (password_verify($senha, $usuario["senha"])) {
                    // Senha correta, cria a sessão
                    $_SESSION["usuario_id"] = $usuario["id"];
                    $_SESSION["usuario_email"] = $usuario["email"];
                    $_SESSION["usuario_nome"] = $usuario["nome"];
                    
                    $resposta["status"] = "sucesso";
                    $resposta["mensagem"] = "Login realizado com sucesso!";
                    $resposta["redirecionamento"] = "painel.php";
                } else {
                    $resposta["status"] = "erro";
                    $resposta["mensagem"] = "Senha incorreta.";
                }
            } else {
                $resposta["status"] = "erro";
                $resposta["mensagem"] = "Usuário não encontrado.";
            }
        } catch (PDOException $e) {
            $resposta["status"] = "erro";
            $resposta["mensagem"] = "Erro ao fazer login: " . $e->getMessage();
        }
    }
    
    // Retorna a resposta como JSON
    header('Content-Type: application/json');
    echo json_encode($resposta);
    exit;
}

// Se não foi POST, redireciona para a página de login
header("Location: login.php");
exit;
?>








