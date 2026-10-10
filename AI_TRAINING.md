# ShopHub AI Training

This project now has a small Python-trained AI layer for the chatbot and recommendation agent.

## Train the model

Run this from the project root:

```powershell
python ai\train_shop_ai.py
```

The script reads:

```text
data/training_data.json
```

and creates:

```text
data/shop_ai_model.json
```

## How it works

- Python trains intent keywords from example sentences.
- PHP loads the trained JSON model in `includes/shop_agents.php`.
- The chatbot detects intent like recommendation, budget, order help, payment help, or greeting.
- Product recommendations are scored using trained shopping terms plus product name, category, description, and price.

To improve the AI, add more examples or shopping terms in `data/training_data.json`, then run the trainer again.
