# Android TWA do AQAtende

Este guia prepara o AQAtende para virar um app Android usando Trusted Web Activity. Nesse formato, o app instalado abre o sistema web em tela cheia, mantendo login, PWA, service worker e atualizações pelo próprio site.

## 1. Requisitos

- Site publicado em HTTPS.
- `manifest.webmanifest` acessível publicamente.
- Service worker ativo.
- Android Studio ou Android SDK instalado.
- Node.js instalado.
- Chave de assinatura do app Android.

## 2. Variáveis em produção

Configure no `.env` do servidor:

```env
APP_URL=https://aqatende.com.br
APP_VERSION=0.0.3

TWA_ENABLED=true
TWA_PACKAGE_NAME=br.com.aqatende.app
TWA_SHA256_CERT_FINGERPRINT=69:38:27:28:C8:A5:82:A6:B3:46:89:17:13:2C:CB:12:D0:5F:A4:EE:8E:C7:18:18:5B:EB:E5:BA:9A:5F:9A:38
```

Depois rode no servidor:

```bash
php artisan config:clear
php artisan route:clear
```

Confira estes endereços:

```text
https://aqatende.com.br/manifest.webmanifest
https://aqatende.com.br/.well-known/assetlinks.json
```

O `assetlinks.json` deve retornar o pacote Android e a impressão SHA-256 configurada.

O SHA-256 acima pertence à chave de teste gerada localmente em `mobile/android-twa/app/android.keystore`. Para publicação na Play Store, substitua pelo SHA-256 do certificado final.

## 3. Criar o projeto Android

Instale ou execute o Bubblewrap:

```bash
npm install -g @bubblewrap/cli
```

Crie o projeto em uma pasta separada:

```bash
mkdir -p mobile/android-twa
cd mobile/android-twa
bubblewrap init --manifest=https://aqatende.com.br/manifest.webmanifest
```

Valores recomendados durante o assistente:

```text
Application name: AQAtende
Short name: AQAtende
Package ID: br.com.aqatende.app
Start URL: /dashboard
Launcher name: AQAtende
Theme color: #256d7f
Navigation color: #256d7f
```

Para gerar o app:

```bash
bubblewrap build
```

## 4. Atenção ao Play Store

Se usar Play App Signing, o SHA-256 correto para `TWA_SHA256_CERT_FINGERPRINT` é o certificado de assinatura do app exibido no Google Play Console, não necessariamente a chave de upload local.

Se o SHA-256 estiver errado, o app abre com barra do navegador ou pode apresentar comportamento de navegador comum, em vez de TWA validada.

## 5. Atualizações

Como o app carrega o AQAtende web, alterações no sistema chegam pelo deploy normal do site. Quando alterar arquivos cacheados pelo service worker, atualize `APP_VERSION` para forçar renovação de cache.
