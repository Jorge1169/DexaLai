<?php include('../config/groq_key.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asistente IA - Sistema de Recolección</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    #chat-box {
      height: 70vh;
      overflow-y: auto;
      background: white;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    .user-msg { text-align: right; }
    .ai-msg { text-align: left; }
    .bubble {
      display: inline-block;
      padding: 10px 15px;
      border-radius: 20px;
      margin: 5px 0;
      max-width: 80%;
    }
    .user-msg .bubble {
      background-color: #0d6efd;
      color: white;
    }
    .ai-msg .bubble {
      background-color: #e9ecef;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <h4 class="text-center mb-3">🤖 Asistente IA - Sistema de Recolección</h4>

  <div id="chat-box" class="mb-3"></div>

  <form id="chat-form" class="input-group">
    <input type="text" id="user-input" class="form-control" placeholder="Escribe tu pregunta..." required>
    <button class="btn btn-primary" type="submit">Enviar</button>
  </form>
</div>

<script>
const chatBox = document.getElementById('chat-box');
const chatForm = document.getElementById('chat-form');
const userInput = document.getElementById('user-input');

function addMessage(content, sender) {
  const div = document.createElement('div');
  div.className = sender + '-msg';
  div.innerHTML = `<div class="bubble">${content}</div>`;
  chatBox.appendChild(div);
  chatBox.scrollTop = chatBox.scrollHeight;
}

chatForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const message = userInput.value.trim();
  if (!message) return;

  addMessage(message, 'user');
  userInput.value = '';

  addMessage('Escribiendo...', 'ai');

// En el fetch, cambia a:
const res = await fetch('mod/ia_advanced_processor.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message })
});

  const data = await res.text();
  chatBox.lastChild.remove(); // eliminar "Escribiendo..."
  addMessage(data, 'ai');
});
</script>
</body>
</html>
