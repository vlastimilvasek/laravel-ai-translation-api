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
- 🌐 **Web rozhraní** - Přátelský formulář v prohlížeči
- 🔌 **REST API** - JSON API pro externí integraci
- 🏗️ **Zachování HTML struktury** - Překládá pouze textový obsah
- 🌍 **10 jazyků** - čeština, polština, angličtina, němčina, slovenština, francouzština, španělština, italština, ruština, ukrajinština

## 📋 Požadavky

- PHP 8.1 nebo vyšší
- Composer
- Laravel 10
- API klíč pro Anthropic Claude
- API klíč pro OpenAI (volitelně)

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

5. **Nastav API klíče v .env:**
```env
ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...
```

6. **Spusť aplikaci:**
```bash
php artisan serve
```

Aplikace poběží na `http://localhost:8000`

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
