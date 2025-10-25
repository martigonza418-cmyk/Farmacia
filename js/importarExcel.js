const xlsx = require('xlsx');
const mysql = require('mysql2/promise');
const path = require('path');

(async () => {
  try {
    const excelPath = path.join(__dirname, 'ARANCEL FARMACIA.xlsx');

    const workbook = xlsx.readFile(excelPath);
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    let data = xlsx.utils.sheet_to_json(sheet, { header: 1 });

    const headerIndex = data.findIndex(row =>
      row.some(cell => String(cell).toUpperCase().includes('NOMBRE COMERCIAL'))
    );
    if (headerIndex === -1) throw new Error('No se encontró encabezado en el Excel');

    const headers = data[headerIndex];
    const rows = data.slice(headerIndex + 1);

    const idx = {
      nombre: headers.findIndex(h => String(h).toUpperCase().includes('NOMBRE COMERCIAL')),
      presentacion: headers.findIndex(h => String(h).toUpperCase().includes('PRESENT')),
      principio: headers.findIndex(h => String(h).toUpperCase().includes('PRINCIPIO')),
      casa: headers.findIndex(h => String(h).toUpperCase().includes('CASA')),
      expira: headers.findIndex(h => String(h).toUpperCase().includes('EXPIRA')),
      costo: headers.findIndex(h => String(h).toUpperCase().includes('COSTO')),
      aprox: headers.findIndex(h => String(h).toUpperCase().includes('APROX')),
      publico: headers.findIndex(h => String(h).toUpperCase().includes('PÚBLICO') || String(h).toUpperCase().includes('PUBLICO'))
    };

    console.log('📋 Columnas detectadas:', idx);

    const connection = await mysql.createConnection({
      host: 'localhost',
      user: 'root',          
      password: '',          
      database: 'farmacia_pos', 
      multipleStatements: true
    });

    console.log('Conectado a la base de datos.');

    let insertados = 0;

    for (const r of rows) {
      const nombre_comercial = r[idx.nombre]?.toString().trim() || '';
      if (!nombre_comercial) continue; 

      const presentacion = r[idx.presentacion]?.toString().trim() || '';
      const principio_activo = r[idx.principio]?.toString().trim() || '';
      const casa = r[idx.casa]?.toString().trim() || '';
      let expira = null;
const expiraRaw = r[idx.expira];

if (expiraRaw) {
    try {
    if (typeof expiraRaw === 'number') {
      const excelEpoch = new Date(Math.round((expiraRaw - 25569) * 86400 * 1000));
        expira = excelEpoch.toISOString().slice(0, 10);
    } else if (typeof expiraRaw === 'string') {
        const cleaned = expiraRaw.replace(' 00:00:00', '').trim();
        const d = new Date(cleaned);
        if (!isNaN(d)) {
        expira = d.toISOString().slice(0, 10);
        }else {
        expira = null;
      }
    } else if (expiraRaw instanceof Date) {
      expira = expiraRaw.toISOString().slice(0, 10);
    }
  } catch (e) {
    expira = null;
  }
}

      const precio_costo = parseFloat(r[idx.costo]) || 0;
      const precio_aprox = parseFloat(r[idx.aprox]) || 0;
      const precio_publico = parseFloat(r[idx.publico]) || 0;
      const stock = 0;

      const sql = `
        INSERT INTO productos (nombre_comercial, presentacion, principio_activo, casa, expira, stock, precio_costo, precio_aprox, precio_publico)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          presentacion = VALUES(presentacion),
          principio_activo = VALUES(principio_activo),
          casa = VALUES(casa),
          expira = VALUES(expira),
          precio_costo = VALUES(precio_costo),
          precio_aprox = VALUES(precio_aprox),
          precio_publico = VALUES(precio_publico);
      `;

      await connection.execute(sql, [
        nombre_comercial,
        presentacion,
        principio_activo,
        casa,
        expira,
        stock,
        precio_costo,
        precio_aprox,
        precio_publico
      ]);

      insertados++;
    }

    console.log(`Importación completada. Total de productos procesados: ${insertados}`);

    await connection.end();
  } catch (err) {
    console.error('Error al importar Excel:', err.message);
  }
})();
