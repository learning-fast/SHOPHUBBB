import json
import math
import re
from collections import Counter, defaultdict
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
TRAINING_FILE = ROOT / "data" / "training_data.json"
MODEL_FILE = ROOT / "data" / "shop_ai_model.json"

STOP_WORDS = {
    "a", "an", "and", "are", "can", "for", "how", "i", "is", "me", "my",
    "of", "on", "or", "show", "the", "to", "what", "where", "with", "you"
}


def tokenize(text):
    words = re.findall(r"[a-z0-9]+", text.lower())
    return [word for word in words if len(word) > 1 and word not in STOP_WORDS]


def train_intents(intents):
    document_count = 0
    intent_documents = Counter()
    token_counts = defaultdict(Counter)
    vocabulary = set()

    for intent in intents:
        name = intent["name"]
        for example in intent["examples"]:
            tokens = tokenize(example)
            if not tokens:
                continue
            document_count += 1
            intent_documents[name] += 1
            token_counts[name].update(tokens)
            vocabulary.update(tokens)

    vocab_size = max(1, len(vocabulary))
    model = {}

    for intent_name, counts in token_counts.items():
        total_tokens = sum(counts.values())
        keywords = {}
        for token in vocabulary:
            probability = (counts[token] + 1) / (total_tokens + vocab_size)
            keywords[token] = round(math.log(probability), 6)

        prior = intent_documents[intent_name] / max(1, document_count)
        model[intent_name] = {
            "prior": round(math.log(prior), 6),
            "keywords": keywords,
            "top_terms": [word for word, _ in counts.most_common(10)]
        }

    return model


def train_shopping_terms(shopping_terms):
    weighted_terms = {}

    for group, terms in shopping_terms.items():
        weighted_terms[group] = {}
        for index, term in enumerate(terms):
            weighted_terms[group][term] = round(1.0 - (index * 0.04), 2)

    return weighted_terms


def main():
    with TRAINING_FILE.open("r", encoding="utf-8") as handle:
        training_data = json.load(handle)

    model = {
        "version": 1,
        "trained_by": "ai/train_shop_ai.py",
        "intents": train_intents(training_data["intents"]),
        "shopping_terms": train_shopping_terms(training_data["shopping_terms"]),
        "reply_templates": training_data["reply_templates"]
    }

    MODEL_FILE.parent.mkdir(parents=True, exist_ok=True)
    with MODEL_FILE.open("w", encoding="utf-8") as handle:
        json.dump(model, handle, indent=2)

    print(f"Trained ShopHub AI model: {MODEL_FILE}")
    print(f"Intents: {', '.join(model['intents'].keys())}")


if __name__ == "__main__":
    main()
