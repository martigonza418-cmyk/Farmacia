let CART = [];

function getToken() {
  return localStorage.getItem('token');
}

function getAuthHeaders(extra = {}) {
  const token = getToken();
  if (!token) {
    window.location.href = 'login.html';
    return {};
  }
  return {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ' + token,
    ...extra
  };
}

function logout() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  window.location.href = 'login.html';
}

function formatCurrency(value) {
  return 'Q' + parseFloat(value).toFixed(2);
}

async function fetchProductos(q = '') {
  const url = q ? ('https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/productos.php?q=' + encodeURIComponent(q)) : 'https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/productos.php';
  const res = await fetch(url, { headers: getAuthHeaders() });
  if (!res.ok) {
    if (res.status === 401) logout();
    throw new Error('Error al obtener productos');
  }
  return await res.json();
}

function renderProductos(items) {
  const lista = document.getElementById('lista');
  lista.innerHTML = '';
  items.forEach(p => {
    const d = document.createElement('div');
    d.className = 'product';
    d.innerHTML = `
      <div>
        <strong>${p.nombre_comercial}</strong>
        <div><small>${p.presentacion || ''} - ${p.principio_activo || ''} - ${p.casa || ''}</small></div>
        <small>Stock: ${p.stock} | ${formatCurrency(p.precio_publico)}</small>
      </div>
      <div>
        <button data-id="${p.id}" class="add">Agregar</button>
        <button data-id="${p.id}" class="edit">Editar</button>
        <button data-id="${p.id}" class="del">Eliminar</button>
      </div>`;
    lista.appendChild(d);
  });
}

function renderCart() {
  const el = document.getElementById('cartItems');
  el.innerHTML = '';
  let total = 0;
  CART.forEach(it => {
    const subtotal = it.cantidad * parseFloat(it.precio_unit);
    total += subtotal;
    const div = document.createElement('div');
    div.className = 'cart-item';
    div.innerHTML = `
      <div>${it.nombre} x ${it.cantidad}</div>
      <div>${formatCurrency(subtotal)} <button class="rm" data-id="${it.producto_id}">x</button></div>`;
    el.appendChild(div);
  });
  document.getElementById('cartTotal').innerText = formatCurrency(total);
}

function addToCart(prod) {
  const exists = CART.find(x => x.producto_id == prod.id);
  if (exists) exists.cantidad += 1;
  else CART.push({ producto_id: prod.id, nombre: prod.nombre_comercial, cantidad: 1, precio_unit: prod.precio_publico });
  renderCart();
}

async function load(q = '') {
  try {
    const items = await fetchProductos(q);
    renderProductos(items);
  } catch (err) {
    console.error(err);
  }
}

document.getElementById('buscar').addEventListener('click', () => load(document.getElementById('q').value.trim()));
document.getElementById('q').addEventListener('input', (e) => load(e.target.value.trim()));

document.getElementById('nuevo').addEventListener('click', () => {
  document.getElementById('formSection').style.display = '';
  document.getElementById('prodForm').reset();
  document.getElementById('formTitle').innerText = 'Nuevo producto';
});

document.getElementById('cancel').addEventListener('click', () => document.getElementById('formSection').style.display = 'none');

document.getElementById('prodForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const obj = Object.fromEntries(fd.entries());
  const url = obj.id ? `https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/productos.php?id=${obj.id}` : 'https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/productos.php';
  const method = obj.id ? 'PUT' : 'POST';
  const res = await fetch(url, { method, headers: getAuthHeaders(), body: JSON.stringify(obj) });
  if (!res.ok) alert(obj.id ? 'Error al actualizar' : 'Error al crear');
  document.getElementById('formSection').style.display = 'none';
  load();
});

document.getElementById('lista').addEventListener('click', async (e) => {
  const id = e.target.dataset.id;
  if (!id) return;
  const items = await fetchProductos();
  const p = items.find(x => x.id == id);

  if (e.target.matches('.edit') && p) {
    const form = document.getElementById('prodForm');
    form.nombre_comercial.value = p.nombre_comercial;
    form.presentacion.value = p.presentacion;
    form.principio_activo.value = p.principio_activo;
    form.casa.value = p.casa;
    form.expira.value = p.expira ? p.expira.split(' ')[0] : '';
    form.stock.value = p.stock;
    form.precio_costo.value = p.precio_costo;
    form.precio_publico.value = p.precio_publico;
    form.id.value = p.id;
    document.getElementById('formSection').style.display = '';
    document.getElementById('formTitle').innerText = 'Editar producto';
    document.getElementById('formSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  if (e.target.matches('.del') && confirm('¿Eliminar producto?')) {
    const res = await fetch(`https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/productos.php?id=${id}`, { method: 'DELETE', headers: getAuthHeaders() });
    if (res.ok) load();
    else alert('Error al eliminar');
  }

  if (e.target.matches('.add') && p) addToCart(p);
});

document.getElementById('cartItems').addEventListener('click', (e) => {
  if (e.target.matches('.rm')) {
    const id = e.target.dataset.id;
    CART = CART.filter(x => x.producto_id != id);
    renderCart();
  }
});

document.getElementById('checkoutBtn').addEventListener('click', async () => {
  if (CART.length === 0) { alert('Carrito vacío'); return; }

  const total = CART.reduce((s, i) => s + (i.cantidad * parseFloat(i.precio_unit)), 0);
  const payload = { items: CART.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad, precio_unit: i.precio_unit })), total };
  // añadir datos de cliente y nota (visibles solo para cajero)
  payload.customer_type = document.getElementById('cartCustomerType') ? document.getElementById('cartCustomerType').value : (document.getElementById('customerType') ? document.getElementById('customerType').value : 'consumidor_final');
  payload.customer_document = document.getElementById('cartCustomerDocument') ? document.getElementById('cartCustomerDocument').value : document.getElementById('customerDocument') ? document.getElementById('customerDocument').value : '';
  payload.note = document.getElementById('cartNote') ? document.getElementById('cartNote').value : '';
  payload.branch_id = document.getElementById('branchSelect') ? document.getElementById('branchSelect').value : null;


  try {
    const res = await fetch('https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/checkout.php', {
      method:'POST',
      headers:getAuthHeaders(),
      body:JSON.stringify(payload)
    });
    if(!res.ok){
      const err = await res.json();
      alert(err.error || 'Error al cobrar');
      return;
    }

    const data = await res.json();

    CART = [];
    renderCart();
    load();

    const modal = document.getElementById('successModal');
    document.getElementById('successTotal').innerText = `Total: Q${total.toFixed(2)}`;
    modal.style.display = 'flex';
    document.getElementById('openPDF').onclick = () => window.open(`https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/invoice_pdf_fpdf.php?venta_id=${data.venta_id}`, '_blank');
    document.getElementById('closeModal').onclick = () => modal.style.display = 'none';

  } catch(error){
    console.error('Error en checkout:', error);
    alert('Error al procesar la venta.');
  }
});

document.getElementById('logoutBtn').addEventListener('click', logout);
document.getElementById('backup').addEventListener('click', () => window.location.href = 'https://revelational-madelene-propositionally.ngrok-free.dev/farmacia_pos/api/backup.php');

load();
renderCart();
