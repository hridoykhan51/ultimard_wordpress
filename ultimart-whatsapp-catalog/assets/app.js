(function () {
    function buildList(items) {
        return (items || [])
            .map(function (item) {
                return "<li>" + item + "</li>";
            })
            .join("");
    }

    function buildParagraphs(items) {
        return (items || [])
            .map(function (item) {
                return "<p>" + item + "</p>";
            })
            .join("");
    }

    function buildWhatsAppUrl(phone, product, quantity, siteName, pageTitle) {
        var lines = [
            "Assalamu Alaikum, আমি এই পণ্যটি অর্ডার করতে চাই:",
            "",
            "পণ্যের নাম: " + product.name,
            "ক্যাটাগরি: " + product.tag,
            "পরিমাণ: " + quantity,
            "",
            "পেজ: " + pageTitle,
            "ওয়েবসাইট: " + siteName
        ];

        return "https://wa.me/" + phone + "?text=" + encodeURIComponent(lines.join("\n"));
    }

    document.querySelectorAll(".ultimart-catalog").forEach(function (catalog) {
        var payload = JSON.parse(catalog.dataset.catalog || "{}");
        var products = payload.products || [];
        var currentProduct = products[0] || null;
        var quantity = 1;

        var cards = catalog.querySelectorAll(".ultimart-card");
        var qtyInput = catalog.querySelector("[data-quantity]");
        var orderLink = catalog.querySelector("[data-order-link]");

        var fields = {
            tag: catalog.querySelector('[data-field="tag"]'),
            name: catalog.querySelector('[data-field="name"]'),
            excerpt: catalog.querySelector('[data-field="excerpt"]'),
            description: catalog.querySelector('[data-field="description"]'),
            features: catalog.querySelector('[data-field="features"]'),
            why_buy: catalog.querySelector('[data-field="why_buy"]')
        };

        function syncWhatsAppLink() {
            if (!currentProduct || !payload.phone) {
                orderLink.removeAttribute("href");
                orderLink.textContent = "Shortcode-এ WhatsApp নম্বর দিন";
                return;
            }

            orderLink.href = buildWhatsAppUrl(
                payload.phone,
                currentProduct,
                quantity,
                payload.siteName || "",
                payload.pageTitle || ""
            );
            orderLink.textContent = "WhatsApp-এ অর্ডার পাঠান";
        }

        function renderProduct(productId) {
            currentProduct = products.find(function (product) {
                return product.id === productId;
            }) || products[0] || null;

            if (!currentProduct) {
                return;
            }

            fields.tag.textContent = currentProduct.tag || "";
            fields.name.textContent = currentProduct.name || "";
            fields.excerpt.textContent = currentProduct.excerpt || "";
            fields.description.innerHTML = buildParagraphs(currentProduct.description);
            fields.features.innerHTML = buildList(currentProduct.features);
            fields.why_buy.innerHTML = buildList(currentProduct.why_buy);

            cards.forEach(function (card) {
                card.classList.toggle("is-active", card.dataset.productId === currentProduct.id);
            });

            quantity = 1;
            qtyInput.value = String(quantity);
            syncWhatsAppLink();
        }

        cards.forEach(function (card) {
            card.addEventListener("click", function () {
                renderProduct(card.dataset.productId);
            });
        });

        catalog.querySelectorAll(".ultimart-qty__btn").forEach(function (button) {
            button.addEventListener("click", function () {
                if (button.dataset.action === "increase") {
                    quantity += 1;
                } else if (button.dataset.action === "decrease") {
                    quantity = Math.max(1, quantity - 1);
                }

                qtyInput.value = String(quantity);
                syncWhatsAppLink();
            });
        });

        renderProduct((products[0] || {}).id);
    });
})();
