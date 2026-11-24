# Güvenlik ve kod denetimi

**Denetim tarihi:** 24 Kasım 2026  
**Sürüm:** 2.0.1  
**Kapsam:** Eklenti PHP kaynakları, ACP seçenekleri, şablon/phrase XML'leri, kurulum şeması ve SMS sağlayıcı adaptörlerinin statik incelemesi.

Bu çalışma kaynak kodu incelemesidir; üretim forumunda veya canlı SMS sağlayıcı hesaplarında penetrasyon testi yapılmamıştır. Bu ortamda PHP CLI bulunmadığından PHP lint de çalıştırılamadı. XML dosyaları biçimsel olarak kontrol edildi.

## Düzeltilen bulgular

| Öncelik | Bulgu | Düzeltme |
| --- | --- | --- |
| Kritik | Doğrulama kodu yokken boş değer ile gevşek eşitlik kullanılması, bazı PHP karşılaştırmalarında doğrulamayı atlatabilirdi. | Kod satırı varlığı denetleniyor; yeni kodlar `password_hash` ile saklanıp `password_verify` ile karşılaştırılıyor. |
| Yüksek | Kod `rand()` ile üretiliyordu; geçerlilik ve yanlış deneme sınırı yoktu. | `random_int()` kullanılıyor; kod en az 10 dakika ve yapılandırılmış aralık kadar geçerli, en fazla 5 deneme kabul ediliyor. |
| Yüksek | SMS aralığı yalnızca form görüntülenirken denetlendiği için doğrudan POST ile aşılabilirdi. | Saat aralığı gönderim POST isteğinde de kontrol ediliyor; telefon biçimi sunucu tarafında doğrulanıyor. |
| Yüksek | Bazı kısıtlama kontrolleri XenForo'nun asıl yazma işlemi çalıştıktan sonra yapılıyordu. | İmza, konu ve konuşma işlemlerindeki kontroller yazma işleminden önce yapılıyor. |
| Yüksek | NetGSM, VatanSMS ve HızlıSMS isteklerinde HTTPS doğrulaması kapalı veya HTTP kullanılıyordu; XML alanları da güvenli biçimde kaçışlanmıyordu. | TLS sertifika doğrulaması etkin, mevcut uç noktalar HTTPS'e geçirildi ve XML değerleri kaçışlanıyor. VatanSMS form gövdesi URL-encode ediliyor. |
| Orta | API sağlayıcısı seçilmediğinde tanımsız servis çağrısı hata oluşturabilirdi; sağlayıcı yanıtları dikkate alınmıyordu. | Sağlayıcı yoksa kullanıcıya anlaşılır hata gösteriliyor; temel HTTP ve bilinen başarılı yanıt biçimleri kontrol edilip hatalar XenForo günlüğüne yazılıyor. |
| Orta | Kurulum tablosunda alan varsayılanları ve yanlış deneme sayacı bulunmuyordu. | Yeni kurulum şeması varsayılanlarla oluşturuluyor; 2.0.1 yükseltmesi `sms_attempts` alanını ekliyor. |

## Açık kalan riskler ve sınırlamalar

1. **IP ve toplam SMS kotası yok:** Kod yalnızca hesap bazındaki bekleme aralığını uygular. Birden fazla hesap veya IP kullanılarak SMS kredisi tüketimi riski tamamen önlenemez. Sağlayıcı kotası ve WAF hız sınırı önerilir.
2. **Telefon numarası için veritabanı benzersiz indeksi yok:** Uygulama sorguyla mevcut numarayı kontrol ediyor; eşzamanlı iki istek teorik olarak aynı numarayı iki hesaba ayırabilir. Mevcut verilerde yinelenen numara olup olmadığı bilinmediğinden yükseltmeye benzersiz indeks eklenmedi.
3. **Üye silinince telefon satırı otomatik silinmiyor:** İlgili `gurbetseli_sms` satırı numarayı tekrar kullanılamaz bırakabilir. ACP/veri temizliği veya XenForo kullanıcı silme olayı için ayrı bir temizlik işleyicisi eklenmelidir.
4. **Sağlayıcıya özgü canlı yanıt doğrulaması yapılmadı:** HTTP seviyesindeki hata kontrolleri ve bilinen yanıt desenleri kodda bulunur. Özellikle HızlıSMS HTTPS uç noktasının ve hesabınızın yanıt biçiminin sağlayıcıyla doğrulanması, canlı SMS testi gerekir.
5. **MasGSM doküman adı/alan adı farkı:** Paylaşılan MasGSM PDF'si `api.senagsm.com.tr` servisini belgeliyor. MasGSM hesabının bu API kimlik bilgileriyle bu uç noktayı kullanabildiği teyit edilmelidir.
6. **Hassas veri saklama:** Telefon numaraları ve sağlayıcı kimlik bilgileri XenForo veritabanında bulunur. Veritabanı ve yedek erişimlerini kısıtlayın; numaralar için saklama/silme politikanızı belirleyin.

## Yayın öncesi önerilen canlı kontroller

- Test forumunda eklentiyi sıfırdan kurun ve 2.0.0'dan 2.0.1'e yükseltin.
- Her sağlayıcı için bir geçerli ve bir geçersiz kimlik bilgisiyle API yanıtını doğrulayın.
- Kodun doğru, yanlış, süresi dolmuş ve 5 kez yanlış girilmiş senaryolarını deneyin.
- Üye silme ve aynı numarayla yeni hesap denemesi senaryolarını kontrol edin.
- Sağlayıcı paneli, XenForo hata günlüğü ve Cloudflare hız sınırını izleyin.
