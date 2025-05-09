<?php
session_start();
require 'conexao.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuario = ["nome" => "Usuário"];
$erro = "";
$clientes = [];
$termo_busca = "";

try {
    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION["usuario_id"]]);
    $usuario_db = $stmt->fetch();

    if ($usuario_db && !empty($usuario_db["nome"])) {
        $usuario["nome"] = $usuario_db["nome"];
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar informações do usuário: " . $e->getMessage();
}

if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $termo_busca = $_GET['busca'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario_id = ? AND 
                              (nome LIKE ? OR email LIKE ? OR cpf LIKE ? OR telefone LIKE ?) 
                              ORDER BY id DESC");
        $termo_like = "%" . $termo_busca . "%";
        $stmt->execute([$_SESSION["usuario_id"], $termo_like, $termo_like, $termo_like, $termo_like]);
        $clientes = $stmt->fetchAll();
    } catch (PDOException $e) {
        $erro = "Erro ao buscar clientes: " . $e->getMessage();
    }
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario_id = ? ORDER BY id DESC");
        $stmt->execute([$_SESSION["usuario_id"]]);
        $clientes = $stmt->fetchAll();
    } catch (PDOException $e) {
        $erro = "Erro ao buscar clientes: " . $e->getMessage();
    }
}

if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    try {
        $stmt = $pdo->prepare("SELECT anexo FROM clientes WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$_GET['excluir'], $_SESSION["usuario_id"]]);
        $cliente = $stmt->fetch();

        if ($cliente && !empty($cliente['anexo']) && file_exists($cliente['anexo'])) {
            unlink($cliente['anexo']);
        }

        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$_GET['excluir'], $_SESSION["usuario_id"]]);

        $redirect_url = "painel.php?msg=excluido";
        if (!empty($termo_busca)) {
            $redirect_url .= "&busca=" . urlencode($termo_busca);
        }

        header("Location: " . $redirect_url);
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao excluir cliente: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle - Sistema MPHP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="painel.php">Sistema MPHP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="painel.php">Painel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cadastro_cliente.php">Novo Cliente</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">
                    Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!
                </span>
                <a href="perfil.php" class="btn btn-light btn-sm me-2">Meu Perfil</a>
                <a href="logout.php" class="btn btn-light btn-sm">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Cliente excluído com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Clientes Cadastrados</h4>
                <a href="cadastro_cliente.php" class="btn btn-light btn-sm">
                    <i class="fas fa-plus"></i> Novo Cliente
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="painel.php" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="busca" placeholder="Buscar por nome, email, CPF ou telefone" value="<?php echo htmlspecialchars($termo_busca); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <?php if (!empty($termo_busca)): ?>
                            <a href="painel.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (empty($clientes)): ?>
                    <div class="alert alert-info">
                        <?php if (!empty($termo_busca)): ?>
                            Nenhum cliente encontrado para a busca: "<?php echo htmlspecialchars($termo_busca); ?>".
                            <a href="painel.php">Voltar para lista completa</a>.
                        <?php else: ?>
                            Nenhum cliente cadastrado ainda. <a href="cadastro_cliente.php">Cadastre seu primeiro cliente</a>.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Telefone</th>
                                    <th>CPF</th>
                                    <th>Anexo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo $cliente['id']; ?></td>
                                    <td><?php echo htmlspecialchars($cliente['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['cpf']); ?></td>
                                    <td>
                                        <?php if (!empty($cliente['anexo'])): ?>
                                            <a href="<?php echo $cliente['anexo']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                <i class="fas fa-file"></i> Ver anexo
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Sem anexo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalCliente<?php echo $cliente['id']; ?>">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <a href="editar_cliente.php?id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $cliente['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                        <div class="modal fade" id="modalCliente<?php echo $cliente['id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $cliente['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-scrollable">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title" id="modalLabel<?php echo $cliente['id']; ?>">Detalhes do Cliente</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($cliente['nome']); ?></p>
                                                        <p><strong>E-mail:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                                                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                                                        <p><strong>CPF:</strong> <?php echo htmlspecialchars($cliente['cpf']); ?></p>
                                                        <p><strong>RG:</strong> <?php echo htmlspecialchars($cliente['rg']); ?></p>
                                                        <p><strong>Endereço:</strong> <?php echo htmlspecialchars($cliente['endereco']); ?></p>
                                                        <p><strong>Anexo:</strong>
                                                            <?php if (!empty($cliente['anexo'])): ?>
                                                                <a href="<?php echo $cliente['anexo']; ?>" target="_blank" class="btn btn-outline-primary btn-sm">Ver Anexo</a>
                                                            <?php else: ?>
                                                                <span class="text-muted">Sem anexo</span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmarExclusao(id) {
            if (confirm("Tem certeza que deseja excluir este cliente?")) {
                let url = "painel.php?excluir=" + id;
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('busca')) {
                    url += "&busca=" + encodeURIComponent(urlParams.get('busca'));
                }
                window.location.href = url;
            }
        }
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>










