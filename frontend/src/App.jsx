import { useState, useEffect, useRef, useCallback } from 'preact/hooks';

const TRANSLATIONS = {
  ar: 'هذا تطبيق ويب لتحويل النص إلى كلام مكتوب بالكامل بلغة PHP ومبني على مكتبة Piper و piper-php. يعمل بالكامل على PHP مع Workerman كخادم HTTP، ولا يحتاج إلى Node.js أو Python. لا حاجة لوحدة معالجة رسومات — كل شيء يعمل على المعالج. اختر صوتًا، اكتب نصك، واستمع إلى النتائج فورًا — كل شيء يُعالج محليًا على جهازك.',
  bg: 'Това е изцяло PHP уеб приложение за синтез на реч, задвижвано от Piper и библиотеката piper-php. Работи изцяло на PHP с Workerman като HTTP сървър, без нужда от Node.js или Python. Не е необходима видеокарта — всичко работи на процесора. Изберете глас, въведете текст и чуйте резултата мигновено — всичко се обработва локално на вашето устройство.',
  ca: 'Aquesta és una aplicació web de text a veu escrita completament en PHP, impulsada per Piper i la biblioteca piper-php. Funciona íntegrament en PHP amb Workerman com a servidor HTTP, sense necessitat de Node.js ni Python. No cal GPU — tot funciona amb la CPU. Trieu una veu, escriviu el vostre text i escolteu els resultats a l\'instant — tot processat localment a la vostra màquina.',
  cs: 'Toto je plně PHP webová aplikace pro převod textu na řeč poháněná knihovnou Piper a piper-php. Běží kompletně na PHP s Workermanem jako HTTP serverem, bez potřeby Node.js nebo Pythonu. Není potřeba GPU — vše běží na CPU. Vyberte hlas, napište text a okamžitě uslyšte výsledek — vše zpracováno lokálně na vašem počítači.',
  cy: 'Mae hwn yn gymhwysiad gwe testun-i-lefar wedi\'i ysgrifennu\'n gyfan gwbl yn PHP, wedi\'i bweru gan Piper a\'r llyfrgell piper-php. Mae\'n rhedeg yn gyfan gwbl ar PHP gyda Workerman fel y gweinydd HTTP, heb angen Node.js na Python. Dim angen GPU — mae popeth yn rhedeg ar y CPU. Dewiswch lais, teipiwch eich testun, a chlywed y canlyniadau ar unwaith — popeth wedi\'i brosesu\'n lleol ar eich peiriant.',
  da: 'Dette er en fuldt PHP-baseret tekst-til-tale webapplikation drevet af Piper og piper-php-biblioteket. Det kører udelukkende på PHP med Workerman som HTTP-server, uden behov for Node.js eller Python. Ingen GPU nødvendig — alt kører på CPU\'en. Vælg en stemme, indtast din tekst, og hør resultaterne øjeblikkeligt — alt behandlet lokalt på din maskine.',
  de: 'Dies ist eine vollständig in PHP geschriebene Text-to-Speech-Webanwendung, die auf Piper und der piper-php-Bibliothek basiert. Sie läuft komplett auf PHP mit Workerman als HTTP-Server, ohne Node.js oder Python. Keine GPU erforderlich — alles läuft auf der CPU. Wählen Sie eine Stimme, geben Sie Ihren Text ein und hören Sie sofort das Ergebnis — alles lokal auf Ihrem Rechner verarbeitet.',
  el: 'Αυτή είναι μια πλήρως PHP εφαρμογή μετατροπής κειμένου σε ομιλία, βασισμένη στο Piper και τη βιβλιοθήκη piper-php. Τρέχει εξ ολοκλήρου σε PHP με Workerman ως διακομιστή HTTP, χωρίς ανάγκη για Node.js ή Python. Δεν απαιτείται GPU — όλα τρέχουν στην CPU. Επιλέξτε φωνή, πληκτρολογήστε το κείμενό σας και ακούστε τα αποτελέσματα άμεσα — όλα επεξεργάζονται τοπικά στο μηχάνημά σας.',
  en: 'This is a fully PHP-based text-to-speech web application powered by Piper and the piper-php library. It runs entirely on PHP with Workerman as the HTTP server, requiring no Node.js or Python backend. No GPU needed — everything runs on CPU. Choose a voice, type your text, and hear the results instantly — all processed locally on your machine.',
  es: 'Esta es una aplicación web de texto a voz escrita completamente en PHP, impulsada por Piper y la biblioteca piper-php. Funciona íntegramente en PHP con Workerman como servidor HTTP, sin necesidad de Node.js ni Python. No se necesita GPU — todo funciona en la CPU. Elija una voz, escriba su texto y escuche los resultados al instante — todo procesado localmente en su máquina.',
  fa: 'این یک برنامه وب تبدیل متن به گفتار است که کاملاً با PHP نوشته شده و توسط Piper و کتابخانه piper-php پشتیبانی می‌شود. به طور کامل روی PHP با Workerman به عنوان سرور HTTP اجرا می‌شود و نیازی به Node.js یا Python ندارد. نیازی به GPU نیست — همه چیز روی CPU اجرا می‌شود. یک صدا انتخاب کنید، متن خود را تایپ کنید و نتایج را فوراً بشنوید — همه چیز به صورت محلی روی دستگاه شما پردازش می‌شود.',
  fi: 'Tämä on täysin PHP-pohjainen tekstistä puheeksi -sovellus, joka perustuu Piperiin ja piper-php-kirjastoon. Se toimii kokonaan PHP:llä Workermanin toimiessa HTTP-palvelimena, ilman Node.js- tai Python-tarvetta. Ei GPU:ta — kaikki toimii suorittimella. Valitse ääni, kirjoita tekstisi ja kuule tulokset välittömästi — kaikki käsitellään paikallisesti koneellasi.',
  fr: 'Cette application web de synthèse vocale est entièrement écrite en PHP, propulsée par Piper et la bibliothèque piper-php. Elle fonctionne entièrement en PHP avec Workerman comme serveur HTTP, sans nécessiter Node.js ni Python. Aucun GPU nécessaire — tout fonctionne sur le processeur. Choisissez une voix, tapez votre texte et écoutez le résultat instantanément — tout est traité localement sur votre machine.',
  hi: 'यह पूरी तरह से PHP-आधारित टेक्स्ट-टू-स्पीच वेब एप्लिकेशन है जो Piper और piper-php लाइब्रेरी द्वारा संचालित है। यह HTTP सर्वर के रूप में Workerman के साथ पूरी तरह से PHP पर चलता है, जिसके लिए Node.js या Python की आवश्यकता नहीं है। GPU की आवश्यकता नहीं — सब कुछ CPU पर चलता है। एक आवाज़ चुनें, अपना टेक्स्ट टाइप करें और तुरंत परिणाम सुनें — सब कुछ आपके मशीन पर स्थानीय रूप से प्रोसेस होता है।',
  hu: 'Ez egy teljesen PHP-alapú szövegfelolvasó webalkalmazás, amelyet a Piper és a piper-php könyvtár hajt. Kizárólag PHP-n fut Workerman HTTP szerverrel, Node.js vagy Python nélkül. Nincs szükség GPU-ra — minden a CPU-n fut. Válasszon hangot, írja be a szövegét, és azonnal hallja az eredményt — minden helyben, az Ön gépén kerül feldolgozásra.',
  id: 'Ini adalah aplikasi web teks-ke-suara yang sepenuhnya berbasis PHP, ditenagai oleh Piper dan pustaka piper-php. Berjalan sepenuhnya di PHP dengan Workerman sebagai server HTTP, tanpa memerlukan Node.js atau Python. Tidak perlu GPU — semuanya berjalan di CPU. Pilih suara, ketik teks Anda, dan dengarkan hasilnya secara instan — semua diproses secara lokal di mesin Anda.',
  is: 'Þetta er fullkomin PHP vefumsókn fyrir texta í tal, knúin af Piper og piper-php safninu. Hún keyrir eingöngu á PHP með Workerman sem HTTP-þjón, án þess að þurfa Node.js eða Python. Engin GPU nauðsynleg — allt keyrir á örgjörva. Veldu rödd, sláðu inn textann þinn og hlustaðu á niðurstöðurnar strax — allt unnið staðbundið á tölvunni þinni.',
  it: 'Questa è un\'applicazione web di sintesi vocale scritta interamente in PHP, basata su Piper e sulla libreria piper-php. Funziona completamente in PHP con Workerman come server HTTP, senza bisogno di Node.js o Python. Nessuna GPU necessaria — tutto funziona sulla CPU. Scegli una voce, digita il tuo testo e ascolta i risultati istantaneamente — tutto elaborato localmente sulla tua macchina.',
  ka: 'ეს არის სრულად PHP-ზე დაფუძნებული ტექსტიდან მეტყველებისკენ ვებ აპლიკაცია, რომელიც მუშაობს Piper-ისა და piper-php ბიბლიოთეკის საშუალებით. ის მთლიანად PHP-ზე მუშაობს Workerman-ით როგორც HTTP სერვერით, Node.js-ის ან Python-ის გარეშე. GPU არ არის საჭირო — ყველაფერი CPU-ზე მუშაობს. აირჩიეთ ხმა, აკრიფეთ ტექსტი და მოუსმინეთ შედეგს მყისიერად — ყველაფერი ლოკალურად მუშავდება თქვენს მანქანაზე.',
  kk: 'Бұл Piper және piper-php кітапханасымен жұмыс істейтін толығымен PHP негізіндегі мәтіннен сөйлеуге арналған веб қосымшасы. HTTP сервері ретінде Workerman-мен толығымен PHP-де жұмыс істейді, Node.js немесе Python қажет емес. GPU қажет емес — бәрі CPU-да жұмыс істейді. Дауысты таңдап, мәтініңізді теріп, нәтижені бірден тыңдаңыз — бәрі жергілікті түрде машинаңызда өңделеді.',
  lb: 'Dëst ass eng komplett a PHP geschriwwen Text-to-Speech Webapplikatioun, déi op Piper an der piper-php Bibliothéik baséiert. Se leeft komplett op PHP mat Workerman als HTTP-Server, ouni Node.js oder Python. Keng GPU néideg — alles leeft um Prozessor. Wielt eng Stëmm, gitt Ären Text an a lauschtert direkt d\'Resultat — alles lokal op Ärem Rechner verschafft.',
  lv: 'Šī ir pilnībā uz PHP balstīta teksta pārvēršanas runā tīmekļa lietotne, ko darbina Piper un piper-php bibliotēka. Tā darbojas pilnībā uz PHP ar Workerman kā HTTP serveri, bez Node.js vai Python nepieciešamības. GPU nav nepieciešams — viss darbojas uz procesora. Izvēlieties balsi, ierakstiet savu tekstu un klausieties rezultātus acumirklī — viss tiek apstrādāts lokāli jūsu ierīcē.',
  ml: 'ഇത് Piper-ഉം piper-php ലൈബ്രറിയും ഉപയോഗിച്ച് പ്രവർത്തിക്കുന്ന പൂർണ്ണമായും PHP അടിസ്ഥാനമാക്കിയുള്ള ടെക്സ്റ്റ്-ടു-സ്പീച്ച് വെബ് ആപ്ലിക്കേഷനാണ്. Node.js അല്ലെങ്കിൽ Python ആവശ്യമില്ലാതെ Workerman HTTP സെർവറായി ഉപയോഗിച്ച് പൂർണ്ണമായും PHP-യിൽ പ്രവർത്തിക്കുന്നു. GPU ആവശ്യമില്ല — എല്ലാം CPU-യിൽ പ്രവർത്തിക്കുന്നു. ഒരു ശബ്ദം തിരഞ്ഞെടുത്ത് നിങ്ങളുടെ ടെക്സ്റ്റ് ടൈപ്പ് ചെയ്ത് ഫലങ്ങൾ ഉടൻ കേൾക്കുക — എല്ലാം നിങ്ങളുടെ മെഷീനിൽ പ്രാദേശികമായി പ്രോസസ്സ് ചെയ്യുന്നു.',
  ne: 'यो पूर्ण रूपमा PHP-आधारित टेक्स्ट-टू-स्पीच वेब एप्लिकेशन हो जुन Piper र piper-php लाइब्रेरी द्वारा संचालित छ। Node.js वा Python को आवश्यकता बिना Workerman HTTP सर्भरको रूपमा प्रयोग गरेर पूर्ण रूपमा PHP मा चल्छ। GPU को आवश्यकता छैन — सबै CPU मा चल्छ। एउटा आवाज छान्नुहोस्, आफ्नो टेक्स्ट टाइप गर्नुहोस् र तुरुन्तै नतिजा सुन्नुहोस् — सबै तपाईंको मेसिनमा स्थानीय रूपमा प्रशोधन गरिन्छ।',
  nl: 'Dit is een volledig op PHP gebaseerde tekst-naar-spraak webapplicatie, aangedreven door Piper en de piper-php-bibliotheek. Het draait volledig op PHP met Workerman als HTTP-server, zonder Node.js of Python. Geen GPU nodig — alles draait op de CPU. Kies een stem, typ uw tekst en hoor direct het resultaat — alles lokaal verwerkt op uw machine.',
  no: 'Dette er en fullstendig PHP-basert tekst-til-tale webapplikasjon drevet av Piper og piper-php-biblioteket. Den kjører utelukkende på PHP med Workerman som HTTP-server, uten behov for Node.js eller Python. Ingen GPU nødvendig — alt kjører på prosessoren. Velg en stemme, skriv inn teksten din og hør resultatene umiddelbart — alt behandlet lokalt på maskinen din.',
  pl: 'To w pełni napisana w PHP aplikacja webowa do syntezy mowy, oparta na bibliotece Piper i piper-php. Działa całkowicie na PHP z Workermanem jako serwerem HTTP, bez potrzeby używania Node.js czy Pythona. Nie wymaga karty GPU — wszystko działa na procesorze. Wybierz głos, wpisz tekst i natychmiast usłysz wynik — wszystko przetwarzane lokalnie na Twoim komputerze.',
  pt: 'Esta é uma aplicação web de texto para fala escrita inteiramente em PHP, alimentada pelo Piper e pela biblioteca piper-php. Funciona completamente em PHP com Workerman como servidor HTTP, sem necessidade de Node.js ou Python. Sem necessidade de GPU — tudo funciona na CPU. Escolha uma voz, digite seu texto e ouça os resultados instantaneamente — tudo processado localmente na sua máquina.',
  ro: 'Aceasta este o aplicație web de sinteză vocală scrisă integral în PHP, bazată pe Piper și biblioteca piper-php. Funcționează complet pe PHP cu Workerman ca server HTTP, fără a necesita Node.js sau Python. Nu este nevoie de GPU — totul rulează pe procesor. Alegeți o voce, tastați textul și ascultați rezultatele instantaneu — totul procesat local pe mașina dumneavoastră.',
  ru: 'Это веб-приложение для синтеза речи, полностью написанное на PHP и работающее на базе Piper и библиотеки piper-php. Оно работает полностью на PHP с Workerman в качестве HTTP-сервера, без необходимости в Node.js или Python. GPU не требуется — всё работает на процессоре. Выберите голос, введите текст и мгновенно услышайте результат — всё обрабатывается локально на вашем компьютере.',
  sk: 'Toto je plne PHP webová aplikácia na prevod textu na reč poháňaná knižnicou Piper a piper-php. Beží kompletne na PHP s Workermanom ako HTTP serverom, bez potreby Node.js alebo Pythonu. Nie je potrebná GPU — všetko beží na CPU. Vyberte hlas, napíšte text a okamžite počujte výsledok — všetko spracované lokálne na vašom počítači.',
  sl: 'To je popolnoma v PHP napisana spletna aplikacija za pretvorbo besedila v govor, ki jo poganjata Piper in knjižnica piper-php. Deluje v celoti na PHP z Workermanom kot HTTP strežnikom, brez potrebe po Node.js ali Pythonu. GPU ni potreben — vse deluje na procesorju. Izberite glas, vnesite besedilo in takoj slišite rezultat — vse obdelano lokalno na vašem računalniku.',
  sr: 'Ово је у потпуности PHP веб апликација за синтезу говора, покренута Piper-ом и piper-php библиотеком. Ради искључиво на PHP-у са Workerman-ом као HTTP сервером, без потребе за Node.js или Python-ом. Није потребан GPU — све ради на процесору. Изаберите глас, унесите текст и одмах чујте резултат — све обрађено локално на вашем рачунару.',
  sv: 'Detta är en helt PHP-baserad text-till-tal webbapplikation driven av Piper och piper-php-biblioteket. Den körs helt på PHP med Workerman som HTTP-server, utan behov av Node.js eller Python. Inget GPU behövs — allt körs på processorn. Välj en röst, skriv din text och hör resultatet omedelbart — allt bearbetas lokalt på din maskin.',
  sw: 'Hii ni programu ya wavuti ya maandishi-kwa-sauti iliyoandikwa kabisa kwa PHP, inayoendeshwa na Piper na maktaba ya piper-php. Inafanya kazi kabisa kwa PHP na Workerman kama seva ya HTTP, bila kuhitaji Node.js au Python. Hakuna GPU inayohitajika — kila kitu kinafanya kazi kwenye CPU. Chagua sauti, andika maandishi yako na usikie matokeo mara moja — yote yanasindika ndani ya mashine yako.',
  te: 'ఇది Piper మరియు piper-php లైబ్రరీ ద్వారా నడిచే పూర్తిగా PHP-ఆధారిత టెక్స్ట్-టు-స్పీచ్ వెబ్ అప్లికేషన్. Node.js లేదా Python అవసరం లేకుండా Workerman HTTP సర్వర్‌తో పూర్తిగా PHP పై నడుస్తుంది. GPU అవసరం లేదు — అన్నీ CPU పై నడుస్తాయి. ఒక వాయిస్ ఎంచుకుని, మీ టెక్స్ట్ టైప్ చేసి, ఫలితాలను వెంటనే వినండి — అన్నీ మీ మెషీన్‌పై స్థానికంగా ప్రాసెస్ అవుతాయి.',
  tr: 'Bu, Piper ve piper-php kütüphanesi tarafından desteklenen tamamen PHP tabanlı bir metinden konuşmaya web uygulamasıdır. Node.js veya Python gerektirmeden, Workerman HTTP sunucusu olarak tamamen PHP üzerinde çalışır. GPU gerekmez — her şey CPU üzerinde çalışır. Bir ses seçin, metninizi yazın ve sonuçları anında dinleyin — her şey makinenizde yerel olarak işlenir.',
  uk: 'Це веб-додаток для синтезу мовлення, повністю написаний на PHP та працюючий на базі Piper і бібліотеки piper-php. Він працює повністю на PHP з Workerman як HTTP-сервером, без необхідності в Node.js або Python. GPU не потрібен — все працює на процесорі. Оберіть голос, введіть текст і миттєво почуйте результат — все обробляється локально на вашому комп\'ютері.',
  vi: 'Đây là ứng dụng web chuyển văn bản thành giọng nói hoàn toàn bằng PHP, được cung cấp bởi Piper và thư viện piper-php. Ứng dụng chạy hoàn toàn trên PHP với Workerman làm máy chủ HTTP, không cần Node.js hay Python. Không cần GPU — mọi thứ chạy trên CPU. Chọn giọng nói, nhập văn bản và nghe kết quả ngay lập tức — tất cả được xử lý cục bộ trên máy của bạn.',
  zh: '这是一个完全基于 PHP 的文本转语音 Web 应用程序，由 Piper 和 piper-php 库驱动。它完全在 PHP 上运行，使用 Workerman 作为 HTTP 服务器，不需要 Node.js 或 Python。不需要 GPU — 所有内容都在 CPU 上运行。选择一个语音，输入您的文本，立即听到结果 — 所有内容都在您的机器上本地处理。',
};

function getBrowserLang() {
  return (navigator.language || 'en').split('-')[0].toLowerCase();
}

function buildWavBlob(pcmChunks, sampleRate) {
  const totalLen = pcmChunks.reduce((sum, c) => sum + c.length, 0);
  const buf = new ArrayBuffer(44 + totalLen);
  const view = new DataView(buf);
  const writeStr = (off, str) => { for (let i = 0; i < str.length; i++) view.setUint8(off + i, str.charCodeAt(i)); };
  writeStr(0, 'RIFF');
  view.setUint32(4, 36 + totalLen, true);
  writeStr(8, 'WAVE');
  writeStr(12, 'fmt ');
  view.setUint32(16, 16, true);
  view.setUint16(20, 1, true);
  view.setUint16(22, 1, true);
  view.setUint32(24, sampleRate, true);
  view.setUint32(28, sampleRate * 2, true);
  view.setUint16(32, 2, true);
  view.setUint16(34, 16, true);
  writeStr(36, 'data');
  view.setUint32(40, totalLen, true);
  const pcm = new Uint8Array(buf, 44);
  let offset = 0;
  for (const chunk of pcmChunks) {
    pcm.set(chunk, offset);
    offset += chunk.length;
  }
  return new Blob([buf], { type: 'audio/wav' });
}

export default function App() {
  const [voices, setVoices] = useState({});
  const [lang, setLang] = useState('en');
  const [voice, setVoice] = useState('');
  const [text, setText] = useState('');
  const [speed, setSpeed] = useState(1);
  const [streamState, setStreamState] = useState('idle');
  const [error, setError] = useState('');
  const audioRef = useRef(null);
  const audioCtxRef = useRef(null);
  const textHashRef = useRef('');
  const blobUrlRef = useRef(null);
  const isStreamingRef = useRef(false);
  const generatedVoiceRef = useRef('');
  const generatedSpeedRef = useRef(0);


  useEffect(() => {
    fetch('/api/voices')
      .then(r => r.json())
      .then(data => {
        setVoices(data);
        const browserLang = getBrowserLang();
        const families = Object.keys(data);
        const defaultLang = families.includes(browserLang) ? browserLang : 'en';
        setLang(defaultLang);
        const v = data[defaultLang]?.voices || {};
        const firstVoice = Object.keys(v)[0] || '';
        setVoice(firstVoice);
        setText(TRANSLATIONS[defaultLang] || TRANSLATIONS.en);
      });
  }, []);

  const stopPlayback = useCallback(() => {
    isStreamingRef.current = false;
    if (audioCtxRef.current) {
      audioCtxRef.current.close();
      audioCtxRef.current = null;
    }
    if (audioRef.current) {
      audioRef.current.pause();
      audioRef.current = null;
    }
    setStreamState('idle');
  }, []);

  useEffect(() => {
    const v = voices[lang]?.voices || {};
    const first = Object.keys(v)[0] || '';
    setVoice(first);
    setText(TRANSLATIONS[lang] || TRANSLATIONS.en);
    stopPlayback();
    textHashRef.current = '';
    blobUrlRef.current = null;
    generatedVoiceRef.current = '';
    generatedSpeedRef.current = 0;
  }, [lang, voices, stopPlayback]);



  useEffect(() => {
    const el = document.getElementById('voice');
    if (el && voice) el.value = voice;
  }, [voice, lang]);

  useEffect(() => {
    if (generatedVoiceRef.current && generatedVoiceRef.current !== voice) {
      stopPlayback();
      blobUrlRef.current = null;
      generatedVoiceRef.current = '';
      generatedSpeedRef.current = 0;
      textHashRef.current = '';
      setStreamState('idle');
    }
  }, [voice, stopPlayback]);

  const handleTextChange = (e) => {
    setText(e.target.value);
  };

  const startStream = async () => {
    setError('');
    const currentVoice = getCurrentVoice();
    if (!currentVoice || !text.trim()) {
      setError('Please select a voice and enter text.');
      return;
    }
    setVoice(currentVoice);

    setStreamState('waiting');
    isStreamingRef.current = true;
    blobUrlRef.current = null;

    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    audioCtxRef.current = audioCtx;
    await audioCtx.resume();

    let sampleRate = 0;
    let scheduledTime = 0;
    let headerParsed = false;
    let buffer = new Uint8Array(0);
    const pcmChunks = [];
    const sources = [];

    try {
      const res = await fetch('/api/synthesize', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ voice: currentVoice, text: text.trim(), speed: parseFloat(speed) }),
      });

      if (!res.ok) {
        const err = await res.json();
        throw new Error(err.error || 'Generation failed');
      }

      const reader = res.body.getReader();

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        const combined = new Uint8Array(buffer.length + value.length);
        combined.set(buffer);
        combined.set(value, buffer.length);
        buffer = combined;

        if (!headerParsed && combined.length >= 44) {
          sampleRate = new DataView(combined.buffer).getUint32(24, true);
          headerParsed = true;
          buffer = combined.slice(44);
          scheduledTime = audioCtx.currentTime + 0.05;
          setStreamState('streaming');
        }

        if (headerParsed && buffer.length >= 2) {
          const len = buffer.length - (buffer.length % 2);
          if (len === 0) continue;

          const chunk = buffer.slice(0, len);
          buffer = buffer.slice(len);

          pcmChunks.push(new Uint8Array(chunk));

          const int16 = new Int16Array(chunk.buffer);
          const float32 = new Float32Array(int16.length);
          for (let i = 0; i < int16.length; i++) {
            float32[i] = int16[i] / 32768;
          }

          const audioBuffer = audioCtx.createBuffer(1, float32.length, sampleRate);
          audioBuffer.getChannelData(0).set(float32);
          const source = audioCtx.createBufferSource();
          source.buffer = audioBuffer;
          source.connect(audioCtx.destination);
          source.start(scheduledTime);
          sources.push(source);
          scheduledTime += audioBuffer.duration;
        }
      }

      // Build blob for replay and download
      const blob = buildWavBlob(pcmChunks, sampleRate);
      blobUrlRef.current = URL.createObjectURL(blob);
      textHashRef.current = text.trim();
      generatedVoiceRef.current = currentVoice;
      generatedSpeedRef.current = speed;

      // Wait for all sources to finish playing
      if (sources.length > 0) {
        const lastSource = sources[sources.length - 1];
        await new Promise(resolve => {
          lastSource.onended = resolve;
        });
      }

      isStreamingRef.current = false;
      setStreamState('finished');
    } catch (err) {
      if (isStreamingRef.current) {
        setError(err.message);
        isStreamingRef.current = false;
        setStreamState('idle');
      }
    } finally {
      audioCtxRef.current = null;
      audioCtx.close();
    }
  };

  const replayAudio = () => {
    if (!blobUrlRef.current) return;
    setStreamState('replaying');
    const audio = new Audio(blobUrlRef.current);
    audioRef.current = audio;
    audio.onended = () => {
      audioRef.current = null;
      setStreamState('finished');
    };
    audio.play();
  };

  const langEntries = Object.entries(voices).sort((a, b) => a[1].name.localeCompare(b[1].name));
  const voiceEntries = Object.entries(voices[lang]?.voices || {});

  const isWaiting = streamState === 'waiting';
  const isStreaming = streamState === 'streaming';
  const isReplaying = streamState === 'replaying';

  const getCurrentVoice = () => document.getElementById('voice')?.value || voice;

  const checkParamsChanged = () => !blobUrlRef.current
    || textHashRef.current !== text.trim()
    || generatedVoiceRef.current !== getCurrentVoice()
    || generatedSpeedRef.current !== speed;

  const paramsChanged = checkParamsChanged();
  const isFinished = streamState === 'finished' && !paramsChanged;
  const isGenerating = streamState === 'waiting' || streamState === 'streaming';
  const canDownload = (isFinished || isReplaying) && !paramsChanged && !isGenerating;

  const handleAction = () => {
    if (streamState === 'waiting' || streamState === 'streaming' || streamState === 'replaying') {
      stopPlayback();
      return;
    }

    const currentVoice = getCurrentVoice();
    if (currentVoice !== voice) {
      setVoice(currentVoice);
    }

    if (streamState === 'finished' && !checkParamsChanged()) {
      replayAudio();
      return;
    }

    startStream();
  };

  const downloadAudio = () => {
    if (!blobUrlRef.current) return;
    const a = document.createElement('a');
    a.href = blobUrlRef.current;
    a.download = `piper-${voice}-${Date.now()}.wav`;
    a.click();
  };

  const getButtonText = () => {
    if (isWaiting) return 'Waiting...';
    if (isStreaming) return 'Stop';
    if (isReplaying) return 'Stop';
    if (isFinished) return 'Play again';
    return 'Generate';
  };

  const getButtonClass = () => {
    if (isStreaming || isReplaying) return 'btn-active';
    return '';
  };

  return (
    <div class="container">
      <a href="https://github.com/crazy-goat/piper-php-api" target="_blank" rel="noopener noreferrer" class="github-corner" aria-label="View source on GitHub">
        <svg viewBox="0 0 250 250" aria-hidden="true">
          <path d="M0,0 L115,115 L130,115 L142,142 L250,250 L250,0 Z" class="corner-bg"></path>
          <path d="M128.3,109.0 C113.8,99.7 119.0,89.6 119.0,89.6 C122.0,82.7 120.5,78.6 120.5,78.6 C119.2,72.0 123.4,76.3 123.4,76.3 C127.3,80.9 125.5,87.3 125.5,87.3 C122.9,97.6 130.6,101.9 134.4,103.2" fill="currentColor" class="corner-octo-arm" style="transform-origin:130px 106px"></path>
          <path d="M115.0,115.0 C114.9,115.1 118.7,116.5 119.8,115.4 L133.7,101.6 C136.9,99.2 139.9,98.4 142.2,98.6 C133.8,88.0 127.5,74.4 143.8,58.0 C148.5,53.4 154.0,51.2 159.7,51.0 C160.3,49.4 163.2,43.6 171.4,40.1 C171.4,40.1 176.1,42.5 178.8,56.2 C183.1,58.6 187.2,61.8 190.9,65.4 C194.5,69.0 197.7,73.2 200.1,77.6 C213.8,80.2 216.3,84.9 216.3,84.9 C212.7,93.1 206.9,96.0 205.4,96.6 C205.1,102.4 203.0,107.8 198.3,112.5 C181.9,128.9 168.3,122.5 157.7,114.1 C157.9,116.9 156.7,120.9 152.7,124.9 L141.0,136.5 C139.8,137.7 141.6,141.9 141.8,141.8 Z" fill="currentColor" class="corner-octo-body"></path>
        </svg>
      </a>
      <h1>Piper TTS</h1>
      <form onSubmit={e => e.preventDefault()}>
        <label for="language">Language</label>
        <select id="language" value={lang} onChange={e => setLang(e.target.value)}>
          {langEntries.map(([code, data]) => (
            <option key={code} value={code}>{data.name}</option>
          ))}
        </select>

        <label for="voice">Voice</label>
        <select id="voice" onChange={e => setVoice(e.target.value)} disabled={!voice}>
          {voiceEntries.map(([key, v]) => (
            <option key={key} value={key}>{v.name} ({v.quality})</option>
          ))}
        </select>

        <label for="text">Text</label>
        <textarea
          id="text"
          value={text}
          maxLength={500}
          onInput={handleTextChange}
        />
        <div class="char-count">{text.length}/500</div>

        <label for="speed">Speed: {speed.toFixed(2)}x</label>
        <input
          id="speed"
          type="range"
          min="0.5"
          max="2"
          step="0.01"
          value={speed}
          onInput={e => setSpeed(parseFloat(e.target.value))}
        />

        <div class="btn-row">
          <button type="button" class={getButtonClass()} onClick={handleAction}>
            {isWaiting && <span class="spinner" />}
            {getButtonText()}
          </button>
          <button type="button" class="btn-download" onClick={downloadAudio} disabled={!canDownload}>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download
          </button>
        </div>
      </form>

      {error && <div class="error">{error}</div>}
    </div>
  );
}
