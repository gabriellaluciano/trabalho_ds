<?php
session_start();

// Trava de Segurança
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$host = 'localhost';
$db   = 'moneyball_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$usuario_id   = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Atleta';

// Busca dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $usuario_id]);
$dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca dados do jogador (se existir)
$jogador = null;
try {
    $stmt_jog = $pdo->prepare("SELECT * FROM jogadores WHERE id = :id LIMIT 1");
    $stmt_jog->execute([':id' => $usuario_id]);
    $jogador = $stmt_jog->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $jogador = null;
}

// Dados mockados para demonstração
$stats = [
    'jogos' => 18,
    'pontos' => 240,
    'ataques' => 64,
    'bloqueios' => 32,
    'aces' => 14,
    'passe' => 78,
    'interesses' => 2
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Atleta | VôleiHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* NOVA IDENTIDADE: Clean SaaS (Foco em clareza e dados) */
        :root {
            --bg-body: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border-light: #E2E8F0;
            --primary: #2563EB;
            --primary-hover: #1D4ED8;
            --success: #16A34A;
            --success-bg: #DCFCE7;
            --warning: #D97706;
            --warning-bg: #FEF3C7;
            --danger: #DC2626;
            --danger-bg: #FEE2E2;
            --radius: 8px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg-body); color: var(--text-main); font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }

        /* Estrutura Principal */
        .app { display: flex; min-height: 100vh; }
        
        /* Sidebar Clean */
        .sidebar { width: 260px; background: var(--bg-card); border-right: 1px solid var(--border-light); display: flex; flex-direction: column; padding: 24px 0; }
        .logo { padding: 0 24px 32px; font-size: 20px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
        .logo i { color: var(--primary); }
        
        nav { flex: 1; padding: 0 12px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; margin-bottom: 4px; color: var(--text-muted); text-decoration: none; font-weight: 500; border-radius: var(--radius); transition: all 0.2s; font-size: 14px; }
        .nav-item:hover { background: #F1F5F9; color: var(--text-main); }
        .nav-item.active { background: #EFF6FF; color: var(--primary); font-weight: 600; }
        
        .sidebar-footer { padding: 24px; border-top: 1px solid var(--border-light); display: flex; align-items: center; justify-content: space-between; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 36px; height: 36px; background: #E2E8F0; color: var(--text-main); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        .user-info strong { display: block; font-size: 14px; }
        .user-info small { color: var(--text-muted); font-size: 12px; }
        .btn-logout { color: var(--text-muted); transition: color 0.2s; }
        .btn-logout:hover { color: var(--danger); }

        /* Main Content */
        .main { flex: 1; padding: 40px; overflow-y: auto; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
        .topbar h2 { font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        .topbar p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
        .badge-status { background: var(--success-bg); color: var(--success); padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        /* Stats Grid (Fugindo do glow, focando em bordas sólidas) */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border-light); padding: 20px; border-radius: var(--radius); display: flex; align-items: center; gap: 16px; box-shadow: var(--shadow-sm); }
        .stat-icon { width: 48px; height: 48px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-icon.blue { background: #EFF6FF; color: var(--primary); }
        .stat-icon.green { background: var(--success-bg); color: var(--success); }
        .stat-icon.teal { background: #F0FDFA; color: #0D9488; }
        .stat-icon.gray { background: #F8FAFC; color: var(--text-muted); }
        .stat-card h3 { font-size: 24px; font-weight: 700; margin-bottom: 2px; }
        .stat-card span { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        /* Tabs e Cards */
        .card { background: var(--bg-card); border: 1px solid var(--border-light); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow-sm); }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

        /* Perfil Clean */
        .profile-header { display: flex; align-items: center; gap: 24px; border-bottom: 1px solid var(--border-light); padding-bottom: 24px; margin-bottom: 24px; }
        .profile-avatar-lg { width: 80px; height: 80px; background: #F1F5F9; border: 1px solid var(--border-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 600; color: var(--text-main); }
        .profile-info h3 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .profile-info p { color: var(--text-muted); font-size: 14px; margin-bottom: 12px; }
        .tag { background: #F1F5F9; color: var(--text-main); padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid var(--border-light); }

        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .info-item { padding: 16px; border: 1px solid var(--border-light); border-radius: var(--radius); background: #FAFAF9; }
        .info-item label { display: block; font-size: 12px; color: var(--text-muted); margin-bottom: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item strong { font-size: 15px; color: var(--text-main); font-weight: 600; }

        /* Barras de Progresso sem gradiente */
        .metric-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; font-weight: 500; }
        .progress-bar { height: 6px; background: #E2E8F0; border-radius: 4px; overflow: hidden; margin-bottom: 20px;}
        .progress-fill { height: 100%; background: var(--text-main); border-radius: 4px; transition: width 0.8s ease; }

        /* Tabelas Clean */
        table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
        th { padding: 12px 16px; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-light); }
        td { padding: 16px; border-bottom: 1px solid var(--border-light); vertical-align: middle; }
        
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .status-pendente { background: var(--warning-bg); color: var(--warning); }
        .status-aceito { background: var(--success-bg); color: var(--success); }
        .status-recusado { background: var(--danger-bg); color: var(--danger); }

        /* Formulários Clean */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: var(--text-main); }
        input, select, textarea { width: 100%; border: 1px solid var(--border-light); padding: 10px 14px; border-radius: 6px; font-size: 14px; font-family: inherit; transition: border 0.2s; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        
        .btn-primary { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: var(--primary-hover); }
        
        /* Botões de Ação na Tabela */
        .btn-outline-success { background: transparent; border: 1px solid var(--border-light); color: var(--success); padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; }
        .btn-outline-success:hover { background: var(--success-bg); border-color: var(--success); }
        .btn-outline-danger { background: transparent; border: 1px solid var(--border-light); color: var(--danger); padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s; }
        .btn-outline-danger:hover { background: var(--danger-bg); border-color: var(--danger); }

        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px; }
    </style>
</head>
<body>

<div class="app">
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-Cosmic frontier"></i><span>Cosmic frontier</span></div>
        <nav>
            <a href="#perfil" class="nav-item active" data-tab="perfil"><i class="fas fa-user"></i> Visão Geral</a>
            <a href="#desempenho" class="nav-item" data-tab="desempenho"><i class="fas fa-chart-bar"></i> Estatísticas</a>
            <a href="#propostas" class="nav-item" data-tab="propostas"><i class="fas fa-inbox"></i> Propostas</a>
            <a href="#chamados" class="nav-item" data-tab="chamados"><i class="fas fa-life-ring"></i> Suporte</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($usuario_nome, 0, 2)); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>
                    <small>Atleta</small>
                </div>
            </div>
            <a href="index.php?logout=1" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h2 id="topbar-title">Visão Geral</h2>
                <p>Bem-vindo de volta, <?php echo htmlspecialchars(explode(' ', $usuario_nome)[0]); ?>.</p>
            </div>
            <span class="badge-status">Perfil Ativo</span>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon gray"><i class="fas fa-hashtag"></i></div>
                <div><h3><?php echo $stats['jogos']; ?></h3><span>Jogos Disputados</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gray"><i class="fas fa-crosshairs"></i></div>
                <div><h3><?php echo $stats['pontos']; ?></h3><span>Pontos Marcados</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gray"><i class="fas fa-shield-halved"></i></div>
                <div><h3><?php echo $stats['bloqueios']; ?></h3><span>Bloqueios</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-envelope"></i></div>
                <div><h3><?php echo $stats['interesses']; ?></h3><span>Interesses Recebidos</span></div>
            </div>
        </div>

        <!-- ABA 1: PERFIL -->
        <section id="perfil" class="card tab-content active">
            <div class="profile-header">
                <div class="profile-avatar-lg"><?php echo strtoupper(substr($usuario_nome, 0, 2)); ?></div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($dados_usuario['nome'] ?? $usuario_nome); ?></h3>
                    <p><?php echo htmlspecialchars($dados_usuario['email'] ?? 'email@exemplo.com'); ?></p>
                    <div class="profile-tags">
                        <span class="tag"><?php echo htmlspecialchars($jogador['posicao'] ?? 'Ponteiro(a)'); ?></span>
                        <span class="tag"><?php echo htmlspecialchars($jogador['status'] ?? 'Livre'); ?></span>
                    </div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item"><label>Nome Completo</label><strong><?php echo htmlspecialchars($dados_usuario['nome'] ?? $usuario_nome); ?></strong></div>
                <div class="info-item"><label>E-mail</label><strong><?php echo htmlspecialchars($dados_usuario['email'] ?? 'Não informado'); ?></strong></div>
                <div class="info-item"><label>Posição</label><strong><?php echo htmlspecialchars($jogador['posicao'] ?? 'Ponteiro(a)'); ?></strong></div>
                <div class="info-item"><label>Altura</label><strong><?php echo htmlspecialchars($jogador['altura'] ?? '1,88 m'); ?></strong></div>
                <div class="info-item"><label>Idade</label><strong><?php echo htmlspecialchars($jogador['idade'] ?? '24 anos'); ?></strong></div>
                <div class="info-item"><label>Status do Contrato</label><strong><?php echo htmlspecialchars($jogador['status'] ?? 'Livre'); ?></strong></div>
            </div>
        </section>

        <!-- ABA 2: ESTATÍSTICAS -->
        <section id="desempenho" class="card tab-content">
            <h3 style="margin-bottom: 24px; font-weight: 600;">Métricas de Desempenho</h3>
            
            <div class="metric-header"><span>Ataques Efetivos</span><strong>64%</strong></div>
            <div class="progress-bar"><div class="progress-fill" style="width: 64%"></div></div>

            <div class="metric-header"><span>Aproveitamento de Passe</span><strong>78%</strong></div>
            <div class="progress-bar"><div class="progress-fill" style="width: 78%"></div></div>

            <div class="charts-grid">
                <div class="info-item">
                    <h4 style="margin-bottom: 16px; font-size: 13px; color: var(--text-muted); text-transform: uppercase;">Evolução Tática</h4>
                    <div style="height: 220px; position: relative;"><canvas id="radarChart"></canvas></div>
                </div>
                <div class="info-item">
                    <h4 style="margin-bottom: 16px; font-size: 13px; color: var(--text-muted); text-transform: uppercase;">Pontos (Últimos Jogos)</h4>
                    <div style="height: 220px; position: relative;"><canvas id="lineChart"></canvas></div>
                </div>
            </div>
        </section>

        <!-- ABA 3: PROPOSTAS -->
        <section id="propostas" class="card tab-content">
            <h3 style="margin-bottom: 24px; font-weight: 600;">Propostas de Clubes</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Clube</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Sesi Bauru</strong><br><small style="color: var(--text-muted);">Superliga</small></td>
                            <td>Transferência</td>
                            <td>R$ 220 mil</td>
                            <td>05/08/2026</td>
                            <td><span class="status-badge status-pendente">Pendente</span></td>
                            <td style="display:flex; gap:8px;">
                                <button class="btn-outline-success">Aceitar</button>
                                <button class="btn-outline-danger">Recusar</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ABA 4: SUPORTE -->
        <section id="chamados" class="card tab-content">
            <h3 style="margin-bottom: 24px; font-weight: 600;">Abrir Chamado</h3>
            <form style="max-width: 500px;" onsubmit="event.preventDefault(); alert('Chamado aberto!');">
                <div class="form-group">
                    <label>Assunto</label>
                    <select required>
                        <option value="">Selecione o assunto</option>
                        <option value="perfil">Atualização de Perfil</option>
                        <option value="proposta">Dúvida sobre Proposta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mensagem</label>
                    <textarea rows="4" placeholder="Como podemos ajudar?" required></textarea>
                </div>
                <button type="submit" class="btn-primary">Enviar Solicitação</button>
            </form>
        </section>
    </main>
</div>

<script>
    // Navegação Clean
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const tabId = item.getAttribute('data-tab');
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            item.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });

    // ChartJS limpo sem fundo neon
    const radarCtx = document.getElementById('radarChart');
    if(radarCtx) {
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['Ataque', 'Bloqueio', 'Saque', 'Passe', 'Defesa', 'Levantamento'],
                datasets: [{
                    label: 'Score',
                    data: [78, 65, 72, 81, 68, 55],
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderColor: '#2563EB',
                    pointBackgroundColor: '#2563EB',
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { r: { ticks: { display: false } } }, plugins: { legend: { display: false } } }
        });
    }

    const lineCtx = document.getElementById('lineChart');
    if(lineCtx) {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['J1', 'J2', 'J3', 'J4', 'J5'],
                datasets: [{
                    label: 'Pontos',
                    data: [12, 18, 9, 22, 15],
                    borderColor: '#0F172A',
                    tension: 0.1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }
</script>
</body>
</html>