async function loadUsers(){
  const res = await fetch('https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/users.php');
  if (!res.ok) {
    alert('No autorizado o error');
    return;
  }
  const users = await res.json();
  const el = document.getElementById('usersList');
  el.innerHTML = '';
  users.forEach(u=>{
    const div = document.createElement('div');
    div.className = 'product';
    div.innerHTML = `<div><strong>${u.username}</strong> <small>${u.rol}</small></div>
      <div>
        <button data-id="${u.id}" class="edit">Editar</button>
        <button data-id="${u.id}" class="del">Eliminar</button>
      </div>`;
    el.appendChild(div);
  });
}

document.getElementById('createForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const fd = new FormData(e.target);
  const obj = Object.fromEntries(fd.entries());
  const res = await fetch('https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/users.php', {
    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(obj)
  });
  const data = await res.json();
  if (res.ok) {
    e.target.reset();
    loadUsers();
    alert('Usuario creado');
  } else {
    alert(data.error || 'Error');
  }
});

document.getElementById('usersList').addEventListener('click', async (e)=>{
  if (e.target.matches('.del')) {
    if (!confirm('Eliminar usuario?')) return;
    const id = e.target.dataset.id;
    const res = await fetch('https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/users.php', {
      method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})
    });
    if (res.ok) loadUsers();
    else { const d = await res.json(); alert(d.error||'Error'); }
  } else if (e.target.matches('.edit')) {
    const id = e.target.dataset.id;
    const newRol = prompt('Nuevo rol (admin/cashier/user):');
    if (!newRol) return;
    const res = await fetch('https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/users.php', {
      method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id, rol: newRol})
    });
    if (res.ok) loadUsers();
    else { const d = await res.json(); alert(d.error||'Error'); }
  }
});

document.getElementById('backBtn').addEventListener('click', ()=> window.location.href='inventario.html');

loadUsers();
