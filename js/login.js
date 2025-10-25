document.getElementById('loginForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;
  const res = await fetch('https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/login.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({username,password})
  });
  const data = await res.json();
  if (res.ok) {
    window.location.href = 'inventario.html';
  } else {
    document.getElementById('msg').innerText = data.error || 'Error';
  }
});
