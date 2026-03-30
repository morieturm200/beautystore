document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        fetch('./add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + encodeURIComponent(productId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Товар додано до кошика!');
            } else {
                alert('Помилка при додаванні товару до кошика: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Помилка при додаванні товару до кошика: мережева помилка.');
        });
    });
});


document.querySelectorAll('.remove-from-cart').forEach(button => {
    button.addEventListener('click', function() {
        const cartId = this.getAttribute('data-id');
        fetch('../backend/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'cart_id=' + encodeURIComponent(cartId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Товар видалено з кошика!');
                location.reload();
            } else {
                alert('Помилка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Виникла помилка.');
        });
    });
});



// Add to Cart
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        fetch('../backend/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + encodeURIComponent(productId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Товар додано до кошика!');
            } else {
                alert('Помилка при додаванні товару до кошика: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Помилка при додаванні товару до кошика: мережева помилка.');
        });
    });
});

// Add to Wishlist
document.querySelectorAll('.add-to-wishlist').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        fetch('../backend/add_to_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + encodeURIComponent(productId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Товар додано до списку бажань!');
            } else {
                alert('Помилка: ' . data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Помилка при додаванні до списку бажань.');
        });
    });
});

// Remove from Wishlist
document.querySelectorAll('.remove-from-wishlist').forEach(button => {
    button.addEventListener('click', function() {
        const wishlistId = this.getAttribute('data-id');
        fetch('../backend/remove_from_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'wishlist_id=' + encodeURIComponent(wishlistId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Товар видалено зі списку бажань!');
                location.reload();
            } else {
                alert('Помилка: ' . data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Помилка при видаленні зі списку бажань.');
        });
    });
});

// Add to Cart from Wishlist
document.querySelectorAll('.add-to-cart-from-wishlist').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        fetch('../backend/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + encodeURIComponent(productId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Товар додано до кошика!');
            } else {
                alert('Помилка: ' . data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Помилка при додаванні до кошика.');
        });
    });
});