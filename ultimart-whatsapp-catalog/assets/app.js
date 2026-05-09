(function () {
    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatMoney(amount, symbol) {
        var numeric = Number(amount || 0);
        return numeric.toLocaleString("bn-BD") + " " + symbol;
    }

    document.querySelectorAll("[data-product-detail]").forEach(function (section) {
        var payload = JSON.parse(section.getAttribute("data-product-detail") || "{}");
        var product = payload.product || null;

        if (!product) {
            return;
        }

        var form = section.querySelector("[data-order-form]");
        var formMessage = section.querySelector("[data-form-message]");
        var submitButton = section.querySelector(".ultimart-order-form__submit");
        var qtyInput = section.querySelector("[data-quantity]");
        var whatsappLink = section.querySelector("[data-whatsapp-link]");
        var totalField = section.querySelector('[data-summary="total"]');
        var hiddenQuantity = form.querySelector('[data-input="quantity"]');
        var quantity = 1;

        function syncWhatsappLink(orderData) {
            if (!payload.whatsapp) {
                whatsappLink.style.display = "none";
                return;
            }

            whatsappLink.style.display = "inline-flex";

            if (orderData && orderData.whatsapp_url) {
                whatsappLink.href = orderData.whatsapp_url;
                return;
            }

            var lines = [
                "আসসালামু আলাইকুম, আমি এই পণ্যটি অর্ডার করতে চাই।",
                "",
                "পণ্যের নাম: " + product.name,
                "পরিমাণ: " + quantity,
                "মোট দাম: " + formatMoney((Number(product.price) || 0) * quantity, payload.currencySymbol || "৳")
            ];

            whatsappLink.href = "https://wa.me/" + payload.whatsapp + "?text=" + encodeURIComponent(lines.join("\n"));
        }

        function updateQuantity(nextQuantity) {
            quantity = Math.max(1, nextQuantity);
            qtyInput.value = String(quantity);
            hiddenQuantity.value = String(quantity);
            totalField.textContent = formatMoney((Number(product.price) || 0) * quantity, payload.currencySymbol || "৳");
            syncWhatsappLink();
        }

        section.querySelectorAll(".ultimart-qty__btn").forEach(function (button) {
            button.addEventListener("click", function () {
                if (button.dataset.action === "increase") {
                    updateQuantity(quantity + 1);
                    return;
                }

                updateQuantity(quantity - 1);
            });
        });

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            formMessage.textContent = "";
            formMessage.className = "ultimart-order-form__message";
            submitButton.disabled = true;
            submitButton.textContent = "অর্ডার হচ্ছে...";

            var formData = new FormData(form);
            formData.set("whatsapp", payload.whatsapp || "");

            fetch(payload.ajaxUrl, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response.success) {
                        throw new Error(response.data && response.data.message ? response.data.message : "অর্ডার সম্পন্ন করা যায়নি।");
                    }

                    formMessage.className = "ultimart-order-form__message is-success";
                    formMessage.innerHTML = "অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে। অর্ডার আইডি: <strong>#"
                        + escapeHtml(response.data.order_id)
                        + "</strong>";

                    form.reset();
                    updateQuantity(1);
                    syncWhatsappLink(response.data);
                })
                .catch(function (error) {
                    formMessage.className = "ultimart-order-form__message is-error";
                    formMessage.textContent = error.message;
                })
                .finally(function () {
                    submitButton.disabled = false;
                    submitButton.textContent = "অর্ডার করুন";
                });
        });

        updateQuantity(1);
    });
})();
