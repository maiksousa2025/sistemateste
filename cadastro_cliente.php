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

// Função para validar CPF
function validaCPF($cpf) {
    // Remove caracteres especiais do CPF
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se o CPF tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += ($cpf[$i] * (10 - $i));
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += ($cpf[$i] * (11 - $i));
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica se os dígitos verificadores estão corretos
    return ($cpf[9] == $dv1 && $cpf[10] == $dv2);
}

// Verifica se foi enviado o formulário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = htmlspecialchars($_POST["nome"]);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $telefone = htmlspecialchars($_POST["telefone"]);
    $cpf = preg_replace('/[^0-9]/', '', $_POST["cpf"]);
    $rg = htmlspecialchars($_POST["rg"]);
    
    // Dados de endereço
    $cep = preg_replace('/[^0-9]/', '', $_POST["cep"]);
    $logradouro = htmlspecialchars($_POST["logradouro"]);
    $numero = htmlspecialchars($_POST["numero"]);
    $complemento = htmlspecialchars($_POST["complemento"]);
    $bairro = htmlspecialchars($_POST["bairro"]);
    $cidade = htmlspecialchars($_POST["cidade"]);
    $estado = htmlspecialchars($_POST["estado"]);
    
    // Monta o endereço completo
    $endereco = "$logradouro, $numero";
    if (!empty($complemento)) {
        $endereco .= ", $complemento";
    }
    $endereco .= " - $bairro - $cidade/$estado - CEP: $cep";
    
    // Validação do CPF
    if (!empty($cpf) && !validaCPF($cpf)) {
        $erro = "CPF inválido. Por favor, verifique e tente novamente.";
    } else {
        // Formatação do CPF para exibição
        if (!empty($cpf)) {
            $cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        
        // Upload do anexo
        $anexo = null;
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
                    $anexo = $destino;
                } else {
                    $erro = "Erro ao fazer upload do anexo.";
                }
            }
        }
        
        // Se não houver erro, insere no banco de dados
        if (empty($erro)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, telefone, endereco, rg, cpf, anexo, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $telefone, $endereco, $rg, $cpf, $anexo, $_SESSION["usuario_id"]]);
                $sucesso = "Cliente cadastrado com sucesso!";
            } catch (PDOException $e) {
                $erro = "Erro ao cadastrar cliente: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Cliente - Sistema MPHP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 offset-md-1">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Cadastro de Cliente</h2>
                        <div>
                            <a href="painel.php" class="btn btn-light btn-sm">Voltar ao Painel</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger"><?php echo $erro; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sucesso)): ?>
                            <div class="alert alert-success"><?php echo $sucesso; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label">Nome Completo*</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">E-mail*</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone">
                                </div>
                                <div class="col-md-6">
                                    <label for="cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf">
                                    <div id="cpfFeedback" class="invalid-feedback">
                                        CPF inválido. Por favor, verifique.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="rg" class="form-label">RG</label>
                                <input type="text" class="form-control" id="rg" name="rg">
                            </div>
                            
                            <!-- Campos de Endereço -->
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    Endereço
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="cep" class="form-label">CEP</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="cep" name="cep">
                                                <button class="btn btn-outline-secondary" type="button" id="buscarCep">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                            <div id="cepFeedback" class="invalid-feedback">
                                                CEP não encontrado.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label for="logradouro" class="form-label">Logradouro</label>
                                            <input type="text" class="form-control" id="logradouro" name="logradouro">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="numero" class="form-label">Número</label>
                                            <input type="text" class="form-control" id="numero" name="numero">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="complemento" class="form-label">Complemento</label>
                                            <input type="text" class="form-control" id="complemento" name="complemento">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bairro" class="form-label">Bairro</label>
                                            <input type="text" class="form-control" id="bairro" name="bairro">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label for="cidade" class="form-label">Cidade</label>
                                            <input type="text" class="form-control" id="cidade" name="cidade">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="estado" class="form-label">Estado</label>
                                            <select class="form-select" id="estado" name="estado">
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
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="anexo" class="form-label">Anexo (opcional)</label>
                                <input type="file" class="form-control" id="anexo" name="anexo">
                                <div class="form-text">Formatos aceitos: JPG, JPEG, PNG, PDF, DOC, DOCX</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Cadastrar Cliente</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
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
        
        // Máscara para CEP
        document.getElementById('cep').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.slice(0, 8);
            
            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d{3})/, '$1-$2');
            }
            
            e.target.value = value;
        });
        
        // Validação do CPF em tempo real
        document.getElementById('cpf').addEventListener('blur', function() {
            const cpf = this.value.replace(/\D/g, '');
            if (cpf.length === 11) {
                if (!validarCPF(cpf)) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            } else if (cpf.length > 0) {
                this.classList.add('is-invalid');
            }
        });
        
        function validarCPF(cpf) {
            if (cpf == "") return false;
            
            // Elimina CPFs invalidos conhecidos    
            if (cpf.length != 11 || 
                cpf == "00000000000" || 
                cpf == "11111111111" || 
                cpf == "22222222222" || 
                cpf == "33333333333" || 
                cpf == "44444444444" || 
                cpf == "55555555555" || 
                cpf == "66666666666" || 
                cpf == "77777777777" || 
                cpf == "88888888888" || 
                cpf == "99999999999")
                return false;
                
            // Valida 1o digito    
            let add = 0;    
            for (let i = 0; i < 9; i++)
                add += parseInt(cpf.charAt(i)) * (10 - i);
            let rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(9)))
                return false;
                
            // Valida 2o digito
            add = 0;
            for (let i = 0; i < 10; i++)
                add += parseInt(cpf.charAt(i)) * (11 - i);
            rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(10)))
                return false;
                
            return true;
        }
        
        // Busca de CEP usando API ViaCEP
        document.getElementById('buscarCep').addEventListener('click', function() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                document.getElementById('cep').classList.add('is-invalid');
                return;
            }
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) {
                        document.getElementById('cep').classList.add('is-invalid');
                    } else {
                        document.getElementById('cep').classList.remove('is-invalid');
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        // Foca no campo de número
                        document.getElementById('numero').focus();
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                    document.getElementById('cep').classList.add('is-invalid');
                });
        });
        
        // Também busca CEP quando o usuário pressiona Enter no campo
        document.getElementById('cep').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('buscarCep').click();
            }
        });
    </script>
</body>
</html>
