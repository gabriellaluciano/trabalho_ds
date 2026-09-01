<?php
session_start();

// Trava: se não estiver logado, redireciona para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Conexão com o banco de dados
$host = 'localhost';
$db   = 'moneyball_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta os jogadores importados da planilha
    $stmt = $pdo->query("SELECT * FROM jogadores ORDER BY id DESC");
    $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $jogadores = [];
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Atleta | VôleiHub</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: var(--darker, #12151F); color: var(--white, #F4F6FA); font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid var(--border, #2E3648); margin-bottom: 24px; }
        .user-welcome h1 { font-size: 22px; margin-bottom: 4px; }
        .user-welcome span { color: var(--light-gray, #8B95A8); font-size: 13px; }
        .badge-access { background: rgba(43, 92, 255, 0.2); color: #2B5CFF; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        
        .nav-links { display: flex; gap: 12px; align-items: center; }
        .btn-action { background: var(--dark, #1A1F2E); border: 1px solid var(--border, #2E3648); color: #fff; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; transition: all 0.2s; }
        .btn-action:hover { border-color: var(--royal, #2B5CFF); color: var(--royal, #2B5CFF); }
        .btn-logout { border-color: rgba(239, 68, 68, 0.4); color: #EF4444; }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.1); color: #EF4444; }

        .players-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-top: 20px; }
        .player-card { background: var(--dark, #1A1F2E); border: 1px solid var(--border, #2E3648); border-radius: 12px; padding: 18px; transition: all 0.2s; }
        .player-card:hover { border-color: var(--royal, #2B5CFF); transform: translateY(-2px); }
        .player-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .player-avatar { width: 40px; height: 40px; background: var(--petroleum, #0A4D68); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #fff; }
        .player-header h3 { font-size: 15px; margin: 0; }
        .player-header small { color: var(--light-gray, #8B95A8); }

        .player-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding-top: 12px; border-top: 1px solid var(--border, #2E3648); font-size: 12px; }
        .player-stats span { color: var(--light-gray, #8B95A8); display: block; }
        .player-stats strong { color: #fff; font-size: 13px; }
        
        .empty-state { text-align: center; padding: 40px; background: var(--dark, #1A1F2E); border-radius: 12px; border: 1px dashed var(--border, #2E3648); color: var(--light-gray, #8B95A8); }
    </style>
</head> 
<body>

    <div class="dashboard-container">
        
        <!-- HEADER -->
        <div class="header-bar">
            <div class="user-welcome">
                <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Atleta'); ?>!</h1>
                <span>Nível de acesso: <span class="badge-access"><?php echo htmlspecialchars($_SESSION['usuario_tipo'] ?? 'comum'); ?></span></span>
            </div>

            <div class="nav-links">
                <?php if (($_SESSION['usuario_tipo'] ?? '') === 'admin'): ?>
                    <a href="cadastrar_usuario.php" class="btn-action"><i class="fas fa-user-plus"></i> Novo Usuário</a>
                    <a href="importar_jogadores.php" class="btn-action"><i class="fas fa-sync"></i> Sincronizar</a>
                <?php endif; ?>
                <a href="estatisticas.php" class="btn-action"><i class="fas fa-chart-bar"></i> Estatísticas</a>
                <a href="logout.php" class="btn-action btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>

        <h2><i class="fas fa-users" style="color: var(--royal, #2B5CFF);"></i> Elenco de Jogadores</h2>

        <?php if (isset($erro)): ?>
            <p style="color: #EF4444;"><?php echo $erro; ?></p>
        <?php endif; ?>

        <!-- GRID DE JOGADORES -->
        <?php if (!empty($jogadores)): ?>
            <div class="players-grid">
                <?php foreach ($jogadores as $jogador): ?>
                    <div class="player-card">
                        <div class="player-header">
                            <div class="player-avatar">
                                <?php echo strtoupper(substr($jogador['nome'], 0, 1)); ?>
                            </div>
                            <div>
                                <h3><?php echo htmlspecialchars($jogador['nome']); ?></h3>
                                <small><?php echo htmlspecialchars($jogador['posicao'] ?? 'N/A'); ?></small>
                            </div>
                        </div>

                        <div class="player-stats">
                            <div>
                                <span>Idade</span>
                                <strong><?php echo $jogador['idade'] ? $jogador['idade'] . ' anos' : 'N/A'; ?></strong>
                            </div>
                            <div>
                                <span>Altura</span>
                                <strong><?php echo $jogador['altura'] ? number_format($jogador['altura'], 2, ',', '') . ' m' : 'N/A'; ?></strong>
                            </div>
                            <div>
                                <span>Clube</span>
                                <strong><?php echo htmlspecialchars($jogador['clube'] ?? 'N/A'); ?></strong>
                            </div>
                            <div>
                                <span>Status</span>
                                <strong><?php echo htmlspecialchars($jogador['status'] ?? 'Ativo'); ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                Nenhum jogador encontrado no banco de dados.
            </div>
        <?php endif; ?>

    </div>

</body>
</html>