import os
import math
import numpy as np
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from sentence_transformers import SentenceTransformer

# ==========================
# CONFIG: PATHS
# ==========================

metadata_path = r"C:\laragon\www\web\lokalkita\data\latest(231125)\clean data\metadata\metadata_cleaned.csv"
review_score_path = r"C:\laragon\www\web\lokalkita\data\latest(231125)\clean data\review_weighted_scores.csv"

# SBERT embedding cache (from earlier)
embeddings_path = r"C:\laragon\www\web\lokalkita\data\latest(231125)\clean data\metadata\metadata_sbert_embeddings.npy"

# Hybrid output (example)
hybrid_output_path = r"C:\laragon\www\web\lokalkita\data\latest(231125)\clean data\hybrid\hybrid_scores_example.csv"
os.makedirs(os.path.dirname(hybrid_output_path), exist_ok=True)


# ==========================
# STEP 1 — LOAD DATA
# ==========================

print("🔄 Loading metadata and review scores...")

df_meta = pd.read_csv(metadata_path)
df_review = pd.read_csv(review_score_path)

print("✅ Metadata rows:", len(df_meta))
print("✅ Review score rows:", len(df_review))

if "combined_text" not in df_meta.columns:
    raise ValueError("❌ 'combined_text' column missing. Run metadata_cleaning.py first.")

# ==========================
# STEP 2 — PREPARE TF-IDF
# ==========================

print("\n🔄 Building TF-IDF matrix on combined_text...")

vectorizer = TfidfVectorizer(stop_words="english", max_features=5000)
tfidf_matrix = vectorizer.fit_transform(df_meta["combined_text"].fillna(""))

print("✅ TF-IDF matrix shape:", tfidf_matrix.shape)


# ==========================
# STEP 3 — PREPARE SBERT
# ==========================

print("\n🔄 Loading SBERT model...")

model_name = "paraphrase-multilingual-MiniLM-L12-v2"
model = SentenceTransformer(model_name)

print("✅ SBERT model loaded:", model_name)

texts = df_meta["combined_text"].fillna("").tolist()

if os.path.exists(embeddings_path):
    print("\n🔁 Loading existing SBERT embeddings from disk...")
    embeddings = np.load(embeddings_path)
    print("✅ Embeddings loaded. Shape:", embeddings.shape)
else:
    print("\n🔄 Encoding metadata with SBERT (first time, may take a bit)...")
    embeddings = model.encode(
        texts,
        batch_size=32,
        show_progress_bar=True,
        convert_to_numpy=True,
        normalize_embeddings=True
    )
    np.save(embeddings_path, embeddings)
    print("✅ Embeddings created & saved. Shape:", embeddings.shape)


# ==========================
# HELPER — NORMALIZATION
# ==========================

def normalize_array(arr):
    min_val = np.min(arr)
    max_val = np.max(arr)
    if max_val - min_val == 0:
        return np.zeros_like(arr)
    return (arr - min_val) / (max_val - min_val)


# ==========================
# STEP 4 — HYBRID SCORING FUNCTION
# ==========================

def compute_hybrid_scores(user_query: str,
                          w_tfidf: float = 0.25,
                          w_sbert: float = 0.55,
                          w_review: float = 0.20) -> pd.DataFrame:
    """
    For a given user query:
      - compute TF-IDF similarity
      - compute SBERT similarity
      - merge with review weighted scores
      - normalize all
      - compute final hybrid score
    """
    print("\n🔍 User query:", user_query)

    # ---- TF-IDF scores ----
    user_vec = vectorizer.transform([user_query])
    tfidf_cos = cosine_similarity(user_vec, tfidf_matrix).flatten()
    tfidf_norm = normalize_array(tfidf_cos)

    # ---- SBERT scores ----
    query_emb = model.encode([user_query], convert_to_numpy=True, normalize_embeddings=True)[0]
    sbert_cos = np.dot(embeddings, query_emb)
    sbert_norm = normalize_array(sbert_cos)

    # ---- Prepare base DataFrame ----
    df = df_meta.copy()
    df["tfidf_raw"] = tfidf_cos
    df["tfidf_normalized"] = tfidf_norm
    df["sbert_raw"] = sbert_cos
    df["sbert_normalized"] = sbert_norm

    # ---- Merge review weighted scores ----
    # df_review columns: item_id, avg_sentiment, review_count, weight, final_review_score
    df = df.merge(df_review[["item_id", "final_review_score"]],
                  on="item_id",
                  how="left")

    # Some items might have no reviews → NaN
    # Treat them as 0 review score
    df["final_review_score"] = df["final_review_score"].fillna(0.0)

    # Normalize review score as well
    review_scores = df["final_review_score"].to_numpy()
    review_norm = normalize_array(review_scores)
    df["review_normalized"] = review_norm

    # ---- Compute final hybrid score ----
    df["hybrid_score"] = (
        w_tfidf * df["tfidf_normalized"] +
        w_sbert * df["sbert_normalized"] +
        w_review * df["review_normalized"]
    )

    # Sort highest first
    df_sorted = df.sort_values("hybrid_score", ascending=False)

    return df_sorted


# ==========================
# STEP 5 — EXAMPLE QUERIES
# ==========================

example_query_1 = "nature waterfall camping"
example_query_2 = "batik workshop"

print("\n⚡ HYBRID scoring for query 1:", example_query_1)
result1 = compute_hybrid_scores(example_query_1)

print("\nTOP 10 HYBRID RESULTS for:", example_query_1)
print(result1[["item_id", "title", "hybrid_score",
               "tfidf_normalized", "sbert_normalized", "review_normalized"]].head(10))

print("\n⚡ HYBRID scoring for query 2:", example_query_2)
result2 = compute_hybrid_scores(example_query_2)

print("\nTOP 10 HYBRID RESULTS for:", example_query_2)
print(result2[["item_id", "title", "hybrid_score",
               "tfidf_normalized", "sbert_normalized", "review_normalized"]].head(10))

# Save last result as example file
result2[["item_id", "title", "hybrid_score",
         "tfidf_normalized", "sbert_normalized", "review_normalized"]].to_csv(
    hybrid_output_path, index=False, encoding="utf-8"
)

print("\n🎉 HYBRID scoring complete!")
print("Example hybrid scores saved to:")
print(hybrid_output_path)
