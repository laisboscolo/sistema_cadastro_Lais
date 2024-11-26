<!-- 3ª Digitação (Aqui) -->
 <?php
include('valida_sessao.php');
include('conexao.php');

// Função para redimensionar e salvar imagem
function redimensionarESalvarImagem($arquivo, $largura = 80, $altura = 80) {
    $diretorio_destino = "img/";
    $nome_arquivo = uniqid() . '_' . basename($arquivo["name"]);
    $caminho_completo = $diretorio_destino . $nome_arquivo;
    $tipo_arquivo = strtolower(pathinfo($caminho_completo, PATHINFO_EXTENSION));

    // Verifica se é uma imagem válida
    $check = getimagesize($arquivo["tmp_name"]);
    if ($check === false) {
        return "O arquivo não é uma imagem válida.";
    }

    // Verifica o tamanho do arquivo
    if ($arquivo["size"] > 5000000) {
        return "O arquivo é muito grande. O tamanho máximo permitido é 5MB.";
    }

    // Permite alguns formatos de arquivos
    if ($tipo_arquivo != "jpg" && $tipo_arquivo != "jpeg" && $tipo_arquivo != "png" && $tipo_arquivo != "gif") {
        return "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
    }

    // Cria uma nova imagem a partir do arquivo enviado
    if ($tipo_arquivo == "jpg" || $tipo_arquivo == "jpeg") {
        $imagem_original = imagecreatefromjpeg($arquivo["tmp_name"]);
    } elseif ($tipo_arquivo == "png") {
        $imagem_original = imagecreatefrompng($arquivo["tmp_name"]);
    } elseif ($tipo_arquivo == "gif") {
        $imagem_original = imagecreatefromgif($arquivo["tmp_name"]);
    }

    // Obtém as dimensões originais da imagem
    $largura_original = imagesx($imagem_original);
    $altura_original = imagesy($imagem_original);

    // Calcula as novas dimensões mantendo a proporção
    $ratio = min($largura / $largura_original, $altura / $altura_original);
    $nova_largura = $largura_original * $ratio;
    $nova_altura = $altura_original * $ratio;

    // Cria uma nova imagem com as dimensões calculadas
    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

    // Redimensiona a imagem original para a nova imagem
    imagecopyresampled($nova_imagem, $imagem_original, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_original, $altura_original);

    // Salva a nova imagem
    if ($tipo_arquivo == "jpg" || $tipo_arquivo == "jpeg") {
        imagejpeg($nova_imagem, $caminho_completo, 90);
    } elseif ($tipo_arquivo == "png") {
        imagepng($nova_imagem, $caminho_completo);
    } elseif ($tipo_arquivo == "gif") {
        imagegif($nova_imagem, $caminho_completo);
    }

    // Libera a memória
    imagedestroy($imagem_original);
    imagedestroy($nova_imagem);

    return $caminho_completo;
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null; // Protege o ID contra SQL Injection
    $nome = htmlspecialchars($_POST['nome'], ENT_QUOTES, 'UTF-8'); // Protege contra XSS
    $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8'); // Protege contra XSS
    $telefone = htmlspecialchars($_POST['telefone'], ENT_QUOTES, 'UTF-8'); // Protege contra XSS

    // Processa o upload da imagem
    $imagem = "";
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $resultado_upload = redimensionarESalvarImagem($_FILES['imagem']);
        if (strpos($resultado_upload, 'img/') === 0) {
            $imagem = $resultado_upload;
        } else {
            $mensagem_erro = $resultado_upload;
        }
    }

    // Prepara a query SQL para inserção ou atualização
    if ($id) {
        // Se o ID existe, é uma atualização
        $sql = "UPDATE fornecedores SET nome = ?, email = ?, telefone = ?";
        if ($imagem) {
            $sql .= ", imagem = ?";
        }
        $stmt = $conn->prepare($sql);
        if ($imagem) {
            $stmt->bind_param("ssss", $nome, $email, $telefone, $imagem);
        } else {
            $stmt->bind_param("sss", $nome, $email, $telefone);
        }
    } else {
        // Se não há ID, é uma nova inserção
        $sql = "INSERT INTO fornecedores (nome, email, telefone, imagem) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nome, $email, $telefone, $imagem);
    }

    // Executa a query e verifica se houve erro
    if ($stmt->execute()) {
        $mensagem = "Fornecedor cadastrado/atualizado com sucesso!";
    } else {
        $mensagem = "Erro: " . $stmt->error;
    }
    $stmt->close();
}

// Verifica se foi solicitada a exclusão de um fornecedor
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id']; // Protege o ID contra SQL Injection

    // Verifica se o fornecedor tem produtos cadastrados
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM produtos WHERE fornecedor_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $check_produtos = $result->fetch_assoc();
    $stmt->close();

    if ($check_produtos['count'] > 0) {
        $mensagem = "Não é possível excluir este fornecedor pois existem produtos cadastrados para ele.";
    } else {
        $sql = "DELETE FROM fornecedores WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $mensagem = "Fornecedor excluído com sucesso!";
        } else {
            $mensagem = "Erro ao excluir fornecedor: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Busca todos os fornecedores para listar na tabela
$fornecedores = $conn->query("SELECT * FROM fornecedores");

// Se foi solicitada a edição de um fornecedor, busca os dados dele
$fornecedor = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id']; // Protege o ID contra SQL Injection
    $stmt = $conn->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fornecedor = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Cadastro de Fornecedor</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container" style="width: 900px;">
<h2>Cadastro de Fornecedor</h2>
<!-- Formulário para cadastro/edição de fornecedor -->
<form method="post" action="" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php echo $fornecedor['id'] ?? ''; ?>">
<label for="nome">Nome: </label>
<input type="text" name="nome" value="<?php echo $fornecedor['nome'] ?? ''; ?>" required>
<label for="email">Email:</label>
<input type="email" name="email" value="<?php echo $fornecedor['email'] ?? ''; ?>">
<label for="telefone">Telefone: </label>
<input type="text" name="telefone" value="<?php echo $fornecedor['telefone'] ?? ''; ?>">
<label for="imagem">Imagem:</label>
<input type="file" name="imagem" accept="image/*">
<?php if (isset($fornecedor['imagem']) && $fornecedor['imagem']): ?>
<img src="<?php echo $fornecedor['imagem']; ?>" alt="Imagem atual do fornecedor" class="update-image">
<?php endif; ?>
<br>
<button type="submit"><?php echo $fornecedor ? 'Atualizar' : 'Cadastrar'; ?></button>
</form>
<!-- Exibe mensagens de sucesso ou erro -->
<?php
if (isset($mensagem)) echo "<p class='message " . (strpos($mensagem, 'Erro') !== false ? "error" : "success") . "'>$mensagem</p>";
if (isset($mensagem_erro)) echo "<p class='message error'>$mensagem_erro</p>";
?>

<!-- Tabela com fornecedores -->
<h3>Fornecedores Cadastrados</h3>
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Telefone</th>
            <th>Imagem</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($fornecedor = $fornecedores->fetch_assoc()): ?>
            <tr>
                <td><?php echo $fornecedor['id']; ?></td>
                <td><?php echo $fornecedor['nome']; ?></td>
                <td><?php echo $fornecedor['email']; ?></td>
                <td><?php echo $fornecedor['telefone']; ?></td>
                <td><img src="<?php echo $fornecedor['imagem']; ?>" alt="Imagem do fornecedor" width="80"></td>
                <td>
                    <a href="?edit_id=<?php echo $fornecedor['id']; ?>">Editar</a> |
                    <a href="?delete_id=<?php echo $fornecedor['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
</body>
</html>