# Gurbetseli SMS Doğrulama

XenForo üyelerinin Türkiye cep telefonu numaralarını SMS koduyla doğrulamasını ve doğrulamaya göre bazı forum işlemlerini sınırlandırmasını sağlayan eklenti.

## Sistem gereksinimleri

- XenForo **2.2.0 veya üzeri**
- PHP **7.2 veya üzeri** (sunucuda yüklü XenForo sürümünün istediği sürüm daha yüksekse XenForo gereksinimi geçerlidir)
- PHP **cURL** eklentisi ve güncel CA sertifikaları
- XenForo tarafından desteklenen MySQL/MariaDB veritabanı
- Kullanılacak SMS sağlayıcısına dışarıya doğru HTTPS bağlantısı (genellikle TCP 443)
- Etkin API erişimi, onaylı gönderici başlığı ve yeterli SMS bakiyesi

XenForo'nun güncel altyapı gereksinimleri için [resmî sistem gereksinimleri](https://xenforo.com/purchase/self-hosted?e=install) sayfasına bakın.

## Kurulum

### İlk kurulum

1. Önce forum dosyalarını ve veritabanını yedekleyin. Mümkünse kurulumu önce test forumunda deneyin.
2. GitHub'dan projeyi indirin ve ZIP dosyasını açın.
3. Paketteki `Gurbetseli/SMS` klasörünü, forumunuzun kök dizinindeki şu konuma yükleyin:

   ```text
   forum-kok-dizini/src/addons/Gurbetseli/SMS/
   ```

   Sonuçta `addon.json` dosyası `src/addons/Gurbetseli/SMS/addon.json` yolunda bulunmalı.

4. XenForo Yönetim Paneli'nde **Eklentiler** sayfasını açın. Listelenen **Gurbetseli SMS Doğrulama** eklentisinin yanındaki **Yükle** seçeneğini kullanın.
5. Kurulum tamamlanınca **Kurulum › Seçenekler › Gurbetseli SMS doğrulama** sayfasını açın.
6. **SMS sağlayıcısı ayarları** sekmesinde tek bir sağlayıcıyı seçip o sağlayıcının bilgilerini girin. API bilgileri sağlayıcının panelinden alınır.
7. **Genel ayarlar** bölümünde doğrulanmış kullanıcı grubunu seçin. Bu grup, telefonu doğrulayan üyeye ikincil grup olarak eklenir.
8. İzin kısıtlamalarını önce kapalı bırakıp test hesabıyla doğrulamayı deneyin. Her şey doğru çalışınca gerekli kısıtlamaları açın.

### Güncelleme

1. Veritabanı ve eklenti dosyalarını yedekleyin.
2. Yeni sürümdeki `Gurbetseli/SMS` klasörünün içeriğini aynı sunucu konumuna yükleyip eski dosyaların üzerine yazın.
3. Yönetim Paneli'ndeki **Eklentiler** sayfasından Gurbetseli SMS Doğrulama için **Yükselt** işlemini başlatın ve tamamlayın. 2.0.1 sürümü, yanlış kod denemelerini saklamak için eklenti tablosuna bir alan ekler.
4. SMS sağlayıcısı bilgilerini ve izin seçeneklerini tekrar kontrol edin.

Bir yükseltme sırasında daha önce istenmiş, henüz doğrulanmamış SMS kodları eski biçimdeyse geçerli kalabilir; kodun süresi dolduysa yeni kod isteyin.

## Kullanım ve işleyiş

- Üye giriş yaptıktan sonra hesap menüsündeki **SMS ile telefon doğrulama** bağlantısından doğrulamayı başlatır.
- Numara, Türkiye cep telefonu biçiminde **5 ile başlayan 10 rakam** olmalıdır. Boşluk ve ayraçlar temizlenir; `+90` veya başında `0` bulunan ülke kodlu biçimler bu ekrandaki 10 rakam kuralına uymaz.
- Eklenti 8 rakamlı kod üretir, veritabanında kodun kendisi yerine PHP parola hash'i saklar ve gönderimi seçili sağlayıcıya iletir.
- Kodun geçerlilik süresi, ayarlardaki SMS aralığına bağlıdır: en az 10 dakika, aksi durumda ayarlanan saat kadar. Varsayılan değer 24 saattir. Yanlış kod için en fazla 5 deneme yapılabilir.
- SMS aralığı hem form gösterilirken hem de gönderim POST isteğinde denetlenir; en kısa aralık 10 dakikadır. Varsayılan **24 saat** olduğundan bu değeri ihtiyacınıza göre ayarlayın.
- Başarılı doğrulama, seçilen kullanıcı grubunu üyeye ikincil grup olarak ekler.
- Telefonu doğrulamamış üyeler için yeni konu açma, konuya yanıt verme, kişisel konuşma ve imza kısıtlamaları ayrı seçeneklerle yönetilir. Yeni konu kısıtlaması açıksa yanıt kısıtlaması da devreye girer. Forum seçimi boş bırakılırsa tüm forumlar kapsanır.
- Kısıtlı işlem hem sayfa arayüzünde hem de sunucu tarafındaki işlem sırasında kontrol edilir.

## SMS sağlayıcıları

| Sağlayıcı | ACP alanları | Entegrasyon adresi |
| --- | --- | --- |
| VatanSMS | Müşteri numarası, API kullanıcı adı, API şifresi, gönderici adı, mesaj öneki | `https://panel.vatansms.com/panel/smsgonder1Npost.php` |
| NetGSM | API kullanıcı adı, API şifresi, gönderici adı, mesaj öneki | `https://api.netgsm.com.tr/xmlbulkhttppost.asp` |
| HızlıSMS | Müşteri adı, parola, gönderici adı, mesaj öneki | `https://hizlisms.com.tr/api-v3/xml_api.php` |
| Mutlucell | Müşteri adı, parola, gönderici adı, mesaj öneki | `https://smsgw.mutlucell.com/smsgw-ws/sndblkex` |
| MasGSM | API kullanıcı adı, API secret, gönderici başlığı, mesaj öneki | `https://api.senagsm.com.tr/api/smspost/v1` |

API adresleri ve yanıt biçimleri sağlayıcı tarafından değiştirilebilir. Özellikle HızlıSMS HTTPS adresinin hesabınız ve API ürününüz için desteklendiğini sağlayıcınızla doğrulayın. MasGSM için verilen PDF, SENA GSM adını ve `api.senagsm.com.tr` adresini kullanıyor; MasGSM hesabınızın bu API ile uyumlu olduğunu sağlayıcınızdan teyit edin. [MasGSM API PDF'si](https://masgsm.com.tr/files/api-dokumantasyonu.pdf)

Sağlayıcı hesabında API erişimini açın; API kullanıcı adı/şifresi ile gönderici başlığının onaylı olduğuna ve hesabınızda bakiye bulunduğuna emin olun. NetGSM ve VatanSMS API uç noktaları için sağlayıcının güncel resmî dokümanlarını kullanın: [NetGSM API](https://api-docs.netgsm.com.tr/), [VatanSMS API](https://www.vatansms.com/toplu-sms/sms-api/), [Mutlucell XML API](https://mutlucell.com.tr/toplusmsapi/hm_xml_kodunu_post_etme.htm).

## Ayarlar ve kodda önemli adlar

- ACP seçenekleri `_data/options.xml` içinde tanımlıdır. `gurbetseli_sms_smsinterval` SMS gönderimleri arasındaki saat cinsinden bekleme aralığıdır.
- Onaylanan telefon ve doğrulama kaydı `gurbetseli_sms` tablosunda tutulur: `user_id`, `phone_number`, `sms_date`, `sms_code`, `verify_date`, `sms_attempts`.
- `sms_code` alanında yeni sürümde hash saklanır; `sms_attempts` yanlış denemeleri sayar.
- Doğrulama sonrası kullanıcı grubu değişikliği `gurbetselismsverified` anahtarıyla kaydedilir.
- Sağlayıcı API sınıfları `Service/API/` altındadır. İzin ve hesap denetimleri `XF/Pub/Controller/` altındadır.
- ACP metinleri `_data/phrases.xml`, ACP ve kullanıcı şablonları `_data/templates.xml` içindedir.
- `hashes.json`, XenForo eklenti dosyalarının SHA-256 özetlerini içerir. PHP/XML dosyaları değiştirilirken bu dosya da yeniden oluşturulmalıdır.

## Güvenlik ve bilinen sınırlar

- Bu sürüm kullanıcı başına saat aralığı ve kod başına 5 yanlış deneme sınırı uygular. **IP başına saatlik kota, günlük toplam SMS kotası veya yönetici test SMS ekranı yoktur.** SMS kredisi kritikse sağlayıcı panelinde kota/harcama sınırı ve Cloudflare/WAF tarafında gönderim rotasına hız sınırı da kurun.
- Sağlayıcı API kullanıcı adı ve parolaları XenForo seçeneklerinde saklanır; sunucu ve veritabanı yedeklerine erişimi sınırlandırın.
- Telefon numaraları eklenti tablosunda saklanır. Üye silme sırasında bu tablo için ayrı otomatik temizlik kancası yoktur; numara kaydı elle temizlenmezse numara tekrar kullanım kontrolünde kalabilir.
- Eklentiyi kaldırmak `gurbetseli_sms` tablosunu ve eklentiye ait kullanıcı grubu değişiklik kayıtlarını siler. Kaldırmadan önce yedek alın.
- API zaman aşımında sağlayıcının SMS'i alıp almadığı kesin olmayabilir. Eklenti otomatik tekrar göndermez; aynı numaraya ikinci SMS gitmesini önlemek için gönderim aralığı kaydı korunur.
- Bu kaynak denetiminde PHP CLI bulunmadığından PHP sözdizimi denetimi ve gerçek XenForo/SMS sağlayıcı uç noktalarında uçtan uca test yapılamadı.

## Lisans ve telif

Eklenti telif notu Gurbetseli.NeT olarak belirtilmiştir. Bu depoda ayrıca bir açık kaynak lisansı verilmediyse, herkese açık olması kodun yeniden kullanım izni verdiği anlamına gelmez.
