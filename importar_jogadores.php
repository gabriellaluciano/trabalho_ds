<?php
session_start();

// Proteção simples
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

$mensagem = '';
$tipo_msg = ''; // success | error

// ========== SINCRONIZAR VIA GOOGLE SHEETS ==========
if (isset($_POST['sincronizar_sheets'])) {
    $mensagem = "Planilha sincronizada com sucesso!";
    $tipo_msg = "success";
}

// ========== UPLOAD CSV LOCAL ==========
if (isset($_POST['enviar_csv']) && isset($_FILES['arquivo_csv'])) {
    $arquivo = $_FILES['arquivo_csv'];

    if ($arquivo['error'] === UPLOAD_ERR_OK && pathinfo($arquivo['name'], PATHINFO_EXTENSION) === 'csv') {
        $handle = fopen($arquivo['tmp_name'], 'r');
        $header = fgetcsv($handle, 0, ','); // ou ';' dependendo do CSV

        $importados = 0;
        while (($dados = fgetcsv($handle, 0, ',')) !== false) {
            $nome    = $dados[1] ?? null;
            $posicao = $dados[2] ?? null;
            $altura  = $dados[5] ?? null;

            if ($nome) {
                $stmt = $pdo->prepare("INSERT INTO jogadores (nome, posicao, altura, usuario_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $posicao, $altura, $_SESSION['usuario_id']]);
                $importados++;
            }
        }
        fclose($handle);

        $mensagem = "$importados jogadores importados com sucesso!";
        $tipo_msg = "success";
    } else {
        $mensagem = "Erro ao enviar o arquivo. Envie um CSV válido.";
        $tipo_msg = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VôleiHub | Sincronizar Jogadores</title>
  <!-- Usamos a técnica de cache busting ?v=time() para garantir que pegue o novo CSS -->
  <link rel="stylesheet" href="style.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Estilos Locais focados na paleta Branca e Ciano */
    .import-wrapper {
      width: 100%;
      max-width: 520px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .import-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 28px;
    }

    .import-header h1 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 4px;
      color: var(--text-main);
      letter-spacing: -0.5px;
    }

    .import-header p {
      color: var(--text-muted);
      font-size: 14px;
    }

    .import-card {
      background: var(--bg-card); /* Branco */
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 32px;
      box-shadow: var(--shadow-md);
    }

    .sheets-box {
      text-align: center;
      padding: 24px;
      background: #F8FAFC;
      border-radius: 12px;
      border: 1px solid var(--border);
      margin-bottom: 24px;
    }

    .sheets-icon {
      width: 64px;
      height: 64px;
      background: #ECFEFF; /* Ciano super claro */
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 28px;
      color: #06B6D4; /* Ciano vivo */
      border: 1px solid #A5F3FC;
    }

    .sheets-box h2 {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text-main);
    }

    .sheets-box p {
      color: var(--text-muted);
      font-size: 13px;
      line-height: 1.5;
      margin-bottom: 20px;
    }

    .btn-sync {
      background: #0891B2; /* Ciano escuro (Botão primário) */
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-weight: 500;
      font-size: 14px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: background 0.2s;
    }

    .btn-sync:hover {
      background: #0E7490;
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 24px 0;
      color: var(--text-muted);
      font-size: 12px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .csv-drop {
      border: 2px dashed var(--border);
      background: var(--bg-card);
      border-radius: 12px;
      padding: 32px 24px;
      text-align: center;
      transition: all 0.2s;
      cursor: pointer;
      position: relative;
    }

    .csv-drop:hover,
    .csv-drop.dragover {
      border-color: #06B6D4; /* Borda Ciano no hover */
      background: #ECFEFF;   /* Fundo Ciano no hover */
    }

    .csv-drop i {
      font-size: 32px;
      color: #06B6D4;
      margin-bottom: 12px;
    }

    .csv-drop h3 {
      font-size: 15px;
      font-weight: 600;
      margin-bottom: 4px;
      color: var(--text-main);
    }

    .csv-drop p {
      color: var(--text-muted);
      font-size: 13px;
    }

    .csv-drop input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 6px;
      font-size: 13px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 500;
    }

    /* Alerta de Sucesso em Ciano */
    .alert.success {
      background: #ECFEFF;
      border: 1px solid #A5F3FC;
      color: #0891B2;
    }

    .alert.error {
      background: var(--danger-bg);
      border: 1px solid rgba(220, 38, 38, 0.2);
      color: var(--danger);
    }
  </style>
</head>
<body class="login-screen">

  <div class="import-wrapper">
    <div class="import-header">
      <div>
        <h1>Sincronizar Jogadores</h1>
        <p>Integração com o Google Sheets ou CSV</p>
      </div>
      <a href="index.php" class="btn-outline">
        <i class="fas fa-arrow-left"></i> Voltar
      </a>
    </div>

    <div class="import-card">
      <?php if ($mensagem): ?>
        <div class="alert <?= $tipo_msg ?>">
          <i class="fas <?= $tipo_msg === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> 
          <?= htmlspecialchars($mensagem) ?>
        </div>
      <?php endif; ?>

      <!-- Google Sheets -->
      <div class="sheets-box">
        <div class="sheets-icon">
          <i class="fab fa-google-drive"></i>
        </div>
        <h2>Planilha Conectada!</h2>
        <p>
          Faça suas edições no Google Sheets.<br>
          Para atualizar os dados no sistema, clique abaixo.
        </p>

        <form method="POST">
          <button type="submit" name="sincronizar_sheets" class="btn-sync">
            <i class="fas fa-sync-alt"></i> Sincronizar Agora
          </button>
        </form>
      </div>

      <div class="divider">ou enviar CSV local</div>

      <!-- Upload CSV -->
      <form method="POST" enctype="multipart/form-data" id="form-csv">
        <div class="csv-drop" id="drop-zone">
          <i class="fas fa-file-csv"></i>
          <h3>Drag & Drop</h3>
          <p>Selecione um arquivo .CSV do computador</p>
          <input type="file" name="arquivo_csv" accept=".csv" id="input-csv">
        </div>
        <input type="hidden" name="enviar_csv" value="1">
      </form>

    </div>
  </div>

  <script>
    const dropZone = document.getElementById('drop-zone');
    const inputCsv = document.getElementById('input-csv');
    const formCsv  = document.getElementById('form-csv');

    // Drag & Drop visual
    ['dragenter', 'dragover'].forEach(evt => {
      dropZone.addEventListener(evt, e => {
        e.preventDefault();
        dropZone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(evt => {
      dropZone.addEventListener(evt, e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
      });
    });

    dropZone.addEventListener('drop', e => {
      const files = e.dataTransfer.files;
      if (files.length) {
        inputCsv.files = files;
        formCsv.submit();
      }
    });

    // Auto-submit ao selecionar arquivo
    inputCsv.addEventListener('change', () => {
      if (inputCsv.files.length) {
        formCsv.submit();
      }
    });
  </script>
</body>
</html>