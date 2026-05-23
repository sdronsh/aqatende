# AQAtende Android

Projeto reservado para gerar o app Android do AQAtende via Trusted Web Activity.

Domínio usado:

```text
https://aqatende.com.br
```

Comando de criação do projeto Android:

```bash
npx @bubblewrap/cli init --manifest=https://aqatende.com.br/manifest.webmanifest
```

Valores recomendados:

```text
Application name: AQAtende
Short name: AQAtende
Package ID: br.com.aqatende.app
Start URL: /dashboard
Theme color: #256d7f
Navigation color: #256d7f
```

Depois da criação:

```bash
npx @bubblewrap/cli build
```

Arquivos gerados para teste:

```text
mobile/android-twa/app/app-release-signed.apk
mobile/android-twa/app/app-release-bundle.aab
```

SHA-256 da chave de teste local:

```text
69:38:27:28:C8:A5:82:A6:B3:46:89:17:13:2C:CB:12:D0:5F:A4:EE:8E:C7:18:18:5B:EB:E5:BA:9A:5F:9A:38
```

Observação: o JDK 17 e o Android SDK foram instalados localmente pelo Bubblewrap em `~/.bubblewrap`.
