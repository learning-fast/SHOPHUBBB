<div class="chatbot-widget" data-chatbot-widget>
    <button class="chatbot-toggle" type="button" data-chatbot-toggle aria-label="Open ShopHub assistant">
        Chat
    </button>

    <section class="chatbot-panel" data-chatbot-panel aria-label="ShopHub assistant">
        <div class="chatbot-header">
            <div>
                <strong>ShopHub Assistant</strong>
                <span>Recommendations and shopping help</span>
            </div>
            <button type="button" data-chatbot-close aria-label="Close assistant">x</button>
        </div>

        <div class="chatbot-messages" data-chatbot-messages>
            <div class="chatbot-message bot">
                Hi, I can help you find products. Try "kurti under 2000", "show fashion", or "payment options".
            </div>
        </div>

        <div class="chatbot-quick-actions">
            <button type="button" data-chatbot-prompt="show clothing products">Clothing</button>
            <button type="button" data-chatbot-prompt="show skincare products">Skincare</button>
            <button type="button" data-chatbot-prompt="show accessories under 1000">Under Rs. 1000</button>
        </div>

        <form class="chatbot-form" data-chatbot-form>
            <input type="text" data-chatbot-input placeholder="Ask for a product..." autocomplete="off" required>
            <button type="submit">Send</button>
        </form>
    </section>
</div>
