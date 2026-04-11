# Groq Integration for Layer 1

## 1) Tujuan Integrasi

Integrasi Groq di Layer 1 dipakai untuk dua hal saja:

1. STT (voice ke text) pada endpoint transkripsi.
2. Parsing fallback saat rule-based parser gagal.

Core decision tetap deterministic:

- validation engine
- decision handler
- order creation
- queue
- event emit

## 2) Endpoint yang Tersedia

### A. Text Order

POST /api/orders/voice

Body JSON:

{
  "raw_text": "saya mau nasgor sama teh anget"
}

### B. Audio Transcription

POST /api/orders/voice/transcribe

Form-data:

- audio: file audio
- auto_order: true|false (opsional)

Jika auto_order=true, hasil transkripsi langsung diproses ke order flow yang sama seperti endpoint text.

## 3) Environment Variable

Tambahkan di .env:

GROQ_API_KEY=your_real_key
GROQ_BASE_URL=https://api.groq.com/openai/v1
GROQ_MODEL=llama-3.1-8b-instant
GROQ_STT_MODEL=whisper-large-v3-turbo

## 4) Kenapa Ini Aman untuk Core Logic

- Output AI tidak dipercaya mentah.
- Semua hasil fallback tetap masuk validation deterministic.
- Jika Groq gagal/time out, flow tetap berjalan (hanya fallback kosong).
- Layer 2 tetap async melalui event + queue listener.

## 5) Integrasi dengan Python Client

Contoh sederhana mengirim raw_text hasil STT Python ke Laravel:

```python
import requests

API_URL = "http://localhost:8000/api/orders/voice"

text = "saya mau nasgor sama teh anget"
resp = requests.post(API_URL, json={"raw_text": text}, timeout=5)
print(resp.status_code)
print(resp.json())
```

Contoh kirim audio langsung ke endpoint transkripsi:

```python
import requests

API_URL = "http://localhost:8000/api/orders/voice/transcribe"

with open("sample.wav", "rb") as f:
    resp = requests.post(
        API_URL,
        files={"audio": ("sample.wav", f, "audio/wav")},
        data={"auto_order": "true"},
        timeout=15,
    )

print(resp.status_code)
print(resp.json())
```

## 6) Catatan Produksi

- Set timeout API Groq tetap ketat.
- Tambahkan retry terbatas untuk transkripsi jika jaringan tidak stabil.
- Simpan observability (log response code, durasi, fallback hit rate).
- Gunakan queue worker aktif agar listener async berjalan:

php artisan queue:work
