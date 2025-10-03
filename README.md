# Laravel AI Translation API

Laravel 10 aplikace pro překlad HTML textů pomocí Claude AI a ChatGPT. Poskytuje CLI příkazy, webové rozhraní i REST API endpointy.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-10-red)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4)
![License](https://img.shields.io/badge/license-MIT-green)

## ✨ Hlavní funkce

- 🤖 **Claude AI překlady** - Využívá model Claude Sonnet 4.5
- 💬 **ChatGPT překlady** - Podporuje GPT-4o, GPT-4-turbo, GPT-3.5-turbo
- 🖥️ **CLI rozhraní** - Artisan příkazy pro překlad ze souboru
- 🌐 **Web rozhraní** - Přátelský formulář v prohlížeči (vyžaduje přihlášení)
- 🔌 **REST API** - JSON API pro externí integraci (token autentizace)
- 🔐 **Autentizace** - Registrace, přihlášení + Social login (Google, Facebook)
- 🎟️ **API Tokeny** - Sanctum token management pro API přístup
- 🏗️ **Zachování HTML struktury** - Překládá pouze textový obsah
- 🌍 **10 jazyků** - čeština, polština, angličtina, němčina, slovenština, francouzština, španělština, italština, ruština, ukrajinština

## 📋 Požadavky

- PHP 8.1 nebo vyšší
- Composer
- Laravel 10
- MySQL/PostgreSQL/SQLite databáze
- API klíč pro Anthropic Claude
- API klíč pro OpenAI (volitelně)
- Google/Facebook OAuth credentials (pro social login)

## 🚀 Instalace

1. **Naklonuj repozitář:**
```bash
git clone https://github.com/vlastimilvasek/laravel-ai-translation-api.git
cd laravel-ai-translation-api
```

2. **Nainstaluj závislosti:**
```bash
composer install
```

3. **Zkopíruj a nastav .env soubor:**
```bash
cp .env.example .env
```

4. **Vygeneruj aplikační klíč:**
```bash
php artisan key:generate
```

5. **Nastav databázi a API klíče v .env:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...
```

6. **Nastav Social Login (volitelně):**
```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URL=http://localhost:8000/auth/google/callback

FACEBOOK_CLIENT_ID=your-facebook-app-id
FACEBOOK_CLIENT_SECRET=your-facebook-app-secret
FACEBOOK_REDIRECT_URL=http://localhost:8000/auth/facebook/callback
```

**Získání OAuth credentials:**
- **Google**: [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials
- **Facebook**: [Facebook Developers](https://developers.facebook.com/) → My Apps → Create App

7. **Spusť migrace:**
```bash
php artisan migrate
```

8. **Spusť aplikaci:**
```bash
php artisan serve
```

Aplikace poběží na `http://localhost:8000`

## 🔐 Autentizace

Projekt obsahuje kompletní autentizační systém:

### Registrace a přihlášení

1. **Klasická registrace** - `/register`
   - Jméno, email, heslo
   - Automatické přihlášení po registraci

2. **Přihlášení** - `/login`
   - Email a heslo
   - Remember me funkce

3. **Social Login**
   - Google OAuth
   - Facebook OAuth
   - Automatické vytvoření účtu při prvním přihlášení

### Dashboard (`/dashboard`)

Po přihlášení získáte přístup k dashboardu, kde můžete:
- Zobrazit informace o svém účtu
- **Vytvářet API tokeny** pro přístup k API
- Spravovat aktivní tokeny
- Vidět statistiky použití

### API Autentizace (Sanctum Tokens)

**Všechny API endpointy (`/api/v1/*`) vyžadují Bearer token autentizaci!**

1. Přihlas se do aplikace (`/login`)
2. Jdi na Dashboard (`/dashboard`)
3. Vytvoř nový API token
4. Zkopíruj token (uvidíš ho pouze jednou!)
5. Použij token v API requestech:

```bash
curl -X POST http://localhost:8000/api/v1/translate/claude \
  -H "Authorization: Bearer VÁŠ_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"text": "<p>Text k překladu</p>", "from": "cs", "to": "pl"}'
```

⚠️ **Důležité:**
- Web rozhraní (`/preklad`) vyžaduje klasické přihlášení (session)
- REST API (`/api/v1/*`) vyžaduje Bearer token v hlavičce
- Tokeny lze kdykoliv odvolat na dashboardu

## 📖 Použití

### CLI Příkazy (Artisan Commands)

#### Překlad pomocí Claude AI

```bash
# Základní použití (interaktivní)
php artisan claude:translate

# Překlad ze souboru
php artisan claude:translate --input=storage/translations/input/text.html --output=storage/translations/output/text_pl.html

# Překlad přímého textu
php artisan claude:translate --text="<p>Dobrý den</p>" --to=pl

# Překlad do jiného jazyka
php artisan claude:translate --input=text.html --from=cs --to=en --output=text_en.html
```

#### Překlad pomocí ChatGPT

```bash
# Základní použití
php artisan chatgpt:translate --text="<p>Dobrý den</p>" --to=pl

# Překlad se souborem
php artisan chatgpt:translate --input=text.html --output=text_pl.html

# Volba modelu
php artisan chatgpt:translate --text="<p>Hello</p>" --to=cs --model=gpt-3.5-turbo

# Dostupné modely: gpt-4o, gpt-4-turbo, gpt-3.5-turbo
```

#### Kontrola konfigurace

```bash
# Zkontroluj nastavení API klíčů
php artisan claude:check
```

### REST API Endpointy

#### POST `/api/v1/translate/claude`

Přeloží HTML text pomocí Claude API.

**Request:**
```json
{
  "text": "<p>Text k překladu</p>",
  "from": "cs",
  "to": "pl"
}
```

**Response:**
```json
{
  "translated": "<p>Tekst do tłumaczenia</p>"
}
```

#### POST `/api/v1/translate/chatgpt`

Přeloží HTML text pomocí ChatGPT API.

**Request:**
```json
{
  "text": "<p>Text k překladu</p>",
  "from": "cs",
  "to": "en"
}
```

**Response:**
```json
{
  "translated": "<p>Text to translate</p>"
}
```

#### POST `/api/v1/ask/claude`

Pošle obecnou zprávu do Claude AI.

**Request:**
```json
{
  "message": "Jaká je hlavní města Polska?"
}
```

**Response:**
```json
{
  "response": "Hlavním městem Polska je Varšava..."
}
```

### Příklady s CURL

**Překlad pomocí Claude:**
```bash
curl -X POST http://localhost:8000/api/v1/translate/claude \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "text": "<p>Dobrý den, jak se máte?</p>",
    "from": "cs",
    "to": "pl"
  }'
```

**Překlad pomocí ChatGPT:**
```bash
curl -X POST http://localhost:8000/api/v1/translate/chatgpt \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "text": "<p>Hello world</p>",
    "from": "en",
    "to": "cs"
  }'
```

## 🌍 Podporované jazyky

| Kód | Jazyk |
|-----|-------|
| `cs` | Čeština |
| `pl` | Polština |
| `en` | Angličtina |
| `de` | Němčina |
| `sk` | Slovenština |
| `fr` | Francouzština |
| `es` | Španělština |
| `it` | Italština |
| `ru` | Ruština |
| `uk` | Ukrajinština |

## 🏗️ Architektura projektu

```
app/
├── Console/Commands/
│   ├── ClaudeCheckCommand.php      # Kontrola konfigurace
│   ├── TranslateClaudeCommand.php  # CLI překlad přes Claude
│   └── TranslateChatGptCommand.php # CLI překlad přes ChatGPT
├── Http/Controllers/
│   └── TranslationController.php   # API endpointy
└── Services/
    ├── ClaudeApiService.php        # Claude API integrace
    └── ChatGptApiService.php       # OpenAI API integrace

routes/
├── api.php                         # REST API routes
└── web.php                         # Web routes

resources/views/
├── welcome.blade.php               # Dokumentační stránka
└── translate.blade.php             # Web formulář
```

## ⚙️ Konfigurace

### Důležité vlastnosti překladů:

- ✅ Zachovává HTML strukturu a tagy
- ✅ NEPŘEKLÁDÁ názvy alb v `<em>` tazích
- ✅ NEPŘEKLÁDÁ jména osob, značky a vlastní názvy
- ✅ Zachovává všechny HTML atributy
- ✅ Validace vstupu (max 50 000 znaků)
- ✅ Error handling a timeout 120s

### Modely:

- **Claude**: `claude-sonnet-4-5-20250929`
- **ChatGPT**: `gpt-4o` (default), `gpt-4-turbo`, `gpt-3.5-turbo`

## 🧪 Testování

Projekt obsahuje kompletní sadu automatizovaných testů:

- **51 testů** (90 assertions)
- **Feature testy** - testují API endpointy
- **Unit testy** - testují ClaudeApiService a ChatGptApiService
- **HTTP Mocking** - testy neposílají reálné requesty na AI API

### Spuštění testů:

```bash
# Spusť všechny testy
php artisan test

# Spusť pouze unit testy
php artisan test --testsuite=Unit

# Spusť pouze feature testy
php artisan test --testsuite=Feature

# Spusť konkrétní test soubor
php artisan test tests/Feature/TranslationApiTest.php

# Spusť s pokrytím kódu
php artisan test --coverage
```

### Co je testováno:

✅ Validace vstupních dat (povinná pole, max délka, formát jazykových kódů)
✅ Úspěšné překlady přes Claude i ChatGPT API
✅ Zpracování chyb API (401, 429, 500)
✅ Zachování HTML struktury při překladu
✅ Odstranění markdown code bloků z ChatGPT odpovědí
✅ Výchozí hodnoty jazyků (cs → pl)
✅ Změna modelů
✅ Custom parametry (max_tokens, temperature, top_p)
✅ Connection errors

## 🔒 Bezpečnost

⚠️ **Nikdy nezveřejňujte ani nesdílejte API klíče!**

- API klíče ukládejte pouze v `.env` souboru
- `.env` je v `.gitignore` a nebude commitován
- Pro produkci použijte environment variables

## 📝 License

MIT License - viz [LICENSE](LICENSE) soubor.

## 👨‍💻 Autor

Vlastimil Vašek

## 🤝 Contributing

Pull requesty jsou vítány! Pro větší změny prosím nejprve otevřete issue.

## 📞 Podpora

Pokud narazíte na problém, vytvořte issue na GitHubu.

---

**Verze:** 1.0.0
**Laravel:** 10
**PHP:** 8.1+
