<?php
// Exemplo de lógica backend em PHP (ajuste a conexão conforme o seu arquivo de config/conexao.php se tiver)
session_start();

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pegando os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $nivel_acesso = $_POST['nivel_acesso'] ?? 'Atleta';

    if (empty($nome) || empty($email) || empty($senha)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        $tipo_mensagem = "erro";
    } else {
        // Criptografia de senha recomendada
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Exemplo com PDO (Descomente e aponte sua conexão caso necessário):
        /*
        try {
            require_once 'conexao.php'; // ou db.php
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $senha_hash, $nivel_acesso]);
            
            $_SESSION['sucesso'] = "Conta criada com sucesso! Faça login.";
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro ao salvar no banco: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
        */
        $mensagem = "Usuário cadastrado com sucesso! (Modo demonstração)";
        $tipo_mensagem = "sucesso";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - VôleiHub</title>
    <!-- Fonte Inter (Padrão Clean SaaS) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --input-bg: #f8fafc;
            --input-focus-border: #3b82f6;
            --ring-color: rgba(59, 130, 246, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            background: #eff6ff;
            color: var(--primary);
            border-radius: 16px;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .auth-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .auth-header p {
            font-size: 13.5px;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 7px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            height: 44px;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
            font-size: 14px;
            color: var(--text-main);
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px var(--ring-color);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 18px;
            cursor: pointer;
        }

        .btn-submit {
            width: 100%;
            height: 46px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
        }

        .btn-submit:active {
            transform: scale(0.99);
        }

        .auth-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13.5px;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Alertas de Notificação */
        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-erro {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }
        .alert-sucesso {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #dcfce7;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-header">
            <div class="brand-icon">
                <i class="fa-solid fa- Cosmic frontier"></i>
            </div>
            <h1>Criar Conta</h1>
            <p>Junte-se ao Marketplace do VôleiHub</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>">
                <i class="fa-solid <?= $tipo_mensagem === 'sucesso' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    class="form-control" 
                    placeholder="Digite seu nome"
                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="exemplo@voleihub.com" 
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    class="form-control" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <div class="form-group">
                <label for="nivel_acesso">Nível de Acesso</label>
                <select id="nivel_acesso" name="nivel_acesso" class="form-control">
                    <option value="Atleta" <?= (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] === 'Atleta') ? 'selected' : '' ?>>Atleta / Usuário Comum</option>
                    <option value="Clube" <?= (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] === 'Clube') ? 'selected' : '' ?>>Clube / Olheiro</option>
                    <option value="Admin" <?= (isset($_POST['nivel_acesso']) && $_POST['nivel_acesso'] === 'Admin') ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-check"></i> Salvar Usuário
            </button>
        </form>

        <div class="auth-footer">
            Já possui uma conta? <a href="login.php">Faça login</a>
        </div>
    </div>

</body>
</html>