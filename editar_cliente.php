<?php
session_start();
require 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$erro = "";
$sucesso = "";
$cliente = null;
$cliente_id = null;

// Verifica se foi passado um ID válido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $cliente_id = $_GET['id'];
    
    // Busca os dados do cliente
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$cliente_id, $_SESSION["usuario_id"]]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            $erro = "Cliente não encontrado ou você não tem permissão para editá-lo.";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao buscar cliente: " . $e->getMessage();
    }
} else {
    $erro = "ID de cliente inválido.";
}

// Verifica se foi enviado o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST" && $cliente) {
    $nome = htmlspecialchars($_POST["nome"]);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $telefone = htmlspecialchars($_POST["telefone"]);
    $endereco = htmlspecialchars($_POST["endereco"]);
    $rg = htmlspecialchars($_POST["rg"]);
    $cpf = htmlspecialchars($_POST["cpf"]);
    
    // Mantém o anexo atual
    $anexo = $cliente['anexo'];
    
    // Verifica se foi enviado um novo anexo
    if (!empty($_FILES["anexo"]["name"])) {
        $pasta = "uploads/";
        
        // Cria a pasta se não existir
        if (!file_exists($pasta)) {
            mkdir($pasta, 0777, true);
        }
        
        $nome_arquivo = uniqid() . "-" . basename($_FILES["anexo"]["name"]);
        $destino = $pasta . $nome_arquivo;
        
        // Verifica o tipo de arquivo (opcional)
        $tipo_arquivo = strtolower(pathinfo($destino, PATHINFO_EXTENSION));
        $tipos_permitidos = array("jpg", "jpeg", "png", "pdf", "doc", "docx");
        
        if (!in_array($tipo_arquivo, $tipos_permitidos)) {
            $erro = "Apenas arquivos JPG, JPEG, PNG, PDF, DOC e DOCX são permitidos.";
        } else {
            // Faz o upload
            if (move_uploaded_file($_FILES["anexo"]["tmp_name"], $destino)) {
                // Remove o anexo antigo se existir
                if (!empty($cliente['anexo']) && file_exists($cliente['anexo'])) {
                    unlink($cliente['anexo']);
                }
                $anexo = $destino;
            } else {
                $erro = "Erro ao fazer upload do anexo.";
            }
        }
    }
    
    // Se não houver erro, atualiza no banco de dados
    if (empty($erro)) {
        try {
            $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, email = ?, telefone = ?, endereco = ?, rg = ?, cpf = ?, anexo = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $email, $telefone, $endereco, $rg, $cpf, $anexo, $cliente_id, $_SESSION["usuario_id"]]);
            $sucesso = "Cliente atualizado com sucesso!";
            
            // Atualiza os dados do cliente na variável
            $cliente['nome'] = $nome;
            $cliente['email'] = $email;
            $cliente['telefone'] = $telefone;
            $cliente['endereco'] = $endereco;
            $cliente['rg'] = $rg;
            $cliente['cpf'] = $cpf;
            $cliente['anexo'] = $anexo;
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar cliente: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - Sistema MPHP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Editar Cliente</h2>
                        <div>
                            <a href="painel.php" class="btn btn-dark btn-sm">Voltar ao Painel</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger"><?php echo $erro; ?></div>
                            <?php if ($erro == "ID de cliente inválido." || $erro == "Cliente não encontrado ou você não tem permissão para editá-lo."): ?>
                                <div class="text-center">
                                    <a href="painel.php" class="btn btn-primary">Voltar ao Painel</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($sucesso)): ?>
                            <div class="alert alert-success"><?php echo $sucesso; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($cliente): ?>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nome" class="form-label">Nome Completo*</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required 
                                               value="<?php echo htmlspecialchars($cliente['nome']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">E-mail*</label>
                                        <input type="email" class="form-control" id="email" name="email" required
                                               value="<?php echo htmlspecialchars($cliente['email']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="telefone" class="form-label">Telefone</label>
                                        <input type="text" class="form-control" id="telefone" name="telefone"
                                               value="<?php echo htmlspecialchars($cliente['telefone']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="cpf" class="form-label">CPF</label>
                                        <input type="text" class="form-control" id="cpf" name="cpf"
                                               value="<?php echo htmlspecialchars($cliente['cpf']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="rg" class="form-label">RG</label>
                                    <input type="text" class="form-control" id="rg" name="rg"
                                           value="<?php echo htmlspecialchars($cliente['rg']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="endereco" class="form-label">Endereço</label>
                                    <textarea class="form-control" id="endereco" name="endereco" rows="3"><?php echo htmlspecialchars($cliente['endereco']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="anexo" class="form-label">Anexo</label>
                                    <?php if (!empty($cliente['anexo'])): ?>
                                        <div class="mb-2">
                                            <a href="<?php echo $cliente['anexo']; ?>" target="_blank" class="btn btn-info btn-sm">
                                                Ver anexo atual
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="anexo" name="anexo">
                                    <div class="form-text">
                                        Deixe em branco para manter o anexo atual. Formatos aceitos: JPG, JPEG, PNG, PDF, DOC, DOCX
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-warning">Atualizar Cliente</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})/, '$1.$2.$3-');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})/, '$1.$2.');
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})/, '$1.');
            }
            
            e.target.value = value;
        });
        
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})/, '($1) $2-');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})/, '($1) ');
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>