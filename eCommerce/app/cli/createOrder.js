document.getElementById('orderForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Construïm array de productes seleccionats
    const productNames = formData.getAll('products[]');
    const quantities = formData.getAll('quantities[]');

    const products = productNames.map((p, i) => {
        const [name, priceStr] = p.split('|');
        return {
            name: name.trim(),
            unit_price: parseFloat(priceStr),
            quantity: parseInt(quantities[i] || '0', 10)
        };
    }).filter(p => p.quantity > 0); // només productes amb quantitat

    // Payload JSON
    const payload = {
        code: formData.get('code'),
        customer: formData.get('customer'),
        address: formData.get('address'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        products: products
    };

    fetch('../srv/create_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        const resultDiv = document.getElementById('serverResult');
        if (data.success) {
            resultDiv.innerHTML = `✅ ${data.message} - Total: €${data.total_with_vat}`;
            this.reset(); // opcional: reiniciar formulari
        } else {
            resultDiv.innerHTML = `❌ Error: ${data.message}`;
        }
    })
    .catch(err => {
        console.error(err);
        document.getElementById('serverResult').innerHTML = '❌ Error en enviar la comanda.';
    });
});
