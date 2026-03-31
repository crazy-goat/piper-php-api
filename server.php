<?php

declare(strict_types=1);

use App\PiperService;
use Workerman\Worker;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

require_once __DIR__ . '/vendor/autoload.php';

$modelsPath = __DIR__ . '/models';
$piperService = new PiperService($modelsPath);

$httpWorker = new Worker('http://0.0.0.0:8000');
$httpWorker->count = 1;

$httpWorker->onMessage = function ($connection, Request $request) use ($piperService) {
    $path = $request->path();
    $method = $request->method();

    if ($path === '/' && $method === 'GET') {
        $acceptLang = $request->header('accept-language', '');
        $connection->send(renderPage($piperService, $acceptLang));
        return;
    }

    if ($path === '/api/voices' && $method === 'GET') {
        $voices = $piperService->getVoicesByLanguage();
        $connection->send(new Response(200, ['Content-Type' => 'application/json'], json_encode($voices)));
        return;
    }

    if ($path === '/api/synthesize' && $method === 'POST') {
        $data = json_decode($request->rawBody(), true);
        $voice = $data['voice'] ?? '';
        $text = trim($data['text'] ?? '');
        $speed = (float)($data['speed'] ?? 1.0);

        if ($voice === '' || $text === '') {
            $connection->send(new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'voice and text are required'])));
            return;
        }

        if (strlen($text) > 500) {
            $connection->send(new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'text must be max 500 characters'])));
            return;
        }

        try {
            $sampleRate = 0;
            $first = true;

            $headers = "HTTP/1.1 200 OK\r\nContent-Type: audio/wav\r\nTransfer-Encoding: chunked\r\nX-Accel-Buffering: no\r\n\r\n";
            $connection->send($headers, true);

            foreach ($piperService->synthesizeStreaming($voice, $text, $speed) as $chunk) {
                $sampleRate = $chunk['sampleRate'];

                if ($first) {
                    $wavHeader = 'RIFF'
                        . pack('V', 0xFFFFFFFF)
                        . 'WAVE'
                        . 'fmt '
                        . pack('V', 16)
                        . pack('v', 1)
                        . pack('v', 1)
                        . pack('V', $sampleRate)
                        . pack('V', $sampleRate * 2)
                        . pack('v', 2)
                        . pack('v', 16)
                        . 'data'
                        . pack('V', 0xFFFFFFFF);
                    $first = false;
                    $body = $wavHeader . $chunk['pcmData'];
                } else {
                    $body = $chunk['pcmData'];
                }

                $hexLen = dechex(strlen($body));
                $connection->send("$hexLen\r\n$body\r\n", true);

                if ($chunk['isLast']) {
                    break;
                }
            }

            $connection->send("0\r\n\r\n", true);
            $connection->close();
        } catch (\Throwable $e) {
            $connection->send(new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => $e->getMessage()])));
        }
        return;
    }

    $connection->send(new Response(404, ['Content-Type' => 'text/plain'], 'Not Found'));
};

function renderPage(PiperService $piperService, string $acceptLang = ''): Response
{
    $voices = $piperService->getVoicesByLanguage();

    $translations = [
        'ar' => 'هذا تطبيق ويب لتحويل النص إلى كلام مكتوب بالكامل بلغة PHP ومبني على مكتبة Piper و piper-php. يعمل بالكامل على PHP مع Workerman كخادم HTTP، ولا يحتاج إلى Node.js أو Python. لا حاجة لوحدة معالجة رسومات — كل شيء يعمل على المعالج. اختر صوتًا، اكتب نصك، واستمع إلى النتائج فورًا — كل شيء يُعالج محليًا على جهازك.',
        'bg' => 'Това е изцяло PHP уеб приложение за синтез на реч, задвижвано от Piper и библиотеката piper-php. Работи изцяло на PHP с Workerman като HTTP сървър, без нужда от Node.js или Python. Не е необходима видеокарта — всичко работи на процесора. Изберете глас, въведете текст и чуйте резултата мигновено — всичко се обработва локално на вашето устройство.',
        'ca' => 'Aquesta és una aplicació web de text a veu escrita completament en PHP, impulsada per Piper i la biblioteca piper-php. Funciona íntegrament en PHP amb Workerman com a servidor HTTP, sense necessitat de Node.js ni Python. No cal GPU — tot funciona amb la CPU. Trieu una veu, escriviu el vostre text i escolteu els resultats a l\'instant — tot processat localment a la vostra màquina.',
        'cs' => 'Toto je plně PHP webová aplikace pro převod textu na řeč poháněná knihovnou Piper a piper-php. Běží kompletně na PHP s Workermanem jako HTTP serverem, bez potřeby Node.js nebo Pythonu. Není potřeba GPU — vše běží na CPU. Vyberte hlas, napište text a okamžitě uslyšte výsledek — vše zpracováno lokálně na vašem počítači.',
        'cy' => 'Mae hwn yn gymhwysiad gwe testun-i-lefar wedi\'i ysgrifennu\'n gyfan gwbl yn PHP, wedi\'i bweru gan Piper a\'r llyfrgell piper-php. Mae\'n rhedeg yn gyfan gwbl ar PHP gyda Workerman fel y gweinydd HTTP, heb angen Node.js na Python. Dim angen GPU — mae popeth yn rhedeg ar y CPU. Dewiswch lais, teipiwch eich testun, a chlywed y canlyniadau ar unwaith — popeth wedi\'i brosesu\'n lleol ar eich peiriant.',
        'da' => 'Dette er en fuldt PHP-baseret tekst-til-tale webapplikation drevet af Piper og piper-php-biblioteket. Det kører udelukkende på PHP med Workerman som HTTP-server, uden behov for Node.js eller Python. Ingen GPU nødvendig — alt kører på CPU\'en. Vælg en stemme, indtast din tekst, og hør resultaterne øjeblikkeligt — alt behandlet lokalt på din maskine.',
        'de' => 'Dies ist eine vollständig in PHP geschriebene Text-to-Speech-Webanwendung, die auf Piper und der piper-php-Bibliothek basiert. Sie läuft komplett auf PHP mit Workerman als HTTP-Server, ohne Node.js oder Python. Keine GPU erforderlich — alles läuft auf der CPU. Wählen Sie eine Stimme, geben Sie Ihren Text ein und hören Sie sofort das Ergebnis — alles lokal auf Ihrem Rechner verarbeitet.',
        'el' => 'Αυτή είναι μια πλήρως PHP εφαρμογή μετατροπής κειμένου σε ομιλία, βασισμένη στο Piper και τη βιβλιοθήκη piper-php. Τρέχει εξ ολοκλήρου σε PHP με Workerman ως διακομιστή HTTP, χωρίς ανάγκη για Node.js ή Python. Δεν απαιτείται GPU — όλα τρέχουν στην CPU. Επιλέξτε φωνή, πληκτρολογήστε το κείμενό σας και ακούστε τα αποτελέσματα άμεσα — όλα επεξεργάζονται τοπικά στο μηχάνημά σας.',
        'en' => 'This is a fully PHP-based text-to-speech web application powered by Piper and the piper-php library. It runs entirely on PHP with Workerman as the HTTP server, requiring no Node.js or Python backend. No GPU needed — everything runs on CPU. Choose a voice, type your text, and hear the results instantly — all processed locally on your machine.',
        'es' => 'Esta es una aplicación web de texto a voz escrita completamente en PHP, impulsada por Piper y la biblioteca piper-php. Funciona íntegramente en PHP con Workerman como servidor HTTP, sin necesidad de Node.js ni Python. No se necesita GPU — todo funciona en la CPU. Elija una voz, escriba su texto y escuche los resultados al instante — todo procesado localmente en su máquina.',
        'fa' => 'این یک برنامه وب تبدیل متن به گفتار است که کاملاً با PHP نوشته شده و توسط Piper و کتابخانه piper-php پشتیبانی می‌شود. به طور کامل روی PHP با Workerman به عنوان سرور HTTP اجرا می‌شود و نیازی به Node.js یا Python ندارد. نیازی به GPU نیست — همه چیز روی CPU اجرا می‌شود. یک صدا انتخاب کنید، متن خود را تایپ کنید و نتایج را فوراً بشنوید — همه چیز به صورت محلی روی دستگاه شما پردازش می‌شود.',
        'fi' => 'Tämä on täysin PHP-pohjainen tekstistä puheeksi -sovellus, joka perustuu Piperiin ja piper-php-kirjastoon. Se toimii kokonaan PHP:llä Workermanin toimiessa HTTP-palvelimena, ilman Node.js- tai Python-tarvetta. Ei GPU:ta — kaikki toimii suorittimella. Valitse ääni, kirjoita tekstisi ja kuule tulokset välittömästi — kaikki käsitellään paikallisesti koneellasi.',
        'fr' => 'Cette application web de synthèse vocale est entièrement écrite en PHP, propulsée par Piper et la bibliothèque piper-php. Elle fonctionne entièrement en PHP avec Workerman comme serveur HTTP, sans nécessiter Node.js ni Python. Aucun GPU nécessaire — tout fonctionne sur le processeur. Choisissez une voix, tapez votre texte et écoutez le résultat instantanément — tout est traité localement sur votre machine.',
        'hi' => 'यह पूरी तरह से PHP-आधारित टेक्स्ट-टू-स्पीच वेब एप्लिकेशन है जो Piper और piper-php लाइब्रेरी द्वारा संचालित है। यह HTTP सर्वर के रूप में Workerman के साथ पूरी तरह से PHP पर चलता है, जिसके लिए Node.js या Python की आवश्यकता नहीं है। GPU की आवश्यकता नहीं — सब कुछ CPU पर चलता है। एक आवाज़ चुनें, अपना टेक्स्ट टाइप करें और तुरंत परिणाम सुनें — सब कुछ आपके मशीन पर स्थानीय रूप से प्रोसेस होता है।',
        'hu' => 'Ez egy teljesen PHP-alapú szövegfelolvasó webalkalmazás, amelyet a Piper és a piper-php könyvtár hajt. Kizárólag PHP-n fut Workerman HTTP szerverrel, Node.js vagy Python nélkül. Nincs szükség GPU-ra — minden a CPU-n fut. Válasszon hangot, írja be a szövegét, és azonnal hallja az eredményt — minden helyben, az Ön gépén kerül feldolgozásra.',
        'id' => 'Ini adalah aplikasi web teks-ke-suara yang sepenuhnya berbasis PHP, ditenagai oleh Piper dan pustaka piper-php. Berjalan sepenuhnya di PHP dengan Workerman sebagai server HTTP, tanpa memerlukan Node.js atau Python. Tidak perlu GPU — semuanya berjalan di CPU. Pilih suara, ketik teks Anda, dan dengarkan hasilnya secara instan — semua diproses secara lokal di mesin Anda.',
        'is' => 'Þetta er fullkomin PHP vefumsókn fyrir texta í tal, knúin af Piper og piper-php safninu. Hún keyrir eingöngu á PHP með Workerman sem HTTP-þjón, án þess að þurfa Node.js eða Python. Engin GPU nauðsynleg — allt keyrir á örgjörva. Veldu rödd, sláðu inn textann þinn og hlustaðu á niðurstöðurnar strax — allt unnið staðbundið á tölvunni þinni.',
        'it' => 'Questa è un\'applicazione web di sintesi vocale scritta interamente in PHP, basata su Piper e sulla libreria piper-php. Funziona completamente in PHP con Workerman come server HTTP, senza bisogno di Node.js o Python. Nessuna GPU necessaria — tutto funziona sulla CPU. Scegli una voce, digita il tuo testo e ascolta i risultati istantaneamente — tutto elaborato localmente sulla tua macchina.',
        'ka' => 'ეს არის სრულად PHP-ზე დაფუძნებული ტექსტიდან მეტყველებისკენ ვებ აპლიკაცია, რომელიც მუშაობს Piper-ისა და piper-php ბიბლიოთეკის საშუალებით. ის მთლიანად PHP-ზე მუშაობს Workerman-ით როგორც HTTP სერვერით, Node.js-ის ან Python-ის გარეშე. GPU არ არის საჭირო — ყველაფერი CPU-ზე მუშაობს. აირჩიეთ ხმა, აკრიფეთ ტექსტი და მოუსმინეთ შედეგს მყისიერად — ყველაფერი ლოკალურად მუშავდება თქვენს მანქანაზე.',
        'kk' => 'Бұл Piper және piper-php кітапханасымен жұмыс істейтін толығымен PHP негізіндегі мәтіннен сөйлеуге арналған веб қосымшасы. HTTP сервері ретінде Workerman-мен толығымен PHP-де жұмыс істейді, Node.js немесе Python қажет емес. GPU қажет емес — бәрі CPU-да жұмыс істейді. Дауысты таңдап, мәтініңізді теріп, нәтижені бірден тыңдаңыз — бәрі жергілікті түрде машинаңызда өңделеді.',
        'lb' => 'Dëst ass eng komplett a PHP geschriwwen Text-to-Speech Webapplikatioun, déi op Piper an der piper-php Bibliothéik baséiert. Se leeft komplett op PHP mat Workerman als HTTP-Server, ouni Node.js oder Python. Keng GPU néideg — alles leeft um Prozessor. Wielt eng Stëmm, gitt Ären Text an a lauschtert direkt d\'Resultat — alles lokal op Ärem Rechner verschafft.',
        'lv' => 'Šī ir pilnībā uz PHP balstīta teksta pārvēršanas runā tīmekļa lietotne, ko darbina Piper un piper-php bibliotēka. Tā darbojas pilnībā uz PHP ar Workerman kā HTTP serveri, bez Node.js vai Python nepieciešamības. GPU nav nepieciešams — viss darbojas uz procesora. Izvēlieties balsi, ierakstiet savu tekstu un klausieties rezultātus acumirklī — viss tiek apstrādāts lokāli jūsu ierīcē.',
        'ml' => 'ഇത് Piper-ഉം piper-php ലൈബ്രറിയും ഉപയോഗിച്ച് പ്രവർത്തിക്കുന്ന പൂർണ്ണമായും PHP അടിസ്ഥാനമാക്കിയുള്ള ടെക്സ്റ്റ്-ടു-സ്പീച്ച് വെബ് ആപ്ലിക്കേഷനാണ്. Node.js അല്ലെങ്കിൽ Python ആവശ്യമില്ലാതെ Workerman HTTP സെർവറായി ഉപയോഗിച്ച് പൂർണ്ണമായും PHP-യിൽ പ്രവർത്തിക്കുന്നു. GPU ആവശ്യമില്ല — എല്ലാം CPU-യിൽ പ്രവർത്തിക്കുന്നു. ഒരു ശബ്ദം തിരഞ്ഞെടുത്ത് നിങ്ങളുടെ ടെക്സ്റ്റ് ടൈപ്പ് ചെയ്ത് ഫലങ്ങൾ ഉടൻ കേൾക്കുക — എല്ലാം നിങ്ങളുടെ മെഷീനിൽ പ്രാദേശികമായി പ്രോസസ്സ് ചെയ്യുന്നു.',
        'ne' => 'यो पूर्ण रूपमा PHP-आधारित टेक्स्ट-टू-स्पीच वेब एप्लिकेशन हो जुन Piper र piper-php लाइब्रेरी द्वारा संचालित छ। Node.js वा Python को आवश्यकता बिना Workerman HTTP सर्भरको रूपमा प्रयोग गरेर पूर्ण रूपमा PHP मा चल्छ। GPU को आवश्यकता छैन — सबै CPU मा चल्छ। एउटा आवाज छान्नुहोस्, आफ्नो टेक्स्ट टाइप गर्नुहोस् र तुरुन्तै नतिजा सुन्नुहोस् — सबै तपाईंको मेसिनमा स्थानीय रूपमा प्रशोधन गरिन्छ।',
        'nl' => 'Dit is een volledig op PHP gebaseerde tekst-naar-spraak webapplicatie, aangedreven door Piper en de piper-php-bibliotheek. Het draait volledig op PHP met Workerman als HTTP-server, zonder Node.js of Python. Geen GPU nodig — alles draait op de CPU. Kies een stem, typ uw tekst en hoor direct het resultaat — alles lokaal verwerkt op uw machine.',
        'no' => 'Dette er en fullstendig PHP-basert tekst-til-tale webapplikasjon drevet av Piper og piper-php-biblioteket. Den kjører utelukkende på PHP med Workerman som HTTP-server, uten behov for Node.js eller Python. Ingen GPU nødvendig — alt kjører på prosessoren. Velg en stemme, skriv inn teksten din og hør resultatene umiddelbart — alt behandlet lokalt på maskinen din.',
        'pl' => 'To w pełni napisana w PHP aplikacja webowa do syntezy mowy, oparta na bibliotece Piper i piper-php. Działa całkowicie na PHP z Workermanem jako serwerem HTTP, bez potrzeby używania Node.js czy Pythona. Nie wymaga karty GPU — wszystko działa na procesorze. Wybierz głos, wpisz tekst i natychmiast usłysz wynik — wszystko przetwarzane lokalnie na Twoim komputerze.',
        'pt' => 'Esta é uma aplicação web de texto para fala escrita inteiramente em PHP, alimentada pelo Piper e pela biblioteca piper-php. Funciona completamente em PHP com Workerman como servidor HTTP, sem necessidade de Node.js ou Python. Sem necessidade de GPU — tudo funciona na CPU. Escolha uma voz, digite seu texto e ouça os resultados instantaneamente — tudo processado localmente na sua máquina.',
        'ro' => 'Aceasta este o aplicație web de sinteză vocală scrisă integral în PHP, bazată pe Piper și biblioteca piper-php. Funcționează complet pe PHP cu Workerman ca server HTTP, fără a necesita Node.js sau Python. Nu este nevoie de GPU — totul rulează pe procesor. Alegeți o voce, tastați textul și ascultați rezultatele instantaneu — totul procesat local pe mașina dumneavoastră.',
        'ru' => 'Это веб-приложение для синтеза речи, полностью написанное на PHP и работающее на базе Piper и библиотеки piper-php. Оно работает полностью на PHP с Workerman в качестве HTTP-сервера, без необходимости в Node.js или Python. GPU не требуется — всё работает на процессоре. Выберите голос, введите текст и мгновенно услышайте результат — всё обрабатывается локально на вашем компьютере.',
        'sk' => 'Toto je plne PHP webová aplikácia na prevod textu na reč poháňaná knižnicou Piper a piper-php. Beží kompletne na PHP s Workermanom ako HTTP serverom, bez potreby Node.js alebo Pythonu. Nie je potrebná GPU — všetko beží na CPU. Vyberte hlas, napíšte text a okamžite počujte výsledok — všetko spracované lokálne na vašom počítači.',
        'sl' => 'To je popolnoma v PHP napisana spletna aplikacija za pretvorbo besedila v govor, ki jo poganjata Piper in knjižnica piper-php. Deluje v celoti na PHP z Workermanom kot HTTP strežnikom, brez potrebe po Node.js ali Pythonu. GPU ni potreben — vse deluje na procesorju. Izberite glas, vnesite besedilo in takoj slišite rezultat — vse obdelano lokalno na vašem računalniku.',
        'sr' => 'Ово је у потпуности PHP веб апликација за синтезу говора, покренута Piper-ом и piper-php библиотеком. Ради искључиво на PHP-у са Workerman-ом као HTTP сервером, без потребе за Node.js или Python-ом. Није потребан GPU — све ради на процесору. Изаберите глас, унесите текст и одмах чујте резултат — све обрађено локално на вашем рачунару.',
        'sv' => 'Detta är en helt PHP-baserad text-till-tal webbapplikation driven av Piper och piper-php-biblioteket. Den körs helt på PHP med Workerman som HTTP-server, utan behov av Node.js eller Python. Inget GPU behövs — allt körs på processorn. Välj en röst, skriv din text och hör resultatet omedelbart — allt bearbetas lokalt på din maskin.',
        'sw' => 'Hii ni programu ya wavuti ya maandishi-kwa-sauti iliyoandikwa kabisa kwa PHP, inayoendeshwa na Piper na maktaba ya piper-php. Inafanya kazi kabisa kwa PHP na Workerman kama seva ya HTTP, bila kuhitaji Node.js au Python. Hakuna GPU inayohitajika — kila kitu kinafanya kazi kwenye CPU. Chagua sauti, andika maandishi yako na usikie matokeo mara moja — yote yanasindika ndani ya mashine yako.',
        'te' => 'ఇది Piper మరియు piper-php లైబ్రరీ ద్వారా నడిచే పూర్తిగా PHP-ఆధారిత టెక్స్ట్-టు-స్పీచ్ వెబ్ అప్లికేషన్. Node.js లేదా Python అవసరం లేకుండా Workerman HTTP సర్వర్‌తో పూర్తిగా PHP పై నడుస్తుంది. GPU అవసరం లేదు — అన్నీ CPU పై నడుస్తాయి. ఒక వాయిస్ ఎంచుకుని, మీ టెక్స్ట్ టైప్ చేసి, ఫలితాలను వెంటనే వినండి — అన్నీ మీ మెషీన్‌పై స్థానికంగా ప్రాసెస్ అవుతాయి.',
        'tr' => 'Bu, Piper ve piper-php kütüphanesi tarafından desteklenen tamamen PHP tabanlı bir metinden konuşmaya web uygulamasıdır. Node.js veya Python gerektirmeden, Workerman HTTP sunucusu olarak tamamen PHP üzerinde çalışır. GPU gerekmez — her şey CPU üzerinde çalışır. Bir ses seçin, metninizi yazın ve sonuçları anında dinleyin — her şey makinenizde yerel olarak işlenir.',
        'uk' => 'Це веб-додаток для синтезу мовлення, повністю написаний на PHP та працюючий на базі Piper і бібліотеки piper-php. Він працює повністю на PHP з Workerman як HTTP-сервером, без необхідності в Node.js або Python. GPU не потрібен — все працює на процесорі. Оберіть голос, введіть текст і миттєво почуйте результат — все обробляється локально на вашому комп\'ютері.',
        'vi' => 'Đây là ứng dụng web chuyển văn bản thành giọng nói hoàn toàn bằng PHP, được cung cấp bởi Piper và thư viện piper-php. Ứng dụng chạy hoàn toàn trên PHP với Workerman làm máy chủ HTTP, không cần Node.js hay Python. Không cần GPU — mọi thứ chạy trên CPU. Chọn giọng nói, nhập văn bản và nghe kết quả ngay lập tức — tất cả được xử lý cục bộ trên máy của bạn.',
        'zh' => '这是一个完全基于 PHP 的文本转语音 Web 应用程序，由 Piper 和 piper-php 库驱动。它完全在 PHP 上运行，使用 Workerman 作为 HTTP 服务器，不需要 Node.js 或 Python。不需要 GPU — 所有内容都在 CPU 上运行。选择一个语音，输入您的文本，立即听到结果 — 所有内容都在您的机器上本地处理。',
    ];

    // Determine default language from browser
    $defaultLang = 'en';
    if ($acceptLang !== '') {
        $browserLang = strtolower(explode(',', $acceptLang)[0]);
        $browserFamily = explode('-', $browserLang)[0];
        $availableFamilies = array_keys($voices);
        if (in_array($browserFamily, $availableFamilies, true)) {
            $defaultLang = $browserFamily;
        }
    }

    $defaultText = $translations[$defaultLang] ?? $translations['en'];

    $langOptions = '';
    $defaultVoice = '';
    foreach ($voices as $family => $data) {
        $selected = $family === $defaultLang ? ' selected' : '';
        $langOptions .= sprintf('<option value="%s"%s>%s</option>', htmlspecialchars($family), $selected, htmlspecialchars($data['name']));
        if ($family === $defaultLang && !empty($data['voices'])) {
            $defaultVoice = array_key_first($data['voices']);
        }
    }

    $voiceOptions = '';
    foreach ($voices as $family => $data) {
        foreach ($data['voices'] as $v) {
            $selected = $v['key'] === $defaultVoice ? ' selected' : '';
            $voiceOptions .= sprintf(
                '<option value="%s" data-lang="%s"%s>%s (%s)</option>',
                htmlspecialchars($v['key']),
                htmlspecialchars($family),
                $selected,
                htmlspecialchars($v['name']),
                htmlspecialchars($v['quality'])
            );
        }
    }

    $translationsJson = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piper TTS</title>
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.1); padding: 2rem; width: 100%; max-width: 480px; }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; text-align: center; }
        label { display: block; font-size: .875rem; font-weight: 600; margin-bottom: .375rem; }
        select, textarea { width: 100%; padding: .625rem .75rem; border: 1px solid #ddd; border-radius: 8px; font-size: .9375rem; margin-bottom: 1rem; background: #fafafa; }
        select:focus, textarea:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.15); }
        textarea { resize: vertical; min-height: 100px; }
        .row { display: flex; gap: .75rem; }
        .row > div { flex: 1; }
        button { width: 100%; padding: .75rem; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background .2s; }
        button:hover { background: #4338ca; }
        button:disabled { background: #a5b4fc; cursor: not-allowed; }
        .error { color: #dc2626; font-size: .875rem; margin-top: .75rem; text-align: center; }
        audio { width: 100%; margin-top: 1rem; }
        .char-count { font-size: .75rem; color: #888; text-align: right; margin-top: -.75rem; margin-bottom: .75rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>Piper TTS</h1>
    <form id="tts-form">
        <label for="language">Language</label>
        <select id="language" name="language">
            <option value="">-- select language --</option>
            $langOptions
        </select>

        <label for="voice">Voice</label>
        <select id="voice" name="voice" disabled>
            <option value="">-- select voice --</option>
            $voiceOptions
        </select>

        <label for="text">Text</label>
        <textarea id="text" name="text" maxlength="500" placeholder="Type up to 500 characters...">$defaultText</textarea>
        <div class="char-count"><span id="char-count">0</span>/500</div>

        <div class="row">
            <div>
                <label for="speed">Speed</label>
                <select id="speed" name="speed">
                    <option value="0.5">0.5x</option>
                    <option value="0.75">0.75x</option>
                    <option value="1" selected>1x</option>
                    <option value="1.25">1.25x</option>
                    <option value="1.5">1.5x</option>
                    <option value="2">2x</option>
                </select>
            </div>
        </div>

        <button type="submit" id="generate-btn">Generate</button>
    </form>
    <div id="error" class="error"></div>
    <div id="audio-container"></div>
</div>

<script id="translations-data" type="application/json">$translationsJson</script>

<script>
    const translations = JSON.parse(document.getElementById('translations-data').textContent);
    const voices = document.getElementById('voice');
    const language = document.getElementById('language');
    const text = document.getElementById('text');
    const charCount = document.getElementById('char-count');
    const form = document.getElementById('tts-form');
    const btn = document.getElementById('generate-btn');
    const errorEl = document.getElementById('error');
    const audioContainer = document.getElementById('audio-container');

    language.addEventListener('change', function() {
        const lang = this.value;
        voices.value = '';
        voices.disabled = !lang;
        Array.from(voices.options).forEach(opt => {
            if (!opt.value) return;
            opt.style.display = !lang || opt.dataset.lang === lang ? '' : 'none';
        });
        if (lang) {
            const firstVisible = Array.from(voices.options).find(o => o.value && o.style.display !== 'none');
            if (firstVisible) voices.value = firstVisible.value;
        }
        if (translations[lang]) {
            text.value = translations[lang];
            charCount.textContent = text.value.length;
        }
    });

    // Initialize voice visibility and selection on load
    (function initVoices() {
        const lang = language.value;
        if (lang) {
            voices.disabled = false;
            Array.from(voices.options).forEach(opt => {
                if (!opt.value) return;
                opt.style.display = opt.dataset.lang === lang ? '' : 'none';
            });
            if (!voices.value) {
                const firstVisible = Array.from(voices.options).find(o => o.value && o.style.display !== 'none');
                if (firstVisible) voices.value = firstVisible.value;
            }
        }
    })();

    text.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    charCount.textContent = text.value.length;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        errorEl.textContent = '';
        audioContainer.innerHTML = '';

        const voice = voices.value;
        const textVal = text.value.trim();
        const speed = document.getElementById('speed').value;

        if (!voice || !textVal) {
            errorEl.textContent = 'Please select a voice and enter text.';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Generating...';

        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        let sampleRate = 0;
        let scheduledTime = 0;
        let headerParsed = false;

        try {
            const res = await fetch('/api/synthesize', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({voice, text: textVal, speed: parseFloat(speed)})
            });

            if (!res.ok) {
                const err = await res.json();
                errorEl.textContent = err.error || 'Generation failed';
                btn.disabled = false;
                btn.textContent = 'Generate';
                return;
            }

            const reader = res.body.getReader();
            let buffer = new Uint8Array(0);

            while (true) {
                const {done, value} = await reader.read();
                if (done) break;

                const combined = new Uint8Array(buffer.length + value.length);
                combined.set(buffer);
                combined.set(value, buffer.length);
                buffer = combined;

                if (!headerParsed && combined.length >= 44) {
                    sampleRate = new DataView(combined.buffer).getUint32(24, true);
                    headerParsed = true;
                    buffer = combined.slice(44);
                }

                if (headerParsed && buffer.length >= 2) {
                    const samplesToProcess = buffer.length - (buffer.length % 2);
                    if (samplesToProcess === 0) continue;

                    const pcmChunk = buffer.slice(0, samplesToProcess);
                    buffer = buffer.slice(samplesToProcess);

                    const int16 = new Int16Array(pcmChunk.buffer);
                    const float32 = new Float32Array(int16.length);
                    for (let i = 0; i < int16.length; i++) {
                        float32[i] = int16[i] / 32768;
                    }

                    const audioBuffer = audioCtx.createBuffer(1, float32.length, sampleRate);
                    audioBuffer.getChannelData(0).set(float32);
                    const source = audioCtx.createBufferSource();
                    source.buffer = audioBuffer;
                    source.connect(audioCtx.destination);

                    if (scheduledTime === 0) {
                        audioCtx.resume();
                        scheduledTime = audioCtx.currentTime;
                    }
                    source.start(scheduledTime);
                    scheduledTime += audioBuffer.duration;
                }
            }

            btn.disabled = false;
            btn.textContent = 'Generate';
        } catch (err) {
            errorEl.textContent = 'Network error: ' + err.message;
            btn.disabled = false;
            btn.textContent = 'Generate';
        }
    });
</script>
</body>
</html>
HTML;

    return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
}

Worker::runAll();
