# Ücretsiz AI API Kurulumu (Free AI API Setup)

## 🆓 Ücretsiz Alternatifler

Sistem artık 3 farklı AI servisini destekliyor. Ücretsiz kullanım için **Google Gemini** önerilir.

### 1. Google Gemini API (ÖNERİLEN - ÜCRETSİZ)

**Ücretsiz Limit:**
- Günde 60 istek
- Aylık limit: ~1,800 istek
- Tamamen ücretsiz!

**Kurulum:**
1. Google AI Studio'ya gidin: https://aistudio.google.com/
2. "Get API Key" butonuna tıklayın
3. Google hesabınızla giriş yapın
4. API key'inizi kopyalayın
5. `config.php` dosyasında:
   ```php
   define('AI_SERVICE', 'gemini');
   define('GEMINI_API_KEY', 'your_gemini_key_here');
   ```

### 2. OpenAI API (Ücretsiz Tier)

**Ücretsiz Limit:**
- $5 kredi (yeni hesaplar için)
- GPT-3.5-turbo kullanımı
- Yaklaşık 1,000-2,000 istek (dosya boyutuna göre)

**Kurulum:**
1. OpenAI'ye kaydolun: https://platform.openai.com/
2. API Keys bölümünden yeni key oluşturun
3. $5 ücretsiz kredi otomatik eklenir
4. `config.php` dosyasında:
   ```php
   define('AI_SERVICE', 'openai');
   define('OPENAI_API_KEY', 'your_openai_key_here');
   ```

### 3. Anthropic Claude (ÜCRETLİ)

**Not:** Claude API ücretsiz değil, kredi satın almanız gerekir.

**Kurulum:**
1. Anthropic'e kaydolun: https://www.anthropic.com/
2. Kredi satın alın
3. `config.php` dosyasında:
   ```php
   define('AI_SERVICE', 'claude');
   define('CLAUDE_API_KEY', 'your_claude_key_here');
   ```

## Hızlı Başlangıç (Gemini ile)

1. **API Key Alın:**
   ```
   https://aistudio.google.com/app/apikey
   ```

2. **config.php'yi Düzenleyin:**
   ```php
   define('AI_SERVICE', 'gemini');
   define('GEMINI_API_KEY', 'AIza...'); // Buraya key'inizi yapıştırın
   ```

3. **Test Edin:**
   - Bir audio dosyası yükleyin
   - Transcription çalışacak
   - Summary Gemini API ile oluşturulacak

## Karşılaştırma

| Özellik | Gemini | OpenAI | Claude |
|---------|--------|--------|--------|
| Ücretsiz Limit | 60/gün | $5 kredi | ❌ Yok |
| Model Kalitesi | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Hız | Hızlı | Hızlı | Orta |
| Türkçe Desteği | ✅ İyi | ✅ İyi | ✅ Mükemmel |

## Sorun Giderme

### "API key not configured" hatası
- `config.php` dosyasında API key'inizi kontrol edin
- Key'in doğru yapıştırıldığından emin olun

### "Quota exceeded" hatası (Gemini)
- Günlük 60 istek limitine ulaştınız
- Yarın tekrar deneyin veya başka bir servis kullanın

### "Insufficient credits" hatası (OpenAI)
- $5 ücretsiz krediniz bitti
- Gemini'ye geçin veya kredi satın alın

## Öneri

**Günlük kullanım için:** Google Gemini (60 istek/gün yeterli)
**Daha fazla kullanım için:** OpenAI ($5 kredi ile başlayın)
**En iyi kalite için:** Claude (ücretli)

