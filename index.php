<?php
session_start();

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

$erro_login = '';

// ========== LOGIN ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fazer_login'])) {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && (password_verify($senha, $usuario['senha']) || $senha === $usuario['senha'])) {
        $_SESSION['usuario_id']   = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'] ?? 'admin';

        // Redireciona aluno
        if (($usuario['tipo'] ?? '') === 'comum') {
            header("Location: dashboard_usuario.php");
            exit;
        }

        header("Location: index.php");
        exit;
    } else {
        $erro_login = "E-mail ou senha inválidos!";
    }
}

// ========== LOGOUT ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$esta_logado = isset($_SESSION['usuario_id']);

// ========== BUSCA JOGADORES ==========
$jogadores = [];
if ($esta_logado) {
    $stmt = $pdo->query("SELECT * FROM jogadores ORDER BY id DESC");
    $jogadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($jogadores as &$j) {
        if (!empty($j['data_nascimento'])) {
            $j['idade'] = date_diff(date_create($j['data_nascimento']), date_create('today'))->y;
        } else {
            $j['idade'] = $j['idade'] ?? '--';
        }
        $j['clube']  = $j['clube'] ?? 'Livre';
        $j['status'] = $j['status'] ?? ($j['status_cadastro'] ?? 'Ativo');
        $j['altura'] = $j['altura'] ?? '--';
    }
    unset($j);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cosmic frontier | Marketplace</title>
  <!-- Script Inline Anti-Flicker (Executa antes do CSS carregar para evitar tela branca ao recarregar) -->
  <script>
    if (localStorage.getItem('theme') === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
  </script>
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- ==================== TELA DE LOGIN ==================== -->
<div id="login-screen" class="login-screen <?= $esta_logado ? 'hidden' : '' ?>">
  <div class="login-card">
    <div class="login-logo">
      <i class="fas fa-Cosmic frontier"></i>
      <h1>VôleiHub</h1>
      <p>Marketplace de Transferências</p>
    </div>

    <form method="POST" action="index.php">
      <input type="hidden" name="fazer_login" value="1">

      <?php if ($erro_login): ?>
        <div class="login-error">
          <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro_login) ?>
        </div>
      <?php endif; ?>

      <div class="form-group">
        <label>E-mail</label>
        <input type="email" name="email" placeholder="seu@clube.com" required>
      </div>

      <div class="form-group">
        <label>Senha</label>
        <input type="password" name="senha" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn-primary btn-full">
        <i class="fas fa-sign-in-alt"></i> Entrar na Plataforma
      </button>
    </form>

    <p class="login-footer">
      Ainda não tem conta? <a href="cadastrar_usuario.php">Cadastre-se</a>
    </p>
  </div>
</div>

<!-- ==================== APP PRINCIPAL ==================== -->
<div id="app" class="app <?= $esta_logado ? '' : 'hidden' ?>">

  <aside class="sidebar">
    <div class="logo">
      <i class="fas fa-Cosmic frontier"></i>
      <span>VôleiHub</span>
    </div>
    <nav>
      <a href="#" class="nav-item active" data-view="marketplace"><i class="fas fa-store"></i> <span>Marketplace</span></a>
      <a href="#" class="nav-item" data-view="jogadores"><i class="fas fa-users"></i> <span>Meus Jogadores</span></a>
      <a href="#" class="nav-item" data-view="pitch"><i class="fas fa-paper-plane"></i> <span>Pitch</span></a>
      <a href="#" class="nav-item" data-view="mensagens"><i class="fas fa-comments"></i> <span>Mensagens</span></a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['usuario_nome'] ?? 'DR', 0, 2)); ?></div>
            <div>
                <strong><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Diretor') ?></strong>
                <small>Diretor Esportivo</small>
            </div>
        </div>
        <a href="?logout=1" class="btn-logout" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar jogadores por nome ou posição...">
      </div>
      <div class="topbar-actions">
        <!-- BOTÃO PARA ALTERNAR TEMA (DARK/LIGHT) -->
        <button id="themeToggle" class="btn-theme-toggle" type="button" title="Alternar Tema">
          <i class="fas fa-moon"></i>
        </button>

        <a href="importar_jogadores.php" class="btn-outline">
          <i class="fas fa-file-excel"></i> Importar
        </a>
        <button class="btn-primary" onclick="document.querySelector('[data-view=pitch]').click()">
          <i class="fas fa-plus"></i> Novo Pitch
        </button>
      </div>
    </header>

    <!-- MARKETPLACE -->
    <section id="view-marketplace" class="view-section active">
      <h1>Marketplace</h1>
      <p class="subtitle">Jogadores disponíveis para negociação</p>
      
      <div class="players-grid">
        <?php if (empty($jogadores)): ?>
          <div class="card" style="grid-column:1/-1;text-align:center;padding:60px 40px;color:var(--text-muted);">
            <i class="fas fa-user-slash" style="font-size: 32px; margin-bottom: 16px; opacity: 0.5;"></i><br>
            Nenhum jogador encontrado.<br>Use o botão <strong>Importar</strong> no menu superior.
          </div>
        <?php else: ?>
          <?php foreach ($jogadores as $j): ?>
            <div class="player-card">
              <div class="player-header">
                <div class="player-avatar lg"><?= strtoupper(substr($j['nome'] ?? 'J', 0, 2)) ?></div>
                <div>
                  <h3><?= htmlspecialchars($j['nome'] ?? '') ?></h3>
                  <span><?= htmlspecialchars($j['posicao'] ?? '—') ?> • <?= htmlspecialchars($j['idade']) ?> anos</span>
                </div>
              </div>
              <div class="player-info">
                <div><span>Altura</span><strong><?= htmlspecialchars($j['altura']) ?> m</strong></div>
                <div><span>Status</span><strong><?= htmlspecialchars($j['status']) ?></strong></div>
              </div>
              <div class="player-actions">
                <button class="btn-outline" onclick="alert('Interesse declarado no jogador!')">Interesse</button>
                <button class="btn-primary" onclick="document.querySelector('[data-view=pitch]').click()">Fazer Pitch</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- MEUS JOGADORES -->
    <section id="view-jogadores" class="view-section">
      <h1>Meus Jogadores</h1>
      <p class="subtitle">Gerencie o elenco sincronizado com a planilha</p>
      <div class="card" style="overflow:auto; padding: 0;">
        <table>
          <thead style="background: var(--bg-body);">
            <tr>
              <th>Nome do Atleta</th>
              <th>Posição</th>
              <th>Idade</th>
              <th>Altura</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jogadores as $j): ?>
              <tr>
                <td style="font-weight:600; color: var(--text-main);"><?= htmlspecialchars($j['nome'] ?? '') ?></td>
                <td><?= htmlspecialchars($j['posicao'] ?? '—') ?></td>
                <td><?= htmlspecialchars($j['idade']) ?> anos</td>
                <td><?= htmlspecialchars($j['altura']) ?> m</td>
                <td>
                  <span class="badge-status available">
                    <?= htmlspecialchars($j['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- PITCH -->
    <section id="view-pitch" class="view-section">
      <h1>Enviar Pitch</h1>
      <p class="subtitle">Ofereça um jogador do seu elenco a outro clube</p>
      <div class="card form-card">
        <form onsubmit="event.preventDefault(); alert('Pitch enviado com sucesso!');">
          <div class="form-row">
            <div class="form-group">
              <label>Selecione o Jogador</label>
              <select required>
                <option value="">Escolha um atleta...</option>
                <?php foreach ($jogadores as $j): ?>
                  <option><?= htmlspecialchars($j['nome'] ?? '') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Clube Destino</label>
              <select required>
                <option value="">Selecione o clube...</option>
                <option>Praia Clube</option>
                <option>Sesi Bauru</option>
                <option>Osasco</option>
                <option>Minas Tênis Clube</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Mensagem da Proposta</label>
            <textarea rows="5" placeholder="Descreva os termos da proposta, valores ou condições de empréstimo..." required></textarea>
          </div>
          <div style="text-align: right; margin-top: 16px;">
              <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Enviar Pitch Oficial</button>
          </div>
        </form>
      </div>
    </section>

    <!-- MENSAGENS -->
    <section id="view-mensagens" class="view-section">
      <h1>Caixa de Mensagens</h1>
      <p class="subtitle">Acompanhe as negociações em tempo real</p>
      
      <div class="chat-container">
        <div class="chat-list">
          <?php foreach (array_slice($jogadores, 0, 6) as $idx => $j): ?>
            <div class="chat-item <?= $idx === 0 ? 'active' : '' ?>" onclick="selecionarChat('<?= addslashes(htmlspecialchars($j['nome'] ?? '')) ?>', this)">
              <div class="player-avatar">
                <?= strtoupper(substr($j['nome'] ?? 'J', 0, 2)) ?>
              </div>
              <div>
                <strong style="color: var(--text-main);"><?= htmlspecialchars($j['nome'] ?? '') ?></strong>
                <small style="color: var(--text-muted); display: block; font-weight: 500;">Atleta Livre</small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div class="chat-main">
          <div class="chat-header">
            <strong id="chat-nome" style="font-size: 15px;"><?= !empty($jogadores) ? htmlspecialchars($jogadores[0]['nome']) : 'Selecione uma conversa' ?></strong>
          </div>
          <div class="chat-messages" id="chat-body">
            <div class="msg-bubble received">Olá diretor! Recebi a proposta, podemos conversar?</div>
          </div>
          <div class="chat-input-area">
            <input type="text" id="chat-input" placeholder="Escreva sua mensagem..." onkeypress="if(event.key==='Enter')enviarMsg()">
            <button onclick="enviarMsg()" class="btn-primary" style="padding: 0 16px;">
              <i class="fas fa-paper-plane" style="margin:0;"></i>
            </button>
          </div>
        </div>
      </div>
    </section>

  </main>
</div>

<script>
// Sistema de Abas (Navegação SPA Falsa)
document.querySelectorAll('.nav-item[data-view]').forEach(item => {
  item.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
    
    item.classList.add('active');
    document.getElementById('view-' + item.dataset.view).classList.add('active');
  });
});

// Sistema de Chat Falso
function selecionarChat(nome, el) {
  document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('chat-nome').innerText = nome;
  document.getElementById('chat-body').innerHTML = `<div class="msg-bubble received">Bom dia! Meu agente pediu para alinhar os detalhes com você, ${nome} falando.</div>`;
}

function enviarMsg() {
  const input = document.getElementById('chat-input');
  const txt = input.value.trim();
  if (!txt) return;
  
  const body = document.getElementById('chat-body');
  body.innerHTML += `<div class="msg-bubble sent">${txt}</div>`;
  input.value = '';
  body.scrollTop = body.scrollHeight;
  
  setTimeout(() => {
    const respostas = [
        "Perfeito, vou analisar o contrato.", 
        "Consegue melhorar a questão do bônus?", 
        "Excelente! Amanhã assino os papéis."
    ];
    body.innerHTML += `<div class="msg-bubble received">${respostas[Math.floor(Math.random()*3)]}</div>`;
    body.scrollTop = body.scrollHeight;
  }, 1000);
}

// LÓGICA DO BOTÃO ALTERNAR TEMA ESCURO
const themeToggleBtn = document.getElementById('themeToggle');
if (themeToggleBtn) {
  const themeIcon = themeToggleBtn.querySelector('i');

  // Ajusta o ícone se o tema escuro já estiver ativo no carregamento
  if (document.documentElement.getAttribute('data-theme') === 'dark') {
    themeIcon.classList.replace('fa-moon', 'fa-sun');
  }

  themeToggleBtn.addEventListener('click', () => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    
    if (isDark) {
      document.documentElement.removeAttribute('data-theme');
      localStorage.setItem('theme', 'light');
      themeIcon.classList.replace('fa-sun', 'fa-moon');
    } else {
      document.documentElement.setAttribute('data-theme', 'dark');
      localStorage.setItem('theme', 'dark');
      themeIcon.classList.replace('fa-moon', 'fa-sun');
    }
  });
}
</script>
</body>
</html>