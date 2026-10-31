# Python tahmin servisini (FastAPI) bir Docker konteynerinde çalıştırır.
#
# Proje ana klasöründe (PowerShell):
#   Kurmak:      docker build -t predictive-inventory-ml ml
#   Çalıştırmak: docker run --rm -p 127.0.0.1:8001:8001 `
#                  -v "${PWD}\backend\database:/data" `
#                  -v "${PWD}\ml\models:/app/models" `
#                  predictive-inventory-ml
#
# -v satırları Laravel'in veritabanını ve eğitilmiş modeli konteynere bağlar.

FROM python:3.12-slim

WORKDIR /app

# Önce sadece paket listesini kopyala: kod değişince paketler yeniden kurulmaz (daha hızlı)
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Sonra kodu kopyala
COPY src ./src

# Laravel veritabanı konteynerde bu yolda olacak (yukarıdaki -v ile bağlanır)
ENV DB_PATH=/data/database.sqlite

EXPOSE 8001

# Konteyner içinde 0.0.0.0 gerekir; dışarıya sadece 127.0.0.1 ile açılır (yukarıdaki run komutu)
CMD ["uvicorn", "src.api:app", "--host", "0.0.0.0", "--port", "8001"]
