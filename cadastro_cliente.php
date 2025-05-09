<?php
session_start();
include("conexao.php");

// Verifica se o usuário está logado
if(!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

// Função para sanitizar input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Configurações para upload de documentos
$diretorioUpload = "uploads/documentos/";
if (!file_exists($diretorioUpload)) {
    mkdir($diretorioUpload, 0777, true);
}

$mensagem = "";
$tipo_mensagem = "";

// Processamento do formulário quando enviado
if(isset($_POST['salvar'])) {
    $nome = sanitize($_POST['nome']);
    $cpf = sanitize($_POST['cpf']);
    $cnpj = sanitize($_POST['cnpj']);
    $email = sanitize($_POST['email']);
    $rg = sanitize($_POST['rg']);
    $data_nascimento = sanitize($_POST['data_nascimento']);
    $cep = sanitize($_POST['cep']);
    $rua = sanitize($_POST['rua']);
    $numero = sanitize($_POST['numero']);
    $bairro = sanitize($_POST['bairro']);
    $cidade = sanitize($_POST['cidade']);
    $estado = sanitize($_POST['estado']);
    $estado_civil = sanitize($_POST['estado_civil']);
    
    // Validação dos campos obrigatórios
    if(empty($nome) || empty($email) || empty($cep) || empty($cidade) || empty($estado)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios!";
        $tipo_mensagem = "erro";
    } else {
        // Processamento do upload de documento (se houver)
        $documento_path = "";
        if(isset($_FILES['documento']) && $_FILES['documento']['error'] == 0) {
            $arquivo_tmp = $_FILES['documento']['tmp_name'];
            $nomeArquivo = $_FILES['documento']['name'];
            $novoNome = uniqid() . '-' . $nomeArquivo;
            
            // Move o arquivo para o diretório de uploads
            if(move_uploaded_file($arquivo_tmp, $diretorioUpload.$novoNome)) {
                $documento_path = $diretorioUpload.$novoNome;
            } else {
                $mensagem = "Erro ao fazer upload do documento!";
                $tipo_mensagem = "erro";
            }
        }
        
        // Se não houve erro no upload, continua com a inserção no banco
        if($tipo_mensagem != "erro") {
            // Preparar a query com prepared statements
            $stmt = $mysqli->prepare("INSERT INTO clientes (nome, cpf, cnpj, email, rg, data_nascimento, cep, rua, numero, bairro, cidade, estado, estado_civil, documento_path, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssssssssssi", $nome, $cpf, $cnpj, $email, $rg, $data_nascimento, $cep, $rua, $numero, $bairro, $cidade, $estado, $estado_civil, $documento_path, $_SESSION['id_usuario']);
            
            if($stmt->execute()) {
                $mensagem = "Cliente cadastrado com sucesso!";
                $tipo_mensagem = "sucesso";
                
                // Limpar os campos após cadastro bem-sucedido
                $nome = $cpf = $cnpj = $email = $rg = $data_nascimento = $cep = $rua = $numero = $bairro = $cidade = $estado = $estado_civil = "";
            } else {
                $mensagem = "Erro ao cadastrar cliente: " . $stmt->error;
                $tipo_mensagem = "erro";
            }
            
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .card-header {
            background-color: #fff;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .card-title {
            margin-bottom: 0;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #ced4da;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .file-upload-zone {
            border: 2px dashed #ced4da;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-zone:hover {
            border-color: #4e73df;
        }
        .icon-container {
            width: 20px;
            display: inline-block;
            text-align: center;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem == 'sucesso' ? 'success' : 'danger'; ?> alert-dismissible fade show mt-3" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-plus"></i> Cadastro de Cliente
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome" class="form-label">
                                <i class="fas fa-user icon-container"></i> Nome Completo
                            </label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estado_civil" class="form-label">
                                <i class="fas fa-heart icon-container"></i> Estado Civil
                            </label>
                            <select class="form-control form-select" id="estado_civil" name="estado_civil">
                                <option value="">Selecione</option>
                                <option value="Solteiro(a)">Solteiro(a)</option>
                                <option value="Casado(a)">Casado(a)</option>
                                <option value="Divorciado(a)">Divorciado(a)</option>
                                <option value="Viúvo(a)">Viúvo(a)</option>
                                <option value="União Estável">União Estável</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="cpf" class="form-label">
                                <i class="fas fa-id-card icon-container"></i> CPF
                            </label>
                            <input type="text" class="form-control" id="cpf" name="cpf">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cnpj" class="form-label">
                                <i class="fas fa-building icon-container"></i> CNPJ
                            </label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope icon-container"></i> Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="rg" class="form-label">
                                <i class="fas fa-id-badge icon-container"></i> RG
                            </label>
                            <input type="text" class="form-control" id="rg" name="rg">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="data_nascimento" class="form-label">
                                <i class="fas fa-calendar-alt icon-container"></i> Data de Nascimento
                            </label>
                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" placeholder="dd/mm/aaaa">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="cep" class="form-label">
                                <i class="fas fa-map-marker-alt icon-container"></i> CEP
                            </label>
                            <input type="text" class="form-control" id="cep" name="cep" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="rua" class="form-label">
                                <i class="fas fa-road icon-container"></i> Rua
                            </label>
                            <input type="text" class="form-control" id="rua" name="rua" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="numero" class="form-label">
                                <i class="fas fa-hashtag icon-container"></i> Número
                            </label>
                            <input type="text" class="form-control" id="numero" name="numero" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="bairro" class="form-label">
                                <i class="fas fa-map icon-container"></i> Bairro
                            </label>
                            <input type="text" class="form-control" id="bairro" name="bairro" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cidade" class="form-label">
                                <i class="fas fa-city icon-container"></i> Cidade
                            </label>
                            <input type="text" class="form-control" id="cidade" name="cidade" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label">
                                <i class="fas fa-map-signs icon-container"></i> Estado
                            </label>
                            <select class="form-control form-select" id="estado" name="estado" required>
                                <option value="">Selecione</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="documento" class="form-label">
                                <i class="fas fa-file-alt icon-container"></i> Documentos
                            </label>
                            <div class="file-upload-zone" id="dropzone">
                                <input type="file" name="documento" id="documento" class="d-none">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-muted"></i>
                                <p class="mb-0">Clique para anexar documentos</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='painel.php'">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" name="salvar" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Cliente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function() {
            // Máscaras para os campos
            $('#cpf').mask('000.000.000-00');
            $('#cnpj').mask('00.000.000/0000-00');
            $('#cep').mask('00000-000');
            
            // Função para buscar CEP
            $('#cep').blur(function() {
                const cep = $(this).val().replace(/\D/g, '');
                
                if (cep.length === 8) {
                    $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                        if (!data.erro) {
                            $('#rua').val(data.logradouro);
                            $('#bairro').val(data.bairro);
                            $('#cidade').val(data.localidade);
                            $('#estado').val(data.uf);
                            $('#numero').focus();
                        }
                    });
                }
            });
            
            // Upload de documentos
            $('#dropzone').click(function() {
                $('#documento').click();
            });
            
            $('#documento').change(function() {
                const fileName = $(this).val().split('\\').pop();
                if (fileName) {
                    $('#dropzone p').text(fileName);
                } else {
                    $('#dropzone p').text('Clique para anexar documentos');
                }
            });
        });
    </script>
</body>
</html>
